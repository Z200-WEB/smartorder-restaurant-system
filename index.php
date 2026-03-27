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
.menu-card{background:var(--surface);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);cursor:pointer;transition:transform var(--transition),box-shadow var(--transition),border-color var(--transition);border:2px solid transparent;position:relative}
.menu-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);border-color:var(--primary-light)}
.item-img-wrap{width:100%;aspect-ratio:1;background:linear-gradient(135deg,#e0fdfa 0%,#ccfbf1 100%);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.item-img-wrap img{width:100%;height:100%;object-fit:cover;transition:transform .4s ease}
.menu-card:hover .item-img-wrap img{transform:scale(1.06)}
.item-img-wrap .placeholder-icon{font-size:3rem;color:#99f6e4}
.item-info{padding:12px}
.item-name{font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:5px;line-height:1.3}
.item-price{font-size:.95rem;font-weight:700;color:var(--primary)}
.item-add-btn{width:100%;padding:8px;background:var(--primary);color:#fff;border:none;font-size:.82rem;font-weight:600;cursor:pointer;transition:background var(--transition);letter-spacing:.02em}
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

/* ââ MASCOT RESPONSIVE ââ */
#waifu{bottom:0!important;left:10px!important;transition:width .3s,height .3s!important}
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
  #waifu{width:120px!important;height:120px!important;left:4px!important}
  #waifu canvas{width:120px!important;height:120px!important}
  #waifu-tips{font-size:.7rem!important;max-width:150px!important;top:-60px!important;padding:7px 10px!important}
}


/* âââââââââââââââââââââââââââââââââââââââ
   RESPONSIVE - Phone, Tablet, Desktop
âââââââââââââââââââââââââââââââââââââââ */

/* ââ TABLET (iPad 481-900px) ââ */
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

/* ââ PHONE (â¤480px) ââ */
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

/* ââ LARGE PHONE landscape / small tablet (481-600px) ââ */
@media(min-width:481px) and (max-width:600px){
  .menu-grid{grid-template-columns:repeat(2,1fr)!important}
}</style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
<div class="toast-container" id="toastContainer"></div>
<div class="confetti-container" id="confettiContainer"></div>
<div class="order-celebrate" id="orderCelebrate">
  <div class="celebrate-card">
    <span class="celebrate-mascot" id="celebrateMascot">ð¼</span>
    <div class="celebrate-text" id="celebrateText">カートに追加しました！</div>
    <div class="celebrate-sub" id="celebrateSub">ぜひお楽しみください！</div>
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
<script src="https://cdn.jsdelivr.net/npm/live2d-widgets@0.9.1/autoload.js"></script>
<script>
// Block widget's own showMessage to prevent unexpected greetings
window.showMessage = function(){};
</script>
<script>
function speakVoicevox(text){
  try{
    const clean=text.replace(/[ï¼ï¼ããï½âªâ¡ðððððâ¨ð½ï¸ð¥³ððððð¨âð³ðð¸]/gu,'').trim();
    if(!clean)return;
    const url='https://api.tts.quest/v3/voicevox/synthesis?text='+encodeURIComponent(clean)+'&speaker=20';
    fetch(url).then(r=>r.json()).then(d=>{
      if(!d||!d.mp3DownloadUrl)return;
      function tryPlay(attempts){
        fetch(d.audioStatusUrl).then(r=>r.json()).then(s=>{
          if(s.isAudioReady){
            if(window._mascotAudio){window._mascotAudio.pause();}
            const a=new Audio(d.mp3DownloadUrl);
            window._mascotAudio=a;
            a.play().catch(()=>{});
          } else if(attempts>0){
            setTimeout(()=>tryPlay(attempts-1),600);
          }
        }).catch(()=>{});
      }
      setTimeout(()=>tryPlay(8),400);
    }).catch(()=>{});
  }catch(e){}
}
</script>
<script>
// Show Japanese welcome message after mascot loads
(function() {
  var welcomeMsgs = [
    'ããã£ãããã¾ãï¼ä»æ¥ã¯ä½ã«ãªããã¾ããï¼',
    'ããã£ãããã¾ãï¼ããã£ãããé¸ã³ãã ããâª',
    'ããã£ãããã¾ãï¼æ¬æ¥ããæ¥åºãããã¨ããããã¾ãï¼',
    'ããã£ãããã¾ãï¼ãå¥½ã¿ã®ã¡ãã¥ã¼ã¯ãããã¾ããï¼',
  ];
  function tryShowWelcome(attempts) {
    var el = document.getElementById('waifu-tips');
    if (el && el.style !== undefined) {
      var msg = welcomeMsgs[Math.floor(Math.random() * welcomeMsgs.length)];
      el.innerHTML = msg; speakVoicevox(msg);
      el.style.opacity = '1';
      el.style.display = 'block';
      setTimeout(function() { el.style.opacity = '0'; }, 8000);
    } else if (attempts > 0) {
      setTimeout(function() { tryShowWelcome(attempts - 1); }, 500);
    }
  }
  setTimeout(function() { tryShowWelcome(10); }, 3000);
})();
</script>
<div class="call-staff-overlay" id="callStaffOverlay">
  <div class="call-staff-box">
    <span class="call-staff-emoji">ð</span>
    <div class="call-staff-title">ã¹ã¿ãããå¼ã¶</div>
    <div class="call-staff-msg">ä½ãå¿è¦ã§ããï¼ããã«ã¹ã¿ãããåãã¾ãï¼</div>
    <div class="call-staff-options">
      <button class="call-option-btn" onclick="selectCallOption(this,'ãæ°´')"><span class="call-option-icon">ð§</span>ãæ°´</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'ãç¿')"><span class="call-option-icon">ð½ï¸</span>ãç¿</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'ããã¼ã')"><span class="call-option-icon">ð§»</span>ããã¼ã</button>
      <button class="call-option-btn" onclick="selectCallOption(this,'ãæä¼ã')"><span class="call-option-icon">â</span>ãæä¼ã</button>
    </div>
    <button class="btn-call-confirm" onclick="sendCallRequest()">ð ã¹ã¿ãããå¼ã¶</button>
    <button class="btn-call-close" onclick="closeCallStaff()">ã­ã£ã³ã»ã«</button>
  </div>
</div>
<div class="combo-overlay" id="comboOverlay">
  <div class="combo-box">
    <div class="combo-header">
      <div class="combo-header-title">ð± ã»ããã¡ãã¥ã¼ã¯ãããï¼</div>
      <div class="combo-header-sub">ä¸ç·ã«æ³¨æãã¦ãå¾ã«ãããï¼</div>
    </div>
    <div class="combo-body">
      <div class="combo-added-item">
        <div class="combo-added-img" id="comboAddedImg"><span style="font-size:1.5rem">ð½ï¸</span></div>
        <div><div class="combo-added-name" id="comboAddedName">Item</div><div class="combo-added-price" id="comboAddedPrice">Â¥0</div></div>
      </div>
      <div class="combo-arrow">â¨ ãã¡ããä¸ç·ã«ã©ãã...</div>
      <div class="combo-suggestion-label">ããããã®çµã¿åãã</div>
      <div class="combo-suggestions" id="comboSuggestions"></div>
    </div>
    <div class="combo-footer">
      <button class="btn-combo-skip" onclick="skipCombo()">ããããçµæ§ã§ã</button>
      <button class="btn-combo-add" id="btnComboAdd" onclick="addComboItem()" disabled>ï¼ æ³¨æã«è¿½å </button>
    </div>
  </div>
</div>
<header class="app-header">
  <div class="header-inner">
    <div class="header-brand">
      <div class="header-logo">ð½ï¸</div>
      <div><div class="header-title">SmartOrder</div><div class="header-table">Table <?php echo (int)$tableNo; ?></div></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      <button onclick="openCallStaff()" style="padding:8px 14px;font-size:.78rem;border-radius:999px;background:rgba(245,158,11,.85);border:1px solid rgba(255,255,255,.3);color:#fff;cursor:pointer;font-weight:700;display:flex;align-items:center;gap:5px;transition:background .2s">ð ã¹ã¿ãã</button>
      <button class="btn-cart-header" onclick="showCart()">ð ã«ã¼ã <span class="cart-badge" id="cartCountBadge"><?php echo $itemCount; ?></span></button>
    </div>
  </div>
</header>
<div class="search-bar-wrap">
  <div class="search-bar-inner">
    <span class="search-icon">ð</span>
    <input type="text" class="search-input" id="searchInput" placeholder="ã¡ãã¥ã¼ãæ¤ç´¢... (ä¾ï¼å¯¿å¸ããã¼ã«ããã¶ã¼ã)" oninput="handleSearch(this.value)">
    <button class="search-clear" id="searchClear" onclick="clearSearch()">â</button>
  </div>
</div>
<nav class="category-nav">
  <div class="category-inner">
    <button class="cat-btn active" onclick="filterCategory('all',this)">ãã¹ã¦</button>
    <?php foreach($categories as $cat): ?>
    <button class="cat-btn" onclick="filterCategory('<?php echo (int)$cat['id']; ?>',this)"><?php echo htmlspecialchars($cat['icon'].' '.$cat['categoryName']); ?></button>
    <?php endforeach; ?>
  </div>
</nav>
<div class="app-layout">
  <section class="menu-section">
    <div class="menu-section-title" id="sectionTitle">å¨ã¡ãã¥ã¼</div>
    <div class="menu-grid" id="menuGrid">
      <?php foreach($items as $item):
        $imgUrl = getItemImage($item['id']);
        $iname  = $item['name'];
        $badge=''; $badgeClass='';
        if(strpos($iname,'ãããã')!==false){$badge='â­ ãããã';$badgeClass='badge-recommended';}
        elseif(strpos($iname,'æééå®')!==false){$badge='â³ æééå®';$badgeClass='badge-limited';}
        elseif($item['price']==0){$badge='ð ç¡æ';$badgeClass='badge-free';}
      ?>
      <div class="menu-card" data-category="<?php echo (int)$item['category']; ?>" data-name="<?php echo htmlspecialchars(mb_strtolower($iname,'UTF-8')); ?>"
           onclick="openOrderModal(<?php echo (int)$item['id']; ?>,'<?php echo addslashes(htmlspecialchars($iname)); ?>',<?php echo (int)$item['price']; ?>,'<?php echo $imgUrl?htmlspecialchars($imgUrl):''; ?>')">
        <?php if($badge): ?><div class="item-badge <?php echo $badgeClass; ?>"><?php echo $badge; ?></div><?php endif; ?>
        <div class="item-img-wrap">
          <?php if($imgUrl): ?><img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($iname); ?>" loading="lazy">
          <?php else: ?><span class="placeholder-icon">ð½ï¸</span><?php endif; ?>
        </div>
        <div class="item-info">
          <div class="item-name"><?php echo htmlspecialchars($iname); ?></div>
          <div class="item-price">Â¥<?php echo number_format($item['price']); ?></div>
        </div>
        <button class="item-add-btn">ï¼ ã«ã¼ãã«è¿½å </button>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="search-no-results" id="searchNoResults"><div style="font-size:3rem;margin-bottom:12px">ð</div><div>è¦ã¤ããã¾ããã§ããã<br><small>å¥ã®ã­ã¼ã¯ã¼ãã§è©¦ãã¦ã¿ã¦ãã ããï¼</small></div></div>
  </section>
  <aside class="cart-sidebar">
    <div class="cart-box">
      <div class="cart-header-bar">ð ã«ã¼ã</div>
      <div class="cart-items" id="cartItemsDesktop">
        <?php if(empty($cartItems)): ?>
        <div class="cart-empty"><div class="cart-empty-icon">ð</div>ã¾ã ä½ãå¥ã£ã¦ãã¾ãã<br><small>ã¡ãã¥ã¼ãã¿ãããã¦æ³¨æãã¾ãããï¼</small></div>
        <?php else: foreach($cartItems as $ci): $ciImg=getItemImage($ci['itemId']); ?>
        <div class="cart-item" id="cartItem_<?php echo (int)$ci['orderId']; ?>">
          <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>ð½ï¸<?php endif; ?></div>
          <div class="cart-item-info">
            <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
            <div class="cart-item-price">Â¥<?php echo number_format($ci['price']); ?> Ã <?php echo (int)$ci['amount']; ?></div>
          </div>
          <div class="cart-item-qty">
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">â</button>
            <span class="qty-num" id="qty_<?php echo (int)$ci['orderId']; ?>"><?php echo (int)$ci['amount']; ?></span>
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">ï¼</button>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
      <div class="cart-footer">
        <div class="cart-total"><span>åè¨</span><span class="cart-total-price" id="totalPrice">Â¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" id="checkoutBtn" <?php echo empty($cartItems)?'disabled':''; ?> onclick="confirmCheckout()">â æ³¨æãç¢ºå®ãã</button>
        <button class="btn-call-staff" onclick="openCallStaff()">ð ã¹ã¿ãããå¼ã¶</button>
      </div>
    </div>
  </aside>
</div>
<button class="float-cart" onclick="showCart()">ð ã«ã¼ã <span class="cart-badge" id="floatCartCount"><?php echo $itemCount; ?></span></button>
<div class="modal-overlay" id="orderModal">
  <div class="modal-box">
    <div class="modal-header-bar" id="modalTitle">Order Item</div>
    <div class="modal-body-inner">
      <div class="modal-item-img" id="modalImgWrap"></div>
      <div class="modal-item-price" id="modalPrice">Â¥0</div>
      <div class="modal-qty-control">
        <button class="modal-qty-btn" onclick="changeModalQty(-1)">â</button>
        <span class="modal-qty-num" id="modalQty">1</span>
        <button class="modal-qty-btn" onclick="changeModalQty(1)">ï¼</button>
      </div>
      <textarea class="modal-notes" id="modalNotes" placeholder="ç¹å¥ãªãªã¯ã¨ã¹ããã¢ã¬ã«ã®ã¼ãªã©..." rows="2"></textarea>
    </div>
    <div class="modal-footer-row">
      <button class="btn-cancel-modal" onclick="closeModal('orderModal')">ã­ã£ã³ã»ã«</button>
      <button class="btn-order-modal" onclick="executeOrder()">ã«ã¼ãã«è¿½å </button>
    </div>
  </div>
</div>
<div class="cart-modal-overlay" id="cartModalOverlay" onclick="closeCartModal()">
  <div class="cart-modal" onclick="event.stopPropagation()">
    <div class="cart-modal-title">ð ã·ã§ããã³ã°ã«ã¼ã</div>
    <div id="cartItemsMobile">
      <?php if(empty($cartItems)): ?>
      <div class="cart-empty"><div class="cart-empty-icon">ð</div>ã¾ã ä½ãå¥ã£ã¦ãã¾ãã</div>
      <?php else: foreach($cartItems as $ci): $ciImg=getItemImage($ci['itemId']); ?>
      <div class="cart-item">
        <div class="cart-item-thumb"><?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>ð½ï¸<?php endif; ?></div>
        <div class="cart-item-info">
          <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
          <div class="cart-item-price">Â¥<?php echo number_format($ci['price']); ?></div>
        </div>
        <div class="cart-item-qty">
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">â</button>
          <span class="qty-num"><?php echo (int)$ci['amount']; ?></span>
          <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">ï¼</button>
        </div>
      </div>
      <?php endforeach; endif; ?>
      <div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">
        <div class="cart-total"><span>合計</span><span class="cart-total-price">Â¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" <?php echo empty($cartItems)?'disabled':''; ?> onclick="closeCartModal();confirmCheckout()">â æ³¨æãç¢ºå®ãã</button>
        <button class="btn-call-staff" onclick="closeCartModal();openCallStaff()">ð ã¹ã¿ãããå¼ã¶</button>
      </div>
    </div>
  </div>
</div>
<div class="confirm-overlay" id="confirmModal">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirmIcon">â ï¸</div>
    <div class="confirm-title" id="confirmTitle">Confirm</div>
    <div class="confirm-message" id="confirmMessage"></div>
    <div class="confirm-buttons">
      <button class="btn-confirm-cancel" onclick="closeModal('confirmModal')">ã­ã£ã³ã»ã«</button>
      <button class="btn-confirm-ok" onclick="confirmAction()">ç¢ºèª</button>
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

/* ââ MASCOT (Live2D widget auto-initialises via CDN) ââ */
// Override Live2D widget tips to react to our app events
function mascotReact(type){
  const msgs={
    add:["ãé¸æãããã¨ããããã¾ãï¼","ãã¡ãäººæ°ã¡ãã¥ã¼ã§ãããã¾ãï¼","ç´ æ´ããããé¸ã³ã§ãã­ï¼","ãã²ãæ¥½ãã¿ãã ããï¼","ããããã§ãããã¾ãï¼"], checkout:["ãæ³¨æãããã¨ããããã¾ãï¼ããã£ããã©ããâª","ãããã¨ããããã¾ãï¼ããã«ãæã¡ãã¾ãï¼","ãæ³¨æç¢ºãã«æ¿ãã¾ããï¼"], search:["ä½ããæ¢ãã§ããããï¼","ãå¥½ã¿ã®ãã®ã¯è¦ã¤ããã¾ãããï¼","ä»ã«ãããããããããã¾ãï¼"], staff:["ã¹ã¿ãããããã«åãã¾ãï¼","å°ããå¾ã¡ãã ããï¼","ãã ãã¾ãå¼ã³ãã¦ããã¾ãï¼"] }; const arr=msgs[type]||msgs.add;
  const msg=arr[Math.floor(Math.random()*arr.length)]; speakVoicevox(msg);
  // Try to use Live2D widget tip system if available, fallback to toast
  showToast('ð¸ '+msg,'success');
}
/* ââ SEARCH ââ */
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

/* ââ CALL STAFF ââ */
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
  showToast('ð ' + req + ' ã®ãä¾é ¼ãæ¿ãã¾ããï¼', 'success');
  mascotReact('staff');
}

/* ââ COMBO ââ */
function getSuggestions(item){
  const n=item.name;
  let pool=[];
  const isDrink=n.includes('ãã¼ã«')||n.includes('ãµã¯ã¼')||n.includes('ãã¤ãã¼ã«')||n.includes('ããªã³ã¯')||n.includes('é£²ã¿æ¾é¡');
  if(!isDrink){
    pool=ALL_ITEMS.filter(i=>i.id!==item.id&&(i.name.includes('ãã¼ã«')||i.name.includes('ãµã¯ã¼')||i.name.includes('ãã¤ãã¼ã«')));
  }
  if(pool.length===0){
    pool=ALL_ITEMS.filter(i=>i.id!==item.id&&(i.name.includes('ãã¶ã¼ã')||i.name.includes('ã¯ããã«')||i.name.includes('ãã³ã±ã¼ã­')||i.name.includes('ã¢ã¤ã¹')||i.name.includes('æè±')));
  }
  if(pool.length===0)pool=ALL_ITEMS.filter(i=>i.id!==item.id).slice(0,4);
  for(let i=pool.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[pool[i],pool[j]]=[pool[j],pool[i]];}
  return pool.slice(0,4);
}
function showCombo(addedItem,suggestions){
  const imgW=document.getElementById('comboAddedImg');
  imgW.innerHTML=addedItem.img?'<img src="'+addedItem.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.5rem">ð½ï¸</span>';
  document.getElementById('comboAddedName').textContent=addedItem.name;
  document.getElementById('comboAddedPrice').textContent='Â¥'+addedItem.price.toLocaleString();
  const grid=document.getElementById('comboSuggestions');
  grid.innerHTML='';selectedComboId=null;
  document.getElementById('btnComboAdd').disabled=true;
  suggestions.forEach(s=>{
    const d=document.createElement('div');
    d.className='combo-suggest-item';
    d.innerHTML='<div class="combo-suggest-img">'+(s.img?'<img src="'+s.img+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:1.3rem">ð½ï¸</span>')+'</div><div class="combo-suggest-name">'+s.name+'</div><div class="combo-suggest-price">Â¥'+s.price.toLocaleString()+'</div>';
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
    if(res.ok){showLoading(false);showCelebration(selectedComboName,true);refreshCart();}
  }catch(e){showToast('ç³ãè¨³ãããã¾ãããè¿½å ã§ãã¾ããã§ããã','error');}
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

/* ââ CELEBRATION ââ */
function showCelebration(name,isCombo){
  const cel=document.getElementById('orderCelebrate');
  const mascots=['ð¼','ð£','ð','ð','ð¥³','ð¨âð³','ð±'];
  document.getElementById('celebrateMascot').textContent=mascots[Math.floor(Math.random()*mascots.length)];
  document.getElementById('celebrateText').textContent=isCombo?'ã»ããè¿½å å®äºï¼ð':name+'ãè¿½å ãã¾ããï¼ð';
  document.getElementById('celebrateSub').textContent=isCombo?'ç´ æµãªçµã¿åããã§ãã­âª':['ç¾å³ãããã§ãããã¾ãï¼','ç´ æ´ããããé¸ã³ã§ãï¼','ãããã¨ããããã¾ãï¼','ãã²ãæ¥½ãã¿ãã ããï¼'][Math.floor(Math.random()*4)];
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

/* ââ CORE ââ */
function filterCategory(catId,btn){
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');clearSearch();
  document.querySelectorAll('.menu-card').forEach(card=>{card.style.display=(catId==='all'||card.dataset.category===catId)?'':'none';});
  document.getElementById('sectionTitle').textContent=catId==='all'?'å¨ã¡ãã¥ã¼':btn.textContent+' ã¡ãã¥ã¼';
}
function openOrderModal(itemId,itemName,itemPrice,imgUrl){
  selectedItemId=itemId;selectedItemName=itemName;selectedItemPrice=itemPrice;selectedItemImg=imgUrl;modalQty=1;
  document.getElementById('modalTitle').textContent=itemName;
  document.getElementById('modalPrice').textContent='Â¥'+itemPrice.toLocaleString();
  document.getElementById('modalQty').textContent='1';
  document.getElementById('modalNotes').value='';
  document.getElementById('modalImgWrap').innerHTML=imgUrl?'<img src="'+imgUrl+'" alt="'+itemName+'" style="width:100%;height:100%;object-fit:cover">':'<span style="font-size:3.5rem">ð½ï¸</span>';
  document.getElementById('orderModal').classList.add('show');
}
function changeModalQty(delta){
  modalQty=Math.max(1,Math.min(99,modalQty+delta));
  document.getElementById('modalQty').textContent=modalQty;
  document.getElementById('modalPrice').textContent='Â¥'+(selectedItemPrice*modalQty).toLocaleString();
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
      else{showCelebration(selectedItemName,false);refreshCart();}
    }else throw new Error('failed');
  }catch(e){showToast('ç³ãè¨³ãããã¾ãããããä¸åº¦ãè©¦ããã ããã','error');showLoading(false);}
}
async function updateQuantity(orderId,delta){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('orderId',orderId);fd.append('change',delta);
    const res=await fetch('cart_update.php',{method:'POST',body:fd});
    if(res.ok)refreshCart();
  }catch(e){showToast('ç³ãè¨³ãããã¾ãããæ´æ°ã§ãã¾ããã§ããã','error');}
  finally{showLoading(false);}
}
function confirmCheckout(){
  document.getElementById('confirmIcon').textContent='â';
  document.getElementById('confirmTitle').textContent='ãæ³¨æã®ç¢ºèª';
  document.getElementById('confirmMessage').textContent='ãæ³¨æãç¢ºå®ãã¾ããï¼';
  pendingAction=executeCheckout;
  document.getElementById('confirmModal').classList.add('show');
}
function confirmAction(){closeModal('confirmModal');if(pendingAction){pendingAction();pendingAction=null;}}
async function executeCheckout(){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('tableNo',TABLE_NO);
    const res=await fetch('checkout.php',{method:'POST',body:fd});
    if(res.ok){mascotReact('checkout');launchConfetti();showToast('ãæ³¨æãããã¨ããããã¾ãï¼å°ããå¾ã¡ãã ããð','success');refreshCart();}
    else throw new Error('failed');
  }catch(e){showToast('ç³ãè¨³ãããã¾ãããããä¸åº¦ãè©¦ããã ããã','error');}
  finally{showLoading(false);}
}
function showCart(){document.getElementById('cartModalOverlay').classList.add('show');document.body.style.overflow='hidden';}
function closeCartModal(){document.getElementById('cartModalOverlay').classList.remove('show');document.body.style.overflow='';}
function showLoading(show){document.getElementById('loadingOverlay').classList.toggle('show',show);}
function showToast(msg,type=''){
  const c=document.getElementById('toastContainer');const t=document.createElement('div');
  t.className='toast'+(type?' '+type:'');t.textContent=msg;c.appendChild(t);setTimeout(()=>t.remove(),3200);
}
/* ââ AJAX CART REFRESH (no page reload) ââ */
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
    if(totalEl) totalEl.textContent = 'Â¥' + (data.total||0).toLocaleString();
    
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
          ? '<div class="cart-empty"><div class="cart-empty-icon">ð</div>ã¾ã ä½ãå¥ã£ã¦ãã¾ãã</div>'
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
    mobileTotals.forEach(el => el.textContent = 'Â¥' + (data.total||0).toLocaleString());
    const mobileCheckout = document.querySelector('.cart-modal .btn-checkout');
    if(mobileCheckout) mobileCheckout.disabled = count === 0;
    
  }catch(e){ console.warn('refreshCart error:', e); }
}

function buildCartItemHTML(ci){
  const img = ci.img ? '<img src="'+ci.img+'" alt="" style="width:100%;height:100%;object-fit:cover">' : 'ð½ï¸';
  return '<div class="cart-item" id="cartItem_'+ci.orderId+'">'
    +'<div class="cart-item-thumb">'+img+'</div>'
    +'<div class="cart-item-info">'
    +'<div class="cart-item-name">'+ci.name+'</div>'
    +'<div class="cart-item-price">Â¥'+Number(ci.price).toLocaleString()+' Ã '+ci.amount+'</div>'
    +'</div>'
    +'<div class="cart-item-qty">'
    +'<button class="qty-btn" onclick="event.stopPropagation();updateQuantity('+ci.orderId+',-1)">â</button>'
    +'<span class="qty-num" id="qty_'+ci.orderId+'">'+ci.amount+'</span>'
    +'<button class="qty-btn" onclick="event.stopPropagation();updateQuantity('+ci.orderId+',1)">ï¼</button>'
    +'</div></div>';
}

function buildCartHTML(items, isMobile){
  if(!items || items.length === 0){
    return '<div class="cart-empty"><div class="cart-empty-icon">ð</div>ã¾ã ä½ãå¥ã£ã¦ãã¾ãã<br><small>ã¡ãã¥ã¼ãã¿ãããã¦æ³¨æãã¾ãããï¼</small></div>';
  }
  return items.map(ci => buildCartItemHTML(ci)).join('');
}

function buildMobileFooter(total, count){
  return '<div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">'
    +'<div class="cart-total"><span>åè¨</span><span class="cart-total-price">Â¥'+(total||0).toLocaleString()+'</span></div>'
    +'<button class="btn-checkout" '+(count===0?'disabled':'')
    +' onclick="closeCartModal();confirmCheckout()">â æ³¨æãç¢ºå®ãã</button>'
    +'<button class="btn-call-staff" onclick="closeCartModal();openCallStaff()">ð ã¹ã¿ãããå¼ã¶</button>'
    +'</div>';
}

/* ââ MASCOT RESPONSIVE RESIZE ââ */
function resizeMascot(){
  const waifu = document.getElementById('waifu');
  if(!waifu) return;
  const w = window.innerWidth;
  let size;
  if(w <= 480) size = 120;
  else if(w <= 900) size = 180;
  else size = 280;
  waifu.style.setProperty('width', size+'px', 'important');
  waifu.style.setProperty('height', size+'px', 'important');
  const canvas = waifu.querySelector('canvas');
  if(canvas){
    canvas.style.setProperty('width', size+'px', 'important');
    canvas.style.setProperty('height', size+'px', 'important');
  }
}
// Apply on load and resize
window.addEventListener('resize', resizeMascot);
// Apply after widget loads (it takes a moment)
setTimeout(resizeMascot, 1000);
setTimeout(resizeMascot, 2500);
setTimeout(resizeMascot, 4000);

document.getElementById('orderModal').addEventListener('click',function(e){if(e.target===this)closeModal('orderModal');});
document.getElementById('confirmModal').addEventListener('click',function(e){if(e.target===this)closeModal('confirmModal');});
document.getElementById('callStaffOverlay').addEventListener('click',function(e){if(e.target===this)closeCallStaff();});
document.getElementById('comboOverlay').addEventListener('click',function(e){if(e.target===this)skipCombo();});
</script>
</body>
</html>