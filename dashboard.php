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

$pdo = pdoConnection();

$stats = $pdo->query("
    SELECT
      COUNT(*) total,
      SUM(CASE WHEN status='successful' THEN 1 ELSE 0 END) successful,
      SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending,
      SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) failed,
      COALESCE(SUM(CASE WHEN status='successful' THEN amount ELSE 0 END),0) revenue
    FROM payments
")->fetch();

$rows = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TRA Dashboard — TRA Smart Hub</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#f4c400">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card">
    <div class="brand">
      <img src="assets/icons/icon-192.png" alt="TRA logo">
      <div>
        <h1>TRA Revenue Dashboard</h1>
        <p class="muted">Live payment monitoring and receipts</p>
      </div>
    </div>

    <div class="grid">
      <div class="panel"><h3>Total Payments</h3><p><?= (int)$stats['total'] ?></p></div>
      <div class="panel"><h3>Successful</h3><p class="status-success"><?= (int)$stats['successful'] ?></p></div>
      <div class="panel"><h3>Pending</h3><p class="status-pending"><?= (int)$stats['pending'] ?></p></div>
      <div class="panel"><h3>Failed</h3><p class="status-failed"><?= (int)$stats['failed'] ?></p></div>
      <div class="panel"><h3>Total Revenue</h3><p>TZS <?= number_format((float)$stats['revenue'], 2) ?></p></div>
    </div>

    <div class="panel" style="margin-top:16px;">
      <h3>Recent Transactions</h3>
      <table>
        <thead>
          <tr>
            <th>Ref</th><th>Name</th><th>Tax</th><th>Amount</th><th>Status</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['tx_ref']) ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['tax_type']) ?></td>
            <td>TZS <?= number_format((float)$r['amount'], 2) ?></td>
            <td class="status-<?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></td>
            <td><a href="receipt.php?tx_ref=<?= urlencode($r['tx_ref']) ?>">Receipt</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="actions" style="margin-top:16px;">
      <a href="index.html">New Payment</a>
    </div>
  </div>
</body>
</html>
