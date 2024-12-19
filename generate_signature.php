<?php
// Prepare the payload data
$payloadData = [
    'tx_ref' => 'transaction',
    'status' => 'success'
];

$payload = '{"tx_ref":"test","status":"success"}';
$secret ='';
$signature = hash_hmac('sha256', $payload, $secret);
echo "Generated Signature: " . $signature . PHP_EOL;

?>
