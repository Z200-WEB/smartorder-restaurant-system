<?php
require_once 'pdo.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

try {
    $itemId = (int)$_POST['itemId'];
    $tableNo = (int)$_POST['tableNo'];
    $amount = (int)$_POST['amount'];
    // Customer note for the kitchen (allergies, "no wasabi", etc.)
    $notes = trim((string)($_POST['item_notes'] ?? ''));
    $notes = mb_substr(preg_replace('/\s+/u', ' ', $notes), 0, 200);

    if ($itemId <= 0 || $tableNo <= 0 || $amount <= 0) {
        throw new Exception('Invalid parameters');
    }

    // Check if there's an existing draft order (state=0) for this table
    $sql = "SELECT orderNo FROM sManagement WHERE tableNo = :tableNo AND state = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Use existing draft order
        $orderNo = $existing['orderNo'];
    } else {
        // Create new draft order with generated orderNo
        $orderNo = date('YmdHis') . '-' . sprintf('%04d', rand(0, 9999));

        $sql = "INSERT INTO sManagement (state, orderNo, tableNo) VALUES (0, :orderNo, :tableNo)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
        $stmt->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Check if this item already exists in the order
    $sql = "SELECT id, amount, item_notes FROM sOrder WHERE orderNo = :orderNo AND itemNo = :itemNo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
    $stmt->bindValue(':itemNo', $itemId, PDO::PARAM_INT);
    $stmt->execute();
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingItem) {
        // Update quantity of existing item
        $newAmount = $existingItem['amount'] + $amount;
        $mergedNotes = trim(implode(' / ', array_filter([(string)$existingItem['item_notes'], $notes], 'strlen')));
        $sql = "UPDATE sOrder SET amount = :amount, item_notes = :notes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':amount', $newAmount, PDO::PARAM_INT);
        $stmt->bindValue(':notes', $mergedNotes !== '' ? mb_substr($mergedNotes, 0, 255) : null, $mergedNotes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $existingItem['id'], PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // Add new item to order
        $sql = "INSERT INTO sOrder (state, orderNo, itemNo, amount, item_notes) VALUES (1, :orderNo, :itemNo, :amount, :notes)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':orderNo', $orderNo, PDO::PARAM_STR);
        $stmt->bindValue(':itemNo', $itemId, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $stmt->execute();
    }

    echo json_encode(['status' => 'success', 'orderNo' => $orderNo]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
