<?php

namespace App\Http\Controllers;

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


class PaymentLinkController extends Controller
{
    /**
     * Generate a payment link and initialize the payment process.
     */
    public function generateLink(Request $request)
{
    // Validate the incoming request
    $validated = $request->validate([
        'amount' => 'required|numeric',
        'currency' => 'required|string',
        'email' => 'required|email',
        'phone' => 'required|string',
        'callback_url' => 'required|url'
    ]);

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
            
            $imagePath = storage_path('app/qr.png');


            // Generate the QR code with the correct checkout URL
            $qrCodeImage = QrCode::format('png')->size(200)->generate($checkoutUrl, $imagePath);


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




public function initializePayment(Request $request)
{
    // Validate the incoming request data
    $validated = $request->validate([
        'amount' => 'required|numeric',
        'currency' => 'required|string',
        'email' => 'required|email',
        'phone' => 'required|string',
        'callback_url' => 'required|url'
    ]);

    // Prepare data for the payment initialization
    $postData = [
        'amount' => $validated['amount'],
        'currency' => $validated['currency'],
        'email' => $validated['email'],
        'phone' => $validated['phone'],
        'callback_url' => $validated['callback_url'],
        'tx_ref' => 'chewatatest-' . time()  // Add tx_ref for transaction reference
    ];

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
            // Verify the webhook signature using the secret key
            $webhookSignature = $request->header('X-Chapa-Signature');
            $computedSignature = hash_hmac('sha256', $request->getContent(), env('CHAPA_WEBHOOK_SECRET'));

            if ($webhookSignature !== $computedSignature) {
                Log::error('Invalid webhook signature');
                return response('Invalid signature', 400);
            }

            // Process the webhook payload
            $data = $request->json()->all();

            // Example: Check the payment status and process accordingly
            if ($data['status'] == 'success') {
                // Update the transaction status in the database
                Transaction::where('tx_ref', $data['tx_ref'])->update(['status' => 'paid']);

                // Optionally notify the user, or perform other actions
                // Send email, SMS, etc.
            } else {
                // Handle failed or pending payments
                Transaction::where('tx_ref', $data['tx_ref'])->update(['status' => 'failed']);
            }

            return response('Webhook received successfully');
        }



}
