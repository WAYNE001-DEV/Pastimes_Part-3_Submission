<?php
/**
 * admin/message.php
 * Admin messaging system — send messages to buyers and sellers.
 * Part 3 requirement: Administrator communicates with buyers and sellers.
 */

session_start();
require_once '../DBConn.php';

if (!isset($_SESSION['adminID'])) {
    header("Location: login.php");
    exit;
}

$adminID = $_SESSION['adminID'];
$errors  = [];
$success = '';

// ── Send message (POST) ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toUserID = (int)   trim($_POST['toUserID'] ?? 0);
    $subject  = trim($_POST['subject']  ?? '');
    $body     = trim($_POST['body']     ?? '');

    if ($toUserID <= 0) $errors[] = "Please select a recipient.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($body))    $errors[] = "Message body is required.";

    if (empty($errors)) {
        $s = $conn->prepare(
            "INSERT INTO tblMessage (fromAdminID, toUserID, subject, body) VALUES (?,?,?,?)"
        );
        $s->bind_param("iiss", $adminID, $toUserID, $subject, $body);
        if ($s->execute()) {
            $success = "Message sent successfully.";
        } else {
            $errors[] = "Failed to send message: " . $conn->error;
        }
        $s->close();
    }
}

// ── Fetch all users for dropdown ──────────────────────────────────────────────
$users = $conn->query(
    "SELECT userID, fullName, email, role FROM tblUser WHERE status='active' ORDER BY fullName"
)->fetch_all(MYSQLI_ASSOC);

// ── Fetch sent messages ───────────────────────────────────────────────────────
$messages = $conn->query(
    "SELECT m.messageID, u.fullName, u.email, m.subject, m.body, m.sentAt
     FROM tblMessage m
     JOIN tblUser u ON m.toUserID = u.userID
     ORDER BY m.sentAt DESC LIMIT 50"
)->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Messages — Admin | PASTIMES</title>
<style>
  :root { --bg:#0c0c0c; --card:#161616; --gold:#c9a86c; --text:#e5e5e5; --muted:#888; --border:#2a2a2a; --radius:8px; --err:#e05252; --ok:#5cb85c; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
  .sidebar { width:220px; background:#111; border-right:1px solid var(--border); padding:1.5rem 1rem; flex-shrink:0; }
  .sidebar .logo { font-size:1.2rem; color:var(--gold); font-weight:700; margin-bottom:2rem; display:block; text-decoration:none; }
  .sidebar a { display:block; padding:.65rem .9rem; color:var(--muted); text-decoration:none; border-radius:var(--radius); margin-bottom:.3rem; font-size:.9rem; }
  .sidebar a:hover, .sidebar a.active { background:#1f1f1f; color:var(--gold); }
  .sidebar .logout { margin-top:2rem; color:#e05252; }
  .main { flex:1; padding:2rem; }
  h1 { font-size:1.5rem; color:var(--gold); margin-bottom:1.5rem; }
  .alert { padding:.85rem 1rem; border-radius:var(--radius); margin-bottom:1rem; font-size:.9rem; }
  .alert-ok  { background:#122a12; border-left:4px solid var(--ok); color:#aaffaa; }
  .alert-err { background:#2a1212; border-left:4px solid var(--err); color:#ffaaaa; }
  .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; margin-bottom:2rem; max-width:600px; }
  .form-card h2 { color:var(--gold); font-size:1rem; margin-bottom:1.2rem; }
  .form-group { margin-bottom:1rem; }
  .form-group label { display:block; font-size:.78rem; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:.35rem; }
  .form-group input, .form-group select, .form-group textarea {
    width:100%; padding:.6rem .85rem; background:#1f1f1f; border:1px solid var(--border);
    color:var(--text); border-radius:var(--radius); font-size:.92rem; font-family:'Segoe UI',sans-serif;
  }
  .form-group textarea { min-height:120px; resize:vertical; }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:var(--gold); }
  .btn-send { padding:.65rem 1.8rem; background:var(--gold); color:#111; border:none; border-radius:var(--radius); font-weight:700; cursor:pointer; font-size:.95rem; }
  table { width:100%; border-collapse:collapse; font-size:.88rem; }
  th { background:#1f1f1f; padding:.7rem 1rem; text-align:left; color:var(--muted); text-transform:uppercase; font-size:.75rem; border-bottom:1px solid var(--border); }
  td { padding:.65rem 1rem; border-bottom:1px solid var(--border); vertical-align:top; }
  .msg-body { color:var(--muted); font-size:.85rem; margin-top:.2rem; max-width:400px; }
</style>
</head>
<body>
<nav class="sidebar">
  <a href="index.php" class="logo">🔐 ADMIN</a>
  <a href="index.php">👥 Users</a>
  <a href="clothes.php">🧥 Clothes</a>
  <a href="message.php" class="active">✉️ Messages</a>
  <a href="../shop.php">🛍️ View Shop</a>
  <a href="logout.php" class="logout">⏻ Logout</a>
</nav>

<div class="main">
  <h1>✉️ Admin Messaging</h1>

  <?php if ($success): ?>
    <div class="alert alert-ok"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $e): ?>
    <div class="alert alert-err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <!-- ── Send Message Form ─────────────────────────────── -->
  <div class="form-card">
    <h2>📤 Send Message to User</h2>
    <form method="POST" action="message.php">
      <div class="form-group">
        <label>To (Recipient) *</label>
        <select name="toUserID" required>
          <option value="">— Select a user —</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= $u['userID'] ?>">
              <?= htmlspecialchars($u['fullName']) ?> (<?= ucfirst($u['role']) ?> — <?= htmlspecialchars($u['email']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Subject *</label>
        <input type="text" name="subject" placeholder="e.g. Order Update, Delivery Confirmation" required>
      </div>
      <div class="form-group">
        <label>Message *</label>
        <textarea name="body" placeholder="Write your message here..." required></textarea>
      </div>
      <button type="submit" class="btn-send">📨 Send Message</button>
    </form>
  </div>

  <!-- ── Sent Messages Log ─────────────────────────────── -->
  <h2 style="color:var(--gold);font-size:1rem;margin-bottom:1rem;">📬 Sent Messages</h2>
  <?php if (empty($messages)): ?>
    <p style="color:var(--muted);">No messages sent yet.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>To</th><th>Email</th><th>Subject</th><th>Message</th><th>Sent At</th>
      </tr>
      <?php foreach ($messages as $m): ?>
      <tr>
        <td><?= htmlspecialchars($m['fullName']) ?></td>
        <td style="color:var(--muted)"><?= htmlspecialchars($m['email']) ?></td>
        <td><strong><?= htmlspecialchars($m['subject']) ?></strong></td>
        <td><div class="msg-body"><?= nl2br(htmlspecialchars($m['body'])) ?></div></td>
        <td style="color:var(--muted);font-size:.8rem;"><?= date('d M Y H:i', strtotime($m['sentAt'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
</body>
</html>
