<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'pdo.php';

$tableNo = isset($_GET['tableNo']) ? (int)$_GET['tableNo'] : 1;

// カテゴリ取得
$sqlCategory = "SELECT * FROM sCategory WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtCategory = $pdo->prepare($sqlCategory);
$stmtCategory->execute();
$categories = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

// 商品取得
$sqlItem = "SELECT * FROM sItem WHERE state = 1 ORDER BY sort_order ASC, id ASC";
$stmtItem = $pdo->prepare($sqlItem);
$stmtItem->execute();
$items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);

// 現在の注文内容取得（カート）
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

// Helper: get item image URL if exists
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
    <title>SmartOrder - Table <?php echo (int)$tableNo; ?></title>title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Noto Sans JP',sans-serif;background:#f8f9fa;color:#1a1a2e;min-height:100vh}
    .app-header{position:sticky;top:0;z-index:100;background:linear-gradient(135deg,#e63946,#c1121f);color:#fff;padding:12px 16px;box-shadow:0 2px 12px rgba(0,0,0,.15)}
    .header-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
    .header-title{font-size:1.3rem;font-weight:700}
    .header-table{font-size:.85rem;opacity:.85}
    .btn-header{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:#fff;padding:6px 14px;border-radius:999px;font-size:.8rem;cursor:pointer;transition:background .2s}
    .btn-header:hover{background:rgba(255,255,255,.35)}
    .cart-count{background:#fff;color:#e63946;border-radius:50%;width:18px;height:18px;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
    .category-nav{background:#fff;border-bottom:1px solid #eee;position:sticky;top:56px;z-index:90;overflow-x:auto;scrollbar-width:none}
    .category-nav::-webkit-scrollbar{display:none}
    .category-inner{display:flex;gap:4px;padding:10px 16px;min-width:max-content}
    .cat-btn{padding:6px 16px;border-radius:999px;border:2px solid #e0e0e0;background:#fff;color:#666;font-size:.85rem;cursor:pointer;white-space:nowrap;transition:all .2s}
    .cat-btn:hover{border-color:#e63946;color:#e63946}
    .cat-btn.active{background:#e63946;border-color:#e63946;color:#fff;font-weight:600}
    .app-layout{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1fr 340px;gap:20px;padding:20px 16px}
    @media(max-width:768px){.app-layout{grid-template-columns:1fr}.cart-sidebar{display:none}}
    .menu-section h2{font-size:1.1rem;font-weight:700;margin-bottom:12px;color:#444}
    .menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
    .menu-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 8px rgba(0,0,0,.06);cursor:pointer;transition:transform .2s,box-shadow .2s;border:2px solid transparent}
    .menu-card:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1);border-color:#e63946}
    .item-img-wrap{width:100%;aspect-ratio:1;background:#f5f5f5;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
    .item-img-wrap img{width:100%;height:100%;object-fit:cover}
    .item-img-wrap .placeholder-icon{font-size:3rem;color:#ddd}
    .item-info{padding:10px}
    .item-name{font-size:.85rem;font-weight:600;color:#222;margin-bottom:4px;line-height:1.3}
    .item-price{font-size:.95rem;font-weight:700;color:#e63946}
    .item-add-btn{width:100%;padding:7px;background:#e63946;color:#fff;border:none;font-size:.8rem;font-weight:600;cursor:pointer;transition:background .2s}
    .item-add-btn:hover{background:#c1121f}
    .cart-sidebar{position:sticky;top:120px;height:fit-content}
    .cart-box{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.08);overflow:hidden}
    .cart-header{background:linear-gradient(135deg,#e63946,#c1121f);color:#fff;padding:14px 16px;font-weight:700;font-size:1rem}
    .cart-items{max-height:400px;overflow-y:auto;padding:10px}
    .cart-empty{text-align:center;padding:30px;color:#bbb;font-size:.9rem}
    .cart-item{display:flex;align-items:center;gap:10px;padding:8px;border-radius:8px;margin-bottom:6px;background:#fafafa}
    .cart-item-thumb{width:40px;height:40px;border-radius:8px;overflow:hidden;background:#f5f5f5;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#ccc}
    .cart-item-thumb img{width:100%;height:100%;object-fit:cover}
    .cart-item-info{flex:1}
    .cart-item-name{font-size:.82rem;font-weight:600}
    .cart-item-price{font-size:.78rem;color:#888}
    .cart-item-qty{display:flex;align-items:center;gap:4px}
    .qty-btn{width:24px;height:24px;border-radius:50%;border:1px solid #ddd;background:#fff;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .qty-btn:hover{background:#e63946;color:#fff;border-color:#e63946}
    .qty-num{font-size:.85rem;font-weight:600;min-width:20px;text-align:center}
    .cart-footer{border-top:1px solid #eee;padding:12px 16px}
    .cart-total{display:flex;justify-content:space-between;font-weight:700;font-size:1rem;margin-bottom:10px}
    .cart-total-price{color:#e63946;font-size:1.1rem}
    .btn-checkout{width:100%;padding:12px;background:#e63946;color:#fff;border:none;border-radius:8px;font-size:.95rem;font-weight:700;cursor:pointer;transition:background .2s}
    .btn-checkout:hover{background:#c1121f}
    .btn-checkout:disabled{background:#ccc;cursor:not-allowed}
    .float-cart{display:none;position:fixed;bottom:20px;right:20px;background:#e63946;color:#fff;border:none;border-radius:999px;padding:12px 20px;font-size:.9rem;font-weight:700;cursor:pointer;box-shadow:0 4px 20px rgba(230,57,70,.4);z-index:200;align-items:center;gap:8px}
    @media(max-width:768px){.float-cart{display:flex}}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px}
    .modal-overlay.show{display:flex}
    .modal-box{background:#fff;border-radius:16px;width:100%;max-width:420px;overflow:hidden;animation:slideUp .25s ease}
    @keyframes slideUp{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}
    .modal-header{background:linear-gradient(135deg,#e63946,#c1121f);color:#fff;padding:16px 20px;font-weight:700;font-size:1.05rem}
    .modal-body{padding:20px}
    .modal-item-img{width:100%;aspect-ratio:16/9;background:#f5f5f5;border-radius:8px;overflow:hidden;margin-bottom:14px;display:flex;align-items:center;justify-content:center}
    .modal-item-img img{width:100%;height:100%;object-fit:cover}
    .modal-item-price{font-size:1.2rem;font-weight:700;color:#e63946;margin-bottom:14px}
    .modal-qty-control{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:14px}
    .modal-qty-btn{width:36px;height:36px;border-radius:50%;border:2px solid #e63946;background:#fff;color:#e63946;font-size:1.2rem;cursor:pointer}
    .modal-qty-btn:hover{background:#e63946;color:#fff}
    .modal-qty-num{font-size:1.4rem;font-weight:700;min-width:40px;text-align:center}
    .modal-notes{width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem;font-family:inherit;resize:none;margin-bottom:14px}
    .modal-footer{display:flex;gap:8px;padding:14px 20px;border-top:1px solid #eee}
    .btn-cancel{flex:1;padding:10px;border:1px solid #ddd;background:#fff;border-radius:8px;cursor:pointer;font-size:.9rem}
    .btn-order{flex:2;padding:10px;background:#e63946;color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:700;cursor:pointer}
    .btn-order:hover{background:#c1121f}
    .cart-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000}
    .cart-modal-overlay.show{display:block}
    .cart-modal{position:fixed;bottom:0;left:0;right:0;background:#fff;border-radius:20px 20px 0 0;padding:20px;z-index:1001;max-height:80vh;overflow-y:auto;animation:slideUp .3s ease}
    .confirm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center}
    .confirm-overlay.show{display:flex}
    .confirm-box{background:#fff;border-radius:16px;padding:24px;max-width:340px;width:90%;text-align:center}
    .confirm-icon{font-size:2.5rem;margin-bottom:10px}
    .confirm-title{font-size:1.1rem;font-weight:700;margin-bottom:8px}
    .confirm-message{color:#666;font-size:.9rem;margin-bottom:20px}
    .confirm-buttons{display:flex;gap:10px}
    .btn-confirm-cancel{flex:1;padding:10px;border:1px solid #ddd;background:#fff;border-radius:8px;cursor:pointer}
    .btn-confirm-ok{flex:1;padding:10px;background:#e63946;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600}
    .toast-container{position:fixed;top:80px;right:16px;z-index:3000;display:flex;flex-direction:column;gap:8px}
    .toast{background:#1a1a2e;color:#fff;padding:10px 16px;border-radius:8px;font-size:.85rem;min-width:200px;animation:slideIn .3s ease;box-shadow:0 4px 12px rgba(0,0,0,.15)}
    @keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}
    .toast.success{background:#2d6a4f}
    .toast.error{background:#c1121f}
    .loading-overlay{display:none;position:fixed;inset:0;background:rgba(255,255,255,.8);z-index:9999;align-items:center;justify-content:center}
    .loading-overlay.show{display:flex}
    .spinner{width:40px;height:40px;border:4px solid #eee;border-top-color:#e63946;border-radius:50%;animation:spin .8s linear infinite}
    @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>head>
    <body>

        <div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div>div></div>div>
        <div class="toast-container" id="toastContainer"></div>div>

        <header class="app-header">
            <div class="header-inner">
                <div>
                    <div class="header-title">SmartOrder</div>div>
                    <div class="header-table">Table <?php echo (int)$tableNo; ?></div>div>
                </div>div>
                <div style="display:flex;gap:8px">
                    <button class="btn-header" onclick="showCart()">Cart <span class="cart-count" id="cartCountBadge"><?php echo $itemCount; ?></span></button>button>
                </div>div>
            </div>div>
        </header>header>

        <nav class="category-nav">
            <div class="category-inner">
                <button class="cat-btn active" onclick="filterCategory('all',this)">All</button>button>
                <?php foreach($categories as $cat): ?>
    <button class="cat-btn" onclick="filterCategory('<?php echo (int)$cat['id']; ?>',this)"><?php echo htmlspecialchars($cat['icon'].' '.$cat['categoryName']); ?></button>
                <?php endforeach; ?>
            </div>div>
        </nav>nav>

        <div class="app-layout">
            <section class="menu-section">
                <h2 id="sectionTitle">Menu</h2>h2>
                <div class="menu-grid" id="menuGrid">
                    <?php foreach($items as $item):
        $imgUrl = getItemImage($item['id']);
    ?>
    <div class="menu-card" data-category="<?php echo (int)$item['category']; ?>" onclick="openOrderModal(<?php echo (int)$item['id']; ?>,'<?php echo addslashes(htmlspecialchars($item['name'])); ?>',<?php echo (int)$item['price']; ?>,'<?php echo $imgUrl ? htmlspecialchars($imgUrl) : ''; ?>')">
        <div class="item-img-wrap">
            <?php if ($imgUrl): ?>
        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" loading="lazy">
            <?php else: ?>
        <span class="placeholder-icon">🍽️</span>span>
            <?php endif; ?>
        </div>div>
        <div class="item-info">
            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
            <div class="item-price">&yen;<?php echo number_format($item['price']); ?></div>div>
        </div>div>
        <button class="item-add-btn">+ Order</button>button>
    </div>div>
                    <?php endforeach; ?>
                </div>div>
            </section>section>

            <aside class="cart-sidebar">
                <div class="cart-box">
                    <div class="cart-header">Cart</div>div>
                    <div class="cart-items" id="cartItemsDesktop">
                        <?php if(empty($cartItems)): ?>
    <div class="cart-empty">No items yet</div>div>
                        <?php else: ?>
    <?php foreach($cartItems as $ci): $ciImg = getItemImage($ci['itemId']); ?>
    <div class="cart-item" id="cartItem_<?php echo (int)$ci['orderId']; ?>">
        <div class="cart-item-thumb">
            <?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?>
        </div>div>
        <div class="cart-item-info">
            <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
            <div class="cart-item-price">&yen;<?php echo number_format($ci['price']); ?> x <?php echo (int)$ci['amount']; ?></div>div>
        </div>div>
        <div class="cart-item-qty">
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">-</button>button>
            <span class="qty-num" id="qty_<?php echo (int)$ci['orderId']; ?>"><?php echo (int)$ci['amount']; ?></span>
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">+</button>button>
        </div>div>
    </div>div>
                        <?php endforeach; ?>
    <?php endif; ?>
                    </div>div>
                    <div class="cart-footer">
                        <div class="cart-total"><span>Total</span>span><span class="cart-total-price" id="totalPrice">&yen;<?php echo number_format($currentTotal); ?></span>span></div>div>
                        <button class="btn-checkout" id="checkoutBtn" <?php echo empty($cartItems)?'disabled':''; ?> onclick="confirmCheckout()">Confirm Order</button>button>
                    </div>div>
                </div>div>
            </aside>aside>
        </div>div>

        <button class="float-cart" onclick="showCart()">Cart <span class="cart-count" id="floatCartCount" style="background:#fff;color:#e63946"><?php echo $itemCount; ?></span></button>button>

        <!-- Order Modal -->
        <div class="modal-overlay" id="orderModal">
            <div class="modal-box">
                <div class="modal-header" id="modalTitle">Order</div>div>
                <div class="modal-body">
                    <div class="modal-item-img" id="modalImgWrap"></div>div>
                    <div class="modal-item-price" id="modalPrice">&yen;0</div>div>
                    <div class="modal-qty-control">
                        <button class="modal-qty-btn" onclick="changeModalQty(-1)">-</button>button>
                        <span class="modal-qty-num" id="modalQty">1</span>span>
                        <button class="modal-qty-btn" onclick="changeModalQty(1)">+</button>button>
                    </div>div>
                    <textarea class="modal-notes" id="modalNotes" placeholder="Special requests..." rows="2"></textarea>
                </div>div>
                <div class="modal-footer">
                    <button class="btn-cancel" onclick="closeModal('orderModal')">Cancel</button>button>
                    <button class="btn-order" onclick="executeOrder()">Add to Cart</button>button>
                </div>div>
            </div>div>
        </div>div>

        <!-- Cart Modal (mobile) -->
        <div class="cart-modal-overlay" id="cartModalOverlay" onclick="closeCartModal()">
            <div class="cart-modal" onclick="event.stopPropagation()">
                <div style="font-size:1.1rem;font-weight:700;margin-bottom:14px">Cart</div>div>
                <div id="cartItemsMobile">
                    <?php if(empty($cartItems)): ?>
    <div class="cart-empty">No items yet</div>div>
                    <?php else: ?>
    <?php foreach($cartItems as $ci): $ciImg = getItemImage($ci['itemId']); ?>
    <div class="cart-item">
        <div class="cart-item-thumb">
            <?php if($ciImg): ?><img src="<?php echo htmlspecialchars($ciImg); ?>" alt=""><?php else: ?>🍽️<?php endif; ?>
        </div>div>
        <div class="cart-item-info">
            <div class="cart-item-name"><?php echo htmlspecialchars($ci['name']); ?></div>
            <div class="cart-item-price">&yen;<?php echo number_format($ci['price']); ?></div>div>
        </div>div>
        <div class="cart-item-qty">
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,-1)">-</button>button>
            <span class="qty-num"><?php echo (int)$ci['amount']; ?></span>
            <button class="qty-btn" onclick="event.stopPropagation();updateQuantity(<?php echo (int)$ci['orderId']; ?>,1)">+</button>button>
        </div>div>
    </div>div>
                    <?php endforeach; ?>
    <?php endif; ?>
<div style="margin-top:14px;border-top:1px solid #eee;padding-top:12px">
    <div class="cart-total"><span>Total</span>span><span class="cart-total-price">&yen;<?php echo number_format($currentTotal); ?></span>span></div>div>
    <button class="btn-checkout" <?php echo empty($cartItems)?'disabled':''; ?> onclick="closeCartModal();confirmCheckout()">Confirm Order</button>button>
</div>div>
                </div>div>
            </div>div>

            <!-- Confirm Modal -->
            <div class="confirm-overlay" id="confirmModal">
                <div class="confirm-box">
                    <div class="confirm-icon" id="confirmIcon">&#x26A0;&#xFE0F;</div>div>
                    <div class="confirm-title" id="confirmTitle">Confirm</div>div>
                    <div class="confirm-message" id="confirmMessage"></div>div>
                    <div class="confirm-buttons">
                        <button class="btn-confirm-cancel" onclick="closeModal('confirmModal')">Cancel</button>button>
                        <button class="btn-confirm-ok" onclick="confirmAction()">OK</button>button>
                    </div>div>
                </div>div>
            </div>div>

            <script>
                const TABLE_NO = <?php echo (int)$tableNo; ?>;
                let selectedItemId=null,selectedItemName='',selectedItemPrice=0,selectedItemImg='',modalQty=1,pendingAction=null;

                function filterCategory(catId,btn){
                        document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
                        btn.classList.add('active');
                        document.querySelectorAll('.menu-card').forEach(card=>{
                                    card.style.display=(catId==='all'||card.dataset.category===catId)?'':'none';
                        });
                        document.getElementById('sectionTitle').textContent=catId==='all'?'Menu':btn.textContent;
                }

                function openOrderModal(itemId,itemName,itemPrice,imgUrl){
                        selectedItemId=itemId;selectedItemName=itemName;selectedItemPrice=itemPrice;selectedItemImg=imgUrl;modalQty=1;
                        document.getElementById('modalTitle').textContent=itemName;
                        document.getElementById('modalPrice').textContent='\u00A5'+itemPrice.toLocaleString();
                        document.getElementById('modalQty').textContent='1';
                        document.getElementById('modalNotes').value='';
                        const imgWrap=document.getElementById('modalImgWrap');
                        if(imgUrl){
                                    imgWrap.innerHTML='<img src="'+imgUrl+'" alt="'+itemName+'" style="width:100%;height:100%;object-fit:cover">';
                        } else {
                                    imgWrap.innerHTML='<span style="font-size:3rem;color:#ddd">🍽️</span>';
                        }
                        document.getElementById('orderModal').classList.add('show');
                }

                function changeModalQty(delta){
                        modalQty=Math.max(1,Math.min(99,modalQty+delta));
                        document.getElementById('modalQty').textContent=modalQty;
                        document.getElementById('modalPrice').textContent='\u00A5'+(selectedItemPrice*modalQty).toLocaleString();
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
                                    if(res.ok){showToast(selectedItemName+' ordered','success');setTimeout(()=>location.reload(),500);}
                                    else throw new Error('failed');
                        }catch(e){showToast('Order failed','error');}
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
                        document.getElementById('confirmIcon').textContent='\u2705';
                        document.getElementById('confirmTitle').textContent='Confirm Order';
                        document.getElementById('confirmMessage').textContent='Confirm your order?';
                        pendingAction=executeCheckout;
                        document.getElementById('confirmModal').classList.add('show');
                }

                function confirmAction(){closeModal('confirmModal');if(pendingAction){pendingAction();pendingAction=null;}}

                async function executeCheckout(){
                        showLoading(true);
                        try{
                                    const fd=new FormData();fd.append('tableNo',TABLE_NO);
                                    const res=await fetch('checkout.php',{method:'POST',body:fd});
                                    if(res.ok){showToast('Order confirmed!','success');setTimeout(()=>location.reload(),500);}
                                    else throw new Error('failed');
                        }catch(e){showToast('Checkout failed','error');}
                        finally{showLoading(false);}
                }

                function showCart(){document.getElementById('cartModalOverlay').classList.add('show');document.body.style.overflow='hidden';}
                function closeCartModal(){document.getElementById('cartModalOverlay').classList.remove('show');document.body.style.overflow='';}
                function showLoading(show){document.getElementById('loadingOverlay').classList.toggle('show',show);}
                function showToast(msg,type=''){
                        const c=document.getElementById('toastContainer');
                        const t=document.createElement('div');
                        t.className='toast'+(type?' '+type:'');t.textContent=msg;
                        c.appendChild(t);setTimeout(()=>t.remove(),3000);
                }
                document.getElementById('orderModal').addEventListener('click',function(e){if(e.target===this)closeModal('orderModal');});
                document.getElementById('confirmModal').addEventListener('click',function(e){if(e.target===this)closeModal('confirmModal');});
            </script>
        </div>body>
    </body>html>
            </script>
</style></title>
</head>
