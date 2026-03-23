<?php
// Admin Image Manager - Real-time photo upload/delete for menu items
session_start();
require_once 'pdo.php';

// Simple admin auth - check session
$adminPassword = 'admin123'; // Change this password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $adminPassword) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $loginError = 'Wrong password';
    }
}
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin_image.php');
    exit;
}

// Handle image upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin_logged_in'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    if ($_POST['action'] === 'upload' && isset($_FILES['image']) && isset($_POST['item_id'])) {
        $itemId = (int)$_POST['item_id'];
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'File type not allowed. Use: jpg, png, gif, webp']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large. Max 5MB']);
            exit;
        }
        
        $uploadDir = __DIR__ . '/itemImages/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        // Remove old images for this item
        foreach (glob($uploadDir . $itemId . '.*') as $old) {
            unlink($old);
        }
        
        $newFile = $uploadDir . $itemId . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $newFile)) {
            echo json_encode(['success' => true, 'url' => 'itemImages/' . $itemId . '.' . $ext . '?t=' . time()]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete' && isset($_POST['item_id'])) {
        $itemId = (int)$_POST['item_id'];
        $uploadDir = __DIR__ . '/itemImages/';
        $deleted = false;
        foreach (glob($uploadDir . $itemId . '.*') as $file) {
            unlink($file);
            $deleted = true;
        }
        echo json_encode(['success' => true, 'deleted' => $deleted]);
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// Get all menu items for display
$stmt = $pdo->query("SELECT id, name, price, category_id FROM sItem WHERE state = 1 ORDER BY sort_order ASC, id ASC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check which items have images
function getItemImageUrl($itemId) {
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
<title>Admin - Image Manager</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;color:#1a1a2e;min-height:100vh}
.header{background:linear-gradient(135deg,#e63946,#c1121f);color:#fff;padding:16px 24px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:1.3rem;font-weight:700}
.btn-logout{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.4);color:#fff;padding:6px 16px;border-radius:20px;cursor:pointer;font-size:.85rem}
.container{max-width:1200px;margin:24px auto;padding:0 16px}
.login-box{background:#fff;border-radius:16px;padding:40px;max-width:400px;margin:60px auto;box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}
.login-box h2{margin-bottom:24px;color:#e63946}
.input-field{width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:1rem;margin-bottom:16px}
.btn-primary{width:100%;padding:12px;background:#e63946;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer}
.error{color:#e63946;font-size:.9rem;margin-bottom:12px}
.stats{background:#fff;border-radius:12px;padding:16px 24px;margin-bottom:20px;display:flex;gap:24px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.stat{text-align:center}.stat-num{font-size:1.8rem;font-weight:700;color:#e63946}.stat-label{font-size:.8rem;color:#888}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.item-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:transform .2s}
.item-card:hover{transform:translateY(-2px)}
.img-area{position:relative;width:100%;aspect-ratio:1;background:#f5f5f5;display:flex;align-items:center;justify-content:center;overflow:hidden}
.img-area img{width:100%;height:100%;object-fit:cover}
.no-img{color:#ccc;font-size:3rem;flex-direction:column;display:flex;align-items:center;justify-content:center;gap:4px}
.no-img span{font-size:.75rem;color:#bbb}
.delete-img-btn{position:absolute;top:6px;right:6px;background:rgba(220,38,38,.9);color:#fff;border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s}
.img-area:hover .delete-img-btn{opacity:1}
.item-info{padding:12px}
.item-name{font-weight:600;font-size:.9rem;margin-bottom:4px;color:#222}
.item-price{color:#e63946;font-size:.85rem;font-weight:600;margin-bottom:10px}
.upload-area{border:2px dashed #e0e0e0;border-radius:8px;padding:10px;text-align:center;cursor:pointer;transition:all .2s;position:relative}
.upload-area:hover{border-color:#e63946;background:#fff5f5}
.upload-area.dragover{border-color:#e63946;background:#fff5f5}
.upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-label{font-size:.8rem;color:#888;pointer-events:none}
.upload-label span{color:#e63946;font-weight:600}
.status-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px}
.has-img .status-dot{background:#22c55e}
.no-img-dot .status-dot{background:#e5e7eb}
.toast{position:fixed;bottom:24px;right:24px;background:#1a1a2e;color:#fff;padding:12px 20px;border-radius:10px;font-size:.9rem;z-index:9999;opacity:0;transform:translateY(10px);transition:all .3s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.success{background:#166534}
.toast.error{background:#991b1b}
.loading{opacity:.5;pointer-events:none}
</style>
</head>
<body>

<?php if (!isset($_SESSION['admin_logged_in'])): ?>
<div style="background:linear-gradient(135deg,#e63946,#c1121f);min-height:100vh;display:flex;align-items:center;justify-content:center">
  <div class="login-box">
    <h2>Admin Login</h2>
    <p style="color:#666;font-size:.9rem;margin-bottom:20px">Image Manager</p>
    <?php if (isset($loginError)): ?>
    <div class="error"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input class="input-field" type="password" name="password" placeholder="Password" required autofocus>
      <button class="btn-primary" type="submit">Login</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="header">
  <h1>Image Manager</h1>
  <form method="POST" style="display:inline">
    <input type="hidden" name="logout" value="1">
    <button class="btn-logout" type="submit">Logout</button>
  </form>
</div>

<div class="container">
  <div class="stats">
    <?php
      $withImg = 0;
      foreach ($items as $item) { if (getItemImageUrl($item['id'])) $withImg++; }
    ?>
    <div class="stat"><div class="stat-num"><?= count($items) ?></div><div class="stat-label">Total Items</div></div>
    <div class="stat"><div class="stat-num"><?= $withImg ?></div><div class="stat-label">With Photo</div></div>
    <div class="stat"><div class="stat-num"><?= count($items) - $withImg ?></div><div class="stat-label">No Photo</div></div>
  </div>

  <div class="grid">
    <?php foreach ($items as $item):
      $imgUrl = getItemImageUrl($item['id']);
    ?>
    <div class="item-card" id="card_<?= $item['id'] ?>">
      <div class="img-area">
        <?php if ($imgUrl): ?>
        <img id="img_<?= $item['id'] ?>" src="<?= htmlspecialchars($imgUrl) ?>?t=<?= time() ?>" alt="<?= htmlspecialchars($item['name']) ?>">
        <button class="delete-img-btn" onclick="deleteImage(<?= $item['id'] ?>)" title="Delete photo">✕</button>
        <?php else: ?>
        <div class="no-img" id="noimg_<?= $item['id'] ?>">
          <span style="font-size:2.5rem">📷</span>
          <span>No photo</span>
        </div>
        <?php endif; ?>
      </div>
      <div class="item-info">
        <div class="item-name">
          <?php if ($imgUrl): ?>
          <span class="status-dot has-img"></span>
          <?php else: ?>
          <span class="status-dot no-img-dot"></span>
          <?php endif; ?>
          <?= htmlspecialchars($item['name']) ?>
        </div>
        <div class="item-price">¥<?= number_format($item['price']) ?></div>
        <div class="upload-area" id="upload_<?= $item['id'] ?>" 
             ondragover="event.preventDefault();this.classList.add('dragover')"
             ondragleave="this.classList.remove('dragover')"
             ondrop="handleDrop(event,<?= $item['id'] ?>)">
          <input type="file" accept="image/*" onchange="uploadImage(<?= $item['id'] ?>,this.files[0])">
          <div class="upload-label">📁 <span>Click or drag</span> to upload</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
async function uploadImage(itemId, file) {
    if (!file) return;
    const card = document.getElementById('card_' + itemId);
    card.classList.add('loading');
    showToast('Uploading...', '');
    
    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('item_id', itemId);
    fd.append('image', file);
    
    try {
        const res = await fetch('admin_image.php', {method:'POST', body: fd});
        const data = await res.json();
        if (data.success) {
            // Update UI to show new image
            const imgArea = card.querySelector('.img-area');
            imgArea.innerHTML = `
                <img id="img_${itemId}" src="${data.url}" alt="">
                <button class="delete-img-btn" onclick="deleteImage(${itemId})" title="Delete photo">✕</button>
            `;
            showToast('Photo uploaded!', 'success');
        } else {
            showToast(data.error || 'Upload failed', 'error');
        }
    } catch(e) {
        showToast('Upload failed', 'error');
    } finally {
        card.classList.remove('loading');
    }
}

async function deleteImage(itemId) {
    if (!confirm('Delete this photo?')) return;
    const card = document.getElementById('card_' + itemId);
    card.classList.add('loading');
    
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('item_id', itemId);
    
    try {
        const res = await fetch('admin_image.php', {method:'POST', body: fd});
        const data = await res.json();
        if (data.success) {
            const imgArea = card.querySelector('.img-area');
            imgArea.innerHTML = `
                <div class="no-img" id="noimg_${itemId}">
                    <span style="font-size:2.5rem">📷</span>
                    <span>No photo</span>
                </div>
            `;
            showToast('Photo deleted', 'success');
        }
    } catch(e) {
        showToast('Delete failed', 'error');
    } finally {
        card.classList.remove('loading');
    }
}

function handleDrop(e, itemId) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadImage(itemId, file);
    }
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
<?php endif; ?>
</body>
</html>