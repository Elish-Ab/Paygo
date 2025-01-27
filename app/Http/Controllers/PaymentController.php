<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use SimpleQRCode;
use App\Models\PaymentLink;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use Mockery\Generator\StringManipulation\Pass\Pass;

class PaymentLinkController extends Controller
{
    /**
     * Generate a payment link and initialize the payment process.
     */
    public function generateLink(PaymentRequest $request)
{
    // Validate the incoming request
    $validated = $request->validated();
    // Prepare the data to send to the API
    $postData = [
        'amount' => $validated['amount'],
        'currency' => $validated['currency'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'callback_url' => $validated['callback_url'],
        'tx_ref' => 'chewatatest-' . time()  // Add tx_ref for unique transaction reference
    ];

    try {
        // Make the API request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY')
        ])->post('https://api.chapa.co/v1/transaction/initialize', $postData);

        // Log the raw API response for debugging purposes
        Log::info('Chapa API Response:', $response->json());

        if ($response->successful()) {
            // Get the response data as an array
            $responseData = $response->json();

            // Access the checkout URL safely
            $checkoutUrl = $responseData['data']['checkout_url'] ?? null;

            if (!$checkoutUrl) {
                Log::error('Checkout URL missing in API response', $responseData);
                return response()->json([
                    'status' => 'failure',
                    'message' => 'Checkout URL is missing in the response from Chapa.'
                ]);
            }

            // Generate a directory based on the current date
            $date = now()->format('Y-m-d');
            $directory = storage_path("app/{$date}");
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true); // Create the directory if it doesn't exist
            }

            // Generate a unique filename within the directory
            $timestamp = time();
            $filename = "{$directory}/qr_{$timestamp}.png";

            // Generate the QR code
            $qrCodeImage = QrCode::format('png')->size(200)->generate($checkoutUrl, $filename);


            return response()->json([
                'status' => 'success',
                'checkout_url' => $checkoutUrl,
                'qr_code' => 'data:image/png;base64,' . $qrCodeImage // Embed QR code as base64 in response
            ]);
        } else {
            // Log error details if the request was unsuccessful
            Log::error('Chapa API Error:', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_data' => $response->json()
            ]);
            return response()->json([
                'status' => 'failure',
                'message' => 'Error initializing payment. Please try again.'
            ]);
        }
    } catch (\Exception $e) {
        // Log the exception message
        Log::error('Error during payment link generation: ' . $e->getMessage(), [
            'exception' => $e
        ]);
        return response()->json([
            'status' => 'failure',
            'message' => 'An error occurred while generating the payment link.'
        ]);
    }
}




public function initalizePayment(PaymentRequest $request)
{
    // Validate the incoming request data
    $validated = $request->validated();

    // Prepare data for the payment initialization
    $postData = [
        'amount' => $validated['amount'],
        'currency' => $validated['currency'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'callback_url' => $validated['callback_url'],
        'tx_ref' => 'chewatatest-' . time()  // Add tx_ref for transaction reference
    ];


       // Save transaction details to the database
       Transaction::create([
        'tx_ref' => $postData['tx_ref'],
        'amount' => $postData['amount'],
        'currency' => $postData['currency'],
        'email' => $postData['email'],
        'phone' => $postData['phone'],
        'callback_url' => $postData['callback_url'],
        'status' => 'pending',
        'transaction_type' => 'payment'
    ]);

    try {
        // Make the API request with the correct Authorization header
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY')
        ])->post('https://api.chapa.co/v1/transaction/initialize', $postData);

        // Log the raw response for debugging
        Log::info('Chapa API Response:', $response->json());

        if ($response->successful()) {
            $responseData = $response->json();  // Get the response data as an array
            // Safely access the checkout URL
            $checkoutUrl = $responseData['data']['checkout_url'] ?? null;
            return response()->json([
                'status' => 'success',
                'checkout_url' => $checkoutUrl
            ]);
        } else {
            Log::error('Chapa API Error:', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_data' => $response->json()
            ]);
            return response()->json([
                'status' => 'failure',
                'message' => 'Error initializing payment. Please try again.'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error during payment initialization: ' . $e->getMessage());
        return response()->json([
            'status' => 'failure',
            'message' => 'Error during payment initialization. Please try again.'
        ]);
    }
}




public function handleWebhook(Request $request)
{
    // Raw payload
    $rawPayload = $request->getContent();

    // Canonicalize payload to ensure consistent formatting
    $canonicalPayload = json_encode(json_decode($rawPayload, true));

    // Compute the HMAC signature
    $computedSignature = hash_hmac('sha256', $canonicalPayload, env('CHAPA_WEBHOOK_SECRET'));

    // Log details for debugging
    Log::info('Request Headers: ' . json_encode($request->headers->all()));
    Log::info('Raw Payload: ' . $rawPayload);
    Log::info('Canonical Payload: ' . $canonicalPayload);
    Log::info('Webhook Signature from Header: ' . $request->header('X-Chapa-Signature'));
    Log::info('Computed Signature: ' . $computedSignature);
    Log::info('Webhook Secret Key: ' . env('CHAPA_WEBHOOK_SECRET'));

    // Validate signature
    if (!hash_equals($request->header('X-Chapa-Signature'), $computedSignature)) {
        Log::error('Invalid webhook signature');
        return response('Invalid signature', 400);
    }

    Log::info('Webhook verified successfully');
    Log::info("message", $request->all());

    return response('Webhook verified', 200);
}


}
