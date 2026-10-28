<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'pdo.php';
require __DIR__ . '/table_assign.php';
$tableNo = isset($_GET['tableNo']) ? (int)$_GET['tableNo'] : 1;
// Basic mode (basic.php): Myanmar only, no AI chat — used as System A in user tests
$isBasic = defined('SMARTORDER_BASIC');
// Both pages are Myanmar-only (no language switcher)
$myNames = require __DIR__ . '/menu_my.php';

// Get categories
$sqlCategory = "SELECT * FROM sCategory WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtCategory = $pdo->prepare($sqlCategory);
$stmtCategory->execute();
$categories = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
$catIcons = [];
foreach ($categories as $c) { $catIcons[(int)$c['id']] = $c['icon'] ?: '🍽️'; }
require_once __DIR__ . '/item_emoji.php';
require_once __DIR__ . '/item_my.php';

// Get items
$sqlItem = "SELECT * FROM sItem WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtItem = $pdo->prepare($sqlItem);
$stmtItem->execute();
$items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);

// Get current cart
$sqlCart = "
    SELECT o.id as orderId, o.itemNo, o.amount, o.item_notes,
           i.id as itemId, i.name, i.price, i.category, (i.price * o.amount) as subtotal
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
<html lang="my">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartOrder - Table <?php echo (int)$tableNo; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&family=Noto+Sans+Myanmar:wght@400;500;700&display=swap" rel="stylesheet">
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
body{font-family:'Inter','Noto Sans Myanmar','Noto Sans JP',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
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
.lang-btn{background:transparent;border:none;color:rgba(255,255,255,.75);font-size:.8rem;font-weight:700;padding:7px 12px;min-width:36px;min-height:30px;position:relative;z-index:1;border-radius:999px;cursor:pointer;transition:all .18s;white-space:nowrap;line-height:1}
.lang-btn.active{background:rgba(255,255,255,.25);color:#fff;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.lang-btn:hover:not(.active){background:rgba(255,255,255,.1);color:#fff}
@media(max-width:480px){
  .lang-switcher{padding:2px;gap:2px;margin-right:4px}
  .lang-btn{font-size:.72rem;padding:6px 9px;min-width:32px;min-height:28px}
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
.item-img-wrap .placeholder-icon{font-size:4rem;line-height:1;filter:drop-shadow(0 4px 8px rgba(15,118,110,.18))}
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
.float-cart{display:none;position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:999px;padding:14px 22px;font-size:.9rem;font-weight:700;cursor:pointer;box-shadow:0 6px 24px var(--primary-glow);z-index:400;align-items:center;gap:8px;transition:all var(--transition)}
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
  .item-img-wrap{aspect-ratio:4/3}
  .item-info{padding:6px}
  .item-name{font-size:.74rem;margin-bottom:2px}
  .item-price{font-size:.80rem}
  .item-add-btn{font-size:.72rem;padding:5px}
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
/* ── FEATURE: Popular / History badges ── */
.badge-hot{background:linear-gradient(135deg,#ef4444,#f97316)!important;color:#fff!important;animation:hotPulse 2s infinite}
@keyframes hotPulse{0%,100%{box-shadow:0 2px 6px rgba(239,68,68,.3)}50%{box-shadow:0 4px 14px rgba(239,68,68,.7)}}
.badge-before-corner{position:absolute;bottom:8px;left:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;border-radius:999px;padding:2px 8px;font-size:.62rem;font-weight:700;z-index:4;white-space:nowrap}
/* ── FEATURE: Lucky Roulette button ── */
#lucky-btn{position:fixed;right:24px;bottom:90px;z-index:400;background:linear-gradient(135deg,#8b5cf6,#6366f1);color:#fff;border:none;border-radius:999px;padding:10px 18px;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(139,92,246,.45);display:flex;align-items:center;gap:6px;transition:transform .2s,box-shadow .2s;white-space:nowrap}
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
#chat-btn{position:fixed;right:24px;bottom:160px;z-index:450;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;border:none;border-radius:999px;padding:10px 16px;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(15,118,110,.4);display:flex;align-items:center;gap:6px;transition:transform .2s;white-space:nowrap}
#chat-btn:hover{transform:scale(1.06)}
#chat-btn .chat-badge{background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:.65rem;display:none;align-items:center;justify-content:center;font-weight:700}
#chat-btn .chat-badge.show{display:flex}
#chat-overlay{display:none;position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center}
#chat-overlay.show{display:flex}
#chat-box{background:var(--surface,#fff);border-radius:24px 24px 0 0;padding:24px;width:100%;max-width:480px;max-height:75vh;display:flex;flex-direction:column;animation:slideUp .3s ease}
#chat-title{font-size:1rem;font-weight:700;margin-bottom:4px;color:#0f766e}
#chat-subtitle{font-size:.78rem;color:#94a3b8;margin-bottom:8px}
#chat-lang-hint{font-size:.8rem;font-weight:600;color:#0f766e;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;padding:7px 10px;margin-bottom:12px;text-align:center}
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
.chat-add-row{display:flex;flex-direction:column;gap:6px;margin-top:8px}
.chat-add-btn{display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border:1px solid #e5e5e2;border-radius:12px;background:#fff;cursor:pointer;font:inherit;font-size:.82rem;font-weight:700;color:#16181d;text-align:left}
.chat-add-btn:hover{border-color:#16181d}
.chat-add-ic{font-size:1.2rem}
.chat-add-name{flex:1}
.chat-add-plus{background:#16181d;color:#fff;border-radius:999px;padding:3px 9px;font-size:.75rem;white-space:nowrap}
/* ── FEATURE: Last-Call Banner ── */
#last-call-banner{display:none;position:fixed;top:60px;left:0;right:0;z-index:99;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;text-align:center;padding:10px 16px;font-size:.88rem;font-weight:700;animation:lastCallPulse 1.5s infinite;cursor:pointer;box-shadow:0 4px 16px rgba(239,68,68,.5)}
@keyframes lastCallPulse{0%,100%{background:linear-gradient(135deg,#ef4444,#dc2626)}50%{background:linear-gradient(135deg,#f97316,#ef4444)}}
#last-call-banner .lc-close{float:right;background:rgba(255,255,255,.25);border:none;color:#fff;border-radius:50%;width:22px;height:22px;font-size:.8rem;cursor:pointer;line-height:22px}
#last-call-banner.show{display:block}
/* ── FEATURE: Wait Time Estimator ── */
#wait-time-indicator{display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #86efac;border-radius:12px;padding:10px 14px;margin:12px 0;font-size:.85rem;color:#166534;font-weight:600}
#wait-time-indicator .wt-icon{font-size:1.3rem}
#wait-time-indicator .wt-detail{font-size:.72rem;color:#4ade80;font-weight:400}
@media(max-width:480px){
  #lucky-btn{right:12px;bottom:72px;padding:8px 13px;font-size:.74rem}
  #chat-btn{left:auto!important;right:12px!important;bottom:126px!important;padding:8px 12px;font-size:.74rem;z-index:450!important}
}

/* ── Receipt / Order History Button ── */
#receipt-btn{position:fixed;right:24px;bottom:230px;z-index:450;background:linear-gradient(135deg,#0f766e,#14b8a6);color:#fff;border:none;border-radius:999px;padding:10px 18px;font-size:.82rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(15,118,110,.4);display:flex;align-items:center;gap:6px;transition:transform .2s,box-shadow .2s;white-space:nowrap}
#receipt-btn:hover{transform:scale(1.06);box-shadow:0 6px 22px rgba(15,118,110,.55)}
@media(max-width:480px){
  #receipt-btn{right:12px;bottom:210px;padding:8px 13px;font-size:.74rem;z-index:450!important}
}
/* ── Receipt Modal ── */
#receipt-overlay{display:none;position:fixed;inset:0;z-index:3100;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);align-items:flex-end;justify-content:center}
#receipt-overlay.open{display:flex}
#receipt-modal{background:var(--surface);border-radius:24px 24px 0 0;width:100%;max-width:520px;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 -8px 40px rgba(0,0,0,.25)}
.receipt-header{padding:20px 20px 12px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.receipt-header h3{font-size:1.1rem;font-weight:700;color:var(--text)}
.receipt-close-btn{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text3);padding:4px}
.receipt-body{flex:1;overflow-y:auto;padding:16px 20px}
.receipt-item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)}
.receipt-item-row:last-child{border-bottom:none}
.receipt-item-img{width:48px;height:48px;border-radius:10px;object-fit:cover;background:var(--surface2);flex-shrink:0}
.receipt-item-img-placeholder{width:48px;height:48px;border-radius:10px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.receipt-item-info{flex:1}
.receipt-item-name{font-size:.88rem;font-weight:600;color:var(--text)}
.receipt-item-qty{font-size:.78rem;color:var(--text3);margin-top:2px}
.receipt-item-price{font-size:.9rem;font-weight:700;color:var(--primary);white-space:nowrap}
.receipt-total-section{padding:16px 20px;border-top:2px solid var(--border);flex-shrink:0;background:var(--surface)}
.receipt-total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.receipt-total-label{font-size:.9rem;color:var(--text3)}
.receipt-total-amount{font-size:1.2rem;font-weight:800;color:var(--text)}
.receipt-split-section{margin-top:12px;padding-top:12px;border-top:1px solid var(--border)}
.receipt-split-label{font-size:.82rem;color:var(--text3);margin-bottom:8px}
.receipt-split-row{display:flex;align-items:center;gap:10px}
.receipt-split-input{width:70px;padding:6px 10px;border:2px solid var(--border);border-radius:999px;font-size:.9rem;font-weight:700;text-align:center;background:var(--surface2);color:var(--text);outline:none}
.receipt-split-input:focus{border-color:var(--primary)}
.receipt-per-person{font-size:1rem;font-weight:700;color:var(--primary)}
.receipt-empty{text-align:center;padding:32px 0;color:var(--text3);font-size:.9rem}

/* ═══════════════════════════════════════════════════════
   MODERN THEME — clean & minimal
   Neutral surfaces, one ink colour for actions, one warm
   accent. Overrides the legacy styles above.
═══════════════════════════════════════════════════════ */
:root{
  --primary:#16181d;--primary-dark:#000;--primary-light:#ff5a36;--primary-glow:rgba(22,24,29,.12);
  --accent:#ff5a36;--accent-dark:#e94a28;--accent-soft:#fff1ec;
  --bg:#f6f6f4;--surface:#fff;--surface2:#f6f6f4;--muted:#efefec;--border:#ececea;
  --text:#16181d;--text2:#5b5f68;--text3:#9a9ea6;--green:#13a05f;
  --radius-sm:10px;--radius:14px;--radius-lg:18px;--radius-xl:24px;
  --shadow-sm:0 1px 2px rgba(16,24,40,.04);
  --shadow:0 2px 8px rgba(16,24,40,.06);
  --shadow-md:0 8px 24px rgba(16,24,40,.08);
  --shadow-lg:0 20px 48px rgba(16,24,40,.14);
  --transition:.18s ease;
}
body{background:var(--bg);-webkit-font-smoothing:antialiased;letter-spacing:-.005em}
button{font-family:inherit}

/* Header */
.app-header{background:rgba(255,255,255,.88);backdrop-filter:saturate(180%) blur(14px);-webkit-backdrop-filter:saturate(180%) blur(14px);color:var(--text);box-shadow:none;border-bottom:1px solid var(--border)}
.header-logo{background:var(--text);color:#fff;font-weight:800;font-size:1rem;border-radius:10px}
.header-title{letter-spacing:-.03em}
.header-table{opacity:1;color:var(--text3);font-weight:500}
.app-header button[onclick="openCallStaff()"]{background:var(--surface)!important;border:1px solid var(--border)!important;color:var(--text)!important;font-weight:600!important;box-shadow:none}
.app-header button[onclick="openCallStaff()"]:hover{background:var(--muted)!important}
.lang-switcher{background:var(--muted);border:none;padding:3px}
.lang-btn{color:var(--text2);font-weight:600}
.lang-btn.active{background:var(--surface);color:var(--text);box-shadow:0 1px 3px rgba(16,24,40,.12)}
.lang-btn:hover:not(.active){background:transparent;color:var(--text)}
.btn-cart-header{background:var(--text);border:none;color:#fff;font-weight:600}
.btn-cart-header:hover{background:#000}
.cart-badge{background:var(--accent);color:#fff;font-weight:700}

/* Search + categories */
.search-bar-wrap,.category-nav{background:rgba(255,255,255,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--border)}
.search-input{border:1px solid transparent;background:var(--muted);border-radius:12px;padding-top:11px;padding-bottom:11px}
.search-input:focus{background:var(--surface);border-color:var(--text);box-shadow:0 0 0 4px var(--primary-glow)}
.cat-btn{border:1px solid var(--border);background:var(--surface);color:var(--text2);font-weight:600;padding:8px 16px}
.cat-btn:hover{border-color:var(--text);color:var(--text)}
.cat-btn.active{background:var(--text);border-color:var(--text);color:#fff;box-shadow:none}

/* Section title + wait time */
.menu-section-title{font-size:1.35rem;font-weight:800;color:var(--text);text-transform:none;letter-spacing:-.03em;margin-bottom:10px}
#wait-time-indicator{background:var(--surface);border:1px solid var(--border);color:var(--text);font-weight:600;border-radius:var(--radius);margin:0 0 18px;padding:12px 14px}
#wait-time-indicator .wt-icon{width:34px;height:34px;border-radius:10px;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;font-size:1.05rem}
#wait-time-indicator .wt-detail{color:var(--text3);font-weight:500}

/* Menu cards */
.menu-grid{grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:18px}
.menu-card{border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:none;background:var(--surface)}
.menu-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--border)}
.item-img-wrap{background:var(--muted);aspect-ratio:4/3;margin:8px 8px 0;width:auto;border-radius:12px}
.item-img-wrap .placeholder-icon{font-size:3.6rem;filter:none;transition:transform .3s ease}
.menu-card:hover .placeholder-icon{transform:scale(1.08) rotate(-4deg)}
.item-info{padding:12px 56px 14px 14px;flex:1}
.item-name{font-size:.9rem;font-weight:600;line-height:1.4;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.item-price{font-size:1rem;font-weight:700;color:var(--text);letter-spacing:-.01em}
.item-add-btn{position:absolute;right:12px;bottom:12px;width:38px;height:38px;padding:0;border-radius:50%;background:var(--text);font-size:0;display:flex;align-items:center;justify-content:center;margin:0;box-shadow:0 4px 12px rgba(22,24,29,.18);transition:transform var(--transition),background var(--transition)}
.item-add-btn::after{content:'+';font-size:1.45rem;font-weight:400;line-height:1;color:#fff;margin-top:-2px}
.item-add-btn:hover{background:var(--accent);transform:scale(1.08)}
.item-badge{top:16px;left:16px;padding:4px 9px;border-radius:8px;font-size:.68rem;font-weight:700;box-shadow:none;letter-spacing:0;background:var(--surface)!important;color:var(--text)!important;animation:none!important}
.badge-hot{color:var(--accent)!important}
.badge-recommended{color:#b7791f!important}
.badge-limited{color:#6d4fd6!important}
.badge-free{color:var(--green)!important}
.fav-btn{top:16px;right:16px;width:32px;height:32px;background:var(--surface);box-shadow:none;font-size:.9rem}
.badge-before-corner{bottom:auto;top:46px;left:16px;background:var(--text);border-radius:8px;font-size:.62rem;padding:3px 8px}

.item-price.is-free{color:var(--green)}

/* Cart sidebar */
.cart-sidebar{top:150px}
.cart-box{border:1px solid var(--border);box-shadow:none;border-radius:var(--radius-xl)}
.cart-header-bar{background:var(--surface);color:var(--text);font-size:1.1rem;font-weight:800;letter-spacing:-.02em;padding:20px 20px 8px}
.cart-items{padding:8px 14px}
.cart-empty{padding:28px 16px}
.cart-empty-icon{opacity:.35}
.cart-item{background:transparent;border:none;border-bottom:1px solid var(--border);border-radius:0;margin:0;padding:12px 4px}
.cart-item:last-child{border-bottom:none}
.cart-item-thumb{background:var(--muted);border-radius:10px;color:inherit}
.cart-item-name{font-size:.86rem}
.qty-btn{border:1px solid var(--border);width:28px;height:28px;color:var(--text)}
.qty-btn:hover{background:var(--text);border-color:var(--text);color:#fff}
.cart-footer{background:var(--surface);border-top:1px solid var(--border);padding:16px 20px 20px}
.cart-total-price{color:var(--text);font-size:1.25rem;font-weight:800}
.btn-checkout{background:var(--text);border-radius:var(--radius);box-shadow:none;padding:14px;font-weight:700}
.btn-checkout:hover{background:#000;box-shadow:var(--shadow-md)}
.btn-checkout:disabled{background:var(--muted);color:var(--text3)}
.btn-call-staff{background:var(--surface);color:var(--text);border:1px solid var(--border);box-shadow:none;font-weight:600}
.btn-call-staff:hover{background:var(--muted);box-shadow:none}

/* Floating action buttons (desktop) */
#receipt-btn,#chat-btn,#lucky-btn{background:var(--surface);color:var(--text);border:1px solid var(--border);box-shadow:var(--shadow-md);font-weight:600;padding:10px 16px;right:24px}
#receipt-btn:hover,#chat-btn:hover,#lucky-btn:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}
#lucky-btn{bottom:24px;background:var(--accent);color:#fff;border-color:var(--accent)}
#chat-btn{bottom:76px}
#receipt-btn{bottom:128px}
#chat-btn .chat-badge{background:var(--accent)}

/* Mobile cart bar */
.float-cart{left:16px;right:16px;bottom:16px;justify-content:space-between;align-items:center;background:var(--text);border-radius:16px;padding:14px 18px;box-shadow:0 10px 30px rgba(22,24,29,.3)}
.float-cart:hover{transform:none}
.fc-left{display:flex;align-items:center;gap:10px}
.fc-total{font-weight:800}

/* Modals */
.modal-overlay,.combo-overlay,.confirm-overlay,.call-staff-overlay,.cart-modal-overlay,#chat-overlay,#receipt-overlay,#lucky-reveal,.stamp-reward-overlay{background:rgba(16,18,22,.4)}
.modal-box,.combo-box{border-radius:28px;box-shadow:var(--shadow-lg)}
.modal-header-bar{background:var(--surface);color:var(--text);font-size:1.2rem;font-weight:800;letter-spacing:-.02em;padding:22px 22px 4px}
.modal-item-img{background:var(--muted);border-radius:18px;aspect-ratio:16/10}
.modal-item-price{color:var(--text);font-size:1.5rem}
.modal-qty-control{background:var(--muted);border-radius:999px;padding:6px;width:max-content;margin:0 auto 16px;gap:10px}
.modal-qty-btn{border:none;background:var(--surface);color:var(--text);box-shadow:var(--shadow-sm)}
.modal-qty-btn:hover{background:var(--text);color:#fff}
.modal-notes{border:1px solid var(--border);border-radius:12px;background:var(--surface2)}
.modal-notes:focus{border-color:var(--text);background:var(--surface)}
.modal-footer-row,.combo-footer{border-top:none;padding-top:4px;padding-bottom:20px}
.btn-cancel-modal,.btn-combo-skip,.btn-confirm-cancel,.btn-call-close{border:1px solid var(--border);border-radius:var(--radius);color:var(--text)}
.btn-cancel-modal:hover,.btn-combo-skip:hover,.btn-confirm-cancel:hover,.btn-call-close:hover{border-color:var(--text);color:var(--text)}
.btn-order-modal,.btn-confirm-ok,.btn-combo-add,.btn-call-confirm{background:var(--text);border-radius:var(--radius);padding:13px}
.btn-order-modal:hover,.btn-confirm-ok:hover,.btn-combo-add:hover,.btn-call-confirm:hover{background:#000;opacity:1}
.btn-combo-add:disabled{background:var(--muted);color:var(--text3)}
.combo-header{background:var(--surface);color:var(--text);padding:22px 22px 4px}
.combo-header-title{font-size:1.15rem;font-weight:800}
.combo-header-sub{color:var(--text2);opacity:1}
.combo-added-item{border:1px solid var(--border);background:var(--surface2)}
.combo-added-img,.combo-suggest-img{background:var(--muted)}
.combo-arrow{font-size:.9rem;color:var(--text2);font-weight:600}
.combo-suggest-item{border:1px solid var(--border);background:var(--surface)}
.combo-suggest-item:hover,.combo-suggest-item.selected{border-color:var(--text);background:var(--surface);box-shadow:0 0 0 1px var(--text)}
.combo-suggest-price{color:var(--text)}
.combo-suggest-img{aspect-ratio:16/10}
.combo-suggest-img span{font-size:2.2rem!important}
.combo-added-img span{font-size:1.8rem!important}
.btn-combo-skip{white-space:nowrap}
.call-option-btn{border:1px solid var(--border);background:var(--surface)}
.call-option-btn:hover,.call-option-btn.selected{border-color:var(--text);background:var(--surface);color:var(--text);box-shadow:0 0 0 1px var(--text)}
.call-staff-emoji,#lucky-reveal-emoji,.stamp-reward-emoji{animation:none}
.cart-modal{border-radius:28px 28px 0 0}

/* Lucky / stamp / chat */
#lucky-reveal-box,.stamp-reward-box{background:var(--surface);border:none;box-shadow:var(--shadow-lg)}
#lucky-reveal-label{color:var(--accent)}
#lucky-reveal-name{color:var(--text)}
#lucky-reveal-price{color:var(--text)}
.btn-lucky-add,.stamp-reward-btn{background:var(--text);box-shadow:none}
.btn-lucky-skip{background:var(--muted);color:var(--text2)}
.stamp-reward-title{color:var(--text)}
.stamp-reward-sub{color:var(--text2)}
.stamp-card-bar{background:var(--surface);border:1px solid var(--border);box-shadow:var(--shadow-md)}
.stamp-dot{border-color:var(--border)}
.stamp-dot.filled{background:var(--accent);border-color:var(--accent)}
#chat-title{color:var(--text);font-size:1.15rem;font-weight:800}
.chat-msg.sent{background:var(--text)}
.chat-msg.system{background:var(--muted);color:var(--text);border:none}
#chat-input{border:1px solid var(--border)}
#chat-input:focus{border-color:var(--text)}
#chat-send{background:var(--text)}

/* Toasts, banners, celebrate */
.toast-container{top:auto;bottom:24px;right:auto;left:50%;transform:translateX(-50%);align-items:center}
.toast,.toast.success{background:var(--text);border-radius:999px;padding:11px 18px;min-width:0;box-shadow:var(--shadow-lg)}
.toast.error{background:#d93025}
@keyframes slideInToast{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}
.staff-arrival-toast{background:var(--text)}
#last-call-banner{background:var(--accent-soft);color:var(--accent-dark);animation:none;box-shadow:none;border-bottom:1px solid #ffd9cc;font-weight:600}
#last-call-banner .lc-close{background:rgba(233,74,40,.12);color:var(--accent-dark)}
.celebrate-card{border:none;box-shadow:var(--shadow-lg);border-radius:24px}
.spinner{border-color:var(--muted);border-top-color:var(--text)}
.receipt-item-price,.receipt-per-person{color:var(--text)}
.receipt-split-input:focus{border-color:var(--text)}

/* Tablet & phone */
@media(max-width:900px){
  .float-cart{display:flex!important}
  #receipt-btn,#chat-btn,#lucky-btn{right:16px!important;padding:9px 14px;font-size:.78rem}
  #lucky-btn{bottom:88px!important}
  #chat-btn{bottom:136px!important}
  #receipt-btn{bottom:184px!important}
  .toast-container{bottom:88px}
  .app-layout{padding-bottom:110px}
  .stamp-card-bar{bottom:88px;left:16px;transform:none}
}
@media(max-width:480px){
  .float-cart{left:12px!important;right:12px!important;bottom:12px!important;padding:13px 16px!important;font-size:.88rem!important}
  .menu-grid{grid-template-columns:repeat(2,1fr);gap:10px}
  .menu-section-title{font-size:1.1rem}
  .item-img-wrap{margin:6px 6px 0;border-radius:10px}
  .item-img-wrap .placeholder-icon{font-size:2.8rem}
  .item-info{padding:9px 44px 11px 10px}
  .item-name{font-size:.78rem}
  .item-price{font-size:.88rem}
  .item-add-btn{width:32px;height:32px;right:8px;bottom:8px;font-size:0;padding:0}
  .item-badge{top:12px;left:12px;font-size:.6rem;padding:3px 7px}
  .fav-btn{top:12px;right:12px;width:28px;height:28px}
  .badge-before-corner{top:38px;left:12px}
  /* quick actions become one row above the cart bar */
  #receipt-btn,#chat-btn,#lucky-btn{bottom:76px!important;padding:8px 11px;font-size:.7rem;white-space:nowrap;box-shadow:var(--shadow)}
  #receipt-btn{left:12px!important;right:auto!important}
  #chat-btn{left:50%!important;right:auto!important;transform:translateX(-50%)}
  #chat-btn:hover{transform:translateX(-50%)}
  #lucky-btn{right:12px!important}
  .app-layout{padding-bottom:140px}
  .toast-container{left:12px;right:12px;transform:none;bottom:124px}
  .toast,.toast.success{border-radius:14px}
  .stamp-card-bar{bottom:124px}
  .app-header button[onclick="openCallStaff()"]{white-space:nowrap;padding:7px 10px!important}
}
@media(max-width:900px){ .btn-cart-header{display:none} }
</style>
<?php if ($isBasic): ?>
<style>
/* Basic mode: no AI chat */
#chat-btn,#chat-overlay{display:none!important}
</style>
<?php endif; ?>
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
<button id="receipt-btn" type="button" onclick="showReceiptModal()">🧾 注文履歴</button>
<div id="chat-overlay" onclick="if(event.target===this)toggleStaffChat(false)">
  <div id="chat-box">
    <div id="chat-title">スタッフチャット</div>
    <div id="chat-subtitle">注文前の相談やおすすめ確認にどうぞ</div>
    <div id="chat-lang-hint" translate="no">🌐 Ask in any language ・ 何語でも質問できます</div>
    <div id="chat-messages"><div class="chat-empty">まだメッセージはありません</div></div>
    <div id="chat-input-row">
      <textarea id="chat-input" placeholder="Type in any language… / 何語でもOK"></textarea>
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
    <div id="lucky-reveal-price">0 Ks</div>
    <div class="lucky-btn-row">
      <button class="btn-lucky-add" type="button" onclick="addLuckyPick()">これにする</button>
      <button class="btn-lucky-skip" type="button" onclick="closeLuckyReveal()">また今度</button>
    </div>
  </div>
</div>

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
        <div><div class="combo-added-name" id="comboAddedName">Item</div><div class="combo-added-price" id="comboAddedPrice">0 Ks</div></div>
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
      <div class="header-logo">S</div>
      <div><div class="header-title">SmartOrder</div><div class="header-table">Table <?php echo (int)$tableNo; ?></div></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <button onclick="openCallStaff()" style="padding:8px 14px;font-size:.78rem;border-radius:999px;background:rgba(245,158,11,.85);border:1px solid rgba(255,255,255,.3);color:#fff;cursor:pointer;font-weight:700;display:flex;align-items:center;gap:5px;transition:background .2s">🔔 スタッフ</button>

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
        $iname  = $item['name'];                       // stored name (may contain [おすすめ]/[期間限定] tags)
        $dname  = myItemName($item['id'], $iname);     // display name (tags removed)
        $badge=''; $badgeClass='';
        if(strpos($iname,'おすすめ')!==false){$badge='⭐ おすすめ';$badgeClass='badge-recommended';}
        elseif(strpos($iname,'期間限定')!==false){$badge='⏳ 期間限定';$badgeClass='badge-limited';}
        elseif($item['price']==0){$badge='🎁 無料';$badgeClass='badge-free';}
      ?>
      <div class="menu-card" data-item-id="<?php echo (int)$item['id']; ?>" data-item-name="<?php echo htmlspecialchars($dname); ?>" data-item-price="<?php echo (int)$item['price']; ?>" data-item-img="<?php echo $imgUrl?htmlspecialchars($imgUrl):''; ?>" data-category="<?php echo (int)$item['category']; ?>" data-name="<?php echo htmlspecialchars(mb_strtolower($dname,'UTF-8')); ?>"
           onclick="openOrderModal(<?php echo (int)$item['id']; ?>,'<?php echo addslashes(htmlspecialchars($dname)); ?>',<?php echo (int)$item['price']; ?>,'<?php echo $imgUrl?htmlspecialchars($imgUrl):''; ?>')">
        <button class="fav-btn" id="fav_<?php echo (int)$item['id']; ?>" onclick="event.stopPropagation();toggleFav(<?php echo (int)$item['id']; ?>,this)" title="お気に入り">🤍</button>
        <?php if($badge): ?><div class="item-badge <?php echo $badgeClass; ?>"><?php echo $badge; ?></div><?php endif; ?>
        <div class="item-img-wrap">
          <?php if($imgUrl): ?><img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($dname); ?>" loading="lazy">
          <?php else: ?><span class="placeholder-icon"><?php echo htmlspecialchars(itemEmoji($iname, $item['category'], $catIcons)); ?></span><?php endif; ?>
        </div>
        <div class="item-info">
          <div class="item-name"><?php echo htmlspecialchars($dname); ?></div>
          <div class="item-price<?php echo $item['price']==0 ? ' is-free' : ''; ?>"><?php echo $item['price']==0 ? '無料' : number_format($item['price']).' Ks'; ?></div>
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
          <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?><?php echo htmlspecialchars(itemEmoji($ci['name'], $ci['category'] ?? 0, $catIcons)); ?><?php endif; ?></div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?php echo htmlspecialchars(myItemName($ci['itemId'], $ci['name'])); ?></div>
            <div class="cart-item-price"><?php echo $ci['price']==0 ? '無料' : number_format($ci['price']).' Ks'; ?> × <?php echo (int)$ci['amount']; ?></div>
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
        <div class="cart-total"><span>合計</span><span class="cart-total-price" id="totalPrice"><?php echo number_format($currentTotal); ?> Ks</span></div>
        <button class="btn-checkout" id="checkoutBtn" <?php echo empty($cartItems)?'disabled':''; ?> onclick="confirmCheckout()">✓ 注文を確定する</button>
        <button class="btn-call-staff" onclick="openCallStaff()">🔔 スタッフを呼ぶ</button>
      </div>
    </div>
  </aside>
</div>
<button class="float-cart" onclick="showCart()"><span class="fc-left"><span class="cart-badge" id="floatCartCount"><?php echo $itemCount; ?></span> カートを見る</span><span class="fc-total" id="floatCartTotal"><?php echo number_format($currentTotal); ?> Ks</span></button>
<div class="modal-overlay" id="orderModal">
  <div class="modal-box">
    <div class="modal-header-bar" id="modalTitle">Order Item</div>
    <div class="modal-body-inner">
      <div class="modal-item-img" id="modalImgWrap"></div>
      <div class="modal-item-price" id="modalPrice">0 Ks</div>
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
        <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?><?php echo htmlspecialchars(itemEmoji($ci['name'], $ci['category'] ?? 0, $catIcons)); ?><?php endif; ?></div>
        <div class="cart-item-info">
          <div class="cart-item-name"><?php echo htmlspecialchars(myItemName($ci['itemId'], $ci['name'])); ?></div>
          <div class="cart-item-price"><?php echo $ci['price']==0 ? '無料' : number_format($ci['price']).' Ks'; ?></div>
        </div>
        <div class="cart-item-qty">
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">−</button>
          <span class="qty-num"><?php echo (int)$ci['amount']; ?></span>
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">＋</button>
        </div>
      </div>
      <?php endforeach; endif; ?>
      <div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">
        <div class="cart-total"><span>合計</span><span class="cart-total-price"><?php echo number_format($currentTotal); ?> Ks</span></div>
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
const BASIC_MODE = <?php echo $isBasic ? 'true' : 'false'; ?>;
const FIXED_LANG = 'my';
const MY_NAMES = <?php echo json_encode((object)$myNames, JSON_UNESCAPED_UNICODE); ?>;
let selectedItemId=null,selectedItemName='',selectedItemPrice=0,selectedItemImg='',modalQty=1,pendingAction=null;
let selectedCallOption='',selectedComboId=null,selectedComboName='',selectedComboPrice=0,selectedComboImg='';
let _pendingOrderAfterCombo=null;

const ALL_ITEMS = <?php
  $jsItems=[];
  foreach($items as $it){
    $img=getItemImage($it['id']);
    $jsItems[]=['id'=>(int)$it['id'],'name'=>myItemName($it['id'],$it['name']),'price'=>(int)$it['price'],'category'=>(int)$it['category'],'img'=>$img??'','icon'=>itemEmoji($it['name'],$it['category'],$catIcons)];
  }
  echo json_encode($jsItems,JSON_UNESCAPED_UNICODE);
?>;
const DRINK_CATS = <?php echo json_encode(array_values(array_map(fn($c)=>(int)$c['id'], array_filter($categories, fn($c)=>preg_match('/သောက်စရာ|ドリンク|飲み物/u',$c['categoryName']))))); ?>;
const DESSERT_CATS = <?php echo json_encode(array_values(array_map(fn($c)=>(int)$c['id'], array_filter($categories, fn($c)=>preg_match('/အချိုပွဲ|デザート/u',$c['categoryName']))))); ?>;
const CAT_ICONS = <?php echo json_encode($catIcons, JSON_UNESCAPED_UNICODE); ?>;
function itemIcon(key){
  const it = ALL_ITEMS.find(i => i.id === Number(key)) || ALL_ITEMS.find(i => i.name === key);
  return (it && (it.icon || CAT_ICONS[it.category])) || '🍽️';
}
function fmtPrice(n){ return n===0 ? (typeof I18N_T==='function' ? I18N_T('無料') : '無料') : n.toLocaleString()+' Ks'; }

/* ── SEARCH ── */
function handleSearch(val){
  const q=val.trim().toLowerCase();
  document.getElementById('searchClear').classList.toggle('visible',q.length>0);
  let vis=0;
  document.querySelectorAll('.menu-card').forEach(c=>{
    const shown=(c.querySelector('.item-name')||{}).textContent||'';
    const show=!q||c.dataset.name.includes(q)||shown.toLowerCase().includes(q);
    c.style.display=show?'':'none';
    if(show)vis++;
  });
  document.getElementById('searchNoResults').classList.toggle('show',q.length>0&&vis===0);
  if(q.length>1)feedbackToast('search');
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
  feedbackToast('staff');
  showStaffArrival();
}

/* ── COMBO ── */
function getSuggestions(item){
  // suggest a drink for food, a dessert for drinks (by category)
  const full=ALL_ITEMS.find(i=>i.id===item.id)||item;
  const isDrink=DRINK_CATS.includes(full.category);
  let pool=[];
  if(!isDrink) pool=ALL_ITEMS.filter(i=>i.id!==item.id&&DRINK_CATS.includes(i.category)&&i.price>0);
  if(pool.length===0) pool=ALL_ITEMS.filter(i=>i.id!==item.id&&DESSERT_CATS.includes(i.category));
  if(pool.length===0)pool=ALL_ITEMS.filter(i=>i.id!==item.id).slice(0,4);
  for(let i=pool.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[pool[i],pool[j]]=[pool[j],pool[i]];}
  return pool.slice(0,4);
}
function showCombo(addedItem,suggestions){
  const imgW=document.getElementById('comboAddedImg');
  imgW.innerHTML=addedItem.img?'<img src="'+addedItem.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.5rem">'+itemIcon(addedItem.id)+'</span>';
  document.getElementById('comboAddedName').textContent=addedItem.name;
  document.getElementById('comboAddedPrice').textContent=fmtPrice(addedItem.price);
  const grid=document.getElementById('comboSuggestions');
  grid.innerHTML='';selectedComboId=null;
  document.getElementById('btnComboAdd').disabled=true;
  suggestions.forEach(s=>{
    const d=document.createElement('div');
    d.className='combo-suggest-item';
    d.innerHTML='<div class="combo-suggest-img">'+(s.img?'<img src="'+s.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.3rem">'+itemIcon(s.id)+'</span>')+'</div><div class="combo-suggest-name">'+s.name+'</div><div class="combo-suggest-price">'+s.price.toLocaleString()+' Ks</div>';
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
  launchConfetti();feedbackToast('add');
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
  if(MY_NAMES[itemId])itemName=MY_NAMES[itemId];
  selectedItemId=itemId;selectedItemName=itemName;selectedItemPrice=itemPrice;selectedItemImg=imgUrl;modalQty=1;
  document.getElementById('modalTitle').textContent=itemName;
  document.getElementById('modalPrice').textContent=fmtPrice(itemPrice);
  document.getElementById('modalQty').textContent='1';
  document.getElementById('modalNotes').value='';
  document.getElementById('modalImgWrap').innerHTML=imgUrl?'<img src="'+imgUrl+'" alt="'+itemName+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:4.5rem">'+itemIcon(itemId)+'</span>';
  document.getElementById('orderModal').classList.add('show');
}
function changeModalQty(delta){
  modalQty=Math.max(1,Math.min(99,modalQty+delta));
  document.getElementById('modalQty').textContent=modalQty;
  document.getElementById('modalPrice').textContent=fmtPrice(selectedItemPrice*modalQty);
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
    if(res.ok){feedbackToast('checkout');launchConfetti();showToast('ご注文ありがとうございます！少々お待ちください🎉','success');refreshCart();}
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
    if(totalEl) totalEl.textContent = (data.total||0).toLocaleString()+' Ks';
    const fcTotal = document.getElementById('floatCartTotal');
    if(fcTotal) fcTotal.textContent = (data.total||0).toLocaleString()+' Ks';
    
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
    mobileTotals.forEach(el => el.textContent = (data.total||0).toLocaleString()+' Ks');
    const mobileCheckout = document.querySelector('.cart-modal .btn-checkout');
    if(mobileCheckout) mobileCheckout.disabled = count === 0;
    
  }catch(e){ console.warn('refreshCart error:', e); }
}

function buildCartItemHTML(ci){
  const img = ci.img ? '<img src="'+ci.img+'" alt="" style="width:100%;height:100%;object-fit:cover">' : itemIcon(ci.name);
  return '<div class="cart-item" id="cartItem_'+ci.orderId+'">'
    +'<div class="cart-item-thumb">'+img+'</div>'
    +'<div class="cart-item-info">'
    +'<div class="cart-item-name">'+ci.name+'</div>'
    +'<div class="cart-item-price">'+fmtPrice(Number(ci.price))+' × '+ci.amount+'</div>'
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
    +'<div class="cart-total"><span>合計</span><span class="cart-total-price">'+(total||0).toLocaleString()+' Ks</span></div>'
    +'<button class="btn-checkout" '+(count===0?'disabled':'')
    +' onclick="closeCartModal();confirmCheckout()">✓ 注文を確定する</button>'
    +'<button class="btn-call-staff" onclick="closeCartModal();openCallStaff()">🔔 スタッフを呼ぶ</button>'
    +'</div>';
}



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

/* ── FEATURE BLOCK: Lucky / Chat / Last-Call / History / WaitTime / FeedbackToast ── */
window.luckyPick = null;
window.staffChatMessages = [];
window.staffChatUnread = 1;

function feedbackToast(type){
  const msgs={
    add:["ご注文ありがとうございます。できあがりをお楽しみに。","人気の一品ですね。良いお選びです。","こちら、相性の良いセットもございます。"],
    checkout:["ご注文を承りました。少々お待ちください。","ありがとうございます。順番にご用意いたします。","ただいま厨房へ共有しました。"],
    search:["気になるメニューは見つかりましたか。","お好みがあればおすすめもご案内できます。","迷ったらおまかせボタンもどうぞ。"],
    staff:["スタッフへお声がけしました。","まもなくスタッフが参ります。","ご用件をしっかりお伝えしますね。"],
    lucky:["今日の運試しメニューです。","新しいお気に入りに出会えるかもしれません。","直感で選ぶのも楽しいですよ。"]
  };
  const arr=msgs[type]||msgs.add;
  const msg=arr[Math.floor(Math.random()*arr.length)];
  showToast(msg,'success');
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
      // sit just below the header so it never covers the language / cart buttons
      const header=document.querySelector('.app-header');
      if(header) banner.style.top=header.getBoundingClientRect().height+'px';
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
    document.getElementById('lucky-reveal-price').textContent=fmtPrice(Number(window.luckyPick.price||0));
    document.getElementById('lucky-reveal-emoji').textContent=['🎯','🎲','✨','🍀'][Math.floor(Math.random()*4)];
    document.getElementById('lucky-reveal').classList.add('show');
    feedbackToast('lucky');
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
// Escape chat text and turn [[add:ID]] tags from the AI into "add to cart" buttons
function chatHTML(text, allowButtons){
  const esc=String(text||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const btns=[];
  let out=esc.replace(/\[\[add:(\d+)\]\]/g,(m,id)=>{
    const it=ALL_ITEMS.find(i=>i.id===Number(id));
    if(!allowButtons||!it) return '';
    if(!btns.includes(it.id)) btns.push(it.id);
    return '';
  }).replace(/\*\*(.+?)\*\*/g,'<b>$1</b>').replace(/^\s*[*-]\s+/gm,'• ').replace(/\n{2,}/g,'\n').trim().replace(/\n/g,'<br>');
  if(btns.length){
    out+='<div class="chat-add-row">'+btns.map(id=>{
      const it=ALL_ITEMS.find(i=>i.id===id);
      return '<button type="button" class="chat-add-btn" onclick="chatAddItem('+id+')"><span class="chat-add-ic">'+itemIcon(id)+'</span><span class="chat-add-name">'+it.name.replace(/[&<>]/g,'')+'</span><span class="chat-add-plus">＋🛒</span></button>';
    }).join('')+'</div>';
  }
  return out;
}
function chatAddItem(id){
  const it=ALL_ITEMS.find(i=>i.id===id); if(!it) return;
  toggleStaffChat(false);
  openOrderModal(it.id, it.name, it.price, it.img||'');
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
    return '<div class="chat-msg '+msg.type+'">'+chatHTML(msg.text, msg.type!=='sent')+(msg.time?'<div class="chat-msg-time">'+msg.time+'</div>':'')+'</div>';
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
let currentLang = FIXED_LANG;
const menuTranslCache = {};
function setLang(lang) {
  currentLang = lang;
  lang = FIXED_LANG; // Myanmar only
  currentLang = lang;
  ['ja','en','zh'].forEach(l => {
    const id = 'lang' + l.charAt(0).toUpperCase() + l.slice(1);
    const btn = document.getElementById(id);
    if (btn) btn.classList.toggle('active', l === lang);
  });
  applyMenuTranslation(lang);
  updateChatLangHint(lang);
  applyUITranslation();
}
function updateChatLangHint(lang) {
  const title = document.getElementById('chat-title');
  const subtitle = document.getElementById('chat-subtitle');
  const hints = {
    ja: {title:'スタッフチャット', sub:'注文前の相談やおすすめ確認にどうぞ'},
    en: {title:'Staff Chat', sub:'Ask about menu, recommendations & more'},
    zh: {title:'服务员聊天', sub:'询问菜单推荐，随时为您服务'},
    my: {title:'ဝန်ထမ်းနှင့် စကားပြောရန်', sub:'မီနူး၊ အကြံပြုချက်၊ ဓာတ်မတည့်မှု စသည် မေးနိုင်ပါသည်'}
  };
  if (title && hints[lang]) title.textContent = hints[lang].title;
  if (subtitle && hints[lang]) subtitle.textContent = hints[lang].sub;
}
async function applyMenuTranslation(lang) {
  if (lang === 'ja') {
    document.querySelectorAll('.menu-card').forEach(card => {
      const nameEl = card.querySelector('.item-name');
      const origName = card.dataset.itemName || card.dataset.name;
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
  if (typeof applyUITranslation === 'function') applyUITranslation();
}
document.addEventListener('DOMContentLoaded', () => {
  // fixed Myanmar names, no AI translation
  menuTranslCache.my = {};
  Object.keys(MY_NAMES).forEach(id => { menuTranslCache.my[id] = { name: MY_NAMES[id] }; });
  setLang(FIXED_LANG);
});

// ── Order History / Receipt ──
let receiptTotal = 0;
async function showReceiptModal(){
  document.getElementById('receipt-overlay').classList.add('open');
  document.body.style.overflow='hidden';
  const body = document.getElementById('receipt-body');
  body.innerHTML='<div class="receipt-empty">読み込み中...</div>';
  receiptTotal = 0;
  document.getElementById('receipt-total').textContent='0 Ks';
  document.getElementById('receipt-per-person').textContent='0 Ks / 人';
  document.getElementById('receipt-people').value=1;
  try{
    const fd=new FormData();
    fd.append('tableNo',TABLE_NO);
    const res=await fetch('order_history.php',{method:'POST',body:fd});
    const data=await res.json();
    if(!data.success||data.items.length===0){
      body.innerHTML='<div class="receipt-empty">まだ注文がありません</div>';
      return;
    }
    receiptTotal=data.total;
    document.getElementById('receipt-total').textContent=receiptTotal.toLocaleString()+' Ks';
    calcSplit();
    body.innerHTML=data.items.map(item=>`
      <div class="receipt-item-row">
        ${item.image_url
          ? `<img class="receipt-item-img" src="${item.image_url}" alt="${item.name}" onerror="this.style.display='none'">`
          : `<div class="receipt-item-img-placeholder">${itemIcon(item.name)}</div>`}
        <div class="receipt-item-info">
          <div class="receipt-item-name">${item.name}</div>
          <div class="receipt-item-qty">×${item.amount} &nbsp;${Number(item.price).toLocaleString()} Ks</div>
        </div>
        <div class="receipt-item-price">${Number(item.subtotal).toLocaleString()} Ks</div>
      </div>
    `).join('');
  }catch(e){
    body.innerHTML='<div class="receipt-empty">データの取得に失敗しました</div>';
  }
}
function closeReceiptModal(){
  document.getElementById('receipt-overlay').classList.remove('open');
  document.body.style.overflow='';
}
function calcSplit(){
  const people=Math.max(1,parseInt(document.getElementById('receipt-people').value)||1);
  const perPerson=Math.ceil(receiptTotal/people);
  document.getElementById('receipt-per-person').textContent=perPerson.toLocaleString()+' Ks'+' / 人';
}

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

<!-- Receipt Modal -->
<div id="receipt-overlay" onclick="if(event.target==this)closeReceiptModal()">
  <div id="receipt-modal">
    <div class="receipt-header">
      <h3>🧾 注文履歴・会計</h3>
      <button class="receipt-close-btn" onclick="closeReceiptModal()">✕</button>
    </div>
    <div class="receipt-body" id="receipt-body">
      <div class="receipt-empty">読み込み中...</div>
    </div>
    <div class="receipt-total-section">
      <div class="receipt-total-row">
        <span class="receipt-total-label">合計金額</span>
        <span class="receipt-total-amount" id="receipt-total">0 Ks</span>
      </div>
      <div class="receipt-split-section">
        <div class="receipt-split-label">人数で割り勘</div>
        <div class="receipt-split-row">
          <input type="number" class="receipt-split-input" id="receipt-people" value="1" min="1" max="20" oninput="calcSplit()">
          <span style="color:var(--text3);font-size:.82rem">人で</span>
          <span class="receipt-per-person" id="receipt-per-person">0 Ks / 人</span>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
/* ── UI TRANSLATION (JP / EN / 中文) ──
   Translates every static and dynamically inserted UI string.
   Menu item names come from translate_menu.php (menuTranslCache). */
const I18N_DICT = {
  // header / navigation
  'スタッフ': ['Staff','服务员','ဝန်ထမ်း'],
  'カート': ['Cart','购物车','ခြင်းတောင်း'],
  'ショッピングカート': ['Shopping Cart','购物车','ဈေးခြင်းတောင်း'],
  'メニューを検索... (例：寿司、ビール、デザート)': ['Search menu... (e.g. sushi, beer, dessert)','搜索菜单...（例：寿司、啤酒、甜点）','မီနူး ရှာရန်... (ဥပမာ - မုန့်ဟင်းခါး၊ လက်ဖက်ရည်)'],
  '全メニュー': ['All Menu','全部菜单','မီနူးအားလုံး'],
  ' メニュー': [' Menu',' 菜单',' မီနူး'],
  'とりあえず注文': ['Starters','先点这些','အစပြုဟင်းလျာ'],
  'お寿司': ['Sushi','寿司','ဆူရှီ'],
  '焼き物': ['Grilled','烧烤','အကင်'],
  '揚げ物': ['Fried','炸物','အကြော်'],
  'ドリンク': ['Drinks','饮品','သောက်စရာ'],
  'デザート': ['Desserts','甜点','အချိုပွဲ'],
  // badges
  '人気': ['Popular','人气','လူကြိုက်များ'],
  'おすすめ': ['Recommended','推荐','အကြံပြု'],
  '期間限定': ['Limited','限时','ကာလအကန့်အသတ်'],
  '無料': ['Free','免费','အခမဲ့'],
  '以前に注文': ['Ordered before','之前点过','ယခင်က မှာဖူး'],
  'お気に入り': ['Favorite','收藏','အကြိုက်ဆုံး'],
  // wait time
  'ご注文数に応じて更新されます': ['Updated based on current orders','根据订单量更新','အော်ဒါအရေအတွက်အလိုက် ပြောင်းလဲပါသည်'],
  '少なめのご注文なら比較的スムーズです': ['Smaller orders are usually quicker','少量点餐通常更快','အော်ဒါနည်းလျှင် ပိုမြန်ပါသည်'],
  // menu / cart
  '＋ カートに追加': ['+ Add to cart','＋ 加入购物车','＋ ခြင်းတောင်းထဲထည့်ရန်'],
  'カートに追加': ['Add to cart','加入购物车','ခြင်းတောင်းထဲထည့်ရန်'],
  'カートに追加しました！': ['Added to cart!','已加入购物车！','ခြင်းတောင်းထဲ ထည့်ပြီးပါပြီ!'],
  'カートを見る': ['View cart','查看购物车','ခြင်းတောင်းကြည့်ရန်'],
  'ぜひお楽しみください！': ['Enjoy!','请慢慢享用！','အရသာရှိရှိ သုံးဆောင်ပါ!'],
  'を追加しました！🎉': [' added! 🎉',' 已添加！🎉',' ကို ထည့်ပြီးပါပြီ!🎉'],
  'セット追加完了！🎊': ['Set added! 🎊','套餐已添加！🎊','ဆက်တ် ထည့်ပြီးပါပြီ!🎊'],
  'まだ何も入っていません': ['Your cart is empty','购物车是空的','ခြင်းတောင်းထဲ ဘာမှမရှိသေးပါ'],
  'メニューをタップして注文しましょう！': ['Tap a menu item to order!','点击菜单开始点餐吧！','မီနူးကို နှိပ်ပြီး မှာယူပါ!'],
  '合計金額': ['Total','合计金额','စုစုပေါင်း ငွေပမာဏ'],
  '合計': ['Total','合计','စုစုပေါင်း'],
  '✓ 注文を確定する': ['✓ Place order','✓ 确认下单','✓ အော်ဒါ အတည်ပြုရန်'],
  'スタッフを呼ぶ': ['Call staff','呼叫服务员','ဝန်ထမ်း ခေါ်ရန်'],
  '見つかりませんでした。': ['No results found.','未找到结果。','ရှာမတွေ့ပါ။'],
  '別のキーワードで試してみてください！': ['Try a different keyword!','请尝试其他关键词！','တခြားစကားလုံးဖြင့် ထပ်ရှာကြည့်ပါ!'],
  '特別なリクエスト、アレルギーなど...': ['Special requests, allergies...','特殊要求、过敏等...','အထူးတောင်းဆိုချက်၊ ဓာတ်မတည့်မှု စသည်...'],
  'キャンセル': ['Cancel','取消','မလုပ်တော့ပါ'],
  '確認': ['Confirm','确认','အတည်ပြုရန်'],
  'ご注文の確認': ['Confirm order','确认订单','အော်ဒါ အတည်ပြုခြင်း'],
  'ご注文を確定しますか？': ['Place this order?','确定下单吗？','ဒီအော်ဒါကို အတည်ပြုမလား?'],
  '読み込み中...': ['Loading...','加载中...','ဖွင့်နေသည်...'],
  // floating buttons
  'スタッフ相談': ['Ask staff','咨询服务员','ဝန်ထမ်းနှင့် တိုင်ပင်ရန်'],
  'おまかせ': ['Surprise me','随机推荐','ကံစမ်းရွေးမယ်'],
  '注文履歴・会計': ['Order history & bill','订单记录・结账','အော်ဒါမှတ်တမ်း / ငွေရှင်းရန်'],
  '注文履歴': ['Order history','订单记录','အော်ဒါမှတ်တမ်း'],
  // call staff
  '何が必要ですか？すぐにスタッフが参ります！': ['What do you need? Staff will be right there!','需要什么？服务员马上就来！','ဘာလိုအပ်ပါသလဲ? ဝန်ထမ်း ချက်ချင်း လာပါမည်!'],
  'お水': ['Water','水','ရေ'],
  'お皿': ['Plates','盘子','ပန်းကန်'],
  'おしぼり': ['Towel','湿毛巾','လက်သုတ်ပဝါ'],
  'お手伝い': ['Help','帮助','အကူအညီ'],
  ' のご依頼を承りました！': [' request received!',' 请求已收到！',' တောင်းဆိုချက်ကို လက်ခံရရှိပါပြီ!'],
  'スタッフが向かっています...': ['Staff is on the way...','服务员正在赶来...','ဝန်ထမ်း လာနေပါပြီ...'],
  'もうすぐスタッフが参ります！': ['Staff will arrive shortly!','服务员马上就到！','ဝန်ထမ်း မကြာမီ ရောက်ပါမည်!'],
  'スタッフが到着しました！🙋': ['Staff has arrived! 🙋','服务员已到达！🙋','ဝန်ထမ်း ရောက်ပါပြီ!🙋'],
  // combo
  '🍱 セットメニューはいかが？': ['🍱 How about a set?','🍱 来个套餐吗？','🍱 ဆက်တ်မီနူး ယူမလား?'],
  '一緒に注文してお得にしよう！': ['Order together and enjoy more!','一起点更划算！','အတူတူမှာပြီး ပိုတန်အောင် လုပ်ပါ!'],
  '✨ こちらも一緒にどうぞ...': ['✨ Goes well with...','✨ 也推荐搭配...','✨ ဒါနဲ့လည်း လိုက်ဖက်ပါတယ်...'],
  'おすすめの組み合わせ': ['Recommended pairings','推荐搭配','အကြံပြု တွဲဖက်မှုများ'],
  'いいえ、結構です': ['No, thanks','不用了，谢谢','မလိုပါ၊ ကျေးဇူးပါ'],
  '＋ 注文に追加': ['+ Add to order','＋ 加入订单','＋ အော်ဒါထဲ ထည့်ရန်'],
  '素敵な組み合わせですね♪': ['Great combination!','很棒的搭配！','တွဲဖက်မှု ကောင်းလိုက်တာ♪'],
  // stamp card
  'スタンプ': ['Stamps','印章','တံဆိပ်'],
  'スタンプ達成！': ['Stamp card complete!','集章完成！','တံဆိပ် ပြည့်ပါပြီ!'],
  '5回ご注文ありがとうございます！': ['Thanks for ordering 5 times!','感谢您5次点餐！','၅ ကြိမ် မှာယူပေးလို့ ကျေးဇူးတင်ပါတယ်!'],
  'スタッフにこの画面をお見せください✨': ['Please show this screen to staff ✨','请向服务员出示此画面✨','ဒီစခရင်ကို ဝန်ထမ်းကို ပြပါ✨'],
  '✓ 閉じる': ['✓ Close','✓ 关闭','✓ ပိတ်ရန်'],
  '閉じる': ['Close','关闭','ပိတ်ရန်'],
  // last order
  '⏰ ラストオーダーが近づいています。気になる一品は今のうちにどうぞ': ['⏰ Last order is coming up. Order anything you\'d like now!','⏰ 即将最后点餐，想吃的请尽快点！','⏰ နောက်ဆုံးအော်ဒါ အချိန် နီးလာပါပြီ။ စားချင်တာရှိရင် အခုပဲ မှာပါ'],
  // chat
  'まだメッセージはありません': ['No messages yet','暂无消息','မက်ဆေ့ချ် မရှိသေးပါ'],
  '例: おすすめのドリンクはありますか？': ['e.g. Any drink recommendations?','例：有推荐的饮品吗？','ဥပမာ - အကြံပြုချင်တဲ့ သောက်စရာ ရှိလား?'],
  '送信': ['Send','发送','ပို့ရန်'],
  'スタッフからメッセージ': ['Message from staff','来自服务员的消息','ဝန်ထမ်းထံမှ မက်ဆေ့ချ်'],
  'ご相談があればこちらからどうぞ。おすすめやアレルギー確認も承ります。': ['Ask us anything here — recommendations, allergy checks and more.','有任何问题请在这里咨询，推荐或过敏确认都可以。','မေးချင်တာရှိရင် ဒီမှာ မေးပါ။ အကြံပြုချက်နဲ့ ဓာတ်မတည့်မှု စစ်ဆေးခြင်းလည်း ကူညီပေးပါတယ်။'],
  'ありがとうございます。スタッフが対応いたします。': ['Thank you. A staff member will help you.','谢谢，服务员会为您处理。','ကျေးဇူးတင်ပါတယ်။ ဝန်ထမ်းက ကူညီပေးပါမယ်။'],
  'スタッフをお呼びしました。内容を確認して向かいます。': ['We\'ve called staff. They\'ll be right with you.','已呼叫服务员，马上为您服务。','ဝန်ထမ်းကို ခေါ်ပြီးပါပြီ။ မကြာမီ လာပါမယ်။'],
  '申し訳ございません。ただいま接続中です。スタッフを呼んでください。': ['Sorry, we\'re reconnecting. Please call staff.','抱歉，正在连接中，请呼叫服务员。','တောင်းပန်ပါတယ်။ ချိတ်ဆက်နေဆဲဖြစ်လို့ ဝန်ထမ်းကို ခေါ်ပေးပါ။'],
  // lucky
  '今日のラッキーセレクト': ['Today\'s lucky pick','今日幸运推荐','ဒီနေ့ရဲ့ ကံကောင်းရွေးချယ်မှု'],
  'おすすめメニュー': ['Recommended dish','推荐菜品','အကြံပြုဟင်းလျာ'],
  'これにする': ['I\'ll take it','就要这个','ဒါယူမယ်'],
  'また今度': ['Maybe later','下次吧','နောက်မှ'],
  // receipt
  'まだ注文がありません': ['No orders yet','暂无订单','အော်ဒါ မရှိသေးပါ'],
  '人数で割り勘': ['Split the bill','分摊账单','လူအရေအတွက်နဲ့ ခွဲပေးရန်'],
  '人で': ['people','人分摊','ယောက်'],
  '/ 人': ['/ person','/ 人','/ တစ်ယောက်'],
  'データの取得に失敗しました': ['Failed to load data','数据加载失败','ဒေတာ ရယူ၍ မရပါ'],
  // toasts & feedback
  'ご注文ありがとうございます！少々お待ちください🎉': ['Thank you for your order! Please wait a moment 🎉','感谢您的点餐！请稍候🎉','မှာယူပေးလို့ ကျေးဇူးတင်ပါတယ်! ခဏစောင့်ပေးပါ🎉'],
  '申し訳ございません。追加できませんでした。': ['Sorry, could not add the item.','抱歉，无法添加。','တောင်းပန်ပါတယ်။ ထည့်လို့ မရပါ။'],
  '申し訳ございません。もう一度お試しください。': ['Sorry, please try again.','抱歉，请重试。','တောင်းပန်ပါတယ်။ ထပ်ကြိုးစားကြည့်ပါ။'],
  '申し訳ございません。更新できませんでした。': ['Sorry, could not update.','抱歉，无法更新。','တောင်းပန်ပါတယ်။ ပြင်လို့ မရပါ။'],
  'お気に入りから外しました': ['Removed from favorites','已取消收藏','အကြိုက်ဆုံးထဲက ဖယ်လိုက်ပါပြီ'],
  '❤️ お気に入りに追加しました！': ['❤️ Added to favorites!','❤️ 已加入收藏！','❤️ အကြိုက်ဆုံးထဲ ထည့်ပြီးပါပြီ!'],
  '表示中のメニューがありません。検索をクリアしてお試しください。': ['No items shown. Clear the search and try again.','没有显示的菜单，请清除搜索后重试。','ပြသနေတဲ့ မီနူး မရှိပါ။ ရှာဖွေမှုကို ဖျက်ပြီး ထပ်ကြည့်ပါ။'],
  'ご注文ありがとうございます。できあがりをお楽しみに。': ['Thank you! Your food is on its way.','谢谢！请期待您的美食。','မှာယူပေးလို့ ကျေးဇူးတင်ပါတယ်။ အဆင်သင့်ဖြစ်တာကို စောင့်မျှော်ပါ။'],
  '人気の一品ですね。良いお選びです。': ['A popular choice — great pick!','人气菜品，好眼光！','လူကြိုက်များတဲ့ ဟင်းလျာပါ။ ရွေးချယ်မှု ကောင်းပါတယ်။'],
  'こちら、相性の良いセットもございます。': ['We also have sets that go well with this.','还有很搭配的套餐哦。','ဒါနဲ့ လိုက်ဖက်တဲ့ ဆက်တ်လည်း ရှိပါတယ်။'],
  'ご注文を承りました。少々お待ちください。': ['Order received. Please wait a moment.','已收到订单，请稍候。','အော်ဒါ လက်ခံရရှိပါပြီ။ ခဏစောင့်ပေးပါ။'],
  'ありがとうございます。順番にご用意いたします。': ['Thank you. We\'ll prepare it in order.','谢谢，我们会按顺序准备。','ကျေးဇူးတင်ပါတယ်။ အစဉ်လိုက် ပြင်ဆင်ပေးပါမယ်။'],
  'ただいま厨房へ共有しました。': ['Sent to the kitchen.','已通知厨房。','မီးဖိုချောင်ကို ပို့ပြီးပါပြီ။'],
  '気になるメニューは見つかりましたか。': ['Found something you like?','找到想吃的了吗？','စားချင်တာ တွေ့ပြီလား။'],
  'お好みがあればおすすめもご案内できます。': ['Ask us for recommendations anytime.','可以为您推荐哦。','ကြိုက်တာ ပြောပြရင် အကြံပြုပေးနိုင်ပါတယ်။'],
  '迷ったらおまかせボタンもどうぞ。': ['Can\'t decide? Try "Surprise me".','拿不定主意？试试随机推荐。','မဆုံးဖြတ်နိုင်ရင် "ကံစမ်းရွေးမယ်" ကို နှိပ်ကြည့်ပါ။'],
  'スタッフへお声がけしました。': ['We\'ve notified staff.','已通知服务员。','ဝန်ထမ်းကို အကြောင်းကြားပြီးပါပြီ။'],
  'まもなくスタッフが参ります。': ['Staff will be with you shortly.','服务员马上就来。','ဝန်ထမ်း မကြာမီ ရောက်ပါမယ်။'],
  'ご用件をしっかりお伝えしますね。': ['We\'ll pass on your request.','会转达您的需求。','တောင်းဆိုချက်ကို သေချာ ပြောပေးပါမယ်။'],
  '今日の運試しメニューです。': ['Here\'s today\'s lucky dish.','这是今天的幸运菜品。','ဒီနေ့ရဲ့ ကံစမ်းဟင်းလျာပါ။'],
  '新しいお気に入りに出会えるかもしれません。': ['You might find a new favorite.','也许能遇到新的最爱。','အကြိုက်ဆုံး အသစ်တစ်ခု တွေ့နိုင်ပါတယ်။'],
  '直感で選ぶのも楽しいですよ。': ['Going with your gut is fun too.','凭直觉选也很有趣。','စိတ်ထဲရှိတာ ရွေးတာလည်း ပျော်စရာပါ။'],
  'ありがとうございます！': ['Thank you!','谢谢！','ကျေးဇူးတင်ပါတယ်!'],
  '美味しそうでございます！': ['Looks delicious!','看起来很好吃！','စားလို့ကောင်းမယ့်ပုံပါ!'],
  '素晴らしいお選びです！': ['Excellent choice!','好眼光！','ရွေးချယ်မှု ကောင်းပါတယ်!'],
  'こちらはお気に入りに追加されましたね！素敵なお選びです！': ['Added to your favorites — lovely choice!','已加入收藏，好选择！','အကြိုက်ဆုံးထဲ ထည့်ပြီးပါပြီ! ရွေးချယ်မှု ကောင်းပါတယ်!']
};
const I18N_RULES = [
  [/ただいまの目安:\s*(\d+)〜(\d+)分/, ['Estimated wait: $1–$2 min','预计等待：$1–$2 分钟','ခန့်မှန်း စောင့်ချိန် - $1–$2 မိနစ်']],
  [/カート\s*(\d+)点。混雑状況を反映しています/, ['$1 item(s) in cart · reflects current traffic','购物车 $1 件 · 已反映拥挤情况','ခြင်းတောင်းထဲ $1 ခု · လူစည်မှုအလိုက်']],
  [/あと\s*(\d+)\s*秒/, ['$1s left','还剩 $1 秒','$1 စက္ကန့် ကျန်']],
  [/👔 スタッフ:/, ['👔 Staff:','👔 服务员：','👔 ဝန်ထမ်း -']]
];
const I18N_KEYS = Object.keys(I18N_DICT).sort((a,b) => b.length - a.length);
const I18N_NAME_SEL = '.item-name,.cart-item-name,.receipt-item-name,#modalTitle,#comboAddedName,.combo-suggest-name,#lucky-reveal-name';
const I18N_SKIP_SEL = 'script,style,textarea,#langSwitcher';
const i18nOrig = new WeakMap(); // node -> {src, out}
let i18nBusy = false;

function I18N_T(ja){
  const idx = currentLang === 'en' ? 0 : currentLang === 'zh' ? 1 : currentLang === 'my' ? 2 : -1;
  if (idx < 0 || !I18N_DICT[ja]) return ja;
  return I18N_DICT[ja][idx];
}
function i18nNameMap(){
  const tr = menuTranslCache[currentLang];
  const map = {};
  if (tr) ALL_ITEMS.forEach(it => { if (tr[it.id] && tr[it.id].name) map[it.name] = tr[it.id].name; });
  return map;
}
function i18nPhrase(text, exactOnly){
  if (currentLang === 'ja') return text;
  const idx = currentLang === 'en' ? 0 : currentLang === 'zh' ? 1 : 2;
  const trimmed = text.trim();
  if (I18N_DICT[trimmed]) return text.replace(trimmed, I18N_DICT[trimmed][idx]);
  if (exactOnly) return text;
  let out = text;
  for (const [re, rep] of I18N_RULES) out = out.replace(re, rep[idx]);
  for (const k of I18N_KEYS) {
    if (k.length < 2) continue;
    if (out.includes(k)) out = out.split(k).join(I18N_DICT[k][idx]);
  }
  return out;
}
function i18nNode(node, names){
  const el = node.parentElement;
  if (!el || el.closest(I18N_SKIP_SEL)) return;
  let rec = i18nOrig.get(node);
  // the app changed the text since we last touched it -> new source
  if (!rec || node.nodeValue !== rec.out) rec = { src: node.nodeValue };
  let out;
  if (currentLang === 'ja') out = rec.src;
  else if (el.closest(I18N_NAME_SEL)) out = names[rec.src.trim()] ? rec.src.replace(rec.src.trim(), names[rec.src.trim()]) : rec.src;
  else out = i18nPhrase(rec.src, !!el.closest('#chat-messages'));
  rec.out = out;
  i18nOrig.set(node, rec);
  if (node.nodeValue !== out) node.nodeValue = out;
}
function i18nAttr(el, attr){
  const key = 'i18n' + attr;
  if (el.dataset[key] === undefined || el.getAttribute(attr) !== el.dataset[key + 'Out']) el.dataset[key] = el.getAttribute(attr);
  const out = currentLang === 'ja' ? el.dataset[key] : i18nPhrase(el.dataset[key], false);
  el.dataset[key + 'Out'] = out;
  el.setAttribute(attr, out);
}
function applyUITranslation(root){
  if (i18nBusy) return;
  i18nBusy = true;
  try {
    root = root || document.body;
    const names = i18nNameMap();
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let n; while ((n = walker.nextNode())) { if (n.nodeValue.trim()) i18nNode(n, names); }
    if (root.querySelectorAll) {
      root.querySelectorAll('[placeholder]').forEach(el => i18nAttr(el, 'placeholder'));
      root.querySelectorAll('[title]').forEach(el => i18nAttr(el, 'title'));
    }
    document.documentElement.lang = currentLang === 'zh' ? 'zh-Hans' : currentLang;
  } finally { i18nBusy = false; }
}
new MutationObserver(muts => {
  if (i18nBusy || currentLang === 'ja') return;
  const roots = new Set();
  muts.forEach(m => {
    if (m.type === 'characterData') roots.add(m.target.parentElement);
    else m.addedNodes.forEach(a => roots.add(a.nodeType === 3 ? a.parentElement : a));
  });
  roots.forEach(r => { if (r && r.isConnected) applyUITranslation(r); });
}).observe(document.body, { childList: true, characterData: true, subtree: true });
</script>
</body>
</html>
