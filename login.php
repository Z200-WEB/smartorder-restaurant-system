<?php
require_once 'auth.php';

// If already logged in, redirect to admin
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartOrder Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 40%, #14b8a6 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            pointer-events: none;
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 24px;
            color: white;
        }

        .login-brand .brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(255,255,255,0.15);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            margin-bottom: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .login-brand h2 {
            font-size: 1.6em;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .login-brand p {
            font-size: 0.9em;
            opacity: 0.8;
            margin-top: 4px;
        }

        .login-card {
            background: white;
            padding: 40px 36px;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
        }

        .login-card h1 {
            font-size: 1.4em;
            font-weight: 700;
            color: #134e4a;
            margin-bottom: 6px;
        }

        .login-card .subtitle {
            font-size: 0.88em;
            color: #64748b;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.88em;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1em;
            opacity: 0.5;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95em;
            font-family: inherit;
            color: #1e293b;
            background: #f8fafc;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #14b8a6;
            background: white;
            box-shadow: 0 0 0 3px rgba(20,184,166,0.15);
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.88em;
            text-align: center;
            font-weight: 500;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f766e, #0d9488);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1em;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(15,118,110,0.4);
            letter-spacing: 0.02em;
            margin-top: 4px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15,118,110,0.5);
            background: linear-gradient(135deg, #0d6460, #0f766e);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #0d9488;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 600;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #0f766e;
            text-decoration: underline;
        }

        .credentials-hint {
            background: #f0fdf9;
            border: 1px solid #99f6e4;
            padding: 12px 16px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.82em;
            color: #0f766e;
            text-align: center;
        }

        .credentials-hint strong {
            color: #0d6460;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-brand">
        <div class="brand-icon">🍽️</div>
        <h2>SmartOrder</h2>
        <p>Restaurant Management System</p>
    </div>

    <div class="login-card">
        <h1>Admin Login</h1>
        <p class="subtitle">Sign in to access the management panel</p>

        <?php if ($error): ?>
        <div class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="username" name="username" required autofocus
                           placeholder="Enter username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔑</span>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter password">
                </div>
            </div>

            <button type="submit" class="btn-login">Login →</button>
        </form>

        <a href="index.php?tableNo=1" class="back-link">← Back to Customer View</a>

        <div class="credentials-hint">
            Default: <strong>admin</strong> / <strong>admin123</strong>
        </div>
    </div>
</div>

</body>
</html>
