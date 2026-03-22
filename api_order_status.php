<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'pdo.php';

$tableNo = isset($_GET['tableNo']) ? (int)$_GET['tableNo'] : 0;
if ($tableNo <= 0) {
      echo json_encode(['status' => 'error', 'message' => 'Invalid table']);
      exit;
}

// Get current active/recent orders for this table (state 0=draft, 1=confirmed, kitchen_state tracked)
$sql = "SELECT m.id, m.orderNo, m.tableNo, m.state, m.kitchen_state, m.estimated_minutes, m.dateA, m.dateB,
               SUM(i.price * o.amount) as totalAmount,
                              COUNT(o.id) as itemCount
                                      FROM sManagement m
                                              LEFT JOIN sOrder o ON m.orderNo = o.orderNo
                                                      LEFT JOIN sItem i ON o.itemNo = i.id
                                                              WHERE m.tableNo = :tableNo AND m.state IN (0, 1)
                                                                      GROUP BY m.id
                                                                              ORDER BY m.dateA DESC
                                                                                      LIMIT 5";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each order, get the items
$result = [];
foreach ($orders as $order) {
      $sqlItems = "SELECT o.id, o.itemNo, o.amount, o.item_notes, i.name, i.price, (i.price * o.amount) as subtotal
                       FROM sOrder o JOIN sItem i ON o.itemNo = i.id
                                        WHERE o.orderNo = :orderNo";
      $stmtI = $pdo->prepare($sqlItems);
      $stmtI->bindValue(':orderNo', $order['orderNo'], PDO::PARAM_STR);
      $stmtI->execute();
      $order['items'] = $stmtI->fetchAll(PDO::FETCH_ASSOC);

    // Calculate minutes since order placed
    $placed = new DateTime($order['dateA']);
      $now = new DateTime();
      $order['minutes_ago'] = (int)round(($now->getTimestamp() - $placed->getTimestamp()) / 60);

    // Kitchen state labels
    $stateLabels = ['0' => 'received', '1' => 'cooking', '2' => 'ready', '3' => 'served'];
      $order['kitchen_state_label'] = $stateLabels[$order['kitchen_state']] ?? 'unknown';

    $result[] = $order;
}

echo json_encode(['status' => 'ok', 'tableNo' => $tableNo, 'orders' => $result]);
