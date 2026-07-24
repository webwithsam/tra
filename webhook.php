<?php
require_once 'config.php';

$headers = getallheaders();
$webhookHash = $headers['verif-hash'] ?? $headers['Verif-Hash'] ?? '';

if (!$webhookHash || $webhookHash !== FLW_WEBHOOK_HASH) {
    http_response_code(401);
    exit('Unauthorized');
}

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data || !isset($data['data']['tx_ref'])) {
    http_response_code(400);
    exit('Bad request');
}

$txRef = $data['data']['tx_ref'];
$status = $data['data']['status'] ?? 'unknown';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

if ($status === 'successful') {
    $stmt = $pdo->prepare("UPDATE payments SET status='successful', updated_at=NOW() WHERE tx_ref=?");
    $stmt->execute([$txRef]);
}

http_response_code(200);
echo 'OK';
