<?php
require_once 'config.php';

require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

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

$html = '
<h1 style="color:#111;">Official TRA Payment Receipt</h1>
<p><strong>Reference:</strong> ' . htmlspecialchars($payment['tx_ref']) . '</p>
<p><strong>Name:</strong> ' . htmlspecialchars($payment['full_name']) . '</p>
<p><strong>TIN:</strong> ' . htmlspecialchars($payment['tin']) . '</p>
<p><strong>Tax Type:</strong> ' . htmlspecialchars($payment['tax_type']) . '</p>
<p><strong>Amount:</strong> TZS ' . number_format((float)$payment['amount'], 2) . '</p>
<p><strong>Status:</strong> ' . htmlspecialchars($payment['status']) . '</p>
<p><strong>Date:</strong> ' . htmlspecialchars($payment['created_at']) . '</p>
';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save a copy outside the public web root path convention (receipts/ kept private via .htaccess)
$dompdf->stream('receipt-' . $txRef . '.pdf', ['Attachment' => true]);
