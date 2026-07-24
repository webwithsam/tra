<?php
require_once 'config.php';

session_start();

function pdoConnection() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$fullName    = trim($_POST['full_name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$tin         = trim($_POST['tin'] ?? '');
$taxType     = trim($_POST['tax_type'] ?? '');
$amount      = trim($_POST['amount'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($fullName === '' || $email === '' || $phoneNumber === '' || $tin === '' || $taxType === '' || $amount === '') {
    exit('All required fields are required.');
}

$txRef = 'TRA-' . date('YmdHis') . '-' . random_int(1000, 9999);

$pdo = pdoConnection();
$stmt = $pdo->prepare("INSERT INTO payments (tx_ref, full_name, email, phone_number, tin, tax_type, amount, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->execute([$txRef, $fullName, $email, $phoneNumber, $tin, $taxType, $amount, $description]);

$payload = [
    'tx_ref' => $txRef,
    'amount' => $amount,
    'currency' => 'TZS',
    'email' => $email,
    'phone_number' => $phoneNumber,
    'fullname' => $fullName,
    'redirect_url' => PAYMENT_SUCCESS_URL,
    'network' => 'M-PESA',
    'meta' => [
        'tin' => $tin,
        'tax_type' => $taxType,
        'description' => $description
    ]
];

$ch = curl_init('https://api.flutterwave.com/v3/charges?type=mobile_money_tanzania');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . FLW_SECRET_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);

if ($response === false) {
    exit('cURL error: ' . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300 && isset($result['data']['link'])) {
    $_SESSION['tx_ref'] = $txRef;
    $_SESSION['expected_amount'] = $amount;
    $_SESSION['expected_currency'] = 'TZS';

    header('Location: ' . $result['data']['link']);
    exit;
}

$stmt = $pdo->prepare("UPDATE payments SET status='failed' WHERE tx_ref=?");
$stmt->execute([$txRef]);

exit('Payment initiation failed.');
