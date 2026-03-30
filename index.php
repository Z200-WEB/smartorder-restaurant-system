<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'pdo.php';
$tableNo = isset($_GET['tableNo']) ? (int)$_GET['tableNo'] : 1;

// Get categories
$sqlCategory = "SELECT * FROM sCategory WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtCategory = $pdo->prepare($sqlCategory);
$stmtCategory->execute();
$categories = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

// Get items
$sqlItem = "SELECT * FROM sItem WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtItem = $pdo->prepare($sqlItem);
$stmtItem->execute();
$items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);

// Get current cart
$sqlCart = "
    SELECT o.id as orderId, o.itemNo, o.amount, o.item_notes,
           i.id as itemId, i.name, i.price, (i.price * o.amount) as subtotal
    FROM sManagement m
    JOIN sOrder o ON m.orderNo = o.orderNo
    JOIN sItem i ON o.itemNo = i.id
    WHERE m.tableNo = :tableNo AND m.state = 0
    ORDER BY o.id DESC
";
$stmtCart = $pdo->prepare($sqlCart);
$stmtCart->bindValue(':tableNo', $tableNo, PDO::PARAM_INT);
$stmtCart->execute();
$cartItems = $stmtCart->fetchAll(PDO::FETCH_ASSOC);
$currentTotal = 0;
$itemCount = 0;
foreach ($cartItems as $item) {
    $currentTotal += $item['subtotal'];
    $itemCount += $item['amount'];
}

function getItemImage($itemId) {
    $dir = __DIR__ . '/itemImages/';
    foreach (['jpg','jpeg','png','gif','webp'] as $ext) {
        if (file_exists($dir . $itemId . '.' . $ext)) {
            return 'itemImages/' . $itemId . '.' . $ext;
        }
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartOrder - Table <?php echo (int)$tableNo; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
  --primary:#0f766e;--primary-dark:#0d6460;--primary-light:#14b8a6;
  --primary-glow:rgba(15,118,110,.25);--accent:#f59e0b;--accent-dark:#d97706;
  --bg:#f0fafa;--surface:#ffffff;--surface2:#f8fffe;--border:#d1faf5;
  --text:#0f172a;--text2:#475569;--text3:#94a3b8;
  --radius-sm:8px;--radius:12px;--radius-lg:16px;--radius-xl:24px;
  --shadow-sm:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  --shadow:0 4px 16px rgba(15,118,110,.1);--shadow-md:0 8px 24px rgba(15,118,110,.15);
  --shadow-lg:0 16px 40px rgba(15,118,110,.2);--transition:.2s ease;
}
body{font-family:'Inter','Noto Sans JP',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
.app-header{position:sticky;top:0;z-index:100;background:linear-gradient(135deg,#134e4a 0%,#0f766e 100%);color:#fff;padding:12px 20px;box-shadow:0 2px 16px rgba(0,0,0,.15)}
.header-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.header-brand{display:flex;align-items:center;gap:10px}
.header-logo{width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.header-title{font-size:1.15rem;font-weight:800;letter-spacing:-.02em}
.header-table{font-size:.78rem;opacity:.75;margin-top:1px}
.btn-cart-header{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:8px 16px;border-radius:999px;font-size:.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:background var(--transition)}
.btn-cart-header:hover{background:rgba(255,255,255,.25)}
/* ── FEATURE: Language Switcher ── */
.lang-switcher{display:flex;align-items:center;gap:4px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:3px;margin-right:6px}
.lang-btn{background:transparent;border:none;color:rgba(255,255,255,.7);font-size:.72rem;font-weight:600;padding:4px 8px;border-radius:999px;cursor:pointer;transition:all .18s;white-space:nowrap;line-height:1}
.lang-btn.active{background:rgba(255,255,255,.25);color:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.lang-btn:hover:not(.active){background:rgba(255,255,255,.1);color:#fff}
@media(max-width:480px){
  .lang-switcher{padding:2px;gap:2px;margin-right:4px}
  .lang-btn{font-size:.65rem;padding:3px 6px}
}

.cart-badge{background:var(--accent);color:#1a1a1a;border-radius:50%;width:20px;height:20px;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.search-bar-wrap{background:var(--surface);border-bottom:2px solid var(--border);padding:10px 20px;position:sticky;top:60px;z-index:91}
.search-bar-inner{max-width:1280px;margin:0 auto;position:relative}
.search-input{width:100%;padding:9px 16px 9px 40px;border:2px solid var(--border);border-radius:999px;font-size:.9rem;font-family:inherit;background:var(--surface2);color:var(--text);transition:border-color var(--transition),box-shadow var(--transition);outline:none}
.search-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow)}
.search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:1rem;color:var(--text3);pointer-events:none}
.search-clear{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--text3);display:none;line-height:1}
.search-clear.visible{display:block}
.search-no-results{text-align:center;padding:48px 20px;color:var(--text3);font-size:.95rem;display:none}
.search-no-results.show{display:block}
.category-nav{background:var(--surface);border-bottom:2px solid var(--border);position:sticky;top:108px;z-index:90;overflow-x:auto;scrollbar-width:none}
.category-nav::-webkit-scrollbar{display:none}
.category-inner{display:flex;gap:6px;padding:10px 20px;min-width:max-content}
.cat-btn{padding:7px 18px;border-radius:999px;border:2px solid var(--border);background:var(--surface);color:var(--text2);font-size:.83rem;font-weight:500;cursor:pointer;white-space:nowrap;transition:all var(--transition)}
.cat-btn:hover{border-color:var(--primary-light);color:var(--primary)}
.cat-btn.active{background:var(--primary);border-color:var(--primary);color:#fff;font-weight:700;box-shadow:var(--shadow)}
.app-layout{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 360px;gap:24px;padding:24px 20px}
@media(max-width:900px){.app-layout{grid-template-columns:1fr}.cart-sidebar{display:none}}
.menu-section-title{font-size:1rem;font-weight:700;color:var(--text2);margin-bottom:16px;text-transform:uppercase;letter-spacing:.06em}
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:16px}
.menu-card{background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);cursor:pointer;transition:transform var(--transition),box-shadow var(--transition),border-color var(--transition);border:2px solid transparent;position:relative;display:flex;flex-direction:column}
.menu-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);border-color:var(--primary-light)}
.item-img-wrap{width:100%;aspect-ratio:1;background:linear-gradient(135deg,#e0fdfa 0%,#ccfbf1 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.item-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.menu-card:hover .item-img-wrap img{transform:scale(1.06)}
.item-img-wrap .placeholder-icon{font-size:3rem;color:#99f6e4}
.item-info{padding:12px}
.item-name{font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:5px;line-height:1.3}
.item-price{font-size:.95rem;font-weight:700;color:var(--primary)}
.item-add-btn{width:100%;padding:8px;background:var(--primary);color:#fff;border:none;font-size:.82rem;font-weight:600;cursor:pointer;transition:background var(--transition);letter-spacing:.02em;margin-top:auto;display:block;flex-shrink:0}
.item-add-btn:hover{background:var(--primary-dark)}
.item-badge{position:absolute;top:8px;left:8px;padding:3px 9px;border-radius:999px;font-size:.7rem;font-weight:700;z-index:2;letter-spacing:.03em;box-shadow:0 2px 6px rgba(0,0,0,.15)}
.badge-popular{background:linear-gradient(135deg,#ef4444,#f97316);color:#fff}
.badge-recommended{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#1a1a1a}
.badge-limited{background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff}
.badge-free{background:linear-gradient(135deg,#10b981,#34d399);color:#fff}
.cart-sidebar{position:sticky;top:168px;height:fit-content}
.cart-box{background:var(--surface);border-radius:var(--radius-xl);box-shadow:var(--shadow-md);overflow:hidden;border:1px solid var(--border)}
.cart-header-bar{background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;padding:16px 20px;font-weight:700;font-size:1rem;display:flex;align-items:center;gap:8px}
.cart-items{max-height:400px;overflow-y:auto;padding:12px;scrollbar-width:thin;scrollbar-color:var(--border) transparent}
.cart-empty{text-align:center;padding:36px 20px;color:var(--text3);font-size:.9rem}
.cart-empty-icon{font-size:2.5rem;margin-bottom:8px;opacity:.5}
.cart-item{display:flex;align-items:center;gap:10px;padding:10px;border-radius:var(--radius);margin-bottom:8px;background:var(--surface2);border:1px solid var(--border)}
.cart-item-thumb{width:44px;height:44px;border-radius:var(--radius-sm);overflow:hidden;background:linear-gradient(135deg,#e0fdfa,#ccfbf1);flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#99f6e4}
.cart-item-thumb img{width:100%;height:100%;object-fit:cover}
.cart-item-info{flex:1;min-width:0}
.cart-item-name{font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cart-item-price{font-size:.76rem;color:var(--text2)}
.cart-item-qty{display:flex;align-items:center;gap:4px;flex-shrink:0}
.qty-btn{width:26px;height:26px;border-radius:50%;border:2px solid var(--border);background:var(--surface);font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all var(--transition);color:var(--text2)}
.qty-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
.qty-num{font-size:.85rem;font-weight:700;min-width:22px;text-align:center;color:var(--text)}
.cart-footer{border-top:2px solid var(--border);padding:14px 20px;background:var(--surface2)}
.cart-total{display:flex;justify-content:space-between;font-weight:700;font-size:1rem;margin-bottom:12px;color:var(--text)}
.cart-total-price{color:var(--primary);font-size:1.15rem}
.btn-checkout{width:100%;padding:13px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:var(--radius);font-size:.95rem;font-weight:700;cursor:pointer;transition:all var(--transition);box-shadow:var(--shadow);letter-spacing:.02em}
.btn-checkout:hover{transform:translateY(-1px);box-shadow:var(--shadow-md)}
.btn-checkout:disabled{background:#cbd5e1;cursor:not-allowed;box-shadow:none;transform:none}
.btn-call-staff{width:100%;margin-top:10px;padding:11px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:var(--radius);font-size:.88rem;font-weight:700;cursor:pointer;transition:all var(--transition);box-shadow:0 4px 12px rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;gap:8px;letter-spacing:.02em}
.btn-call-staff:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(245,158,11,.4)}
.call-staff-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:2000;align-items:center;justify-content:center}
.call-staff-overlay.show{display:flex}
.call-staff-box{background:var(--surface);border-radius:var(--radius-xl);padding:32px 28px;max-width:380px;width:90%;text-align:center;box-shadow:var(--shadow-lg)}
.call-staff-emoji{font-size:3rem;margin-bottom:12px;display:block;animation:bounce 1s ease infinite}
@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.call-staff-title{font-size:1.15rem;font-weight:700;margin-bottom:8px;color:var(--text)}
.call-staff-msg{color:var(--text2);font-size:.9rem;margin-bottom:22px}
.call-staff-options{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px}
.call-option-btn{padding:12px 10px;border:2px solid var(--border);border-radius:var(--radius);background:var(--surface2);cursor:pointer;font-size:.85rem;font-weight:600;transition:all var(--transition);color:var(--text);display:flex;flex-direction:column;align-items:center;gap:6px}
.call-option-btn:hover,.call-option-btn.selected{border-color:var(--primary);background:rgba(15,118,110,.08);color:var(--primary)}
.call-option-icon{font-size:1.5rem}
.btn-call-confirm{width:100%;padding:12px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:var(--radius);font-weight:700;cursor:pointer;transition:all var(--transition);font-size:.95rem}
.btn-call-confirm:hover{opacity:.9}
.btn-call-close{margin-top:10px;width:100%;padding:10px;border:2px solid var(--border);background:#fff;border-radius:var(--radius);cursor:pointer;font-weight:600;color:var(--text2);transition:all var(--transition)}
.btn-call-close:hover{border-color:var(--primary);color:var(--primary)}
.float-cart{display:none;position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:999px;padding:14px 22px;font-size:.9rem;font-weight:700;cursor:pointer;box-shadow:0 6px 24px var(--primary-glow);z-index:200;align-items:center;gap:8px;transition:all var(--transition)}
.float-cart:hover{transform:translateY(-2px);box-shadow:0 8px 32px var(--primary-glow)}
@media(max-width:900px){.float-cart{display:flex}}
@media(max-width:480px){.float-cart{bottom:16px!important;right:12px!important;padding:10px 16px!important;font-size:.82rem!important}}
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;padding:20px}
.modal-overlay.show{display:flex}
.modal-box{background:var(--surface);border-radius:var(--radius-xl);width:100%;max-width:440px;overflow:hidden;animation:slideUp .25s ease;box-shadow:var(--shadow-lg)}
@keyframes slideUp{from{transform:translateY(24px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-header-bar{background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;padding:18px 22px;font-weight:700;font-size:1.05rem}
.modal-body-inner{padding:20px}
.modal-item-img{width:100%;aspect-ratio:16/9;background:linear-gradient(135deg,#e0fdfa,#ccfbf1);border-radius:var(--radius);overflow:hidden;margin-bottom:14px;display:flex;align-items:center;justify-content:center}
.modal-item-img img{width:100%;height:100%;object-fit:cover}
.modal-item-price{font-size:1.25rem;font-weight:800;color:var(--primary);margin-bottom:14px}
.modal-qty-control{display:flex;align-items:center;justify-content:center;gap:18px;margin-bottom:14px}
.modal-qty-btn{width:38px;height:38px;border-radius:50%;border:2px solid var(--primary);background:#fff;color:var(--primary);font-size:1.25rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all var(--transition)}
.modal-qty-btn:hover{background:var(--primary);color:#fff}
.modal-qty-num{font-size:1.5rem;font-weight:700;min-width:44px;text-align:center}
.modal-notes{width:100%;padding:10px 14px;border:2px solid var(--border);border-radius:var(--radius-sm);font-size:.9rem;font-family:inherit;resize:none;margin-bottom:14px;transition:border-color var(--transition)}
.modal-notes:focus{outline:none;border-color:var(--primary)}
.modal-footer-row{display:flex;gap:10px;padding:14px 20px;border-top:2px solid var(--border)}
.btn-cancel-modal{flex:1;padding:11px;border:2px solid var(--border);background:#fff;border-radius:var(--radius-sm);cursor:pointer;font-size:.9rem;font-weight:600;color:var(--text2);transition:all var(--transition)}
.btn-cancel-modal:hover{border-color:var(--primary);color:var(--primary)}
.btn-order-modal{flex:2;padding:11px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:var(--radius-sm);font-size:.9rem;font-weight:700;cursor:pointer;transition:all var(--transition)}
.btn-order-modal:hover{opacity:.92}
.combo-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:1500;align-items:center;justify-content:center;padding:20px}
.combo-overlay.show{display:flex}
.combo-box{background:var(--surface);border-radius:var(--radius-xl);width:100%;max-width:420px;overflow:hidden;animation:slideUp .25s ease;box-shadow:var(--shadow-lg)}
.combo-header{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:16px 22px}
.combo-header-title{font-size:1rem;font-weight:700}
.combo-header-sub{font-size:.8rem;opacity:.9;margin-top:2px}
.combo-body{padding:18px}
.combo-added-item{display:flex;align-items:center;gap:12px;padding:12px;background:var(--surface2);border-radius:var(--radius);border:2px solid var(--border);margin-bottom:14px}
.combo-added-img{width:52px;height:52px;border-radius:var(--radius-sm);background:linear-gradient(135deg,#e0fdfa,#ccfbf1);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.combo-added-img img{width:100%;height:100%;object-fit:cover}
.combo-added-name{font-weight:600;font-size:.9rem;color:var(--text)}
.combo-added-price{font-size:.8rem;color:var(--text2);margin-top:2px}
.combo-arrow{text-align:center;font-size:1.3rem;margin-bottom:10px;color:var(--accent)}
.combo-suggestion-label{font-size:.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.combo-suggestions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
.combo-suggest-item{border:2px solid var(--border);border-radius:var(--radius);padding:10px;cursor:pointer;transition:all var(--transition);background:var(--surface2);text-align:center}
.combo-suggest-item:hover,.combo-suggest-item.selected{border-color:var(--primary);background:rgba(15,118,110,.08)}
.combo-suggest-img{width:100%;aspect-ratio:1;border-radius:var(--radius-sm);background:linear-gradient(135deg,#e0fdfa,#ccfbf1);display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:6px}
.combo-suggest-img img{width:100%;height:100%;object-fit:cover}
.combo-suggest-name{font-size:.78rem;font-weight:600;color:var(--text);line-height:1.3}
.combo-suggest-price{font-size:.78rem;color:var(--primary);font-weight:700;margin-top:2px}
.combo-footer{display:flex;gap:10px;padding:14px 18px;border-top:2px solid var(--border)}
.btn-combo-skip{flex:1;padding:11px;border:2px solid var(--border);background:#fff;border-radius:var(--radius-sm);cursor:pointer;font-size:.88rem;font-weight:600;color:var(--text2);transition:all var(--transition)}
.btn-combo-skip:hover{border-color:var(--primary);color:var(--primary)}
.btn-combo-add{flex:2;padding:11px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:var(--radius-sm);font-size:.88rem;font-weight:700;cursor:pointer;transition:all var(--transition)}
.btn-combo-add:hover{opacity:.9}
.btn-combo-add:disabled{background:#cbd5e1;cursor:not-allowed}
.cart-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:1000}
.cart-modal-overlay.show{display:block}
.cart-modal{position:fixed;bottom:0;left:0;right:0;background:var(--surface);border-radius:24px 24px 0 0;padding:24px;z-index:1001;max-height:82vh;overflow-y:auto;animation:slideUp .3s ease;box-shadow:var(--shadow-lg)}
.cart-modal-title{font-size:1.1rem;font-weight:700;margin-bottom:16px;color:var(--text)}
.confirm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:2000;align-items:center;justify-content:center}
.confirm-overlay.show{display:flex}
.confirm-box{background:var(--surface);border-radius:var(--radius-xl);padding:28px 24px;max-width:360px;width:90%;text-align:center;box-shadow:var(--shadow-lg)}
.confirm-icon{font-size:2.8rem;margin-bottom:12px}
.confirm-title{font-size:1.1rem;font-weight:700;margin-bottom:8px}
.confirm-message{color:var(--text2);font-size:.9rem;margin-bottom:22px}
.confirm-buttons{display:flex;gap:10px}
.btn-confirm-cancel{flex:1;padding:11px;border:2px solid var(--border);background:#fff;border-radius:var(--radius-sm);cursor:pointer;font-weight:600;color:var(--text2);transition:all var(--transition)}
.btn-confirm-cancel:hover{border-color:var(--primary);color:var(--primary)}
.btn-confirm-ok{flex:1;padding:11px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:var(--radius-sm);cursor:pointer;font-weight:700;transition:all var(--transition)}
.btn-confirm-ok:hover{opacity:.9}
.order-celebrate{display:none;position:fixed;inset:0;z-index:3500;pointer-events:none;align-items:center;justify-content:center}
.order-celebrate.show{display:flex}
.celebrate-card{background:var(--surface);border-radius:var(--radius-xl);padding:28px 32px;text-align:center;box-shadow:var(--shadow-lg);animation:celebPop .4s cubic-bezier(.17,.67,.35,1.3);pointer-events:auto;border:3px solid var(--primary-light);max-width:340px;width:90%}
@keyframes celebPop{from{transform:scale(.5);opacity:0}to{transform:scale(1);opacity:1}}
.celebrate-mascot{font-size:3.5rem;animation:mascotSpin .6s ease;display:block;margin-bottom:8px}
@keyframes mascotSpin{0%{transform:rotate(-15deg) scale(.8)}50%{transform:rotate(10deg) scale(1.1)}100%{transform:rotate(0) scale(1)}}
.celebrate-text{font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:4px}
.celebrate-sub{font-size:.85rem;color:var(--text2)}
.confetti-container{position:fixed;inset:0;pointer-events:none;z-index:3400;overflow:hidden}
.confetti-piece{position:absolute;width:8px;height:8px;border-radius:2px;animation:confettiFall linear forwards}
@keyframes confettiFall{0%{transform:translateY(-20px) rotate(0);opacity:1}100%{transform:translateY(110vh) rotate(720deg);opacity:0}}
.toast-container{position:fixed;top:80px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--text);color:#fff;padding:11px 18px;border-radius:var(--radius);font-size:.85rem;min-width:210px;animation:slideInToast .3s ease;box-shadow:var(--shadow-md)}
@keyframes slideInToast{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
.toast.success{background:#0d6460}
.toast.error{background:#dc2626}
.loading-overlay{display:none;position:fixed;inset:0;background:rgba(255,255,255,.8);backdrop-filter:blur(2px);z-index:9999;align-items:center;justify-content:center}
.loading-overlay.show{display:flex}
.spinner{width:42px;height:42px;border:4px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .75s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
#waifu-tips{position:absolute;top:-80px;left:50%;transform:translateX(-50%);background:#fff;border:2px solid var(--border);border-radius:16px 16px 16px 4px;padding:10px 14px;font-size:.82rem;max-width:220px;line-height:1.45;color:var(--text);box-shadow:var(--shadow-md);white-space:normal;pointer-events:none;text-align:center;animation:bubblePop .3s ease;z-index:10000}
#waifu-tips::after{content:'';position:absolute;bottom:-8px;left:20px;border:4px solid transparent;border-top-color:#fff}
/* Hide unwanted Live2D toolbar buttons */
#waifu-tool-switch-model,#waifu-tool-switch-texture,#waifu-tool-asteroids,#waifu-tool-hitokoto,#waifu-tool-photo,#waifu-tool-info{display:none!important}

/* ── MASCOT RESPONSIVE ── */
#waifu{bottom:0!important;left:10px!important;z-index:300!important;transition:width .3s,height .3s!important}
/* Desktop: normal 300px */
@media(min-width:901px){
  #waifu{width:280px!important;height:280px!important}
  #waifu canvas{width:280px!important;height:280px!important}
}
/* Tablet (iPad): smaller */
@media(min-width:481px) and (max-width:900px){
  #waifu{width:180px!important;height:180px!important;left:6px!important}
  #waifu canvas{width:180px!important;height:180px!important}
  #waifu-tips{font-size:.75rem!important;max-width:160px!important;top:-70px!important}
}
/* Phone: small, corner only */
@media(max-width:480px){
  #waifu{width:200px!important;height:200px!important;left:4px!important}
  #waifu canvas{width:200px!important;height:200px!important}
  #waifu-tips{font-size:.7rem!important;max-width:150px!important;top:-60px!important;padding:7px 10px!important}
}


/* ═══════════════════════════════════════
   RESPONSIVE - Phone, Tablet, Desktop
═══════════════════════════════════════ */

/* ── TABLET (iPad 481-900px) ── */
@media(min-width:481px) and (max-width:900px){
  /* Header */
  .app-header{padding:10px 14px}
  .header-title{font-size:1rem}
  .btn-cart-header{padding:7px 12px;font-size:.78rem}

  /* Search */
  .search-bar-wrap{padding:8px 14px;top:56px}
  .search-bar-inner{top:100px}

  /* Category nav */
  .category-nav{top:100px}
  .cat-btn{padding:6px 14px;font-size:.78rem}

  /* Menu grid - 3 columns on tablet */
  .app-layout{padding:16px 14px;gap:16px}
  .menu-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}
  .menu-section-title{font-size:.88rem;margin-bottom:12px}

  /* Cards */
  .item-name{font-size:.82rem}
  .item-price{font-size:.88rem}
  .item-add-btn{font-size:.78rem;padding:7px}

  /* Float cart always visible on tablet */
  .float-cart{display:flex!important;bottom:16px;right:16px;padding:12px 18px;font-size:.85rem}
}

/* ── PHONE (≤480px) ── */
@media(max-width:480px){
  /* Header - compact */
  .app-header{padding:8px 12px}
  .header-inner{gap:8px}
  .header-logo{width:30px;height:30px;font-size:1rem}
  .header-title{font-size:.95rem}
  .header-table{font-size:.7rem}
  .btn-cart-header{padding:6px 10px;font-size:.75rem;gap:5px}
  .cart-badge{width:18px;height:18px;font-size:.65rem}

  /* Staff button in header */
  .app-header button[onclick="openCallStaff()"]{padding:6px 10px;font-size:.72rem}

  /* Search */
  .search-bar-wrap{padding:7px 12px;top:54px}
  .search-input{font-size:.82rem;padding:8px 14px 8px 36px}
  .search-icon{font-size:.88rem;left:11px}

  /* Category nav */
  .category-nav{top:98px}
  .category-inner{padding:7px 12px;gap:5px}
  .cat-btn{padding:5px 11px;font-size:.74rem}

  /* Layout - single column, no sidebar */
  .app-layout{padding:12px 10px;gap:0}

  /* Menu grid - 2 columns on phone */
  .menu-grid{grid-template-columns:repeat(2,1fr);gap:10px}
  .menu-section-title{font-size:.82rem;margin-bottom:10px}

  /* Cards - compact */
  .menu-card{border-radius:12px}
  .item-img-wrap{aspect-ratio:1}
  .item-info{padding:8px}
  .item-name{font-size:.78rem;margin-bottom:3px}
  .item-price{font-size:.85rem}
  .item-add-btn{font-size:.75rem;padding:7px}
  .item-badge{font-size:.62rem;padding:2px 7px;top:6px;left:6px}

  /* Float cart - always show on phone */
  .float-cart{display:flex!important;bottom:16px;right:12px;padding:10px 16px;font-size:.82rem;gap:6px}

  /* Modals - full width on phone */
  .modal-overlay{padding:10px}
  .modal-box{max-width:100%;border-radius:16px}
  .modal-body-inner{padding:14px}
  .modal-footer-row{padding:10px 14px;gap:8px}

  /* Combo overlay */
  .combo-overlay{padding:10px}
  .combo-box{max-width:100%;border-radius:16px}
  .combo-header{padding:13px 16px}
  .combo-body{padding:13px}
  .combo-footer{padding:11px 14px}

  /* Confirm dialog */
  .confirm-box{padding:20px 16px;max-width:95%}

  /* Call staff */
  .call-staff-box{padding:20px 16px;max-width:95%}
  .call-staff-options{gap:8px}
  .call-option-btn{padding:10px 8px;font-size:.8rem}

  /* Toast */
  .toast-container{top:70px;right:10px;left:10px}
  .toast{min-width:unset;font-size:.8rem;padding:9px 14px}

  /* Celebrate card */
  .celebrate-card{padding:20px 18px;max-width:92%}
  .celebrate-mascot{font-size:2.8rem}
  .celebrate-text{font-size:.95rem}

  /* Cart modal */
  .cart-modal{padding:16px;border-radius:20px 20px 0 0}
  .cart-modal-title{font-size:.95rem;margin-bottom:12px}
  .cart-item{padding:8px;gap:8px}
  .cart-item-thumb{width:38px;height:38px}
  .cart-item-name{font-size:.78rem}
  .cart-item-price{font-size:.72rem}
  .qty-btn{width:24px;height:24px;font-size:.82rem}
  .qty-num{font-size:.8rem;min-width:18px}
}

/* ── LARGE PHONE landscape / small tablet (481-600px) ── */
@media(min-width:481px) and (max-width:600px){
  .menu-grid{grid-template-columns:repeat(2,1fr)!important}
}
/* ── FAVORITES ── */
.fav-btn{position:absolute;top:8px;right:8px;background:rgba(255,255,255,.85);border:none;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1rem;z-index:3;transition:all .2s;backdrop-filter:blur(2px);box-shadow:0 2px 6px rgba(0,0,0,.1)}
.fav-btn:hover{transform:scale(1.15)}
.fav-btn.active{background:rgba(255,80,80,.12)}
.fav-btn.pop{animation:favPop .3s cubic-bezier(.17,.67,.35,1.5)}
@keyframes favPop{0%{transform:scale(1)}50%{transform:scale(1.4)}100%{transform:scale(1)}}

/* ── STAMP CARD ── */
.stamp-card-bar{position:fixed;bottom:10px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#fff9e6,#fff3cc);border:2px solid #f59e0b;border-radius:999px;padding:6px 16px;display:flex;align-items:center;gap:8px;z-index:150;box-shadow:0 4px 16px rgba(245,158,11,.3);font-size:.82rem;font-weight:700;color:#b45309;transition:all .3s;white-space:nowrap}
.stamp-dots{display:flex;gap:4px;align-items:center}
.stamp-dot{width:18px;height:18px;border-radius:50%;border:2px solid #f59e0b;background:#fff;transition:all .3s;font-size:.7rem;display:flex;align-items:center;justify-content:center}
.stamp-dot.filled{background:linear-gradient(135deg,#f59e0b,#fbbf24);border-color:#d97706;color:#fff}
.stamp-dot.new{animation:stampPop .4s cubic-bezier(.17,.67,.35,1.5)}
@keyframes stampPop{0%{transform:scale(0)}60%{transform:scale(1.3)}100%{transform:scale(1)}}
.stamp-reward-overlay{display:none;position:fixed;inset:0;z-index:4000;align-items:center;justify-content:center;background:rgba(0,0,0,.5);backdrop-filter:blur(6px)}
.stamp-reward-overlay.show{display:flex}
.stamp-reward-box{background:linear-gradient(135deg,#fff9e6,#fffbeb);border-radius:24px;padding:32px 28px;text-align:center;max-width:320px;width:90%;box-shadow:0 20px 60px rgba(245,158,11,.4);border:3px solid #f59e0b;animation:celebPop .4s cubic-bezier(.17,.67,.35,1.3)}
.stamp-reward-emoji{font-size:4rem;display:block;margin-bottom:8px;animation:bounce 1s ease infinite}
.stamp-reward-title{font-size:1.25rem;font-weight:800;color:#92400e;margin-bottom:6px}
.stamp-reward-sub{font-size:.9rem;color:#b45309;margin-bottom:20px}
.stamp-reward-btn{padding:12px 28px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:999px;font-size:1rem;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(245,158,11,.4)}

/* ── STAFF ARRIVAL ── */
.staff-arrival-toast{display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#134e4a,#0f766e);color:#fff;border-radius:999px;padding:10px 22px;font-size:.88rem;font-weight:700;z-index:3500;white-space:nowrap;box-shadow:0 4px 16px rgba(15,118,110,.4);align-items:center;gap:8px}
.staff-arrival-toast.show{display:flex}
.staff-progress-ring{width:22px;height:22px;transform:rotate(-90deg)}
.staff-progress-circle{stroke-dasharray:60;stroke-dashoffset:60;transition:stroke-dashoffset .3s linear;stroke:#14b8a6;stroke-width:3;fill:none}

/* Stamp bar mobile - keep above menu but below float cart */
@media(max-width:900px){
  .stamp-card-bar{bottom:80px;font-size:.75rem;padding:5px 12px;gap:6px}
  .stamp-dot{width:16px;height:16px;font-size:.65rem}
}
@media(max-width:480px){
  .stamp-card-bar{bottom:74px;padding:4px 10px;font-size:.72rem;gap:5px}
  .stamp-dot{width:14px;height:14px}
}
/* ── FEATURE: Mobile mascot fix ── */
@media(max-width:480px){
  #waifu{display:none!important}
  #waifu-toggle{display:none!important}
}
@media(min-width:481px) and (max-width:900px){
  #waifu{width:220px!important;height:220px!important;left:6px!important;bottom:0px!important}
  #waifu canvas{width:220px!important;height:220px!important}
}
/* ── FEATURE: Popular / History badges ── */
.badge-hot{background:linear-gradient(135deg,#ef4444,#f97316)!important;color:#fff!important;animation:hotPulse 2s infinite}
@keyframes hotPulse{0%,100%{box-shadow:0 2px 6px rgba(239,68,68,.3)}50%{box-shadow:0 4px 14px rgba(239,68,68,.7)}}
.badge-before-corner{position:absolute;bottom:8px;left:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;border-radius:999px;padding:2px 8px;font-size:.62rem;font-weight:700;z-index:4;white-space:nowrap}
/* ── FEATURE: Lucky Roulette button ── */
#lucky-btn{position:fixed;right:24px;bottom:90px;z-index:200;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;border:none;border-radius:999px;padding:10px 18px;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(139,92,246,.45);display:flex;align-items:center;gap:6px;transition:transform .2s,box-shadow .2s;white-space:nowrap}
#lucky-btn:hover{transform:scale(1.06);box-shadow:0 6px 22px rgba(139,92,246,.6)}
#lucky-btn.spinning{animation:luckySpin .6s ease}
@keyframes luckySpin{0%{transform:rotate(0)scale(1)}50%{transform:rotate(180deg)scale(1.1)}100%{transform:rotate(360deg)scale(1)}}
#lucky-reveal{display:none;position:fixed;inset:0;z-index:3500;background:rgba(0,0,0,.55);backdrop-filter:blur(5px);align-items:center;justify-content:center}
#lucky-reveal.show{display:flex}
#lucky-reveal-box{background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:3px solid #8b5cf6;border-radius:24px;padding:32px 28px;text-align:center;max-width:320px;width:90%;animation:celebPop .4s cubic-bezier(.17,.67,.35,1.3)}
#lucky-reveal-emoji{font-size:4rem;display:block;margin-bottom:10px;animation:bounce 1s ease infinite}
#lucky-reveal-label{font-size:.8rem;color:#7c3aed;font-weight:600;margin-bottom:6px}
#lucky-reveal-name{font-size:1.25rem;font-weight:800;color:#1e1b4b;margin-bottom:6px}
#lucky-reveal-price{font-size:1rem;color:#7c3aed;font-weight:700;margin-bottom:20px}
.lucky-btn-row{display:flex;gap:10px;justify-content:center}
.btn-lucky-add{padding:12px 24px;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;border:none;border-radius:999px;font-size:1rem;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(139,92,246,.4)}
.btn-lucky-skip{padding:12px 20px;background:#f1f5f9;color:#64748b;border:none;border-radius:999px;font-size:.95rem;cursor:pointer}
/* ── FEATURE: Staff Chat ── */
#chat-btn{position:fixed;left:16px;bottom:90px;z-index:200;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;border:none;border-radius:999px;padding:10px 16px;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(15,118,110,.4);display:flex;align-items:center;gap:6px;transition:transform .2s;white-space:nowrap}
#chat-btn:hover{transform:scale(1.06)}
#chat-btn .chat-badge{background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:.65rem;display:none;align-items:center;justify-content:center;font-weight:700}
#chat-btn .chat-badge.show{display:flex}
#chat-overlay{display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center}
#chat-overlay.show{display:flex}
#chat-box{background:var(--surface,#fff);border-radius:24px 24px 0 0;padding:24px;width:100%;max-width:480px;max-height:75vh;display:flex;flex-direction:column;animation:slideUp .3s ease}
#chat-title{font-size:1rem;font-weight:700;margin-bottom:4px;color:#0f766e}
#chat-subtitle{font-size:.78rem;color:#94a3b8;margin-bottom:14px}
#chat-messages{flex:1;overflow-y:auto;min-height:80px;max-height:200px;margin-bottom:12px;display:flex;flex-direction:column;gap:8px}
.chat-msg{padding:8px 12px;border-radius:12px;font-size:.82rem;max-width:85%;line-height:1.4}
.chat-msg.sent{background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
.chat-msg.system{background:#f0fdf4;color:#0f766e;align-self:flex-start;border-bottom-left-radius:4px;border:1px solid #a7f3d0}
.chat-msg-time{font-size:.65rem;opacity:.7;margin-top:2px}
#chat-input-row{display:flex;gap:8px;align-items:flex-end}
#chat-input{flex:1;border:2px solid #e2e8f0;border-radius:14px;padding:10px 14px;font-size:.85rem;resize:none;min-height:42px;max-height:100px;font-family:inherit;outline:none;transition:border-color .2s}
#chat-input:focus{border-color:#14b8a6}
#chat-send{padding:10px 16px;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:.85rem;font-weight:700;cursor:pointer;white-space:nowrap}
#chat-close{display:block;margin-top:10px;text-align:center;color:#94a3b8;font-size:.8rem;cursor:pointer;background:none;border:none;width:100%;padding:4px}
.chat-empty{color:#cbd5e1;text-align:center;font-size:.82rem;padding:20px 0}
/* ── FEATURE: Last-Call Banner ── */
#last-call-banner{display:none;position:fixed;top:0;left:0;right:0;z-index:2500;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;text-align:center;padding:10px 16px;font-size:.88rem;font-weight:700;animation:lastCallPulse 1.5s infinite;cursor:pointer;box-shadow:0 4px 16px rgba(239,68,68,.5)}
@keyframes lastCallPulse{0%,100%{background:linear-gradient(135deg,#ef4444,#dc2626)}50%{background:linear-gradient(135deg,#f97316,#ef4444)}}
#last-call-banner .lc-close{float:right;background:rgba(255,255,255,.25);border:none;color:#fff;border-radius:50%;width:22px;height:22px;font-size:.8rem;cursor:pointer;line-height:22px}
#last-call-banner.show{display:block}
/* ── FEATURE: Wait Time Estimator ── */
#wait-time-indicator{display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:12px;padding:10px 14px;margin:12px 0;font-size:.85rem;color:#166534;font-weight:600}
#wait-time-indicator .wt-icon{font-size:1.3rem}
#wait-time-indicator .wt-detail{font-size:.72rem;color:#4ade80;font-weight:400}
/* ── FEATURE: Mascot Speech bubble ── */
@keyframes mascotJump{0%{transform:translateY(0)}30%{transform:translateY(-18px)rotate(-5deg)}60%{transform:translateY(-8px)rotate(3deg)}100%{transform:translateY(0)rotate(0)}}
@keyframes mascotWave{0%{transform:rotate(0)}25%{transform:rotate(15deg)}75%{transform:rotate(-15deg)}100%{transform:rotate(0)}}
.mascot-jumping{animation:mascotJump .7s ease!important}
.mascot-waving{animation:mascotWave .5s ease 3!important}
#mascot-speech{position:fixed;bottom:290px;left:10px;background:#fff;border:2px solid var(--primary-light,#5eead4);border-radius:16px 16px 16px 4px;padding:10px 14px;font-size:.82rem;max-width:220px;line-height:1.45;color:#1e293b;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:250;opacity:0;transition:opacity .3s;pointer-events:none}
#mascot-speech.show{opacity:1}
#mascot-speech::after{content:'';position:absolute;bottom:-8px;left:20px;border-width:4px;border-style:solid;border-color:#fff transparent transparent}
@media(max-width:480px){
  #lucky-btn{right:12px;bottom:72px;padding:8px 13px;font-size:.74rem}
  #chat-btn{left:auto!important;right:12px!important;bottom:126px!important;padding:8px 12px;font-size:.74rem}
  #mascot-speech{bottom:250px;max-width:170px;font-size:.76rem}
}
</style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
<div class="toast-container" id="toastContainer"></div>
<div class="confetti-container" id="confettiContainer"></div>
<div id="last-call-banner">⏰ ラストオーダーが近づいています。気になる一品は今のうちにどうぞ <button class="lc-close" onclick="event.stopPropagation();closeLastCallBanner()">×</button></div>
<div class="order-celebrate" id="orderCelebrate">
  <div class="celebrate-card">
    <span class="celebrate-mascot" id="celebrateMascot">🐼</span>
    <div class="celebrate-text" id="celebrateText">カートに追加しました！</div>
    <div class="celebrate-sub" id="celebrateSub">ぜひお楽しみください！</div>
  </div>
</div>

<!-- Stamp Card Bar -->
<div class="stamp-card-bar" id="stampBar" style="display:none">
  <span>🎯 スタンプ</span>
  <div class="stamp-dots" id="stampDots"></div>
  <span id="stampLabel">0/5</span>
</div>

<!-- Stamp Reward Overlay -->
<div class="stamp-reward-overlay" id="stampReward">
  <div class="stamp-reward-box">
    <span class="stamp-reward-emoji">🎁</span>
    <div class="stamp-reward-title">スタンプ達成！</div>
    <div class="stamp-reward-sub">5回ご注文ありがとうございます！<br>スタッフにこの画面をお見せください✨</div>
    <button class="stamp-reward-btn" onclick="closeStampReward()">✓ 閉じる</button>
  </div>
</div>

<!-- Staff Arrival Toast -->
<div class="staff-arrival-toast" id="staffArrivalToast">
  <svg class="staff-progress-ring" viewBox="0 0 24 24">
    <circle class="staff-progress-circle" id="staffProgressCircle" cx="12" cy="12" r="9"/>
  </svg>
  <span id="staffArrivalMsg">スタッフが向かっています...</span>
</div>

<button id="chat-btn" type="button" onclick="toggleStaffChat(true)">💬 スタッフ相談 <span class="chat-badge" id="chatBadge">1</span></button>
<button id="lucky-btn" type="button" onclick="spinLuckyRoulette()">🎲 おまかせ</button>
<div id="mascot-speech"></div>
<div id="chat-overlay" onclick="if(event.target===this)toggleStaffChat(false)">
  <div id="chat-box">
    <div id="chat-title">スタッフチャット</div>
    <div id="chat-subtitle">注文前の相談やおすすめ確認にどうぞ</div>
    <div id="chat-messages"><div class="chat-empty">まだメッセージはありません</div></div>
    <div id="chat-input-row">
      <textarea id="chat-input" placeholder="例: おすすめのドリンクはありますか？"></textarea>
      <button id="chat-send" type="button" onclick="sendStaffChat()">送信</button>
    </div>
    <button id="chat-close" type="button" onclick="toggleStaffChat(false)">閉じる</button>
  </div>
</div>
<div id="lucky-reveal" onclick="if(event.target===this)closeLuckyReveal()">
  <div id="lucky-reveal-box">
    <span id="lucky-reveal-emoji">🎉</span>
    <div id="lucky-reveal-label">今日のラッキーセレクト</div>
    <div id="lucky-reveal-name">おすすめメニュー</div>
    <div id="lucky-reveal-price">¥0</div>
    <div class="lucky-btn-row">
      <button class="btn-lucky-add" type="button" onclick="addLuckyPick()">これにする</button>
      <button class="btn-lucky-skip" type="button" onclick="closeLuckyReveal()">また今度</button>
    </div>
  </div>
</div>
<!-- Live2D Widget - Japanese mascot with fetch interceptor -->
<script>
// Intercept fetch to serve Japanese tips instead of CDN default
(function() {
  const origFetch = window.fetch;
  window.fetch = function(url, opts) {
    if (typeof url === 'string' && url.includes('waifu-tips.json')) {
      return origFetch('/waifu-tips.json', opts);
    }
    return origFetch(url, opts);
  };
})();
</script>
<script>
// Custom Live2D loader - works on ALL screen sizes (no mobile block) - v2
(function() {
  const base = "https://fastly.jsdelivr.net/npm/live2d-widgets@0/";
  function loadRes(url, type) {
    return new Promise((resolve, reject) => {
      let tag;
      if (type === "css") {
        tag = document.createElement("link");
        tag.rel = "stylesheet";
        tag.href = url;
      } else {
        tag = document.createElement("script");
        tag.src = url;
      }
      tag.onload = () => resolve(url);
      tag.onerror = () => reject(url);
      document.head.appendChild(tag);
    });
  }
  Promise.all([
    loadRes(base + "waifu.css", "css"),
    loadRes(base + "live2d.min.js", "js"),
    loadRes(base + "waifu-tips.js", "js")
  ]).then(() => {
    localStorage.setItem('modelId', '2');
    localStorage.setItem('modelTexturesId', '0');
    initWidget({
      waifuPath: '/waifu-tips.json',
      cdnPath: "https://fastly.jsdelivr.net/gh/fghrsh/live2d_api/",
      tools: ["switch-model","switch-texture","quit"]
    });
    setTimeout(function() {
      if (typeof loadlive2d === 'function') {
        loadlive2d('live2d', 'https://fastly.jsdelivr.net/gh/fghrsh/live2d_api/model/bilibili-live/22/index.json');
      }
      localStorage.setItem('modelId', '2');
    }, 800);
    setTimeout(function() {
      if (typeof loadlive2d === 'function') {
        loadlive2d('live2d', 'https://fastly.jsdelivr.net/gh/fghrsh/live2d_api/model/bilibili-live/22/index.json');
      }
    }, 2500);
  }).catch(e => console.warn("Live2D load error:", e));
})();
</script>
<script>
// Block widget's own showMessage to prevent unexpected greetings
window.showMessage = function(){};
</script>

<div class="call-staff-overlay" id="callStaffOverlay">
  <div class="call-staff-box">
    <span class="call-staff-emoji">🔔</span>
    <div class="call-staff-title">スタッフを呼ぶ</div>
    <div class="call-staff-msg">何が必要ですか？すぐにスタッフが参ります！</div>
    <div class="call-staff-options">
      <button class="call-option-btn" onclick="selectCallOption(this,'お水')"><span class="call-option-icon">💧</span>お水</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'お皿')"><span class="call-option-icon">🍽️</span>お皿</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'おしぼり')"><span class="call-option-icon">🧻</span>おしぼり</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'お手伝い')"><span class="call-option-icon">❓</span>お手伝い</button>
    </div>
    <button class="btn-call-confirm" onclick="sendCallRequest()">🔔 スタッフを呼ぶ</button>
    <button class="btn-call-close" onclick="closeCallStaff()">キャンセル</button>
  </div>
</div>
<div class="combo-overlay" id="comboOverlay">
  <div class="combo-box">
    <div class="combo-header">
      <div class="combo-header-title">🍱 セットメニューはいかが？</div>
      <div class="combo-header-sub">一緒に注文してお得にしよう！</div>
    </div>
    <div class="combo-body">
      <div class="combo-added-item">
        <div class="combo-added-img" id="comboAddedImg"><span style="font-size:1.5rem">🍽️</span></div>
        <div><div class="combo-added-name" id="comboAddedName">Item</div><div class="combo-added-price" id="comboAddedPrice">¥0</div></div>
      </div>
      <div class="combo-arrow">✨ こちらも一緒にどうぞ...</div>
      <div class="combo-suggestion-label">おすすめの組み合わせ</div>
      <div class="combo-suggestions" id="comboSuggestions"></div>
    </div>
    <div class="combo-footer">
      <button class="btn-combo-skip" onclick="skipCombo()">いいえ、結構です</button>
      <button class="btn-combo-add" id="btnComboAdd" onclick="addComboItem()" disabled>＋ 注文に追加</button>
    </div>
  </div>
</div>
<header class="app-header">
  <div class="header-inner">
    <div class="header-brand">
      <div class="header-logo">🍽️</div>
      <div><div class="header-title">SmartOrder</div><div class="header-table">Table <?php echo (int)$tableNo; ?></div></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <button onclick="openCallStaff()" style="padding:8px 14px;font-size:.78rem;border-radius:999px;background:rgba(245,158,11,.85);border:1px solid rgba(255,255,255,.3);color:#fff;cursor:pointer;font-weight:700;display:flex;align-items:center;gap:5px;transition:background .2s">🔔 スタッフ</button>
      <div class="lang-switcher" id="langSwitcher">
    <button class="lang-btn active" onclick="setLang('ja')" id="langJa">JP</button>
    <button class="lang-btn" onclick="setLang('en')" id="langEn">EN</button>
    <button class="lang-btn" onclick="setLang('zh')" id="langZh">中</button>
  </div>
  <button class="btn-cart-header" onclick="showCart()">🛒 カート <span class="cart-badge" id="cartCountBadge"><?php echo $itemCount; ?></span></button>
    </div>
  </div>
</header>
<div class="search-bar-wrap">
  <div class="search-bar-inner">
    <span class="search-icon">🔍</span>
    <input type="text" class="search-input" id="searchInput" placeholder="メニューを検索... (例：寿司、ビール、デザート)" oninput="handleSearch(this.value)">
    <button class="search-clear" id="searchClear" onclick="clearSearch()">✕</button>
  </div>
</div>
<nav class="category-nav">
  <div class="category-inner">
    <?php foreach($categories as $cat): ?>
    <button class="cat-btn" onclick="filterCategory('<?php echo (int)$cat['id']; ?>',this)"><?php echo htmlspecialchars($cat['icon'].' '.$cat['categoryName']); ?></button>
    <?php endforeach; ?>
  </div>
</nav>
<div class="app-layout">
  <section class="menu-section">
    <div class="menu-section-title" id="sectionTitle">全メニュー</div>
    <div id="wait-time-indicator">
      <span class="wt-icon">⏱️</span>
      <div>
        <div id="waitTimeMain">ただいまの目安: 8〜12分</div>
        <div class="wt-detail" id="waitTimeDetail">ご注文数に応じて更新されます</div>
      </div>
    </div>
    <div class="menu-grid" id="menuGrid">
      <?php foreach($items as $item):
        $imgUrl = getItemImage($item['id']);
        $iname  = $item['name'];
        $badge=''; $badgeClass='';
        if(strpos($iname,'おすすめ')!==false){$badge='⭐ おすすめ';$badgeClass='badge-recommended';}
        elseif(strpos($iname,'期間限定')!==false){$badge='⏳ 期間限定';$badgeClass='badge-limited';}
        elseif($item['price']==0){$badge='🎁 無料';$badgeClass='badge-free';}
      ?>
      <div class="menu-card" data-item-id="<?php echo (int)$item['id']; ?>" data-item-name="<?php echo htmlspecialchars($iname); ?>" data-item-price="<?php echo (int)$item['price']; ?>" data-item-img="<?php echo $imgUrl?htmlspecialchars($imgUrl):''; ?>" data-category="<?php echo (int)$item['category']; ?>" data-name="<?php echo htmlspecialchars(mb_strtolower($iname,'UTF-8')); ?>"
           onclick="openOrderModal(<?php echo (int)$item['id']; ?>,'<?php echo addslashes(htmlspecialchars($iname)); ?>',<?php echo (int)$item['price']; ?>,'<?php echo $imgUrl?htmlspecialchars($imgUrl):''; ?>')">
        <button class="fav-btn" id="fav_<?php echo (int)$item['id']; ?>" onclick="event.stopPropagation();toggleFav(<?php echo (int)$item['id']; ?>,this)" title="お気に入り">🤍</button>
        <?php if($badge): ?><div class="item-badge <?php echo $badgeClass; ?>"><?php echo $badge; ?></div><?php endif; ?>
        <div class="item-img-wrap">
          <?php if($imgUrl): ?><img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($iname); ?>" loading="lazy">
          <?php else: ?><span class="placeholder-icon">🍽️</span><?php endif; ?>
        </div>
        <div class="item-info">
          <div class="item-name"><?php echo htmlspecialchars($iname); ?></div>
          <div class="item-price">¥<?php echo number_format($item['price']); ?></div>
        </div>
        <button class="item-add-btn">＋ カートに追加</button>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="search-no-results" id="searchNoResults"><div style="font-size:3rem;margin-bottom:12px">🔍</div><div>見つかりませんでした。<br><small>別のキーワードで試してみてください！</small></div></div>
  </section>
  <aside class="cart-sidebar">
    <div class="cart-box">
      <div class="cart-header-bar">🛒 カート</div>
      <div class="cart-items" id="cartItemsDesktop">
        <?php if(empty($cartItems)): ?>
        <div class="cart-empty"><div class="cart-empty-icon">🛒</div>まだ何も入っていません<br><small>メニューをタップして注文しましょう！</small></div>
        <?php else: foreach($cartItems as $ci): $ciImg=getItemImage($ci['itemId']); ?>
        <div class="cart-item" id="cartItem_<?php echo (int)$ci['orderId']; ?>">
          <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?></div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
            <div class="cart-item-price">¥<?php echo number_format($ci['price']); ?> × <?php echo (int)$ci['amount']; ?></div>
          </div>
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">−</button>
            <span class="qty-num" id="qty_<?php echo (int)$ci['orderId']; ?>"><?php echo (int)$ci['amount']; ?></span>
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">＋</button>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
      <div class="cart-footer">
        <div class="cart-total"><span>合計</span><span class="cart-total-price" id="totalPrice">¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" id="checkoutBtn" <?php echo empty($cartItems)?'disabled':''; ?> onclick="confirmCheckout()">✓ 注文を確定する</button>
        <button class="btn-call-staff" onclick="openCallStaff()">🔔 スタッフを呼ぶ</button>
      </div>
    </div>
  </aside>
</div>
<button class="float-cart" onclick="showCart()">🛒 カート <span class="cart-badge" id="floatCartCount"><?php echo $itemCount; ?></span></button>
<div class="modal-overlay" id="orderModal">
  <div class="modal-box">
    <div class="modal-header-bar" id="modalTitle">Order Item</div>
    <div class="modal-body-inner">
      <div class="modal-item-img" id="modalImgWrap"></div>
      <div class="modal-item-price" id="modalPrice">¥0</div>
      <div class="modal-qty-control">
        <button class="modal-qty-btn" onclick="changeModalQty(-1)">−</button>
        <span class="modal-qty-num" id="modalQty">1</span>
        <button class="modal-qty-btn" onclick="changeModalQty(1)">＋</button>
      </div>
      <textarea class="modal-notes" id="modalNotes" placeholder="特別なリクエスト、アレルギーなど..." rows="2"></textarea>
    </div>
    <div class="modal-footer-row">
      <button class="btn-cancel-modal" onclick="closeModal('orderModal')">キャンセル</button>
      <button class="btn-order-modal" onclick="executeOrder()">カートに追加</button>
    </div>
  </div>
</div>
<div class="cart-modal-overlay" id="cartModalOverlay" onclick="closeCartModal()">
  <div class="cart-modal" onclick="event.stopPropagation()">
    <div class="cart-modal-title">🛒 ショッピングカート</div>
    <div id="cartItemsMobile">
      <?php if(empty($cartItems)): ?>
      <div class="cart-empty"><div class="cart-empty-icon">🛒</div>まだ何も入っていません</div>
      <?php else: foreach($cartItems as $ci): $ciImg=getItemImage($ci['itemId']); ?>
      <div class="cart-item">
        <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?></div>
        <div class="cart-item-info">
          <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
          <div class="cart-item-price">¥<?php echo number_format($ci['price']); ?></div>
        </div>
        <div class="cart-item-qty">
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">−</button>
          <span class="qty-num"><?php echo (int)$ci['amount']; ?></span>
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">＋</button>
        </div>
      </div>
      <?php endforeach; endif; ?>
      <div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">
        <div class="cart-total"><span>合計</span><span class="cart-total-price">¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" <?php echo empty($cartItems)?'disabled':''; ?> onclick="closeCartModal();confirmCheckout()">✓ 注文を確定する</button>
        <button class="btn-call-staff" onclick="closeCartModal();openCallStaff()">🔔 スタッフを呼ぶ</button>
      </div>
    </div>
  </div>
</div>
<div class="confirm-overlay" id="confirmModal">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirmIcon">⚠️</div>
    <div class="confirm-title" id="confirmTitle">Confirm</div>
    <div class="confirm-message" id="confirmMessage"></div>
    <div class="confirm-buttons">
      <button class="btn-confirm-cancel" onclick="closeModal('confirmModal')">キャンセル</button>
      <button class="btn-confirm-ok" onclick="confirmAction()">確認</button>
    </div>
  </div>
</div>
<script>
const TABLE_NO = <?php echo (int)$tableNo; ?>;
let selectedItemId=null,selectedItemName='',selectedItemPrice=0,selectedItemImg='',modalQty=1,pendingAction=null;
let selectedCallOption='',selectedComboId=null,selectedComboName='',selectedComboPrice=0,selectedComboImg='';
let _pendingOrderAfterCombo=null;

const ALL_ITEMS = <?php
  $jsItems=[];
  foreach($items as $it){
    $img=getItemImage($it['id']);
    $jsItems[]=['id'=>(int)$it['id'],'name'=>$it['name'],'price'=>(int)$it['price'],'category'=>(int)$it['category'],'img'=>$img??''];
  }
  echo json_encode($jsItems,JSON_UNESCAPED_UNICODE);
?>;

/* ── MASCOT (Live2D widget auto-initialises via CDN) ── */
// Override Live2D widget tips to react to our app events
function mascotReact(type){
  const msgs={
    add:["ご選択ありがとうございます！","こちら人気メニューでございます！","素晴らしいお選びですね！","ぜひお楽しみください！","おすすめでございます！"], checkout:["ご注文ありがとうございます！ごゆっくりどうぞ♪","ありがとうございます！すぐにお持ちします！","ご注文確かに承りました！"], search:["何かお探しでしょうか？","お好みのものは見つかりましたか？","他にもおすすめがございます！"], staff:["スタッフがすぐに参ります！","少々お待ちください！","ただいまお呼びしております！"] }; const arr=msgs[type]||msgs.add;
  const msg=arr[Math.floor(Math.random()*arr.length)];
  // Try to use Live2D widget tip system if available, fallback to toast
  showToast('🌸 '+msg,'success');
}
/* ── SEARCH ── */
function handleSearch(val){
  const q=val.trim().toLowerCase();
  document.getElementById('searchClear').classList.toggle('visible',q.length>0);
  let vis=0;
  document.querySelectorAll('.menu-card').forEach(c=>{
    const show=!q||c.dataset.name.includes(q);
    c.style.display=show?'':'none';
    if(show)vis++;
  });
  document.getElementById('searchNoResults').classList.toggle('show',q.length>0&&vis===0);
  if(q.length>1)mascotReact('search');
}
function clearSearch(){document.getElementById('searchInput').value='';handleSearch('');}

/* ── CALL STAFF ── */
function openCallStaff(){
  selectedCallOption='';
  document.querySelectorAll('.call-option-btn').forEach(b=>b.classList.remove('selected'));
  document.getElementById('callStaffOverlay').classList.add('show');
}
function closeCallStaff(){document.getElementById('callStaffOverlay').classList.remove('show');}
function selectCallOption(btn,label){
  document.querySelectorAll('.call-option-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');selectedCallOption=label;
}
function sendCallRequest(){
  const req=selectedCallOption||'Assistance';
  closeCallStaff();
  showToast('🔔 ' + req + ' のご依頼を承りました！', 'success');
  mascotReact('staff');
  showStaffArrival();
}

/* ── COMBO ── */
function getSuggestions(item){
  const n=item.name;
  let pool=[];
  const isDrink=n.includes('ビール')||n.includes('サワー')||n.includes('ハイボール')||n.includes('ドリンク')||n.includes('飲み放題');
  if(!isDrink){
    pool=ALL_ITEMS.filter(i=>i.id!==item.id&&(i.name.includes('ビール')||i.name.includes('サワー')||i.name.includes('ハイボール')));
  }
  if(pool.length===0){
    pool=ALL_ITEMS.filter(i=>i.id!==item.id&&(i.name.includes('デザート')||i.name.includes('ワッフル')||i.name.includes('パンケーキ')||i.name.includes('アイス')||i.name.includes('枝豆')));
  }
  if(pool.length===0)pool=ALL_ITEMS.filter(i=>i.id!==item.id).slice(0,4);
  for(let i=pool.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[pool[i],pool[j]]=[pool[j],pool[i]];}
  return pool.slice(0,4);
}
function showCombo(addedItem,suggestions){
  const imgW=document.getElementById('comboAddedImg');
  imgW.innerHTML=addedItem.img?'<img src="'+addedItem.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.5rem">🍽️</span>';
  document.getElementById('comboAddedName').textContent=addedItem.name;
  document.getElementById('comboAddedPrice').textContent='¥'+addedItem.price.toLocaleString();
  const grid=document.getElementById('comboSuggestions');
  grid.innerHTML='';selectedComboId=null;
  document.getElementById('btnComboAdd').disabled=true;
  suggestions.forEach(s=>{
    const d=document.createElement('div');
    d.className='combo-suggest-item';
    d.innerHTML='<div class="combo-suggest-img">'+(s.img?'<img src="'+s.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.3rem">🍽️</span>')+'</div><div class="combo-suggest-name">'+s.name+'</div><div class="combo-suggest-price">¥'+s.price.toLocaleString()+'</div>';
    d.onclick=()=>{
      grid.querySelectorAll('.combo-suggest-item').forEach(el=>el.classList.remove('selected'));
      d.classList.add('selected');
      selectedComboId=s.id;selectedComboName=s.name;selectedComboPrice=s.price;selectedComboImg=s.img;
      document.getElementById('btnComboAdd').disabled=false;
    };
    grid.appendChild(d);
  });
  document.getElementById('comboOverlay').classList.add('show');
}
async function addComboItem(){
  if(!selectedComboId)return;
  document.getElementById('comboOverlay').classList.remove('show');
  showLoading(true);
  try{
    const fd=new FormData();fd.append('tableNo',TABLE_NO);fd.append('itemId',selectedComboId);fd.append('amount',1);
    const res=await fetch('logic.php',{method:'POST',body:fd});
    if(res.ok){showLoading(false);showCelebration(selectedComboName,true);refreshCart();addStamp();}
  }catch(e){showToast('申し訳ございません。追加できませんでした。','error');}
  finally{showLoading(false);}
}
function skipCombo(){
  document.getElementById('comboOverlay').classList.remove('show');
  if(_pendingOrderAfterCombo){
    showCelebration(_pendingOrderAfterCombo.name,false);
    _pendingOrderAfterCombo=null;
    refreshCart();
  }
}

/* ── CELEBRATION ── */
function showCelebration(name,isCombo){
  const cel=document.getElementById('orderCelebrate');
  const mascots=['🐼','🍣','🎉','🌟','🥳','👨‍🍳','🍱'];
  document.getElementById('celebrateMascot').textContent=mascots[Math.floor(Math.random()*mascots.length)];
  document.getElementById('celebrateText').textContent=isCombo?'セット追加完了！🎊':name+'を追加しました！🎉';
  document.getElementById('celebrateSub').textContent=isCombo?'素敵な組み合わせですね♪':['美味しそうでございます！','素晴らしいお選びです！','ありがとうございます！','ぜひお楽しみください！'][Math.floor(Math.random()*4)];
  cel.classList.add('show');
  launchConfetti();mascotReact('add');
  setTimeout(()=>cel.classList.remove('show'),1800);
}
function launchConfetti(){
  const c=document.getElementById('confettiContainer');c.innerHTML='';
  const cols=['#0f766e','#14b8a6','#f59e0b','#ef4444','#8b5cf6','#10b981','#fbbf24'];
  for(let i=0;i<48;i++){
    const p=document.createElement('div');p.className='confetti-piece';
    p.style.cssText='left:'+Math.random()*100+'vw;background:'+cols[Math.floor(Math.random()*cols.length)]+';width:'+(6+Math.random()*8)+'px;height:'+(6+Math.random()*8)+'px;animation-duration:'+(1+Math.random()*1.5)+'s;animation-delay:'+(Math.random()*0.4)+'s;border-radius:'+(Math.random()>.5?'50%':'2px');
    c.appendChild(p);
  }
  setTimeout(()=>{c.innerHTML='';},2500);
}

/* ── CORE ── */
function filterCategory(catId,btn){
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');clearSearch();
  document.querySelectorAll('.menu-card').forEach(card=>{card.style.display=(catId==='all'||card.dataset.category===catId)?'':'none';});
  document.getElementById('sectionTitle').textContent=catId==='all'?'全メニュー':btn.textContent+' メニュー';
}
function openOrderModal(itemId,itemName,itemPrice,imgUrl){
  selectedItemId=itemId;selectedItemName=itemName;selectedItemPrice=itemPrice;selectedItemImg=imgUrl;modalQty=1;
  document.getElementById('modalTitle').textContent=itemName;
  document.getElementById('modalPrice').textContent='¥'+itemPrice.toLocaleString();
  document.getElementById('modalQty').textContent='1';
  document.getElementById('modalNotes').value='';
  document.getElementById('modalImgWrap').innerHTML=imgUrl?'<img src="'+imgUrl+'" alt="'+itemName+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:3.5rem">🍽️</span>';
  document.getElementById('orderModal').classList.add('show');
}
function changeModalQty(delta){
  modalQty=Math.max(1,Math.min(99,modalQty+delta));
  document.getElementById('modalQty').textContent=modalQty;
  document.getElementById('modalPrice').textContent='¥'+(selectedItemPrice*modalQty).toLocaleString();
}
function closeModal(id){document.getElementById(id).classList.remove('show');}
async function executeOrder(){
  if(!selectedItemId)return;
  const notes=document.getElementById('modalNotes').value;
  closeModal('orderModal');showLoading(true);
  try{
    const fd=new FormData();fd.append('tableNo',TABLE_NO);fd.append('itemId',selectedItemId);fd.append('amount',modalQty);if(notes)fd.append('item_notes',notes);
    const res=await fetch('logic.php',{method:'POST',body:fd});
    if(res.ok){
      showLoading(false);
      const addedItem=ALL_ITEMS.find(i=>i.id===selectedItemId)||{id:selectedItemId,name:selectedItemName,price:selectedItemPrice,img:selectedItemImg};
      const sugg=getSuggestions(addedItem);
      _pendingOrderAfterCombo={name:selectedItemName,img:selectedItemImg};
      if(sugg.length>0){showCombo(addedItem,sugg);}
      else{showCelebration(selectedItemName,false);refreshCart();addStamp();}
    }else throw new Error('failed');
  }catch(e){showToast('申し訳ございません。もう一度お試しください。','error');showLoading(false);}
}
async function updateQuantity(orderId,delta){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('orderId',orderId);fd.append('change',delta);
    const res=await fetch('cart_update.php',{method:'POST',body:fd});
    if(res.ok)refreshCart();
  }catch(e){showToast('申し訳ございません。更新できませんでした。','error');}
  finally{showLoading(false);}
}
function confirmCheckout(){
  document.getElementById('confirmIcon').textContent='✅';
  document.getElementById('confirmTitle').textContent='ご注文の確認';
  document.getElementById('confirmMessage').textContent='ご注文を確定しますか？';
  pendingAction=executeCheckout;
  document.getElementById('confirmModal').classList.add('show');
}
function confirmAction(){closeModal('confirmModal');if(pendingAction){pendingAction();pendingAction=null;}}
async function executeCheckout(){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('tableNo',TABLE_NO);
    const res=await fetch('checkout.php',{method:'POST',body:fd});
    if(res.ok){mascotReact('checkout');launchConfetti();showToast('ご注文ありがとうございます！少々お待ちください🎉','success');refreshCart();}
    else throw new Error('failed');
  }catch(e){showToast('申し訳ございません。もう一度お試しください。','error');}
  finally{showLoading(false);}
}
function showCart(){document.getElementById('cartModalOverlay').classList.add('show');document.body.style.overflow='hidden';}
function closeCartModal(){document.getElementById('cartModalOverlay').classList.remove('show');document.body.style.overflow='';}
function showLoading(show){document.getElementById('loadingOverlay').classList.toggle('show',show);}
function showToast(msg,type=''){
  const c=document.getElementById('toastContainer');const t=document.createElement('div');
  t.className='toast'+(type?' '+type:'');t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),3200);
}
/* ── AJAX CART REFRESH (no page reload) ── */
async function refreshCart(){
  try{
    const res = await fetch('cart_get.php?tableNo='+TABLE_NO);
    if(!res.ok) return;
    const data = await res.json();
    
    // Update cart count badges
    const count = data.itemCount || 0;
    document.querySelectorAll('#cartCountBadge,#floatCartCount').forEach(el=>el.textContent=count);
    
    // Update total price
    const totalEl = document.getElementById('totalPrice');
    if(totalEl) totalEl.textContent = '¥' + (data.total||0).toLocaleString();
    
    // Update checkout button state
    const checkoutBtn = document.getElementById('checkoutBtn');
    if(checkoutBtn) checkoutBtn.disabled = count === 0;
    
    // Rebuild desktop cart items
    const cartDesktop = document.getElementById('cartItemsDesktop');
    const cartMobile = document.getElementById('cartItemsMobile');
    if(cartDesktop) cartDesktop.innerHTML = buildCartHTML(data.items, false);
    if(cartMobile) {
      const mobileContent = document.getElementById('cartItemsMobile');
      if(mobileContent){
        // Keep the footer buttons, only update items
        const items = data.items || [];
        let html = items.length === 0 
          ? '<div class="cart-empty"><div class="cart-empty-icon">🛒</div>まだ何も入っていません</div>'
          : items.map(ci => buildCartItemHTML(ci)).join('');
        // Replace only the items part (before the footer div)
        const existing = mobileContent.innerHTML;
        const footerIdx = existing.indexOf('<div style="margin-top');
        const footer = footerIdx >= 0 ? existing.substring(footerIdx) : '';
        mobileContent.innerHTML = html + (footer || buildMobileFooter(data.total, count));
      }
    }
    
    // Update mobile cart footer total
    const mobileTotals = document.querySelectorAll('.cart-modal .cart-total-price');
    mobileTotals.forEach(el => el.textContent = '¥' + (data.total||0).toLocaleString());
    const mobileCheckout = document.querySelector('.cart-modal .btn-checkout');
    if(mobileCheckout) mobileCheckout.disabled = count === 0;
    
  }catch(e){ console.warn('refreshCart error:', e); }
}

function buildCartItemHTML(ci){
  const img = ci.img ? '<img src="'+ci.img+'" alt="" style="width:100%;height:100%;object-fit:cover">' : '🍽️';
  return '<div class="cart-item" id="cartItem_'+ci.orderId+'">'
    +'<div class="cart-item-thumb">'+img+'</div>'
    +'<div class="cart-item-info">'
    +'<div class="cart-item-name">'+ci.name+'</div>'
    +'<div class="cart-item-price">¥'+Number(ci.price).toLocaleString()+' × '+ci.amount+'</div>'
    +'</div>'
    +'<div class="cart-item-qty">'
    +'<button class="qty-btn" onclick="event.stopPropagation();updateQuantity('+ci.orderId+',-1)">−</button>'
    +'<span class="qty-num" id="qty_'+ci.orderId+'">'+ci.amount+'</span>'
    +'<button class="qty-btn" onclick="event.stopPropagation();updateQuantity('+ci.orderId+',1)">＋</button>'
    +'</div></div>';
}

function buildCartHTML(items, isMobile){
  if(!items || items.length === 0){
    return '<div class="cart-empty"><div class="cart-empty-icon">🛒</div>まだ何も入っていません<br><small>メニューをタップして注文しましょう！</small></div>';
  }
  return items.map(ci => buildCartItemHTML(ci)).join('');
}

function buildMobileFooter(total, count){
  return '<div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">'
    +'<div class="cart-total"><span>合計</span><span class="cart-total-price">¥'+(total||0).toLocaleString()+'</span></div>'
    +'<button class="btn-checkout" '+(count===0?'disabled':'')
    +' onclick="closeCartModal();confirmCheckout()">✓ 注文を確定する</button>'
    +'<button class="btn-call-staff" onclick="closeCartModal();openCallStaff()">🔔 スタッフを呼ぶ</button>'
    +'</div>';
}

/* ── MASCOT RESPONSIVE RESIZE ── */
function resizeMascot(){
  const waifu=document.getElementById('waifu');
  if(!waifu)return;
  const w=window.innerWidth;
  let size;
  if(w<=480)size=200;
  else if(w<=900)size=220;
  else size=280;
  waifu.style.setProperty('width',size+'px','important');
  waifu.style.setProperty('height',size+'px','important');
  waifu.style.setProperty('bottom','0px','important');
  waifu.style.setProperty('display','block','important');
  const canvas=waifu.querySelector('canvas');
  if(canvas){canvas.style.setProperty('width',size+'px','important');canvas.style.setProperty('height',size+'px','important');}
}
// Apply on load and resize
window.addEventListener('resize', resizeMascot);
// Apply after widget loads - more retries for slow mobile networks
[500, 1000, 2000, 3000, 5000, 8000].forEach(t => setTimeout(resizeMascot, t));


/* ── FAVORITES ── */
function initFavs(){
  const favs = JSON.parse(localStorage.getItem('smartorder_favs')||'[]');
  favs.forEach(id => {
    const btn = document.getElementById('fav_'+id);
    if(btn){ btn.textContent='❤️'; btn.classList.add('active'); }
  });
}
function toggleFav(itemId, btn){
  let favs = JSON.parse(localStorage.getItem('smartorder_favs')||'[]');
  const idx = favs.indexOf(itemId);
  if(idx > -1){
    favs.splice(idx,1);
    btn.textContent='🤍'; btn.classList.remove('active');
    showToast('お気に入りから外しました','');
  } else {
    favs.push(itemId);
    btn.textContent='❤️'; btn.classList.add('active');
    btn.classList.add('pop');
    setTimeout(()=>btn.classList.remove('pop'),400);
    showToast('❤️ お気に入りに追加しました！','success');
    const msg = 'こちらはお気に入りに追加されましたね！素敵なお選びです！';
  }
  localStorage.setItem('smartorder_favs', JSON.stringify(favs));
}

/* ── STAMP CARD ── */
let stampCount = parseInt(localStorage.getItem('smartorder_stamps')||'0');
function initStamps(){
  const bar = document.getElementById('stampBar');
  if(!bar) return;
  if(stampCount > 0) bar.style.display='flex';
  renderStamps(false);
}
function renderStamps(isNew){
  const dots = document.getElementById('stampDots');
  const label = document.getElementById('stampLabel');
  if(!dots||!label) return;
  const total = 5;
  const shown = Math.min(stampCount, total);
  dots.innerHTML = '';
  for(let i=0;i<total;i++){
    const d = document.createElement('div');
    d.className = 'stamp-dot' + (i < shown ? ' filled'+(isNew&&i===shown-1?' new':'') : '');
    d.textContent = i < shown ? '⭐' : '';
    dots.appendChild(d);
  }
  label.textContent = shown + '/' + total;
}
function addStamp(){
  stampCount++;
  localStorage.setItem('smartorder_stamps', stampCount);
  const bar = document.getElementById('stampBar');
  if(bar) bar.style.display='flex';
  renderStamps(true);
  if(stampCount > 0 && stampCount % 5 === 0){
    setTimeout(()=>{
      document.getElementById('stampReward').classList.add('show');
    }, 1200);
  }
}
function closeStampReward(){
  document.getElementById('stampReward').classList.remove('show');
  // Reset stamps after reward
  stampCount = 0;
  localStorage.setItem('smartorder_stamps', '0');
  renderStamps(false);
  document.getElementById('stampBar').style.display='none';
}

/* ── STAFF ARRIVAL COUNTDOWN ── */
let _staffTimer = null;
function showStaffArrival(){
  const toast = document.getElementById('staffArrivalToast');
  const msg = document.getElementById('staffArrivalMsg');
  const circle = document.getElementById('staffProgressCircle');
  if(!toast) return;
  if(_staffTimer) clearInterval(_staffTimer);
  let secs = 60; // 60 second countdown
  const totalDash = 56.5; // circumference approx 2*pi*9
  toast.classList.add('show');
  circle.style.strokeDashoffset = totalDash;
  msg.textContent = 'スタッフが向かっています... あと ' + secs + '秒';
  _staffTimer = setInterval(()=>{
    secs--;
    const progress = (secs / 60) * totalDash;
    circle.style.strokeDashoffset = progress;
    if(secs <= 0){
      clearInterval(_staffTimer);
      msg.textContent = 'スタッフが到着しました！🙋';
      setTimeout(()=>toast.classList.remove('show'), 3000);
    } else if(secs === 30){
      msg.textContent = 'もうすぐスタッフが参ります！ あと ' + secs + '秒';
    } else {
      msg.textContent = 'スタッフが向かっています... あと ' + secs + '秒';
    }
  }, 1000);
}

/* ── FEATURE BLOCK: Lucky / Chat / Last-Call / History / WaitTime / MascotReact ── */
window.luckyPick = null;
window.staffChatMessages = [];
window.staffChatUnread = 1;

function mascotReact(type){
  const msgs={
    add:["ご注文ありがとうございます。できあがりをお楽しみに。","人気の一品ですね。良いお選びです。","こちら、相性の良いセットもございます。"],
    checkout:["ご注文を承りました。少々お待ちください。","ありがとうございます。順番にご用意いたします。","ただいま厨房へ共有しました。"],
    search:["気になるメニューは見つかりましたか。","お好みがあればおすすめもご案内できます。","迷ったらおまかせボタンもどうぞ。"],
    staff:["スタッフへお声がけしました。","まもなくスタッフが参ります。","ご用件をしっかりお伝えしますね。"],
    lucky:["今日の運試しメニューです。","新しいお気に入りに出会えるかもしれません。","直感で選ぶのも楽しいですよ。"]
  };
  const arr=msgs[type]||msgs.add;
  const msg=arr[Math.floor(Math.random()*arr.length)];
  showMascotSpeech(msg, type==='staff' ? 'wave' : 'jump');
  showToast('🌸 '+msg,'success');
}
function showMascotSpeech(message, motion){
  const bubble=document.getElementById('mascot-speech');
  if(bubble){
    bubble.textContent=message;
    bubble.classList.add('show');
    clearTimeout(window._mascotSpeechTimer);
    window._mascotSpeechTimer=setTimeout(()=>bubble.classList.remove('show'),3200);
  }
  const waifu=document.getElementById('waifu');
  if(waifu){
    waifu.classList.remove('mascot-jumping','mascot-waving');
    void waifu.offsetWidth;
    waifu.classList.add(motion==='wave'?'mascot-waving':'mascot-jumping');
    setTimeout(()=>waifu.classList.remove('mascot-jumping','mascot-waving'),1800);
  }
}

function getItemStats(){
  try{return JSON.parse(localStorage.getItem('smartorder_item_stats')||'{}');}catch(e){return {};}
}
function setItemStats(stats){
  localStorage.setItem('smartorder_item_stats', JSON.stringify(stats));
}
function rememberHistoryItem(itemId){
  const id=String(itemId);
  const history=Array.from(new Set([id].concat(JSON.parse(localStorage.getItem('smartorder_history')||'[]')))).slice(0,12);
  localStorage.setItem('smartorder_history', JSON.stringify(history));
  const stats=getItemStats();
  stats[id]=(stats[id]||0)+1;
  setItemStats(stats);
  applyHistoryBadges();
}
function applyHistoryBadges(){
  const history=JSON.parse(localStorage.getItem('smartorder_history')||'[]');
  const stats=getItemStats();
  const topIds=Object.entries(stats).sort((a,b)=>b[1]-a[1]).slice(0,3).map(([id])=>id);
  const cards=Array.from(document.querySelectorAll('.menu-card'));
  cards.forEach((card, idx)=>{
    const itemId=card.dataset.itemId;
    let corner=card.querySelector('.badge-before-corner');
    if(history.includes(itemId)){
      if(!corner){
        corner=document.createElement('div');
        corner.className='badge-before-corner';
        corner.textContent='以前に注文';
        card.appendChild(corner);
      }
    }else if(corner){
      corner.remove();
    }
    let badge=card.querySelector('.item-badge');
    const shouldHot=topIds.length ? topIds.includes(itemId) : idx < 3;
    if(shouldHot){
      if(!badge){
        badge=document.createElement('div');
        badge.className='item-badge';
        badge.textContent='人気';
        card.appendChild(badge);
      }
      badge.classList.add('badge-hot');
      if(!badge.dataset.originalLabel) badge.dataset.originalLabel=badge.textContent;
      badge.textContent='🔥 人気';
    }else if(badge && badge.classList.contains('badge-hot')){
      badge.classList.remove('badge-hot');
      badge.textContent=badge.dataset.originalLabel || 'おすすめ';
    }
  });
}

function updateWaitTimeIndicator(){
  const count=parseInt((document.getElementById('cartCountBadge')||{}).textContent||'0',10)||0;
  const hour=(new Date()).getHours();
  let min=8+count*2;
  let max=12+count*3;
  if(hour>=19){min+=4;max+=6;}
  const main=document.getElementById('waitTimeMain');
  const detail=document.getElementById('waitTimeDetail');
  if(main) main.textContent='ただいまの目安: '+min+'〜'+max+'分';
  if(detail) detail.textContent=count>0 ? 'カート '+count+'点。混雑状況を反映しています' : '少なめのご注文なら比較的スムーズです';
}

function closeLastCallBanner(){
  const banner=document.getElementById('last-call-banner');
  if(banner) banner.classList.remove('show');
  localStorage.setItem('smartorder_last_call_closed','1');
}
function maybeShowLastCallBanner(force){
  const banner=document.getElementById('last-call-banner');
  if(!banner) return;
  const hour=(new Date()).getHours();
  if(force || hour>=21 || ((parseInt((document.getElementById('cartCountBadge')||{}).textContent||'0',10)||0)>=3)){
    if(localStorage.getItem('smartorder_last_call_closed')!=='1'){
      banner.classList.add('show');
    }
  }
}

function getVisibleMenuCards(){
  return Array.from(document.querySelectorAll('.menu-card')).filter(card=>card.style.display!=='none');
}
function spinLuckyRoulette(){
  const btn=document.getElementById('lucky-btn');
  const cards=getVisibleMenuCards();
  if(!cards.length){
    showToast('表示中のメニューがありません。検索をクリアしてお試しください。','error');
    return;
  }
  btn.classList.add('spinning');
  const card=cards[Math.floor(Math.random()*cards.length)];
  const itemId=parseInt(card.dataset.itemId||'0',10);
  window.luckyPick=ALL_ITEMS.find(i=>i.id===itemId)||{
    id:itemId,
    name:card.dataset.itemName||'おすすめメニュー',
    price:parseInt(card.dataset.itemPrice||'0',10),
    img:card.dataset.itemImg||''
  };
  setTimeout(()=>{
    btn.classList.remove('spinning');
    document.getElementById('lucky-reveal-name').textContent=window.luckyPick.name;
    document.getElementById('lucky-reveal-price').textContent='¥'+Number(window.luckyPick.price||0).toLocaleString();
    document.getElementById('lucky-reveal-emoji').textContent=['🎯','🎲','✨','🍀'][Math.floor(Math.random()*4)];
    document.getElementById('lucky-reveal').classList.add('show');
    mascotReact('lucky');
  },600);
}
function closeLuckyReveal(){
  const el=document.getElementById('lucky-reveal');
  if(el) el.classList.remove('show');
}
async function addLuckyPick(){
  if(!window.luckyPick) return;
  closeLuckyReveal();
  showLoading(true);
  try{
    const fd=new FormData();
    fd.append('tableNo',TABLE_NO);
    fd.append('itemId',window.luckyPick.id);
    fd.append('amount',1);
    const res=await fetch('logic.php',{method:'POST',body:fd});
    if(!res.ok) throw new Error('failed');
    rememberHistoryItem(window.luckyPick.id);
    refreshCart();
    addStamp();
    showCelebration(window.luckyPick.name,false);
  }catch(e){
    showToast('申し訳ございません。追加できませんでした。','error');
  }finally{
    showLoading(false);
  }
}

function updateChatBadge(){
  const badge=document.getElementById('chatBadge');
  if(!badge) return;
  badge.textContent=String(window.staffChatUnread);
  badge.classList.toggle('show', window.staffChatUnread>0);
}
function renderStaffChat(){
  const box=document.getElementById('chat-messages');
  if(!box) return;
  if(!window.staffChatMessages.length){
    box.innerHTML='<div class="chat-empty">まだメッセージはありません</div>';
    return;
  }
  box.innerHTML=window.staffChatMessages.map(msg=>{
    if(msg.type==='typing') return '<div class="chat-msg received"><span class="typing-dots">●●●</span></div>';
    return '<div class="chat-msg '+msg.type+'">'+msg.text+(msg.time?'<div class="chat-msg-time">'+msg.time+'</div>':'')+'</div>';
  }).join('');
  box.scrollTop=box.scrollHeight;
}
function addStaffSystemMessage(text, unread){
  window.staffChatMessages.push({type:'system',text:text,time:new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})});
  if(unread) window.staffChatUnread++;
  renderStaffChat();
  updateChatBadge();
}

/* Language Switcher Logic */
let currentLang = localStorage.getItem('smartorder_lang') || 'ja';
const menuTranslCache = {};
function setLang(lang) {
  currentLang = lang;
  localStorage.setItem('smartorder_lang', lang);
  ['ja','en','zh'].forEach(l => {
    const id = 'lang' + l.charAt(0).toUpperCase() + l.slice(1);
    const btn = document.getElementById(id);
    if (btn) btn.classList.toggle('active', l === lang);
  });
  applyMenuTranslation(lang);
  updateChatLangHint(lang);
}
function updateChatLangHint(lang) {
  const title = document.getElementById('chat-title');
  const subtitle = document.getElementById('chat-subtitle');
  const hints = {
    ja: {title:'スタッフチャット', sub:'注文前の相談やおすすめ確認にどうぞ'},
    en: {title:'Staff Chat', sub:'Ask about menu, recommendations & more'},
    zh: {title:'服务员聊天', sub:'询问菜单推荐，随时为您服务'}
  };
  if (title && hints[lang]) title.textContent = hints[lang].title;
  if (subtitle && hints[lang]) subtitle.textContent = hints[lang].sub;
}
async function applyMenuTranslation(lang) {
  if (lang === 'ja') {
    document.querySelectorAll('.menu-card').forEach(card => {
      const nameEl = card.querySelector('.item-name');
      const origName = card.dataset.name || card.dataset.itemName;
      if (nameEl && origName) nameEl.textContent = origName;
    });
    return;
  }
  if (menuTranslCache[lang]) { applyTranslToDOM(menuTranslCache[lang]); return; }
  const items = Array.from(document.querySelectorAll('.menu-card')).map(c => ({
    id: c.dataset.itemId, name: c.dataset.name || c.dataset.itemName
  })).filter(i => i.id && i.name);
  if (!items.length) return;
  try {
    const resp = await fetch('/translate_menu.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({items, lang})
    });
    if (!resp.ok) return;
    const data = await resp.json();
    if (data.translations) { menuTranslCache[lang] = data.translations; applyTranslToDOM(data.translations); }
  } catch(e) { console.error('Translation error:', e); }
}
function applyTranslToDOM(translations) {
  document.querySelectorAll('.menu-card').forEach(card => {
    const id = card.dataset.itemId;
    const nameEl = card.querySelector('.item-name');
    if (nameEl && translations[id]) nameEl.textContent = translations[id].name || nameEl.textContent;
  });
}
document.addEventListener('DOMContentLoaded', () => {
  const saved = localStorage.getItem('smartorder_lang') || 'ja';
  if (saved !== 'ja') setTimeout(() => setLang(saved), 800);
});
function toggleStaffChat(show){
  const overlay=document.getElementById('chat-overlay');
  if(!overlay) return;
  overlay.classList.toggle('show', !!show);
  if(show){
    window.staffChatUnread=0;
    updateChatBadge();
    renderStaffChat();
  if(show && window._requestNotifPermission) window._requestNotifPermission();
    const input=document.getElementById('chat-input');
    if(input) input.focus();
  }
}
function sendStaffChat(){
  const input=document.getElementById('chat-input');
  if(!input) return;
  const text=input.value.trim();
  if(!text) return;
  input.value='';
  const stamp=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
  window.staffChatMessages.push({type:'sent',text:text,time:stamp});
  renderStaffChat();
  
  // Show typing indicator
  window.staffChatMessages.push({type:'typing',text:'...',time:''});
  renderStaffChat();
  
  // Get tableNo from URL
  const tableNo = new URLSearchParams(location.search).get('tableNo') || '1';
  
  // History for context (last 6 messages)
  const history = window.staffChatMessages.filter(m=>m.type==='sent'||m.type==='received').slice(-6).map(m=>({role:m.type,text:m.text}));
  
  // Call Gemini AI API
  fetch('gemini_chat.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({message:text, tableNo:parseInt(tableNo), history:history, lang:currentLang})
  })
  .then(r=>r.json())
  .then(data=>{
    // Remove typing indicator
    window.staffChatMessages = window.staffChatMessages.filter(m=>m.type!=='typing');
    const reply = data.reply || 'ありがとうございます。スタッフが対応いたします。';
    const stamp2=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    window.staffChatMessages.push({type:'received',text:reply,time:stamp2});
    window.staffChatUnread++;
    renderStaffChat();
    updateChatBadge();
  })
  .catch(()=>{
    window.staffChatMessages = window.staffChatMessages.filter(m=>m.type!=='typing');
    const stamp2=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    window.staffChatMessages.push({type:'received',text:'申し訳ございません。ただいま接続中です。スタッフを呼んでください。',time:stamp2});
    renderStaffChat();
  });
}

const _origRefreshCart = refreshCart;
refreshCart = async function(){
  await _origRefreshCart();
  updateWaitTimeIndicator();
  applyHistoryBadges();
  maybeShowLastCallBanner(false);
};
const _origExecuteOrder = executeOrder;
executeOrder = async function(){
  const id=selectedItemId;
  await _origExecuteOrder();
  if(id) rememberHistoryItem(id);
};
const _origAddComboItem = addComboItem;
addComboItem = async function(){
  const id=selectedComboId;
  await _origAddComboItem();
  if(id) rememberHistoryItem(id);
};
const _origSendCallRequest = sendCallRequest;
sendCallRequest = function(){
  _origSendCallRequest();
  addStaffSystemMessage('スタッフをお呼びしました。内容を確認して向かいます。', true);
};
const _origFilterCategory = filterCategory;
filterCategory = function(catId, btn){
  _origFilterCategory(catId, btn);
  applyHistoryBadges();
};
const _origClearSearch = clearSearch;
clearSearch = function(){
  _origClearSearch();
  applyHistoryBadges();
};
const _origHandleSearch = handleSearch;
handleSearch = function(val){
  _origHandleSearch(val);
  applyHistoryBadges();
};

// Init on load
window.addEventListener('DOMContentLoaded', ()=>{ initFavs(); initStamps(); });
setTimeout(()=>{ initFavs(); initStamps();
  const _fc=document.querySelector('.cat-btn'); if(_fc)_fc.click();
}, 500);

window.addEventListener('DOMContentLoaded', ()=>{
  applyHistoryBadges();
  updateWaitTimeIndicator();
  maybeShowLastCallBanner(false);
  updateChatBadge();
  if(window.staffChatMessages.length===0){
    addStaffSystemMessage('ご相談があればこちらからどうぞ。おすすめやアレルギー確認も承ります。', false);
  }
  const input=document.getElementById('chat-input');
  if(input){
    input.addEventListener('keydown', e=>{
      if(e.key==='Enter' && !e.shiftKey){
        e.preventDefault();
        sendStaffChat();
      }
    });
  }
  const banner=document.getElementById('last-call-banner');
  if(banner){
    banner.addEventListener('click', ()=>{
      const search=document.getElementById('searchInput');
      if(search){
        search.focus();
        search.scrollIntoView({behavior:'smooth',block:'center'});
      }
    });
  }
  setTimeout(()=>maybeShowLastCallBanner(false), 25000);
});

document.getElementById('orderModal').addEventListener('click',function(e){if(e.target===this)closeModal('orderModal');});
document.getElementById('confirmModal').addEventListener('click',function(e){if(e.target===this)closeModal('confirmModal');});
document.getElementById('callStaffOverlay').addEventListener('click',function(e){if(e.target===this)closeCallStaff();});
document.getElementById('comboOverlay').addEventListener('click',function(e){if(e.target===this)skipCombo();});


// ========== REAL-TIME STAFF REPLY POLLING ==========
(function initStaffReplyPoll(){
  const tableNo = new URLSearchParams(location.search).get('tableNo') || '1';
  let lastId = 0;
  
  function pollForStaffReply(){
    fetch('chat_api.php?action=check_staff_reply&tableNo='+tableNo+'&since='+lastId)
    .then(r=>r.json())
    .then(data=>{
      if(data.ok && data.messages && data.messages.length > 0){
        data.messages.forEach(msg=>{
          if(parseInt(msg.id) > lastId) lastId = parseInt(msg.id);
          // Show staff reply in chat
          const stamp = new Date(msg.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
          window.staffChatMessages = (window.staffChatMessages||[]).filter(m=>m.type!=='typing');
          window.staffChatMessages.push({
            type:'received',
            text:'👔 スタッフ: '+msg.message,
            time:stamp
          });
          window.staffChatUnread = (window.staffChatUnread||0) + 1;
          renderStaffChat();
          updateChatBadge();
          // Browser notification
          if(Notification && Notification.permission==='granted'){
            new Notification('スタッフからメッセージ',{body:msg.message,icon:'/favicon.ico'});
          }
        });
      }
    })
    .catch(()=>{});
  }
  
  // Request notification permission
  window._requestNotifPermission = function(){
    if(Notification && Notification.permission==='default'){
      Notification.requestPermission();
    }
  };
  
  // Poll every 3 seconds
  setInterval(pollForStaffReply, 3000);
  
  // Also load existing staff messages on page load
  setTimeout(()=>{
    fetch('chat_api.php?action=get&tableNo='+tableNo)
    .then(r=>r.json())
    .then(data=>{
      if(data.ok && data.messages){
        const staffMsgs = data.messages.filter(m=>m.role==='staff').reverse();
        if(staffMsgs.length > 0){
          staffMsgs.forEach(msg=>{
            if(parseInt(msg.id) > lastId) lastId = parseInt(msg.id);
          });
          // Don't auto-show old staff messages on load to avoid confusion
        }
      }
    }).catch(()=>{});
  }, 1000);
})();

</script>
</body>
</html>
