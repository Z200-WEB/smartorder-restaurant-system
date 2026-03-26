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
  --primary:#0f766e;
  --primary-dark:#0d6460;
  --primary-light:#14b8a6;
  --primary-glow:rgba(15,118,110,.25);
  --accent:#f59e0b;
  --accent-dark:#d97706;
  --bg:#f0fafa;
  --surface:#ffffff;
  --surface2:#f8fffe;
  --border:#d1faf5;
  --text:#0f172a;
  --text2:#475569;
  --text3:#94a3b8;
  --radius-sm:8px;
  --radius:12px;
  --radius-lg:16px;
  --radius-xl:24px;
  --shadow-sm:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  --shadow:0 4px 16px rgba(15,118,110,.1);
  --shadow-md:0 8px 24px rgba(15,118,110,.15);
  --shadow-lg:0 16px 40px rgba(15,118,110,.2);
  --transition:.2s ease;
}
body{font-family:'Inter','Noto Sans JP',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* HEADER */
.app-header{position:sticky;top:0;z-index:100;background:linear-gradient(135deg,#134e4a 0%,#0f766e 100%);color:#fff;padding:12px 20px;box-shadow:0 2px 16px rgba(0,0,0,.15)}
.header-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.header-brand{display:flex;align-items:center;gap:10px}
.header-logo{width:36px;height:36px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.header-title{font-size:1.15rem;font-weight:800;letter-spacing:-.02em}
.header-table{font-size:.78rem;opacity:.75;margin-top:1px}
.btn-cart-header{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;padding:8px 16px;border-radius:999px;font-size:.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:background var(--transition)}
.btn-cart-header:hover{background:rgba(255,255,255,.25)}
.cart-badge{background:var(--accent);color:#1a1a1a;border-radius:50%;width:20px;height:20px;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center}

/* CATEGORY NAV */
.category-nav{background:var(--surface);border-bottom:2px solid var(--border);position:sticky;top:60px;z-index:90;overflow-x:auto;scrollbar-width:none}
.category-nav::-webkit-scrollbar{display:none}
.category-inner{display:flex;gap:6px;padding:10px 20px;min-width:max-content}
.cat-btn{padding:7px 18px;border-radius:999px;border:2px solid var(--border);background:var(--surface);color:var(--text2);font-size:.83rem;font-weight:500;cursor:pointer;white-space:nowrap;transition:all var(--transition)}
.cat-btn:hover{border-color:var(--primary-light);color:var(--primary)}
.cat-btn.active{background:var(--primary);border-color:var(--primary);color:#fff;font-weight:700;box-shadow:var(--shadow)}

/* LAYOUT */
.app-layout{max-width:1280px;margin:0 auto;display:grid;grid-template-columns:1fr 360px;gap:24px;padding:24px 20px}
@media(max-width:900px){.app-layout{grid-template-columns:1fr}.cart-sidebar{display:none}}

/* MENU SECTION */
.menu-section-title{font-size:1rem;font-weight:700;color:var(--text2);margin-bottom:16px;text-transform:uppercase;letter-spacing:.06em}
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:16px}

/* MENU CARD */
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

/* CART SIDEBAR */
.cart-sidebar{position:sticky;top:120px;height:fit-content}
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

/* FLOAT CART */
.float-cart{display:none;position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#134e4a,var(--primary));color:#fff;border:none;border-radius:999px;padding:14px 22px;font-size:.9rem;font-weight:700;cursor:pointer;box-shadow:0 6px 24px var(--primary-glow);z-index:200;align-items:center;gap:8px;transition:all var(--transition)}
.float-cart:hover{transform:translateY(-2px);box-shadow:0 8px 32px var(--primary-glow)}
@media(max-width:900px){.float-cart{display:flex}}

/* MODAL BASE */
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

/* CART MOBILE MODAL */
.cart-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);z-index:1000}
.cart-modal-overlay.show{display:block}
.cart-modal{position:fixed;bottom:0;left:0;right:0;background:var(--surface);border-radius:24px 24px 0 0;padding:24px;z-index:1001;max-height:82vh;overflow-y:auto;animation:slideUp .3s ease;box-shadow:var(--shadow-lg)}
.cart-modal-title{font-size:1.1rem;font-weight:700;margin-bottom:16px;color:var(--text)}

/* CONFIRM MODAL */
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

/* TOAST */
.toast-container{position:fixed;top:80px;right:20px;z-index:3000;display:flex;flex-direction:column;gap:8px}
.toast{background:var(--text);color:#fff;padding:11px 18px;border-radius:var(--radius);font-size:.85rem;min-width:210px;animation:slideInToast .3s ease;box-shadow:var(--shadow-md)}
@keyframes slideInToast{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
.toast.success{background:#0d6460}
.toast.error{background:#dc2626}

/* LOADING */
.loading-overlay{display:none;position:fixed;inset:0;background:rgba(255,255,255,.8);backdrop-filter:blur(2px);z-index:9999;align-items:center;justify-content:center}
.loading-overlay.show{display:flex}
.spinner{width:42px;height:42px;border:4px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .75s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>
<div class="toast-container" id="toastContainer"></div>

<header class="app-header">
  <div class="header-inner">
    <div class="header-brand">
      <div class="header-logo">🍽️</div>
      <div>
        <div class="header-title">SmartOrder</div>
        <div class="header-table">Table <?php echo (int)$tableNo; ?></div>
      </div>
    </div>
    <button class="btn-cart-header" onclick="showCart()">
      🛒 Cart
      <span class="cart-badge" id="cartCountBadge"><?php echo $itemCount; ?></span>
    </button>
  </div>
</header>

<nav class="category-nav">
  <div class="category-inner">
    <button class="cat-btn active" onclick="filterCategory('all',this)">All</button>
    <?php foreach($categories as $cat): ?>
    <button class="cat-btn" onclick="filterCategory('<?php echo (int)$cat['id']; ?>',this)"><?php echo htmlspecialchars($cat['icon'].' '.$cat['categoryName']); ?></button>
    <?php endforeach; ?>
  </div>
</nav>

<div class="app-layout">
  <section class="menu-section">
    <div class="menu-section-title" id="sectionTitle">All Menu</div>
    <div class="menu-grid" id="menuGrid">
      <?php foreach($items as $item): $imgUrl = getItemImage($item['id']); ?>
      <div class="menu-card" data-category="<?php echo (int)$item['category']; ?>"
           onclick="openOrderModal(<?php echo (int)$item['id']; ?>,'<?php echo addslashes(htmlspecialchars($item['name'])); ?>',<?php echo (int)$item['price']; ?>,'<?php echo $imgUrl ? htmlspecialchars($imgUrl) : ''; ?>')">
        <div class="item-img-wrap">
          <?php if ($imgUrl): ?>
          <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
          <?php else: ?><span class="placeholder-icon">🍽️</span><?php endif; ?>
        </div>
        <div class="item-info">
          <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
          <div class="item-price">¥<?php echo number_format($item['price']); ?></div>
        </div>
        <button class="item-add-btn">＋ Add to Cart</button>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <aside class="cart-sidebar">
    <div class="cart-box">
      <div class="cart-header-bar">🛒 Your Cart</div>
      <div class="cart-items" id="cartItemsDesktop">
        <?php if(empty($cartItems)): ?>
        <div class="cart-empty"><div class="cart-empty-icon">🛒</div>No items yet<br><small>Tap a menu item to start ordering</small></div>
        <?php else: ?>
        <?php foreach($cartItems as $ci): $ciImg = getItemImage($ci['itemId']); ?>
        <div class="cart-item" id="cartItem_<?php echo (int)$ci['orderId']; ?>">
          <div class="cart-item-thumb">
            <?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?>
          </div>
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
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="cart-footer">
        <div class="cart-total"><span>Total</span><span class="cart-total-price" id="totalPrice">¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" id="checkoutBtn" <?php echo empty($cartItems)?'disabled':''; ?> onclick="confirmCheckout()">✓ Confirm Order</button>
      </div>
    </div>
  </aside>
</div>

<button class="float-cart" onclick="showCart()">
  🛒 Cart
  <span class="cart-badge" id="floatCartCount"><?php echo $itemCount; ?></span>
</button>

<!-- Order Modal -->
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
      <textarea class="modal-notes" id="modalNotes" placeholder="Special requests, allergies..." rows="2"></textarea>
    </div>
    <div class="modal-footer-row">
      <button class="btn-cancel-modal" onclick="closeModal('orderModal')">Cancel</button>
      <button class="btn-order-modal" onclick="executeOrder()">Add to Cart</button>
    </div>
  </div>
</div>

<!-- Cart Modal (mobile) -->
<div class="cart-modal-overlay" id="cartModalOverlay" onclick="closeCartModal()">
  <div class="cart-modal" onclick="event.stopPropagation()">
    <div class="cart-modal-title">🛒 Your Cart</div>
    <div id="cartItemsMobile">
      <?php if(empty($cartItems)): ?>
      <div class="cart-empty"><div class="cart-empty-icon">🛒</div>No items yet</div>
      <?php else: ?>
      <?php foreach($cartItems as $ci): $ciImg = getItemImage($ci['itemId']); ?>
      <div class="cart-item">
        <div class="cart-item-thumb">
          <?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?>
        </div>
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
      <?php endforeach; ?>
      <?php endif; ?>
      <div style="margin-top:16px;border-top:2px solid var(--border);padding-top:14px">
        <div class="cart-total"><span>Total</span><span class="cart-total-price">¥<?php echo number_format($currentTotal); ?></span></div>
        <button class="btn-checkout" <?php echo empty($cartItems)?'disabled':''; ?> onclick="closeCartModal();confirmCheckout()">✓ Confirm Order</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="confirm-overlay" id="confirmModal">
  <div class="confirm-box">
    <div class="confirm-icon" id="confirmIcon">⚠️</div>
    <div class="confirm-title" id="confirmTitle">Confirm</div>
    <div class="confirm-message" id="confirmMessage"></div>
    <div class="confirm-buttons">
      <button class="btn-confirm-cancel" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn-confirm-ok" onclick="confirmAction()">OK</button>
    </div>
  </div>
</div>

<script>
const TABLE_NO = <?php echo (int)$tableNo; ?>;
let selectedItemId=null,selectedItemName='',selectedItemPrice=0,selectedItemImg='',modalQty=1,pendingAction=null;

function filterCategory(catId,btn){
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.menu-card').forEach(card=>{
    card.style.display=(catId==='all'||card.dataset.category===catId)?'':'none';
  });
  const label = catId==='all'?'All Menu':btn.textContent+' Menu';
  document.getElementById('sectionTitle').textContent=label;
}

function openOrderModal(itemId,itemName,itemPrice,imgUrl){
  selectedItemId=itemId;selectedItemName=itemName;selectedItemPrice=itemPrice;selectedItemImg=imgUrl;modalQty=1;
  document.getElementById('modalTitle').textContent=itemName;
  document.getElementById('modalPrice').textContent='¥'+itemPrice.toLocaleString();
  document.getElementById('modalQty').textContent='1';
  document.getElementById('modalNotes').value='';
  const imgWrap=document.getElementById('modalImgWrap');
  imgWrap.innerHTML=imgUrl
    ?'<img src="'+imgUrl+'" alt="'+itemName+'" style="width:100%;height:100%;object-fit:cover">'
    :'<span style="font-size:3.5rem">🍽️</span>';
  document.getElementById('orderModal').classList.add('show');
}

function changeModalQty(delta){
  modalQty=Math.max(1,Math.min(99,modalQty+delta));
  document.getElementById('modalQty').textContent=modalQty;
  document.getElementById('modalPrice').textContent='¥'+(selectedItemPrice*modalQty).toLocaleString();
}

function closeModal(id){document.getElementById(id).classList.remove('show')}

async function executeOrder(){
  if(!selectedItemId)return;
  const notes=document.getElementById('modalNotes').value;
  closeModal('orderModal');showLoading(true);
  try{
    const fd=new FormData();
    fd.append('tableNo',TABLE_NO);fd.append('itemId',selectedItemId);
    fd.append('amount',modalQty);if(notes)fd.append('item_notes',notes);
    const res=await fetch('logic.php',{method:'POST',body:fd});
    if(res.ok){showToast(selectedItemName+' added to cart','success');setTimeout(()=>location.reload(),500);}
    else throw new Error('failed');
  }catch(e){showToast('Order failed. Please try again.','error');}
  finally{showLoading(false);}
}

async function updateQuantity(orderId,delta){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('orderId',orderId);fd.append('change',delta);
    const res=await fetch('cart_update.php',{method:'POST',body:fd});
    if(res.ok){setTimeout(()=>location.reload(),300);}
  }catch(e){showToast('Update failed','error');}
  finally{showLoading(false);}
}

function confirmCheckout(){
  document.getElementById('confirmIcon').textContent='✅';
  document.getElementById('confirmTitle').textContent='Confirm Order';
  document.getElementById('confirmMessage').textContent='Ready to place your order?';
  pendingAction=executeCheckout;
  document.getElementById('confirmModal').classList.add('show');
}

function confirmAction(){closeModal('confirmModal');if(pendingAction){pendingAction();pendingAction=null;}}

async function executeCheckout(){
  showLoading(true);
  try{
    const fd=new FormData();fd.append('tableNo',TABLE_NO);
    const res=await fetch('checkout.php',{method:'POST',body:fd});
    if(res.ok){showToast('Order placed successfully! 🎉','success');setTimeout(()=>location.reload(),600);}
    else throw new Error('failed');
  }catch(e){showToast('Checkout failed. Please try again.','error');}
  finally{showLoading(false);}
}

function showCart(){document.getElementById('cartModalOverlay').classList.add('show');document.body.style.overflow='hidden';}
function closeCartModal(){document.getElementById('cartModalOverlay').classList.remove('show');document.body.style.overflow='';}
function showLoading(show){document.getElementById('loadingOverlay').classList.toggle('show',show);}

function showToast(msg,type=''){
  const c=document.getElementById('toastContainer');
  const t=document.createElement('div');
  t.className='toast'+(type?' '+type:'');t.textContent=msg;
  c.appendChild(t);setTimeout(()=>t.remove(),3200);
}

document.getElementById('orderModal').addEventListener('click',function(e){if(e.target===this)closeModal('orderModal');});
document.getElementById('confirmModal').addEventListener('click',function(e){if(e.target===this)closeModal('confirmModal');});
</script>
</body>
</html>
