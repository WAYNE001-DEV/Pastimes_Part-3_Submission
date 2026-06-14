<?php
/**
 * checkout.php — Part 3 Updated
 * Now uses ShoppingCart::Checkout() which writes to tblOrderLine.
 */

session_start();
require_once 'DBConn.php';
require_once 'ShoppingCart.php';

if (!isset($_SESSION['userID'])) {
    header("Location: login.php?msg=login_required");
    exit;
}

$cart = new ShoppingCart($conn);
if (empty($cart->ShowCart())) {
    header("Location: shop.php");
    exit;
}

$userID    = $_SESSION['userID'];
$cartItems = $cart->ShowCart();
$cartTotal = $cart->GetTotal();
$errors    = [];
$success   = false;
$orderRef  = '';
$sessionRef= '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = trim($_POST['deliveryAddress'] ?? '');
    if (empty($deliveryAddress)) $errors[] = "Please enter a delivery address.";

    if (empty($errors)) {
        $result = $cart->Checkout($userID, $deliveryAddress);
        if ($result['success']) {
            $success    = true;
            $orderRef   = $result['orderRef'];
            $sessionRef = $result['sessionRef'];
        } else {
            $errors[] = $result['error'];
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — PASTIMES</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --gold2:#e8c98a; --text:#e5e5e5; --muted:#888; --err:#e05252; --ok:#5cb85c; --border:#2a2a2a; --radius:8px; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
  nav { background:#111; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; padding:.9rem 2rem; }
  .logo { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.3rem; text-decoration:none; }
  .nav-links a { color:var(--muted); text-decoration:none; margin-left:1.5rem; font-size:.9rem; }
  .nav-links a:hover { color:var(--gold); }
  .container { max-width:900px; margin:2rem auto; padding:0 1.5rem; display:grid; grid-template-columns:1fr 340px; gap:2rem; }
  @media(max-width:700px){ .container{grid-template-columns:1fr;} }
  .section-title { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.2rem; margin-bottom:1rem; }
  .alert { padding:.85rem 1rem; border-radius:var(--radius); margin-bottom:1.2rem; font-size:.9rem; }
  .alert-err { background:#2a1212; border-left:4px solid var(--err); color:#ffaaaa; }
  .form-group { margin-bottom:1.2rem; }
  label { display:block; font-size:.82rem; color:var(--muted); margin-bottom:.4rem; letter-spacing:.04em; text-transform:uppercase; }
  input, textarea { width:100%; padding:.65rem .9rem; background:#1f1f1f; border:1px solid var(--border); color:var(--text); border-radius:var(--radius); font-family:'DM Sans',sans-serif; font-size:.95rem; }
  input:focus, textarea:focus { outline:none; border-color:var(--gold); }
  textarea { resize:vertical; min-height:90px; }
  .btn { width:100%; padding:.75rem; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#111; font-weight:600; border:none; border-radius:var(--radius); cursor:pointer; font-size:1rem; }
  .btn:hover { opacity:.9; }
  .btn-back { display:inline-block; color:var(--muted); font-size:.88rem; text-decoration:none; margin-bottom:1.5rem; }
  .btn-back:hover { color:var(--gold); }
  .summary-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.25rem; position:sticky; top:1rem; }
  .summary-card h3 { font-family:'Playfair Display',serif; color:var(--gold); margin-bottom:1rem; font-size:1.1rem; }
  .order-item { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid var(--border); font-size:.88rem; }
  .item-name { color:var(--text); }
  .item-qty  { color:var(--muted); font-size:.8rem; }
  .item-price { color:var(--gold); font-weight:600; }
  .order-total { display:flex; justify-content:space-between; padding:.75rem 0 0; font-weight:600; font-size:1rem; color:var(--gold); border-top:1px solid var(--border); margin-top:.5rem; }
  .success-wrap { text-align:center; padding:3rem 2rem; }
  .success-wrap h2 { font-family:'Playfair Display',serif; color:var(--gold); font-size:1.8rem; margin-bottom:.5rem; }
  .success-wrap p { color:var(--muted); margin-bottom:2rem; }
</style>
</head>
<body>
<nav>
  <a href="index.php" class="logo">PASTIMES</a>
  <div class="nav-links">
    <a href="shop.php">Shop</a>
    <a href="dashboard.php">My Account</a>
    <a href="logout.php">Logout</a>
  </div>
</nav>

<?php if ($success): ?>
  <div style="max-width:600px;margin:4rem auto;padding:0 1.5rem;">
    <div class="success-wrap">
      <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
      <h2>Order Placed!</h2>
      <p>Thank you, <?= htmlspecialchars($_SESSION['fullName'] ?? 'Customer') ?>.<br>
         Your order has been received and is being processed.</p>
      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:1rem 1.5rem;margin:1.25rem auto;max-width:320px;text-align:left;">
        <div style="font-size:.78rem;color:#888;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Reference Numbers</div>
        <div style="font-size:1.1rem;color:#c9a86c;font-weight:600;font-family:monospace;"><?= htmlspecialchars($orderRef) ?></div>
        <div style="font-size:.82rem;color:#666;margin-top:.3rem;">Session: <?= htmlspecialchars($sessionRef) ?></div>
      </div>
      <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="shop.php" style="background:transparent;border:1px solid #c9a86c;color:#c9a86c;padding:.65rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">Continue Shopping →</a>
        <a href="dashboard.php" style="background:#c9a86c;color:#111;padding:.65rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;">View My Orders</a>
      </div>
    </div>
  </div>

<?php else: ?>
  <div class="container">
    <div>
      <a href="shop.php" class="btn-back">← Back to Shop</a>
      <div class="section-title">Delivery Details</div>
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-err"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>
      <form method="POST" action="checkout.php">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= htmlspecialchars($_SESSION['fullName'] ?? '') ?>" readonly>
        </div>
        <div class="form-group">
          <label>Delivery Address</label>
          <textarea name="deliveryAddress" placeholder="Street address, City, Province, Postal code" required><?= htmlspecialchars($_POST['deliveryAddress'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn">Place Order — R <?= number_format($cartTotal, 2) ?> →</button>
      </form>
    </div>
    <div class="summary-card">
      <h3>Order Summary</h3>
      <?php foreach ($cartItems as $item): ?>
        <div class="order-item">
          <div>
            <div class="item-name"><?= htmlspecialchars($item['title']) ?></div>
            <div class="item-qty">Qty: <?= $item['qty'] ?></div>
          </div>
          <div class="item-price">R <?= number_format($item['price'] * $item['qty'], 2) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="order-total">
        <span>Total</span>
        <span>R <?= number_format($cartTotal, 2) ?></span>
      </div>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
