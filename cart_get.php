<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'pdo.php';

$tableNo = isset($_GET['tableNo']) ? (int)$_GET['tableNo'] : 1;

function getItemImage($itemId) {
  $dir = __DIR__ . '/itemImages/';
  foreach (['jpg','jpeg','png','gif','webp'] as $ext) {
    if (file_exists($dir . $itemId . '.' . $ext)) {
      return 'itemImages/' . $itemId . '.' . $ext;
    }
  }
  return null;
}

$sql = "
  SELECT o.id as orderId, o.itemNo, o.amount, o.item_notes,
         i.id as itemId, i.name, i.price,
         (i.price * o.amount) as subtotal
  FROM sManagement m
  JOIN sOrder o ON m.orderNo = o.orderNo
  JOIN sItem i ON o.itemNo = i.id
  WHERE m.tableNo = :tableNo AND m.state = 0
  ORDER BY o.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
$itemCount = 0;
$items = [];
foreach ($cartItems as $ci) {
  $total += $ci['subtotal'];
  $itemCount += $ci['amount'];
  $items[] = [
    'orderId' => (int)$ci['orderId'],
    'name'    => $ci['name'],
    'price'   => (int)$ci['price'],
    'amount'  => (int)$ci['amount'],
    'img'     => getItemImage($ci['itemId']) ?? '',
  ];
}

echo json_encode([
  'success'   => true,
  'items'     => $items,
  'total'     => $total,
  'itemCount' => $itemCount,
], JSON_UNESCAPED_UNICODE);
