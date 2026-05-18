<?php
// PayTR Notification URL (Callback)
// Set this URL in your PayTR merchant panel:
// https://sanalpos.ferhatdalcik.com/callback.php

$merchant_key  = getenv('PAYTR_MERCHANT_KEY');
$merchant_salt = getenv('PAYTR_MERCHANT_SALT');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Verify hash
$hash = base64_encode(hash_hmac(
    'sha256',
    $_POST['merchant_oid'] . $merchant_salt . $_POST['status'] . $_POST['total_amount'],
    $merchant_key,
    true
));

if ($hash !== $_POST['hash']) {
    echo 'PAYTR notification failed: bad hash';
    exit;
}

$status       = $_POST['status'];       // 'success' or 'failed'
$merchant_oid = $_POST['merchant_oid'];
$total_amount = $_POST['total_amount'];  // kuruş

// Log
$log = date('Y-m-d H:i:s') . " | OID: $merchant_oid | Status: $status | Amount: $total_amount kuruş\n";
file_put_contents('/tmp/paytr_transactions.log', $log, FILE_APPEND | LOCK_EX);

if ($status === 'success') {
    // Payment successful — update DB, send email, etc.
} else {
    // Payment failed
}

// Always respond OK so PayTR stops retrying
echo 'OK';
