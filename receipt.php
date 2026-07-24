<?php
require_once 'config.php';

function pdoConnection() {
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

$txRef = $_GET['tx_ref'] ?? '';
if ($txRef === '') {
    exit('Missing reference.');
}

$pdo = pdoConnection();
$stmt = $pdo->prepare("SELECT * FROM payments WHERE tx_ref=?");
$stmt->execute([$txRef]);
$payment = $stmt->fetch();

if (!$payment) {
    exit('Receipt not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt — TRA Smart Hub</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#f4c400">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card">
    <div class="brand">
      <img src="assets/icons/icon-192.png" alt="TRA logo">
      <div>
        <h1>Official TRA Payment Receipt</h1>
        <p class="muted">Generated after verified payment</p>
      </div>
    </div>

    <div class="panel">
      <p><strong>Reference:</strong> <?= htmlspecialchars($payment['tx_ref']) ?></p>
      <p><strong>Name:</strong> <?= htmlspecialchars($payment['full_name']) ?></p>
      <p><strong>TIN:</strong> <?= htmlspecialchars($payment['tin']) ?></p>
      <p><strong>Tax Type:</strong> <?= htmlspecialchars($payment['tax_type']) ?></p>
      <p><strong>Amount:</strong> TZS <?= number_format((float)$payment['amount'], 2) ?></p>
      <p><strong>Status:</strong> <span class="status-success"><?= htmlspecialchars($payment['status']) ?></span></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($payment['created_at']) ?></p>
    </div>

    <div class="actions" style="margin-top:16px;">
      <a href="dashboard.php">Open Dashboard</a>
      <a href="index.html">New Payment</a>
    </div>
  </div>
</body>
</html>
