<?php
// chat_api.php - Staff chat read/reply API
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

require_once 'pdo.php';
require_once 'auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// Ensure table exists
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
    // Get recent messages - for staff panel (requires auth) or by tableNo (for customer)
    $tableNo = intval($_GET['tableNo'] ?? 0);
    $since = intval($_GET['since'] ?? 0);
    
    try {
        if ($tableNo > 0) {
            // Customer getting own messages
            if ($since > 0) {
                $stmt = $pdo->prepare("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE tableNo = ? AND id > ? ORDER BY id ASC LIMIT 20");
                $stmt->execute([$tableNo, $since]);
            } else {
                $stmt = $pdo->prepare("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE tableNo = ? ORDER BY id DESC LIMIT 30");
                $stmt->execute([$tableNo]);
            }
        } else {
            // Staff getting all tables (auth required)
            requireAuth();
            $stmt = $pdo->query("SELECT id, tableNo, role, message, created_at FROM sChatMessages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 HOUR) ORDER BY id DESC LIMIT 100");
        }
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['ok'=>true,'messages'=>$msgs,'ts'=>time()]);
    } catch(Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage(),'messages'=>[]]);
    }
    
} elseif ($action === 'reply') {
    // Staff sending reply to a table
    requireAuth();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $tableNo = intval($input['tableNo'] ?? 0);
    $message = trim($input['message'] ?? '');
    
    if (!$tableNo || !$message) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'error'=>'missing params']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO sChatMessages (tableNo, role, message, created_at) VALUES (?, 'staff', ?, NOW())");
        $stmt->execute([$tableNo, $message]);
        $newId = $pdo->lastInsertId();
        
        // Write SSE notification
        $sseFile = sys_get_temp_dir().'/smartorder_sse.json';
        file_put_contents($sseFile, json_encode(['type'=>'staff_reply','tableNo'=>$tableNo,'message'=>$message,'id'=>$newId,'ts'=>time()]));
        
        echo json_encode(['ok'=>true,'id'=>$newId]);
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
    
} elseif ($action === 'clear') {
    // Clear old messages (admin only)
    requireAuth();
    try {
        $pdo->exec("DELETE FROM sChatMessages WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        echo json_encode(['ok'=>true]);
    } catch(Exception $e) {
        echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'unknown action']);
}
?>
