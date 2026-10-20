<?php
require_once 'auth.php';
requireAuth();
requireCsrf();

require_once 'pdo.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $orderNo = isset($_POST['orderNo']) ? $_POST['orderNo'] : '';

    if (empty($orderNo)) {
        throw new Exception('အော်ဒါ နံပါတ် မပါပါ');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update sManagement state to 2 (paid/completed)
    // This will hide it from active orders list
    $sql = "UPDATE sManagement SET state = 2 WHERE orderNo = :orderNo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('အော်ဒါ ရှာမတွေ့ပါ');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'ငွေရှင်းပြီးပါပြီ',
        'orderNo' => $orderNo
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
