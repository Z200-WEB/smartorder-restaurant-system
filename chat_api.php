<?php
// chat_api.php - Staff chat read/reply API
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'pdo.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// Auth check - returns JSON error instead of redirect
function checkStaffAuth(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Ensure chat table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS sChatMessages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tableNo INT NOT NULL DEFAULT 1,
        role ENUM('user','ai','staff') NOT NULL DEFAULT 'user',
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_table (tableNo),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

if ($action === 'get') {
    $tableNo = intval($_GET['tableNo'] ?? 0);
    $since = intval($_GET['since'] ?? 0);
    
    try {
        if ($tableNo > 0) {
            // Customer or staff getting specific table messages
            if ($since > 0) {
                $stmt = $pdo->prepare("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE tableNo = ? AND id > ? ORDER BY id ASC LIMIT 20");
                $stmt->execute([$tableNo, $since]);
            } else {
                $stmt = $pdo->prepare("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE tableNo = ? ORDER BY id DESC LIMIT 30");
                $stmt->execute([$tableNo]);
            }
        } else {
            // Staff getting all tables - require auth
            if (!checkStaffAuth()) {
                http_response_code(403);
                echo json_encode(['ok'=>false,'error'=>'認証が必要です','messages'=>[]]);
                exit;
            }
            $stmt = $pdo->query("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 HOUR) ORDER BY id DESC LIMIT 200");
        }
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok'=>true,'messages'=>$msgs,'ts'=>time()]);
    } catch(Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'messages'=>[]]);
    }
    
} elseif ($action === 'reply') {
    // Staff sending reply - require auth
    if (!checkStaffAuth()) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'認証が必要です。ログインしてください。']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if(empty($input)) $input = $_POST;
    
    $tableNo = intval($input['tableNo'] ?? 0);
    $message = trim($input['message'] ?? '');
    
    if (!$tableNo || !$message) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'tableNoとmessageが必要です']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'staff', ?, NOW())");
        $stmt->execute([$tableNo, $message]);
        $newId = $pdo->lastInsertId();
        
        // Write SSE notification file
        $sseFile = sys_get_temp_dir().'/smartorder_chat_'.$tableNo.'.json';
        file_put_contents($sseFile, json_encode([
            'type'=>'staff_reply',
            'tableNo'=>$tableNo,
            'message'=>$message,
            'id'=>$newId,
            'ts'=>time()
        ]));
        
        echo json_encode(['ok'=>true,'id'=>$newId,'message'=>$message]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    
} elseif ($action === 'check_staff_reply') {
    // Customer polling for staff replies on their table
    $tableNo = intval($_GET['tableNo'] ?? 0);
    $since = intval($_GET['since'] ?? 0);
    if(!$tableNo) { echo json_encode(['ok'=>false,'messages'=>[]]); exit; }
    
    try {
        $stmt = $pdo->prepare("SELECT id, role, message, created_at FROM sChatMessages WHERE tableNo = ? AND role = 'staff' AND id > ? ORDER BY id ASC LIMIT 10");
        $stmt->execute([$tableNo, $since]);
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok'=>true,'messages'=>$msgs]);
    } catch(Exception $e) {
        echo json_encode(['ok'=>false,'messages'=>[]]);
    }
    
} else {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'不明なアクション: '.$action]);
}
?>
