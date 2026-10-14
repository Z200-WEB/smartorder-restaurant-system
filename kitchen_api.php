<?php
// kitchen_api.php - Live ticket feed for the kitchen display (kitchen.php)
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache');

require_once 'auth.php';
require_once 'pdo.php';
require_once __DIR__ . '/item_emoji.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'auth', 'tickets' => []]);
    exit;
}

try {
    // Confirmed orders (state=1) that have not been served yet (kitchen_state 0..2)
    $stmt = $pdo->prepare("
        SELECT m.orderNo, m.tableNo, m.kitchen_state, m.dateA,
               TIMESTAMPDIFF(SECOND, COALESCE(m.ordered_at, m.dateA), NOW()) AS elapsed
          FROM sManagement m
         WHERE m.state = 1 AND COALESCE(m.kitchen_state, 0) < 3
         ORDER BY COALESCE(m.ordered_at, m.dateA) ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $catIcons = [];
    foreach ($pdo->query("SELECT id, icon FROM sCategory") as $c) {
        $catIcons[(int)$c['id']] = $c['icon'] ?: '🍽️';
    }

    $stmtItems = $pdo->prepare("
        SELECT o.amount, o.item_notes, i.name, i.category
          FROM sOrder o JOIN sItem i ON o.itemNo = i.id
         WHERE o.orderNo = :orderNo
         ORDER BY o.id ASC
    ");

    $tickets = [];
    foreach ($orders as $o) {
        $stmtItems->execute([':orderNo' => $o['orderNo']]);
        $items = [];
        foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $it) {
            $items[] = [
                'name'  => trim(str_replace(['[期間限定]', '[おすすめ]'], '', $it['name'])),
                'qty'   => (int)$it['amount'],
                'notes' => (string)($it['item_notes'] ?? ''),
                'icon'  => itemEmoji($it['name'], $it['category'], $catIcons),
            ];
        }
        if (!$items) continue; // nothing to cook
        $tickets[] = [
            'orderNo' => $o['orderNo'],
            'tableNo' => (int)$o['tableNo'],
            'state'   => (int)($o['kitchen_state'] ?? 0),
            'elapsed' => max(0, (int)$o['elapsed']),
            'items'   => $items,
        ];
    }

    echo json_encode(['ok' => true, 'tickets' => $tickets, 'ts' => time()], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server', 'tickets' => []]);
}
