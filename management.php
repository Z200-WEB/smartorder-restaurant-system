<?php
// UTF-8 ENCODING - MUST BE FIRST!
header('Content-Type: text/html; charset=UTF-8');
// Authentication required
require_once 'auth.php';
requireAuth();
// Load database connection
require_once 'pdo.php';
// Generate CSRF token for payment form
$csrfToken = generateCsrfToken();

$sql = "
  SELECT m.*, SUM(i.price * o.amount) as totalAmount, COUNT(o.id) as itemCount
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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>注文管理 - スマートオーダー</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --primary: #0f766e;
      --primary-dark: #0d6460;
      --primary-light: #14b8a6;
      --primary-bg: #f0fdf9;
      --accent: #f59e0b;
      --success: #059669;
      --success-dark: #047857;
      --error: #dc2626;
      --surface: #ffffff;
      --bg: #ecfdf5;
      --text: #0f172a;
      --text2: #475569;
      --text3: #94a3b8;
      --border: #d1fae5;
      --radius: 14px;
      --radius-lg: 20px;
      --shadow: 0 4px 16px rgba(15,118,110,.1);
      --shadow-md: 0 8px 24px rgba(15,118,110,.14);
      --shadow-lg: 0 16px 40px rgba(15,118,110,.18);
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: linear-gradient(135deg, #134e4a 0%, #0f766e 40%, #115e59 100%);
      min-height: 100vh;
      padding: 24px 16px;
      color: var(--text);
    }
    .container { max-width: 1440px; margin: 0 auto; }

    /* PAGE HEADER */
    .page-header {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 28px 32px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-md);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .header-left h1 {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 4px;
    }
    .header-left p { color: var(--text2); font-size: .9rem; }
    .header-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .back-button {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 10px 24px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 700;
      font-size: .9rem;
      transition: all .25s;
      box-shadow: 0 4px 14px rgba(15,118,110,.3);
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .back-button:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(15,118,110,.4); }

    /* AUTO REFRESH BADGE */
    .refresh-badge {
      background: var(--primary-bg);
      border: 1px solid var(--border);
      color: var(--primary);
      padding: 6px 14px;
      border-radius: 50px;
      font-size: .8rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .refresh-dot {
      width: 7px; height: 7px;
      background: var(--success);
      border-radius: 50%;
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(.9)} }

    /* TABLE LINKS */
    .table-links {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 20px 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow);
    }
    .table-links-title { font-size: .85rem; font-weight: 700; color: var(--text2); margin-bottom: 12px; text-transform: uppercase; letter-spacing: .06em; }
    .table-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .table-btn {
      background: var(--primary-bg);
      border: 2px solid var(--border);
      color: var(--primary);
      padding: 8px 18px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 700;
      font-size: .85rem;
      transition: all .2s;
    }
    .table-btn:hover {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
      transform: translateY(-1px);
    }

    /* STATS BAR */
    .stats-bar {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 12px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--surface);
      border-radius: var(--radius);
      padding: 16px 20px;
      box-shadow: var(--shadow);
      text-align: center;
      border-top: 3px solid var(--primary-light);
    }
    .stat-number { font-size: 1.75rem; font-weight: 800; color: var(--primary); }
    .stat-label { font-size: .8rem; color: var(--text2); font-weight: 600; margin-top: 2px; }

    /* ORDERS GRID */
    .orders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 18px; }

    /* EMPTY STATE */
    .empty-state {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 64px 40px;
      text-align: center;
      box-shadow: var(--shadow);
      grid-column: 1 / -1;
    }
    .empty-icon { font-size: 3.5rem; margin-bottom: 16px; opacity: .6; }
    .empty-title { font-size: 1.3rem; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .empty-text { color: var(--text2); font-size: .95rem; }

    /* ORDER CARD */
    .order-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      padding: 22px;
      box-shadow: var(--shadow);
      transition: all .25s;
      border: 2px solid transparent;
      animation: fadeInUp .3s ease-out backwards;
    }
    .order-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--primary-light); }
    @keyframes fadeInUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

    .order-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      padding-bottom: 14px;
      border-bottom: 2px solid var(--border);
    }
    .table-badge {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 7px 18px;
      border-radius: 50px;
      font-weight: 800;
      font-size: 1rem;
    }
    .order-time { color: var(--text3); font-size: .82rem; font-weight: 500; }

    .order-body { margin-bottom: 14px; }
    .order-info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: .9rem; }
    .order-label { color: var(--text2); font-weight: 600; }
    .order-value { color: var(--text); font-weight: 700; }

    .order-total {
      background: linear-gradient(135deg, #134e4a 0%, var(--primary) 100%);
      padding: 14px 16px;
      border-radius: 12px;
      margin: 12px 0;
    }
    .order-total-row { display: flex; justify-content: space-between; color: white; font-size: 1.1rem; font-weight: 800; }

    .order-actions { display: flex; gap: 10px; margin-top: 14px; }
    .btn-details {
      flex: 1;
      background: var(--primary-bg);
      color: var(--primary);
      border: 2px solid var(--border);
      padding: 10px;
      border-radius: 50px;
      font-weight: 700;
      font-size: .88rem;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
      transition: all .2s;
    }
    .btn-details:hover { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-1px); }
    .btn-payment {
      flex: 1;
      background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
      color: white;
      padding: 10px;
      border: none;
      border-radius: 50px;
      font-weight: 700;
      font-size: .88rem;
      cursor: pointer;
      transition: all .2s;
      box-shadow: 0 3px 12px rgba(5,150,105,.3);
    }
    .btn-payment:hover { transform: translateY(-2px); box-shadow: 0 5px 18px rgba(5,150,105,.4); }

    /* PAYMENT MODAL */
    .payment-modal-overlay {
      position: fixed; top:0; left:0; width:100%; height:100%;
      background: rgba(0,0,0,.55);
      backdrop-filter: blur(6px);
      display: flex; justify-content: center; align-items: center;
      z-index: 10000;
    }
    .payment-modal-content {
      background: white;
      border-radius: 20px;
      padding: 36px;
      width: 90%;
      max-width: 460px;
      box-shadow: 0 24px 64px rgba(0,0,0,.25);
      text-align: center;
      animation: popIn .3s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes popIn { from{transform:scale(.9);opacity:0} to{transform:scale(1);opacity:1} }
    .payment-modal-icon { font-size: 3rem; margin-bottom: 14px; }
    .payment-modal-title { font-size: 1.5rem; font-weight: 800; color: var(--text); margin-bottom: 22px; }
    .payment-modal-details {
      background: var(--primary-bg);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 16px;
      border: 1px solid var(--border);
    }
    .detail-row {
      display: flex; justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { font-weight: 600; color: var(--text2); }
    .detail-value { font-weight: 700; color: var(--text); }
    .total-row {
      background: linear-gradient(135deg, #134e4a 0%, var(--primary) 100%);
      margin: 10px -16px -16px;
      padding: 14px 16px !important;
      border-radius: 0 0 11px 11px;
      border: none !important;
    }
    .total-row .detail-label,
    .total-row .detail-value { color: white; font-size: 1.1rem; }
    .payment-modal-warning {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fde68a;
      padding: 10px 14px;
      border-radius: 8px;
      margin-bottom: 22px;
      font-size: .88rem;
    }
    .payment-modal-buttons { display: flex; gap: 12px; }
    .modal-btn {
      flex: 1; padding: 13px 24px; border: none; border-radius: 50px;
      font-size: 1rem; font-weight: 700; cursor: pointer; transition: all .25s;
    }
    .cancel-btn { background: #e2e8f0; color: #475569; }
    .cancel-btn:hover { background: #cbd5e1; }
    .confirm-btn {
      background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
      color: white;
      box-shadow: 0 4px 14px rgba(5,150,105,.3);
    }
    .confirm-btn:hover { transform: translateY(-2px); }

    /* SUCCESS NOTIFICATION */
    .success-notification { position: fixed; top: 24px; right: 24px; z-index: 10001; }
    .success-content {
      background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
      color: white;
      padding: 18px 26px;
      border-radius: 14px;
      box-shadow: 0 8px 28px rgba(5,150,105,.35);
      display: flex; align-items: center; gap: 14px;
      min-width: 320px;
      animation: popIn .3s cubic-bezier(.34,1.56,.64,1);
    }
    .success-icon {
      font-size: 1.75rem; background: rgba(255,255,255,.2);
      width: 46px; height: 46px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .success-message strong { display: block; font-size: 1.05rem; margin-bottom: 3px; }
    .success-message p { font-size: .88rem; opacity: .9; }

    @media (max-width: 768px) {
      .orders-grid { grid-template-columns: 1fr; }
      .page-header { flex-direction: column; align-items: flex-start; }
      .stats-bar { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
<div class="container">

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="header-left">
      <h1>📊 注文管理一覧</h1>
      <p>お客様からの注文をリアルタイムで管理</p>
    </div>
    <div class="header-actions">
      <div class="refresh-badge">
        <div class="refresh-dot"></div>
        10秒ごとに更新
      </div>
      <a href="admin.php" class="back-button">← 管理画面に戻る</a>
    </div>
  </div>

  <!-- TABLE LINKS -->
  <div class="table-links">
    <div class="table-links-title">📱 お客様テーブルへのリンク:</div>
    <div class="table-buttons">
      <?php for($i=1; $i<=5; $i++): ?>
      <a href="index.php?tableNo=<?php echo $i; ?>" class="table-btn" target="_blank">🪑 Table <?php echo $i; ?></a>
      <?php endfor; ?>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="stat-number"><?php echo count($orders); ?></div>
      <div class="stat-label">アクティブな注文</div>
    </div>
    <?php
      $totalRevenue = array_sum(array_column($orders, 'totalAmount'));
      $totalItems   = array_sum(array_column($orders, 'itemCount'));
      $tableNos     = array_unique(array_column($orders, 'tableNo'));
    ?>
    <div class="stat-card">
      <div class="stat-number">¥<?php echo number_format($totalRevenue); ?></div>
      <div class="stat-label">合計金額</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $totalItems; ?></div>
      <div class="stat-label">注文商品数</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo count($tableNos); ?></div>
      <div class="stat-label">使用中テーブル</div>
    </div>
  </div>

  <!-- ORDERS -->
  <?php if (empty($orders)): ?>
  <div class="orders-grid">
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <div class="empty-title">注文がありません</div>
      <div class="empty-text">新しい注文が入るとここに表示されます</div>
    </div>
  </div>
  <?php else: ?>
  <div class="orders-grid">
    <?php foreach ($orders as $i => $order):
      $ts = strtotime($order['dateB']);
      $dateStr = date('Y/m/d H:i', $ts);
    ?>
    <div class="order-card" style="animation-delay:<?php echo $i * 0.05; ?>s">
      <div class="order-header">
        <div class="table-badge">🍽️ Table <?php echo htmlspecialchars($order['tableNo']); ?></div>
        <div class="order-time">🕐 <?php echo $dateStr; ?></div>
      </div>
      <div class="order-body">
        <div class="order-info-row">
          <span class="order-label">注文番号:</span>
          <span class="order-value"><?php echo htmlspecialchars(substr($order['orderNo'], -8)); ?></span>
        </div>
        <div class="order-info-row">
          <span class="order-label">商品点数:</span>
          <span class="order-value"><?php echo (int)$order['itemCount']; ?> 点</span>
        </div>
      </div>
      <div class="order-total">
        <div class="order-total-row">
          <span>💴 合計金額</span>
          <span>¥<?php echo number_format($order['totalAmount']); ?></span>
        </div>
      </div>
      <div class="order-actions">
        <a href="order.php?orderNo=<?php echo urlencode($order['orderNo']); ?>" class="btn-details">
          📋 詳細を見る
        </a>
        <button onclick="confirmPayment('<?php echo htmlspecialchars($order['orderNo']); ?>', <?php echo (int)$order['tableNo']; ?>, <?php echo (float)$order['totalAmount']; ?>)"
                class="btn-payment">
          ✅ 会計済み
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<!-- PAYMENT MODAL -->
<div id="paymentModal" class="payment-modal-overlay" style="display:none;">
  <div class="payment-modal-content">
    <div class="payment-modal-icon">💳</div>
    <h2 class="payment-modal-title">会計を完了しますか？</h2>
    <div class="payment-modal-details">
      <div class="detail-row">
        <span class="detail-label">テーブル:</span>
        <span class="detail-value" id="modalTableNo">Table 1</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">注文番号:</span>
        <span class="detail-value" id="modalOrderNo">-</span>
      </div>
      <div class="detail-row total-row">
        <span class="detail-label">合計金額:</span>
        <span class="detail-value" id="modalAmount">¥0</span>
      </div>
    </div>
    <p class="payment-modal-warning">⚠️ 会計を完了すると、この注文は一覧から削除されます</p>
    <div class="payment-modal-buttons">
      <button class="modal-btn cancel-btn" onclick="closePaymentModal()">キャンセル</button>
      <button class="modal-btn confirm-btn" onclick="executePayment()" id="confirmPaymentBtn">✓ 会計完了</button>
    </div>
  </div>
</div>

<!-- SUCCESS NOTIFICATION -->
<div id="successNotification" class="success-notification" style="display:none;">
  <div class="success-content">
    <div class="success-icon">✓</div>
    <div class="success-message">
      <strong>会計が完了しました！</strong>
      <p id="successDetails">Table 1 の注文をクリアしました</p>
    </div>
  </div>
</div>

<script>
const csrfToken = '<?php echo htmlspecialchars($csrfToken); ?>';
let currentOrderNo = '', currentTableNo = 0, currentAmount = 0;

function confirmPayment(orderNo, tableNo, totalAmount) {
  currentOrderNo = orderNo;
  currentTableNo = tableNo;
  currentAmount = totalAmount;
  document.getElementById('modalTableNo').textContent = 'Table ' + tableNo;
  document.getElementById('modalOrderNo').textContent = orderNo;
  document.getElementById('modalAmount').textContent = '¥' + Number(totalAmount).toLocaleString();
  document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() { document.getElementById('paymentModal').style.display = 'none'; }

function executePayment() {
  const btn = document.getElementById('confirmPaymentBtn');
  btn.disabled = true;
  btn.textContent = '処理中...';
  const formData = new FormData();
  formData.append('orderNo', currentOrderNo);
  formData.append('csrf_token', csrfToken);
  fetch('process_payment.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        closePaymentModal();
        showSuccessNotification(currentTableNo);
        setTimeout(() => location.reload(), 2000);
      } else {
        alert('エラー: ' + data.message);
        btn.disabled = false;
        btn.textContent = '✓ 会計完了';
      }
    })
    .catch(err => {
      console.error(err);
      alert('通信エラーが発生しました');
      btn.disabled = false;
      btn.textContent = '✓ 会計完了';
    });
}

function showSuccessNotification(tableNo) {
  const n = document.getElementById('successNotification');
  document.getElementById('successDetails').textContent = 'Table ' + tableNo + ' の注文をクリアしました';
  n.style.display = 'block';
  setTimeout(() => { n.style.display = 'none'; }, 3000);
}

// Close modal on backdrop click
document.getElementById('paymentModal').addEventListener('click', function(e) {
  if (e.target === this) closePaymentModal();
});

// Auto-refresh every 10 seconds
setInterval(() => {
  if (document.getElementById('paymentModal').style.display === 'none') location.reload();
}, 10000);
</script>
</body>
</html>
