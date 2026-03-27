<?php
header('Content-Type: text/html; charset=UTF-8');
// QR Code Generator for all tables - force HTTPS
$baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php';
$maxTables = isset($_GET['tables']) ? max(1, min(50, (int)$_GET['tables'])) : 10;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartOrder - QRコード一覧</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Noto Sans JP',sans-serif;background:#f0fafa;color:#0f172a;min-height:100vh}
.page-header{background:linear-gradient(135deg,#134e4a,#0f766e);color:#fff;padding:24px 32px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.page-title{font-size:1.5rem;font-weight:700}
.page-sub{font-size:.85rem;opacity:.8;margin-top:4px}
.controls{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.ctrl-label{font-size:.85rem;opacity:.85}
.ctrl-input{padding:7px 12px;border-radius:8px;border:none;font-size:.9rem;width:70px;text-align:center}
.btn-gen{padding:8px 20px;background:#f59e0b;color:#1a1a1a;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem}
.btn-print{padding:8px 20px;background:rgba(255,255,255,.2);color:#fff;border:2px solid rgba(255,255,255,.4);border-radius:8px;font-weight:700;cursor:pointer;font-size:.9rem}
.btn-print:hover{background:rgba(255,255,255,.3)}
.qr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;padding:32px;max-width:1200px;margin:0 auto}
.qr-card{background:#fff;border-radius:16px;padding:20px;text-align:center;box-shadow:0 4px 16px rgba(15,118,110,.1);border:2px solid #d1faf5;page-break-inside:avoid;transition:all .2s}
.qr-card:hover{box-shadow:0 8px 24px rgba(15,118,110,.2);transform:translateY(-2px)}
.qr-table-label{font-size:.8rem;font-weight:700;color:#0f766e;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.qr-img{width:160px;height:160px;border-radius:8px;border:2px solid #e0fdfa}
.qr-table-name{font-size:1.2rem;font-weight:700;margin-top:10px;color:#0f172a}
.qr-url{font-size:.65rem;color:#94a3b8;margin-top:4px;word-break:break-all}
.brand{margin-top:10px;font-size:.78rem;color:#0f766e;font-weight:600;background:#e0fdfa;padding:5px 10px;border-radius:999px;display:inline-block}
@media print{
  .controls{display:none!important}
  body{background:#fff}
  .qr-grid{padding:10px;gap:8px}
  .qr-card{box-shadow:none;border:1px solid #ccc;break-inside:avoid}
}
</style>
</head>
<body>
<div class="page-header">
  <div>
    <div class="page-title">🍽️ SmartOrder QRコード</div>
    <div class="page-sub">各テーブルのQRコードを印刷してテーブルに設置してください</div>
  </div>
  <div class="controls">
    <span class="ctrl-label">テーブル数：</span>
    <form method="get" style="display:flex;align-items:center;gap:8px">
      <input class="ctrl-input" type="number" name="tables" value="<?php echo $maxTables; ?>" min="1" max="50">
      <button class="btn-gen" type="submit">更新</button>
    </form>
    <button class="btn-print" onclick="window.print()">🖨️ 印刷</button>
  </div>
</div>

<div class="qr-grid">
<?php for($t = 1; $t <= $maxTables; $t++):
  $menuUrl = $baseUrl . '?tableNo=' . $t;
  $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' . urlencode($menuUrl);
?>
  <div class="qr-card">
    <div class="qr-table-label">テーブル</div>
    <img class="qr-img" src="<?php echo htmlspecialchars($qrApiUrl); ?>" alt="Table <?php echo $t; ?> QR Code" loading="lazy">
    <div class="qr-table-name">テーブル <?php echo $t; ?></div>
    <div class="qr-url"><?php echo htmlspecialchars($menuUrl); ?></div>
    <div class="brand">📱 スキャンしてご注文</div>
  </div>
<?php endfor; ?>
</div>
</body>
</html>
