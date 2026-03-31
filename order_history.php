<?php
require_once 'pdo.php';
header('Content-Type: application/json');

$tableNo = intval($_POST['tableNo'] ?? 0);
if(!$tableNo){ echo json_encode(['success'=>false,'message'=>'Invalid table']); exit; }

try {
  // Get all orderNos for this table (active session = state=1)
  $stmtM = $pdo->prepare("SELECT orderNo FROM sManagement WHERE tableNo=? AND state=1 ORDER BY dateA ASC");
  $stmtM->execute([$tableNo]);
  $orders = $stmtM->fetchAll(PDO::FETCH_COLUMN);

  if(empty($orders)){
    echo json_encode(['success'=>true,'items'=>[],'total'=>0]);
    exit;
  }

  // Get all ordered items with name, price, amount
  $placeholders = implode(',', array_fill(0, count($orders), '?'));
  $stmtO = $pdo->prepare("
    SELECT o.id, o.orderNo, o.amount, o.item_notes,
           i.name, i.price, i.image_url,
           (o.amount * i.price) AS subtotal
    FROM sOrder o
    JOIN sItem i ON o.itemNo = i.id
    WHERE o.orderNo IN ($placeholders) AND o.state=1
    ORDER BY o.id ASC
  ");
  $stmtO->execute($orders);
  $items = $stmtO->fetchAll(PDO::FETCH_ASSOC);

  $total = array_sum(array_column($items, 'subtotal'));

  echo json_encode([
    'success' => true,
    'items'   => $items,
    'total'   => (int)$total
  ]);
} catch(Exception $e){
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>
