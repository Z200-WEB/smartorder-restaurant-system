<?php
// orders_api.php - Real-time orders data for management page
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'auth.php';
require_once 'pdo.php';

// Require staff auth - return JSON error instead of redirect
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '認証が必要です', 'orders' => []]);
    exit;
}

try {
    $sql = "
        SELECT m.*, 
               SUM(i.price * o.amount) as totalAmount, 
               COUNT(o.id) as itemCount
        FROM sManagement m
        LEFT JOIN sOrder o ON m.orderNo = o.orderNo
        LEFT JOIN sItem i ON o.itemNo = i.id
        WHERE m.state = 1
        GROUP BY m.id, m.state, m.orderNo, m.tableNo, m.dateA, m.dateB
        ORDER BY m.dateB DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'orders' => $orders, 'ts' => time()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'orders' => []]);
}
?>
