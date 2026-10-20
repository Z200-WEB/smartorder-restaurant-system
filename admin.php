<?php
// UTF-8 ENCODING - MUST BE FIRST!
header('Content-Type: text/html; charset=UTF-8');
// CACHE PREVENTION
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Authentication required
require_once 'auth.php';
requireAuth();
// Load database connection
require_once 'pdo.php';
require_once __DIR__ . '/item_emoji.php';
require_once __DIR__ . '/item_my.php';

// Get categories
$sqlCategory = "SELECT * FROM sCategory ORDER BY id ASC";
$stmtCategory = $pdo->prepare($sqlCategory);
$stmtCategory->execute();
$categories = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
$catIcons = [];
foreach ($categories as $c) { $catIcons[(int)$c['id']] = ($c['icon'] ?? '') ?: '🍽️'; }

// Get items
$sqlItem = "SELECT i.*, c.categoryName FROM sItem i LEFT JOIN sCategory c ON i.category = c.id ORDER BY i.id DESC";
$stmtItem = $pdo->prepare($sqlItem);
$stmtItem->execute();
$items = $stmtItem->fetchAll(PDO::FETCH_ASSOC);

// Generate CSRF token
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="my">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>မီနူး စီမံခန့်ခွဲမှု - SmartOrder</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Noto+Sans+Myanmar:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Design System CSS -->
  <link rel="stylesheet" href="css/design-system.css">
  <link rel="stylesheet" href="css/animations.css">
  <link rel="stylesheet" href="css/admin.css">
  <style>
    /* ========== DELETE CONFIRMATION MODAL ========== */
    .modal-confirm-delete {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,.6);
      backdrop-filter: blur(8px);
      z-index: 10000;
      justify-content: center;
      align-items: center;
    }
    .modal-confirm-delete.active {
      display: flex;
      animation: fadeIn 0.2s ease-out;
    }
    .modal-confirm-content {
      background: white;
      padding: 40px 36px;
      border-radius: 20px;
      width: 90%;
      max-width: 420px;
      box-shadow: 0 24px 64px rgba(0,0,0,.25);
      text-align: center;
      animation: scaleIn 0.28s cubic-bezier(0.34,1.56,0.64,1);
    }
    .confirm-delete-icon {
      width: 76px; height: 76px;
      margin: 0 auto 18px;
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.5em;
      box-shadow: 0 8px 24px rgba(245,158,11,.25);
    }
    .confirm-delete-title {
      font-size: 1.35em;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 10px;
    }
    .confirm-delete-message {
      font-size: 1em;
      color: #475569;
      margin-bottom: 8px;
    }
    .confirm-delete-name {
      font-size: 1.1em;
      font-weight: 700;
      color: #dc2626;
      margin-bottom: 28px;
      background: #fef2f2;
      padding: 8px 16px;
      border-radius: 8px;
      display: inline-block;
    }
    .modal-confirm-buttons {
      display: flex;
      gap: 12px;
    }
    .btn-modal-cancel, .btn-modal-delete {
      flex: 1;
      padding: 13px 24px;
      border: none;
      border-radius: 50px;
      font-size: 1em;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s;
    }
    .btn-modal-cancel {
      background: #e2e8f0;
      color: #475569;
    }
    .btn-modal-cancel:hover {
      background: #cbd5e1;
      transform: translateY(-1px);
    }
    .btn-modal-delete {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      color: white;
      box-shadow: 0 4px 16px rgba(220,38,38,.3);
    }
    .btn-modal-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220,38,38,.4);
    }
    a.btn-delete { text-decoration: none; display: inline-flex; }
  </style>
  <link rel="stylesheet" href="css/modern-admin.css">
</head>
<body class="admin-page">

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-content">
    <div class="loading-spinner"></div>
    <div class="loading-text">လုပ်ဆောင်နေသည်...</div>
  </div>
</div>

<header class="m-topbar"><div class="m-topbar-inner">
  <a class="m-brand" href="management.php"><span class="m-logo">S</span><span><div class="m-brand-name">SmartOrder</div><div class="m-brand-sub">စီမံခန့်ခွဲမှု</div></span></a>
  <nav class="m-nav"><a href="management.php">အော်ဒါ စီမံရန်</a><a href="kitchen.php">မီးဖိုချောင်</a><a href="admin.php" class="active">မီနူး စီမံရန်</a></nav>
  <div class="m-actions"><a class="m-btn-ghost" href="index.php?tableNo=1" target="_blank">ဖောက်သည် စခရင် ↗</a><a class="m-btn-ghost" href="logout.php">ထွက်ရန်</a></div>
</div></header>
<div class="m-page-title"><div><h1>မီနူး စီမံရန်</h1><p>ဟင်းလျာနဲ့ အမျိုးအစားတွေကို စီမံနိုင်ပါတယ်</p></div></div>
<div class="admin-container">

  <!-- ADMIN HEADER -->
  <div class="admin-header">
    <h1>
      <span>⚙️</span>
      <span>မီနူး စီမံခန့်ခွဲမှု - SmartOrder</span>
    </h1>
    <p>ဟင်းလျာနဲ့ အမျိုးအစားတွေကို လွယ်ကူစွာ စီမံနိုင်ပါတယ်</p>
    <div class="admin-header-links">
      <a href="index.php?tableNo=1" class="admin-link">
        <span>←</span>
        <span>ဖောက်သည် စခရင်သို့</span>
      </a>
      <a href="management.php" class="admin-link">
        <span>📊</span>
        <span>အော်ဒါ စီမံရန်</span>
      </a>
      <a href="logout.php" class="admin-link" style="background:rgba(220,38,38,.25);border-color:rgba(220,38,38,.4);">
        <span>🚪</span>
        <span>ထွက်ရန်</span>
      </a>
    </div>
  </div>

  <!-- ADMIN TABS -->
  <div class="admin-tabs">
    <button class="admin-tab active" onclick="switchTab('items', event)">
      <span>📦</span>
      <span>ဟင်းလျာ စီမံရန်</span>
    </button>
    <button class="admin-tab" onclick="switchTab('categories', event)">
      <span>📂</span>
      <span>အမျိုးအစား စီမံရန်</span>
    </button>
  </div>

  <!-- Item Management Tab -->
  <div id="tab-items" class="tab-content active">
    <button class="add-button-large" onclick="openItemModal()">
      <span>➕</span>
      <span>ဟင်းလျာအသစ် ထည့်ရန်</span>
    </button>
    <div class="item-grid">
      <?php foreach ($items as $item): ?>
        <?php
          $imagePath = 'itemImages/' . $item['id'] . '.jpg';
          if (!file_exists($imagePath)) { $imagePath = 'itemImages/default.jpg'; }
          $displayName = str_replace(['[期間限定]', '[おすすめ]'], '', $item['name']);
          $displayName = trim($displayName);
          $hasLimited  = strpos($item['name'], '[期間限定]') !== false;
          $hasRecommend = strpos($item['name'], '[おすすめ]') !== false;
        ?>
        <div class="admin-item-card">
          <?php if (file_exists($imagePath)): ?>
          <img src="<?php echo htmlspecialchars($imagePath); ?>"
               alt="<?php echo htmlspecialchars($displayName); ?>"
               class="admin-item-image">
          <?php else: ?>
          <div class="admin-item-image admin-item-emoji"><?php echo htmlspecialchars(itemEmoji($item['name'], $item['category'], $catIcons)); ?></div>
          <?php endif; ?>
          <div class="admin-item-info">
            <?php $myName = myItemName($item['id'], $displayName); ?>
            <div class="admin-item-name"><?php echo htmlspecialchars($myName); ?></div>
            <?php if ($myName !== $displayName): ?><div class="admin-item-sub">မူရင်းအမည်: <?php echo htmlspecialchars($displayName); ?></div><?php endif; ?>
            <?php if ($hasLimited || $hasRecommend): ?>
            <div class="item-badges">
              <?php if ($hasLimited): ?>
              <span class="badge badge-limited"><span>⏰</span><span>ကာလအကန့်အသတ်</span></span>
              <?php endif; ?>
              <?php if ($hasRecommend): ?>
              <span class="badge badge-recommend"><span>⭐</span><span>အကြံပြု</span></span>
              <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="admin-item-details">
              📂 <?php echo htmlspecialchars(myCategoryName((string)$item['categoryName'])); ?><br>
              💵 <?php echo number_format($item['price']); ?> Ks
            </div>
            <div class="admin-item-actions">
              <button class="btn-edit" onclick='editItem(<?php echo json_encode($item); ?>)'>
                <span>✏️</span> <span>ပြင်ရန်</span>
              </button>
              <a href="#" class="btn-delete"
                 onclick="showDeleteConfirm('item', <?php echo $item['id']; ?>, <?php echo htmlspecialchars(json_encode($displayName), ENT_QUOTES); ?>); return false;">
                <span>🗑️</span> <span>ဖျက်ရန်</span>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Category Management Tab -->
  <div id="tab-categories" class="tab-content">
    <button class="add-button-large" onclick="openCategoryModal()">
      <span>➕</span>
      <span>အမျိုးအစားအသစ် ထည့်ရန်</span>
    </button>
    <div class="category-list">
      <?php foreach ($categories as $cat): ?>
      <div class="category-item">
        <div>
          <strong><?php echo htmlspecialchars($cat['icon'] ?? ''); ?> <?php echo htmlspecialchars(myCategoryName((string)$cat['categoryName'])); ?></strong>
          <span>ID: <?php echo $cat['id']; ?></span>
        </div>
        <div style="display:flex;gap:var(--space-3);">
          <button class="btn-edit" style="padding:var(--space-2) var(--space-5);"
                  onclick='editCategory(<?php echo json_encode($cat); ?>)'>
            <span>✏️</span> <span>ပြင်ရန်</span>
          </button>
          <a href="#" class="btn-delete" style="padding:var(--space-2) var(--space-5);"
             onclick="showDeleteConfirm('category', <?php echo $cat['id']; ?>, <?php echo htmlspecialchars(json_encode($cat['categoryName']), ENT_QUOTES); ?>); return false;">
            <span>🗑️</span> <span>ဖျက်ရန်</span>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div><!-- /admin-container -->

<!-- Item Add/Edit Modal -->
<div id="itemModal" class="modal-form">
  <div class="form-container">
    <h2 id="itemModalTitle">ဟင်းလျာအသစ် ထည့်ရန်</h2>
    <form id="itemForm" action="admin_item_save.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="id" id="itemId">
      <div class="form-group">
        <label>ဟင်းလျာ အမည် *</label>
        <input type="text" name="name" id="itemName" required placeholder="ဥပမာ - ကြက်သားကြော်">
      </div>
      <div class="form-group">
        <label>အမျိုးအစား *</label>
        <select name="category" id="itemCategory" required>
          <option value="">ရွေးချယ်ပါ</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars(myCategoryName((string)$cat['categoryName'])); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>ဈေးနှုန်း (ကျပ်) *</label>
        <input type="number" name="price" id="itemPrice" required min="0" placeholder="ဥပမာ - 3000">
      </div>
      <div class="form-group">
        <label>တံဆိပ် (badge ပြရန်)</label>
        <div style="display:flex;gap:var(--space-5);margin-top:var(--space-2);">
          <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
            <input type="checkbox" name="tag_limited" id="tagLimited">
            <span>⏰ ကာလအကန့်အသတ်</span>
          </label>
          <label style="display:flex;align-items:center;gap:var(--space-2);cursor:pointer;">
            <input type="checkbox" name="tag_recommend" id="tagRecommend">
            <span>⭐ အကြံပြု</span>
          </label>
        </div>
        <p class="text-muted mt-2">အမှန်ခြစ်ရင် ဟင်းလျာပေါ်မှာ badge ပြပါမယ်</p>
      </div>
      <div class="form-group">
        <label>ဟင်းလျာ ဓာတ်ပုံ</label>
        <input type="file" name="image" id="itemImage" accept="image/*" onchange="previewImage(this)">
        <img id="imagePreview" class="image-preview" src="" alt="ကြိုကြည့်ရန်">
        <p class="text-muted mt-2">အကြံပြုချက် - စတုရန်းပုံ (JPG/PNG/WebP)</p>
      </div>
      <div class="form-actions">
        <button type="button" class="btn-cancel" onclick="closeItemModal()">မလုပ်တော့ပါ</button>
        <button type="submit" class="btn-submit">💾 သိမ်းရန်</button>
      </div>
    </form>
  </div>
</div>

<!-- Category Add/Edit Modal -->
<div id="categoryModal" class="modal-form">
  <div class="form-container">
    <h2 id="categoryModalTitle">အမျိုးအစားအသစ် ထည့်ရန်</h2>
    <form id="categoryForm" action="admin_category_save.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <input type="hidden" name="id" id="categoryId">
      <div class="form-group">
        <label>အမျိုးအစား အမည် *</label>
        <input type="text" name="categoryName" id="categoryName" required placeholder="ဥပမာ - သောက်စရာ">
      </div>
      <div class="form-actions">
        <button type="button" class="btn-cancel" onclick="closeCategoryModal()">မလုပ်တော့ပါ</button>
        <button type="submit" class="btn-submit">💾 သိမ်းရန်</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-confirm-delete" id="confirmDeleteModal" onclick="if(event.target===this)closeDeleteConfirmModal();">
  <div class="modal-confirm-content">
    <div class="confirm-delete-icon">⚠️</div>
    <h3 class="confirm-delete-title" id="confirmDeleteTitle">ဖျက်ရန် အတည်ပြုခြင်း</h3>
    <p class="confirm-delete-message" id="confirmDeleteMessage">ဒါကို ဖျက်မလား?</p>
    <p class="confirm-delete-name" id="confirmDeleteItemName">အမည်</p>
    <div class="modal-confirm-buttons">
      <button type="button" class="btn-modal-cancel" onclick="closeDeleteConfirmModal()">မလုပ်တော့ပါ</button>
      <button type="button" class="btn-modal-delete" onclick="executeDelete()">🗑️ ဖျက်ရန်</button>
    </div>
  </div>
</div>

<!-- Hidden form for POST-based deletion -->
<form id="deleteForm" method="POST" style="display:none;">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
  <input type="hidden" name="id" id="deleteFormId" value="">
</form>

<script>
const csrfToken = '<?php echo htmlspecialchars($csrfToken); ?>';

// ========== TOAST NOTIFICATION SYSTEM ==========
function showToast(type, title, message, duration = 4000) {
  const container = document.getElementById('toastContainer');
  const toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  const icons = { success: '✔', error: '✕', warning: '⚠', info: 'ℹ' };
  toast.innerHTML =
    '<div class="toast-icon">' + (icons[type] || 'ℹ') + '</div>' +
    '<div class="toast-content">' +
      '<div class="toast-title">' + title + '</div>' +
      '<div class="toast-message">' + message + '</div>' +
    '</div>' +
    '<button class="toast-close" onclick="closeToast(this)">×</button>';
  container.appendChild(toast);
  setTimeout(() => { closeToast(toast.querySelector('.toast-close')); }, duration);
}

function closeToast(button) {
  const toast = button.closest('.toast');
  toast.classList.add('toast-exit');
  setTimeout(() => { toast.remove(); }, 300);
}

function showLoading() { document.getElementById('loadingOverlay').classList.add('show'); }
function hideLoading() { document.getElementById('loadingOverlay').classList.remove('show'); }

// ========== TAB SWITCHING ==========
function switchTab(tab, e) {
  document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  if (e && e.target) { e.target.closest('.admin-tab').classList.add('active'); }
  document.getElementById('tab-' + tab).classList.add('active');
}

// ========== ITEM MODAL ==========
function openItemModal() {
  document.getElementById('itemModalTitle').textContent = 'ဟင်းလျာအသစ် ထည့်ရန်';
  document.getElementById('itemForm').reset();
  document.getElementById('itemId').value = '';
  document.getElementById('tagLimited').checked = false;
  document.getElementById('tagRecommend').checked = false;
  document.getElementById('imagePreview').classList.remove('show');
  document.getElementById('itemModal').classList.add('active');
}
function closeItemModal() { document.getElementById('itemModal').classList.remove('active'); }

function editItem(item) {
  document.getElementById('itemModalTitle').textContent = 'ဟင်းလျာ ပြင်ရန်';
  document.getElementById('itemId').value = item.id;
  let itemName = item.name;
  const hasLimited  = itemName.includes('[期間限定]');
  const hasRecommend = itemName.includes('[おすすめ]');
  itemName = itemName.replace(/\[期間限定\]/g, '').replace(/\[おすすめ\]/g, '').trim();
  document.getElementById('itemName').value = itemName;
  document.getElementById('itemCategory').value = item.category;
  document.getElementById('itemPrice').value = item.price;
  document.getElementById('tagLimited').checked = hasLimited;
  document.getElementById('tagRecommend').checked = hasRecommend;
  const img = document.getElementById('imagePreview');
  img.src = 'itemImages/' + item.id + '.jpg';
  img.classList.add('show');
  img.onerror = function() { this.src = 'itemImages/default.jpg'; };
  document.getElementById('itemModal').classList.add('active');
}

// ========== CATEGORY MODAL ==========
function openCategoryModal() {
  document.getElementById('categoryModalTitle').textContent = 'အမျိုးအစားအသစ် ထည့်ရန်';
  document.getElementById('categoryForm').reset();
  document.getElementById('categoryId').value = '';
  document.getElementById('categoryModal').classList.add('active');
}
function closeCategoryModal() { document.getElementById('categoryModal').classList.remove('active'); }

function editCategory(cat) {
  document.getElementById('categoryModalTitle').textContent = 'အမျိုးအစား ပြင်ရန်';
  document.getElementById('categoryId').value = cat.id;
  document.getElementById('categoryName').value = cat.categoryName;
  document.getElementById('categoryModal').classList.add('active');
}

// ========== DELETE MODAL ==========
let pendingDeleteType = '';
let pendingDeleteId = 0;

function showDeleteConfirm(type, id, name) {
  pendingDeleteType = type;
  pendingDeleteId = id;
  if (type === 'item') {
    document.getElementById('confirmDeleteTitle').textContent = 'ဒီဟင်းလျာကို ဖျက်မလား?';
    document.getElementById('confirmDeleteMessage').textContent = 'ဒီဟင်းလျာကို ဖျက်ပါမယ်။ ပြန်ယူလို့ မရပါ။';
  } else {
    document.getElementById('confirmDeleteTitle').textContent = 'ဒီအမျိုးအစားကို ဖျက်မလား?';
    document.getElementById('confirmDeleteMessage').textContent = 'ဒီအမျိုးအစားကို ဖျက်ပါမယ်။ ပြန်ယူလို့ မရပါ။';
  }
  document.getElementById('confirmDeleteItemName').textContent = '"' + name + '"';
  document.getElementById('confirmDeleteModal').classList.add('active');
}

function closeDeleteConfirmModal() {
  document.getElementById('confirmDeleteModal').classList.remove('active');
  pendingDeleteType = '';
  pendingDeleteId = 0;
}

function executeDelete() {
  if (pendingDeleteType && pendingDeleteId) {
    const form = document.getElementById('deleteForm');
    document.getElementById('deleteFormId').value = pendingDeleteId;
    form.action = pendingDeleteType === 'item' ? 'admin_item_delete.php' : 'admin_category_delete.php';
    closeDeleteConfirmModal();
    showLoading();
    form.submit();
  }
}

// ========== IMAGE PREVIEW ==========
function previewImage(input) {
  const preview = document.getElementById('imagePreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) { preview.src = e.target.result; preview.classList.add('show'); };
    reader.readAsDataURL(input.files[0]);
  }
}

// ========== CLOSE MODALS ON OUTSIDE CLICK ==========
document.querySelectorAll('.modal-form').forEach(modal => {
  modal.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});

// ========== FORM SUBMISSION WITH TAGS ==========
document.getElementById('itemForm').addEventListener('submit', function(e) {
  const nameInput = document.getElementById('itemName');
  const tagLimited  = document.getElementById('tagLimited').checked;
  const tagRecommend = document.getElementById('tagRecommend').checked;
  let baseName = nameInput.value.replace(/\[期間限定\]/g, '').replace(/\[おすすめ\]/g, '').trim();
  if (tagLimited) baseName += ' [期間限定]';
  if (tagRecommend) baseName += ' [おすすめ]';
  nameInput.value = baseName;
  showLoading();
});

document.getElementById('categoryForm').addEventListener('submit', function() { showLoading(); });

// ========== SUCCESS / ERROR MESSAGES FROM URL ==========
window.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('success')) {
    const successType = urlParams.get('success');
    const messages = {
      item_saved:       ['ဟင်းလျာ သိမ်းပြီးပါပြီ', 'ဟင်းလျာ အချက်အလက်ကို သိမ်းပြီးပါပြီ'],
      item_deleted:     ['ဟင်းလျာ ဖျက်ပြီးပါပြီ', 'ဟင်းလျာကို ဖျက်ပြီးပါပြီ'],
      category_saved:   ['အမျိုးအစား သိမ်းပြီးပါပြီ', 'အမျိုးအစား အချက်အလက်ကို သိမ်းပြီးပါပြီ'],
      category_deleted: ['အမျိုးအစား ဖျက်ပြီးပါပြီ', 'အမျိုးအစားကို ဖျက်ပြီးပါပြီ'],
    };
    const [title, message] = messages[successType] || ['အောင်မြင်ပါပြီ', 'လုပ်ဆောင်ပြီးပါပြီ'];
    showToast('success', title, message);
    window.history.replaceState({}, document.title, window.location.pathname);
  }
  if (urlParams.has('error')) {
    showToast('error', 'အမှား ဖြစ်သွားပါတယ်', decodeURIComponent(urlParams.get('error')));
    window.history.replaceState({}, document.title, window.location.pathname);
  }
});
</script>
</body>
</html>
