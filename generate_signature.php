<?php
// Prepare the payload data
$payloadData = [
    'tx_ref' => 'test_transaction_123',
    'status' => 'success'
];

// Encode payload as JSON
$payload = json_encode($payloadData, JSON_UNESCAPED_SLASHES);

// Load the secret key
$secret = getenv('CHAPA_WEBHOOK_SECRET');

// Generate the HMAC signature
$signature = hash_hmac('sha256', $payload, $secret);

// Output the generated signature
echo "Generated Signature: " . $signature . PHP_EOL;
?>
