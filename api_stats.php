<?php
require_once 'auth.php';
requireAuth();
require_once 'pdo.php';
header('Content-Type: application/json; charset=UTF-8');

try {
      // Today's stats
      $today = date('Y-m-d');

      // Total orders today (state=2 = paid)
      $sqlOrders = "SELECT COUNT(*) as cnt FROM sManagement WHERE DATE(dateA) = :today AND state = 2";
      $s = $pdo->prepare($sqlOrders);
      $s->execute([':today' => $today]);
      $todayOrders = (int)$s->fetchColumn();

      // Today's revenue
      $sqlRev = "SELECT COALESCE(SUM(i.price * o.amount), 0) as revenue
                     FROM sManagement m
                                    JOIN sOrder o ON m.orderNo = o.orderNo
                                                   JOIN sItem i ON o.itemNo = i.id
                                                                  WHERE DATE(m.dateA) = :today AND m.state = 2";
      $s = $pdo->prepare($sqlRev);
      $s->execute([':today' => $today]);
      $todayRevenue = (int)$s->fetchColumn();

      // Active orders right now (state=1 and not paid yet)
      $sqlActive = "SELECT COUNT(*) as cnt FROM sManagement WHERE state = 1";
      $s = $pdo->prepare($sqlActive);
      $s->execute();
      $activeOrders = (int)$s->fetchColumn();

      // Occupied tables
      $sqlTables = "SELECT COUNT(DISTINCT tableNo) as cnt FROM sManagement WHERE state IN (0,1)";
      $s = $pdo->prepare($sqlTables);
      $s->execute();
      $occupiedTables = (int)$s->fetchColumn();

      // Top 5 selling items today
      $sqlTop = "SELECT i.name, SUM(o.amount) as qty, SUM(i.price * o.amount) as revenue
                     FROM sManagement m
                                    JOIN sOrder o ON m.orderNo = o.orderNo
                                                   JOIN sItem i ON o.itemNo = i.id
                                                                  WHERE DATE(m.dateA) = :today AND m.state = 2
                                                                                 GROUP BY i.id, i.name
                                                                                                ORDER BY qty DESC
                                                                                                               LIMIT 5";
      $s = $pdo->prepare($sqlTop);
      $s->execute([':today' => $today]);
      $topItems = $s->fetchAll(PDO::FETCH_ASSOC);

      // Orders by kitchen state
      $sqlKitchen = "SELECT kitchen_state, COUNT(*) as cnt FROM sManagement WHERE state = 1 GROUP BY kitchen_state";
      $s = $pdo->prepare($sqlKitchen);
      $s->execute();
      $kitchenRows = $s->fetchAll(PDO::FETCH_ASSOC);
      $kitchenStats = ['new' => 0, 'cooking' => 0, 'ready' => 0, 'served' => 0];
      $labels = [0 => 'new', 1 => 'cooking', 2 => 'ready', 3 => 'served'];
      foreach ($kitchenRows as $r) {
                $key = $labels[(int)$r['kitchen_state']] ?? 'new';
                $kitchenStats[$key] = (int)$r['cnt'];
      }

      // Recent 10 orders (all time)
      $sqlRecent = "SELECT m.orderNo, m.tableNo, m.state, m.kitchen_state, m.dateA,
                               COALESCE(SUM(i.price * o.amount), 0) as total
                                                 FROM sManagement m
                                                                   LEFT JOIN sOrder o ON m.orderNo = o.orderNo
                                                                                     LEFT JOIN sItem i ON o.itemNo = i.id
                                                                                                       GROUP BY m.id
                                                                                                                         ORDER BY m.dateA DESC
                                                                                                                                           LIMIT 10";
      $s = $pdo->prepare($sqlRecent);
      $s->execute();
      $recentOrders = $s->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode([
                               'status' => 'ok',
                               'today' => [
                                   'orders' => $todayOrders,
                                   'revenue' => $todayRevenue,
                               ],
                               'active_orders' => $activeOrders,
                               'occupied_tables' => $occupiedTables,
                               'top_items' => $topItems,
                               'kitchen_stats' => $kitchenStats,
                               'recent_orders' => $recentOrders,
                           ]);
} catch (Exception $e) {
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
