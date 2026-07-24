<?php
require_once 'config.php';

session_start();

function pdoConnection() {
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

$transactionId = $_GET['transaction_id'] ?? null;
if (!$transactionId) {
    exit('Missing transaction ID.');
}

$pdo = pdoConnection();

$ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . FLW_SECRET_KEY
    ],
]);

$response = curl_exec($ch);
if ($response === false) {
    exit('Verification error: ' . curl_error($ch));
}
curl_close($ch);

$result = json_decode($response, true);

$status  = $result['data']['status'] ?? '';
$amount  = $result['data']['amount'] ?? '';
$currency = $result['data']['currency'] ?? '';
$txRef   = $result['data']['tx_ref'] ?? '';
$transactionRef = $result['data']['id'] ?? null;

$expectedAmount = $_SESSION['expected_amount'] ?? null;
$expectedCurrency = $_SESSION['expected_currency'] ?? 'TZS';
$expectedTxRef = $_SESSION['tx_ref'] ?? null;

if ($status === 'successful' && $currency === $expectedCurrency && (float)$amount >= (float)$expectedAmount && $txRef === $expectedTxRef) {
    $stmt = $pdo->prepare("UPDATE payments SET status='successful', gateway_transaction_id=?, updated_at=NOW() WHERE tx_ref=?");
    $stmt->execute([$transactionRef, $txRef]);

    header('Location: receipt.php?tx_ref=' . urlencode($txRef));
    exit;
}

$stmt = $pdo->prepare("UPDATE payments SET status='failed', updated_at=NOW() WHERE tx_ref=?");
$stmt->execute([$txRef]);

echo "<h1>Payment not verified</h1><p>The transaction failed verification checks.</p>";
