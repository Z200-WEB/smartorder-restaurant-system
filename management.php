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

// Real-time order refresh (polling every 3s instead of page reload)
let _orderPollActive = true;
let _lastOrderHash = '';

function fetchOrdersRealtime(){
  if(!_orderPollActive) return;
  // Only fetch if payment modal is not open
  if(document.getElementById('paymentModal') && document.getElementById('paymentModal').style.display === 'flex') return;
  
  fetch(location.href, {headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}})
  .then(r=>r.text())
  .then(html=>{
    // Extract orders table content
    const parser = new DOMParser();
    const doc2 = parser.parseFromString(html, 'text/html');
    const newTable = doc2.querySelector('.orders-table, .order-list, [data-orders]');
    const oldTable = document.querySelector('.orders-table, .order-list, [data-orders]');
    if(newTable && oldTable){
      const newHash = newTable.innerHTML.length;
      if(newHash !== _lastOrderHash){
        _lastOrderHash = newHash;
        oldTable.innerHTML = newTable.innerHTML;
        // Re-attach event listeners
        attachOrderEvents();
        // Notification for new orders
        if(_lastOrderHash && Notification.permission==='granted'){
          new Notification('新しい注文が入りました', {icon:'/favicon.ico'});
        }
      }
    }
  })
  .catch(()=>{});
}

function attachOrderEvents(){
  // Re-bind any onclick handlers after DOM update
  document.querySelectorAll('[data-action]').forEach(el=>{
    // buttons already have onclick in HTML
  });
}

// Request notification permission
if(Notification && Notification.permission==='default'){
  Notification.requestPermission();
}

// Poll every 3 seconds
setInterval(fetchOrdersRealtime, 3000);

// Also keep a fallback full reload every 60s
setInterval(()=>{
  if(!document.getElementById('paymentModal') || document.getElementById('paymentModal').style.display !== 'flex'){
    location.reload();
  }
}, 60000);
</script>

<!-- ===== STAFF CHAT PANEL ===== -->
<style>
#staff-chat-fab{position:fixed;bottom:80px;right:20px;z-index:9999;background:#00897B;color:#fff;border:none;border-radius:50px;padding:12px 20px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 15px rgba(0,137,123,0.4);display:flex;align-items:center;gap:8px;}
#staff-chat-badge{background:#ff5252;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;display:none;align-items:center;justify-content:center;font-weight:700;}
#staff-chat-badge.show{display:flex;}
#staff-chat-panel{position:fixed;bottom:140px;right:20px;z-index:9998;width:360px;max-height:600px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.2);display:none;flex-direction:column;overflow:hidden;}
#staff-chat-panel.open{display:flex;}
.scp-header{background:linear-gradient(135deg,#00897B,#26A69A);color:#fff;padding:16px;font-weight:700;font-size:15px;}
.scp-tabs{display:flex;border-bottom:1px solid #eee;background:#f8f8f8;}
.scp-tab{flex:1;padding:10px;text-align:center;cursor:pointer;font-size:13px;font-weight:600;color:#666;border-bottom:3px solid transparent;}
.scp-tab.active{color:#00897B;border-bottom-color:#00897B;}
.scp-table-list{flex:1;overflow-y:auto;max-height:300px;}
.scp-table-item{padding:12px 16px;border-bottom:1px solid #f0f0f0;cursor:pointer;display:flex;justify-content:space-between;align-items:center;}
.scp-table-item:hover{background:#f5f5f5;}
.scp-table-item .scp-unread{background:#ff5252;color:#fff;border-radius:10px;padding:2px 8px;font-size:12px;}
.scp-msgs{flex:1;overflow-y:auto;max-height:250px;padding:12px;background:#f5f5f5;display:none;}
.scp-msgs.active{display:block;}
.scp-msg{margin-bottom:10px;padding:10px 14px;border-radius:12px;max-width:85%;font-size:13px;line-height:1.5;}
.scp-msg.user{background:#e3f2fd;margin-left:0;border-radius:12px 12px 12px 4px;}
.scp-msg.ai{background:#fff;border:1px solid #e0e0e0;margin-left:0;}
.scp-msg.staff{background:#00897B;color:#fff;margin-left:auto;border-radius:12px 12px 4px 12px;}
.scp-msg-time{font-size:10px;opacity:0.6;margin-top:4px;}
.scp-msg-table{font-size:11px;font-weight:700;color:#00897B;margin-bottom:4px;}
.scp-reply-area{padding:12px;border-top:1px solid #eee;display:none;}
.scp-reply-area.active{display:flex;gap:8px;}
.scp-reply-input{flex:1;border:1px solid #ddd;border-radius:8px;padding:10px;font-size:13px;resize:none;height:60px;font-family:inherit;}
.scp-reply-btn{background:#00897B;color:#fff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:700;font-size:13px;}
.scp-back{color:#fff;background:none;border:none;cursor:pointer;font-size:16px;margin-right:8px;padding:0;}
</style>

<button id="staff-chat-fab" onclick="toggleStaffChatPanel()">
  💬 チャット <span id="staff-chat-badge" class="staff-chat-badge"></span>
</button>
<div id="staff-chat-panel">
  <div class="scp-header">
    <button class="scp-back" id="scp-back-btn" onclick="scpShowTableList()" style="display:none">←</button>
    <span id="scp-title">スタッフチャット管理</span>
  </div>
  <div class="scp-tabs">
    <div class="scp-tab active" onclick="scpSetTab('active')">アクティブ</div>
    <div class="scp-tab" onclick="scpSetTab('all')">全テーブル</div>
  </div>
  <div id="scp-table-list" class="scp-table-list"></div>
  <div id="scp-msgs" class="scp-msgs"></div>
  <div id="scp-reply-area" class="scp-reply-area">
    <textarea id="scp-reply-input" class="scp-reply-input" placeholder="返信を入力..."></textarea>
    <button class="scp-reply-btn" onclick="scpSendReply()">送信</button>
  </div>
</div>

<script>
// ===== STAFF CHAT PANEL =====
let scpCurrentTable = 0;
let scpMessages = {};
let scpUnread = {};
let scpTab = 'active';
let scpPanelOpen = false;

function toggleStaffChatPanel(){
  scpPanelOpen = !scpPanelOpen;
  document.getElementById('staff-chat-panel').classList.toggle('open', scpPanelOpen);
  if(scpPanelOpen) scpLoadTables();
}

function scpSetTab(tab){
  scpTab = tab;
  document.querySelectorAll('.scp-tab').forEach((t,i)=>t.classList.toggle('active',i===(tab==='active'?0:1)));
  scpLoadTables();
}

function scpLoadTables(){
  fetch('chat_api.php?action=get&tableNo=0')
  .then(r=>r.json())
  .then(data=>{
    if(!data.ok) return;
    // Group by table
    const tables = {};
    data.messages.forEach(m=>{
      if(!tables[m.tableNo]) tables[m.tableNo] = {tableNo:m.tableNo, msgs:[], hasNew:false};
      tables[m.tableNo].msgs.push(m);
      if(m.role==='user') tables[m.tableNo].hasNew = true;
    });
    
    const list = document.getElementById('scp-table-list');
    const entries = Object.values(tables).sort((a,b)=>b.tableNo-a.tableNo);
    
    if(entries.length === 0){
      list.innerHTML = '<div style="padding:20px;text-align:center;color:#999;font-size:13px;">チャットはありません</div>';
      return;
    }
    
    list.innerHTML = entries.map(t=>{
      const lastMsg = t.msgs[t.msgs.length-1];
      const preview = lastMsg ? lastMsg.message.substring(0,30) : '';
      const unreadDot = t.hasNew ? '<span class="scp-unread">NEW</span>' : '';
      return '<div class="scp-table-item" onclick="scpOpenTable('+t.tableNo+')"><div><div style="font-weight:700">テーブル'+t.tableNo+'</div><div style="font-size:12px;color:#999">'+preview+'</div></div>'+unreadDot+'</div>';
    }).join('');
  })
  .catch(e=>console.error(e));
}

function scpOpenTable(tableNo){
  scpCurrentTable = tableNo;
  document.getElementById('scp-title').textContent = 'テーブル'+tableNo;
  document.getElementById('scp-back-btn').style.display = 'inline';
  document.getElementById('scp-table-list').style.display = 'none';
  document.getElementById('scp-msgs').classList.add('active');
  document.getElementById('scp-reply-area').classList.add('active');
  scpLoadMessages(tableNo);
}

function scpShowTableList(){
  scpCurrentTable = 0;
  document.getElementById('scp-title').textContent = 'スタッフチャット管理';
  document.getElementById('scp-back-btn').style.display = 'none';
  document.getElementById('scp-table-list').style.display = 'block';
  document.getElementById('scp-msgs').classList.remove('active');
  document.getElementById('scp-reply-area').classList.remove('active');
  scpLoadTables();
}

function scpLoadMessages(tableNo){
  fetch('chat_api.php?action=get&tableNo='+tableNo)
  .then(r=>r.json())
  .then(data=>{
    if(!data.ok) return;
    const msgs = (data.messages||[]).reverse();
    const box = document.getElementById('scp-msgs');
    box.innerHTML = msgs.map(m=>{
      const time = new Date(m.created_at).toLocaleTimeString('ja-JP',{hour:'2-digit',minute:'2-digit'});
      const roleLabel = m.role==='user'?'👤 お客様':m.role==='ai'?'🤖 AI':'👔 スタッフ';
      return '<div class="scp-msg '+m.role+'"><div class="scp-msg-table">'+roleLabel+'</div>'+m.message+'<div class="scp-msg-time">'+time+'</div></div>';
    }).join('');
    box.scrollTop = box.scrollHeight;
  });
}

function scpSendReply(){
  const input = document.getElementById('scp-reply-input');
  const msg = input.value.trim();
  if(!msg || !scpCurrentTable) return;
  input.value = '';
  
  fetch('chat_api.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'reply', tableNo:scpCurrentTable, message:msg})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.ok) scpLoadMessages(scpCurrentTable);
  });
}

// Enter key in reply input
document.addEventListener('DOMContentLoaded',()=>{
  const inp = document.getElementById('scp-reply-input');
  if(inp) inp.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); scpSendReply(); }
  });
});

// Auto-poll for new messages when panel is open
setInterval(()=>{
  if(!scpPanelOpen) return;
  if(scpCurrentTable > 0) scpLoadMessages(scpCurrentTable);
  else scpLoadTables();
  
  // Update badge
  fetch('chat_api.php?action=get&tableNo=0')
  .then(r=>r.json())
  .then(data=>{
    if(!data.ok) return;
    const hasUser = data.messages.some(m=>m.role==='user');
    const badge = document.getElementById('staff-chat-badge');
    if(badge){ badge.textContent='!'; badge.classList.toggle('show',hasUser); }
  }).catch(()=>{});
}, 5000);

// Check badge on load
setTimeout(()=>{
  fetch('chat_api.php?action=get&tableNo=0')
  .then(r=>r.json())
  .then(data=>{
    if(!data.ok) return;
    const count = data.messages.filter(m=>m.role==='user').length;
    const badge = document.getElementById('staff-chat-badge');
    if(badge && count > 0){ badge.textContent=count; badge.classList.add('show'); }
  }).catch(()=>{});
}, 1000);
</script>
</body>
</html>
