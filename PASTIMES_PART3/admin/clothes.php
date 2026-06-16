<?php
/**
 * admin/clothes.php
 * Admin CRUD for tblClothes — Add, Edit, Delete clothing items.
 * Part 3 requirement: Admin must be able to manage clothing inventory.
 */

session_start();
require_once '../DBConn.php';

// Admin session guard
if (!isset($_SESSION['adminID'])) {
    header("Location: login.php");
    exit;
}

$errors  = [];
$success = '';
$editItem = null;

// ── DELETE ────────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $delID = (int) $_GET['delete'];
    
    // Check for dependent order line records (foreign key constraint)
    $checkStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tblorderline WHERE clothesID = ?");
    $checkStmt->bind_param("i", $delID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($checkResult['cnt'] > 0) {
        $errors[] = "Cannot delete this item: it has " . $checkResult['cnt'] . " order(s) associated with it. 
                     Please mark it as 'sold' or 'inactive' instead.";
    } else {
        $s = $conn->prepare("DELETE FROM tblClothes WHERE clothesID = ?");
        $s->bind_param("i", $delID);
        if ($s->execute()) {
            $success = "Item deleted successfully.";
        } else {
            $errors[] = "Delete failed: " . $conn->error;
        }
        $s->close();
    }
}

// ── LOAD FOR EDIT ─────────────────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $editID = (int) $_GET['edit'];
    $s = $conn->prepare("SELECT * FROM tblClothes WHERE clothesID = ?");
    $s->bind_param("i", $editID);
    $s->execute();
    $editItem = $s->get_result()->fetch_assoc();
    $s->close();
}

// ── ADD or UPDATE (POST) ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clothesID   = (int)   ($_POST['clothesID']   ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $category    = trim($_POST['category']    ?? '');
    $brand       = trim($_POST['brand']       ?? '');
    $size        = trim($_POST['size']        ?? '');
    $colour      = trim($_POST['colour']      ?? '');
    $condition_  = trim($_POST['condition_']  ?? 'Good');
    $sellPrice   = (float) ($_POST['sellPrice']   ?? 0);
    $retailPrice = ($_POST['retailPrice'] !== '') ? (float)$_POST['retailPrice'] : null;
    $status      = trim($_POST['status']      ?? 'active');

    if (empty($title))    $errors[] = "Title is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if ($sellPrice <= 0)  $errors[] = "Sell price must be greater than 0.";

    // Handle image upload
    $imageFile = trim($_POST['currentImage'] ?? 'placeholder.jpg');
    if (!empty($_FILES['imageFile']['name'])) {
        $ext = strtolower(pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $errors[] = "Image must be JPG, PNG, GIF, or WEBP.";
        } else {
            $newName = 'item_' . time() . '.' . $ext;
            $dest    = '../images/' . $newName;
            if (move_uploaded_file($_FILES['imageFile']['tmp_name'], $dest)) {
                $imageFile = $newName;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }

    if (empty($errors)) {
        if ($clothesID > 0) {
            // UPDATE
            $s = $conn->prepare(
                "UPDATE tblClothes SET title=?, category=?, brand=?, size=?, colour=?,
                 condition_=?, sellPrice=?, retailPrice=?, imageFile=?, status=?
                 WHERE clothesID=?"
            );
            $s->bind_param("ssssssdsssi",
                $title, $category, $brand, $size, $colour,
                $condition_, $sellPrice, $retailPrice, $imageFile, $status, $clothesID
            );
            $s->execute();
            $s->close();
            $success = "Item updated successfully.";
            $editItem = null;
        } else {
            // INSERT
            $s = $conn->prepare(
                "INSERT INTO tblClothes (title, category, brand, size, colour,
                 condition_, sellPrice, retailPrice, imageFile, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $s->bind_param("ssssssddss",
                $title, $category, $brand, $size, $colour,
                $condition_, $sellPrice, $retailPrice, $imageFile, $status
            );
            $s->execute();
            $s->close();
            $success = "New item added successfully.";
        }
    }
}

// ── FETCH ALL CLOTHES ─────────────────────────────────────────────────────────
$result  = $conn->query(
    "SELECT clothesID, title, category, brand, size, condition_, sellPrice, imageFile, status
     FROM tblClothes ORDER BY createdAt DESC"
);
$clothes = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Clothes — Admin | PASTIMES</title>
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --text:#e5e5e5; --muted:#888; --border:#2a2a2a; --radius:8px; --err:#e05252; --ok:#5cb85c; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

  /* Sidebar */
  .sidebar { width:220px; background:#111; border-right:1px solid var(--border); padding:1.5rem 1rem; flex-shrink:0; }
  .sidebar .logo { font-size:1.2rem; color:var(--gold); font-weight:700; margin-bottom:2rem; display:block; text-decoration:none; }
  .sidebar a { display:block; padding:.65rem .9rem; color:var(--muted); text-decoration:none; border-radius:var(--radius); margin-bottom:.3rem; font-size:.9rem; }
  .sidebar a:hover, .sidebar a.active { background:#1f1f1f; color:var(--gold); }
  .sidebar .logout { margin-top:2rem; color:#e05252; }

  /* Main */
  .main { flex:1; padding:2rem; }
  h1 { font-size:1.5rem; color:var(--gold); margin-bottom:1.5rem; }

  .alert { padding:.85rem 1rem; border-radius:var(--radius); margin-bottom:1rem; font-size:.9rem; }
  .alert-ok  { background:#122a12; border-left:4px solid var(--ok); color:#aaffaa; }
  .alert-err { background:#2a1212; border-left:4px solid var(--err); color:#ffaaaa; }

  /* Form */
  .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:2rem; }
  .form-card h2 { color:var(--gold); font-size:1rem; margin-bottom:1.2rem; }
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
  .form-group { display:flex; flex-direction:column; gap:.35rem; }
  .form-group label { font-size:.78rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
  .form-group input, .form-group select { padding:.6rem .85rem; background:#1f1f1f; border:1px solid var(--border); color:var(--text); border-radius:var(--radius); font-size:.92rem; }
  .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--gold); }
  .btn-submit { margin-top:1rem; padding:.65rem 1.8rem; background:var(--gold); color:#111; border:none; border-radius:var(--radius); font-weight:700; cursor:pointer; font-size:.95rem; }
  .btn-cancel { margin-top:1rem; margin-left:.5rem; padding:.65rem 1.2rem; background:transparent; color:var(--muted); border:1px solid var(--border); border-radius:var(--radius); cursor:pointer; font-size:.95rem; text-decoration:none; }

  /* Table */
  .table-wrap { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; font-size:.88rem; }
  th { background:#1f1f1f; padding:.7rem 1rem; text-align:left; color:var(--muted); text-transform:uppercase; font-size:.75rem; letter-spacing:.05em; border-bottom:1px solid var(--border); }
  td { padding:.65rem 1rem; border-bottom:1px solid var(--border); }
  tr:last-child td { border-bottom:none; }
  .btn-edit   { padding:.3rem .75rem; background:transparent; border:1px solid var(--gold); color:var(--gold); border-radius:var(--radius); cursor:pointer; font-size:.8rem; text-decoration:none; }
  .btn-delete { padding:.3rem .75rem; background:transparent; border:1px solid var(--err); color:var(--err); border-radius:var(--radius); cursor:pointer; font-size:.8rem; text-decoration:none; margin-left:.4rem; }
  .badge-active   { background:#122a12; color:#5cb85c; padding:.2rem .55rem; border-radius:20px; font-size:.75rem; }
  .badge-sold     { background:#2a1212; color:#e05252; padding:.2rem .55rem; border-radius:20px; font-size:.75rem; }
  .badge-inactive { background:#2a1a2a; color:#888; padding:.2rem .55rem; border-radius:20px; font-size:.75rem; }

  .thumb { width:45px; height:45px; object-fit:cover; border-radius:4px; background:#1f1f1f; }
</style>
</head>
<body>
<nav class="sidebar">
  <a href="index.php" class="logo">🔐 ADMIN</a>
  <a href="index.php">👥 Users</a>
  <a href="clothes.php" class="active">🧥 Clothes</a>
  <a href="message.php">✉️ Messages</a>
  <a href="../shop.php">🛍️ View Shop</a>
  <a href="logout.php" class="logout">⏻ Logout</a>
</nav>

<div class="main">
  <h1>🧥 Manage Clothing Items</h1>

  <?php if ($success): ?>
    <div class="alert alert-ok"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <!-- ── Add / Edit Form ───────────────────────────────────────── -->
  <div class="form-card">
    <h2><?= $editItem ? '✏️ Edit Item' : '➕ Add New Item' ?></h2>
    <form method="POST" action="clothes.php" enctype="multipart/form-data">
      <?php if ($editItem): ?>
        <input type="hidden" name="clothesID" value="<?= $editItem['clothesID'] ?>">
        <input type="hidden" name="currentImage" value="<?= htmlspecialchars($editItem['imageFile']) ?>">
      <?php endif; ?>
      <div class="form-grid">
        <div class="form-group">
          <label>Title *</label>
          <input type="text" name="title" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Category *</label>
          <input type="text" name="category" value="<?= htmlspecialchars($editItem['category'] ?? '') ?>" placeholder="e.g. Jackets, Tops, Shoes" required>
        </div>
        <div class="form-group">
          <label>Brand</label>
          <input type="text" name="brand" value="<?= htmlspecialchars($editItem['brand'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Size</label>
          <input type="text" name="size" value="<?= htmlspecialchars($editItem['size'] ?? '') ?>" placeholder="e.g. S, M, L, 32, 8">
        </div>
        <div class="form-group">
          <label>Colour</label>
          <input type="text" name="colour" value="<?= htmlspecialchars($editItem['colour'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Condition</label>
          <select name="condition_">
            <?php foreach (['Mint','Good','Fair','Well-Loved'] as $c): ?>
              <option value="<?= $c ?>" <?= ($editItem['condition_'] ?? 'Good') === $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Sell Price (R) *</label>
          <input type="number" name="sellPrice" step="0.01" min="0.01" value="<?= $editItem['sellPrice'] ?? '' ?>" required>
        </div>
        <div class="form-group">
          <label>Retail Price (R)</label>
          <input type="number" name="retailPrice" step="0.01" min="0" value="<?= $editItem['retailPrice'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Product Image (JPG/PNG)</label>
          <input type="file" name="imageFile" accept=".jpg,.jpeg,.png,.gif,.webp">
          <?php if (!empty($editItem['imageFile'])): ?>
            <small style="color:var(--muted);">Current: <?= htmlspecialchars($editItem['imageFile']) ?></small>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <?php foreach (['active','sold','inactive'] as $st): ?>
              <option value="<?= $st ?>" <?= ($editItem['status'] ?? 'active') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" class="btn-submit"><?= $editItem ? '💾 Update Item' : '➕ Add Item' ?></button>
      <?php if ($editItem): ?>
        <a href="clothes.php" class="btn-cancel">Cancel</a>
      <?php endif; ?>
    </form>
  </div>

  <!-- ── Items Table ───────────────────────────────────────────── -->
  <div class="table-wrap">
    <table>
      <tr>
        <th>Image</th><th>Title</th><th>Category</th><th>Brand</th>
        <th>Size</th><th>Condition</th><th>Sell Price</th><th>Status</th><th>Actions</th>
      </tr>
      <?php if (empty($clothes)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem;">No items found.</td></tr>
      <?php else: ?>
        <?php foreach ($clothes as $item): ?>
        <tr>
          <td>
            <img src="../images/<?= htmlspecialchars($item['imageFile']) ?>"
                 class="thumb" alt="<?= htmlspecialchars($item['title']) ?>"
                 onerror="this.src='../images/placeholder.jpg'">
          </td>
          <td><?= htmlspecialchars($item['title']) ?></td>
          <td><?= htmlspecialchars($item['category']) ?></td>
          <td><?= htmlspecialchars($item['brand'] ?? '—') ?></td>
          <td><?= htmlspecialchars($item['size'] ?? '—') ?></td>
          <td><?= htmlspecialchars($item['condition_']) ?></td>
          <td>R <?= number_format($item['sellPrice'], 2) ?></td>
          <td>
            <span class="badge-<?= $item['status'] ?>">
              <?= ucfirst($item['status']) ?>
            </span>
          </td>
          <td>
            <a href="clothes.php?edit=<?= $item['clothesID'] ?>" class="btn-edit">Edit</a>
            <a href="clothes.php?delete=<?= $item['clothesID'] ?>"
               class="btn-delete"
               onclick="return confirm('Delete this item? This cannot be undone.')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>
</body>
</html>
