<?php
// Mock payment handler — only works when MOCK_MODE=1
session_start();

if (getenv('MOCK_MODE') !== '1') {
    http_response_code(403);
    exit('Mock mode disabled.');
}

$action      = $_POST['action'] ?? '';       // 'success' or 'fail'
$merchant_oid = $_POST['merchant_oid'] ?? '';
$amount      = $_POST['amount'] ?? '0';

// Log the mock transaction
$log = date('Y-m-d H:i:s') . " | [MOCK] OID: $merchant_oid | Status: $action | Amount: $amount kuruş\n";
file_put_contents('/tmp/paytr_transactions.log', $log, FILE_APPEND | LOCK_EX);

// Simulate PayTR redirect behavior
if ($action === 'success') {
    header('Location: /success.php');
} else {
    header('Location: /fail.php');
}
exit;
