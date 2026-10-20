<?php
/**
 * Authentication & CSRF Protection System
 * SmartOrder - Restaurant Management System
 * 
 * Security features:
 * - Password hashing with bcrypt (PASSWORD_DEFAULT)
 * - CSRF token generation and validation using hash_equals()
 * - Secure session management with regeneration on login
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin credentials
define('ADMIN_USERNAME', 'admin');

// Password hash - generated with: password_hash('admin123', PASSWORD_DEFAULT)
// In production, this should be stored in environment variables or a database.
// To change the password, run: php -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_DEFAULT);"
// Then replace the hash below.
if (!defined('ADMIN_PASSWORD_HASH')) {
    // Generate and cache hash at runtime for demo environment
    // In production, always use a pre-computed hash stored securely
    define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_DEFAULT));
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user with secure password verification
 * Uses password_verify() to compare against bcrypt hash
 */
function login(string $username, string $password): bool {
    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();
        regenerateCsrfToken();
        return true;
    }
    return false;
}

/**
 * Logout user - destroy session completely
 */
function logout(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generate CSRF token using cryptographically secure random bytes
 */
function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Regenerate CSRF token (called after login and sensitive operations)
 */
function regenerateCsrfToken(): string {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token using timing-safe comparison
 */
function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token for POST/DELETE requests
 * Checks both POST body and X-CSRF-TOKEN header
 */
function requireCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            } else {
                header('Location: admin.php?error=' . urlencode('လုံခြုံရေး token သက်တမ်း ကုန်သွားပါပြီ။ ထပ်ကြိုးစားပါ။'));
            }
            exit;
        }
    }
}

/**
 * Get CSRF token input field for HTML forms
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

/**
 * Get current admin username
 */
function getAdminUsername(): string {
    return $_SESSION['admin_username'] ?? 'Admin';
}