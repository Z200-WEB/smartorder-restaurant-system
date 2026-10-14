<?php
require_once 'pdo.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $tableNo = (int)$_POST['tableNo'];
    
    // このテーブルの下書き注文(state=0)を取得
    $sql = "SELECT orderNo FROM sManagement WHERE tableNo = :tableNo AND state = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('注文が見つかりません');
    }
    
    $orderNo = $order['orderNo'];
    
    // 注文を確定: state を 0(下書き) から 1(確定済み) に変更
    // これにより管理画面(management.php)に表示されるようになる
    $sql = "UPDATE sManagement SET state = 1, ordered_at = NOW(), kitchen_state = 0 WHERE orderNo = :orderNo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'orderNo' => $orderNo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>