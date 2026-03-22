<?php
require_once 'auth.php';
requireAuth();
require_once 'pdo.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['success' => false, 'message' => 'Invalid method']);
      exit;
}

requireCsrf();

$orderNo = isset($_POST['orderNo']) ? trim($_POST['orderNo']) : '';
$kitchenState = isset($_POST['kitchen_state']) ? (int)$_POST['kitchen_state'] : -1;

if (empty($orderNo) || $kitchenState < 0 || $kitchenState > 3) {
      echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
      exit;
}

try {
      $sql = "UPDATE sManagement SET kitchen_state = :ks WHERE orderNo = :orderNo";
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':ks', $kitchenState, PDO::PARAM_INT);
      $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
      $stmt->execute();

      $labels = ['0' => 'received', '1' => 'cooking', '2' => 'ready', '3' => 'served'];
      echo json_encode(['success' => true, 'orderNo' => $orderNo, 'kitchen_state' => $kitchenState, 'label' => $labels[$kitchenState]]);
} catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
