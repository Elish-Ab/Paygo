<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\PaymentLink;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Generate a payment link and initialize the payment process.
     */
    public function generateLink(PaymentRequest $request)
    {
        // Validate the incoming request
        $validated = $request->validated();

        // Prepare data for the API request
        $postData = $this->preparePostData($validated);

        try {
            $response = $this->initializePaymentRequest($postData);

            if ($response->successful()) {
                $responseData = $response->json();
                $checkoutUrl = $responseData['data']['checkout_url'] ?? null;

                if (!$checkoutUrl) {
                    Log::error('Checkout URL missing in API response', $responseData);
                    return $this->errorResponse('Checkout URL is missing in the response from Chapa.');
                }

                // Generate QR code for the checkout URL
                $qrCodeImage = $this->generateQrCode($checkoutUrl);

                return response()->json([
                    'status' => 'success',
                    'checkout_url' => $checkoutUrl,
                    'qr_code' => 'data:image/png;base64,' . $qrCodeImage
                ]);
            }

            return $this->logAndReturnApiError($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error during payment link generation');
        }
    }

    /**
     * Initialize a payment process.
     */
    public function initializePayment(PaymentRequest $request)
    {
        // Validate the incoming request
        $validated = $request->validated();

        // Prepare data for the API request
        $postData = $this->preparePostData($validated);

        // Save transaction details to the database
        Transaction::create(array_merge($postData, [
            'status' => 'pending',
            'transaction_type' => 'payment'
        ]));

        try {
            $response = $this->initializePaymentRequest($postData);

            if ($response->successful()) {
                $responseData = $response->json();
                $checkoutUrl = $responseData['data']['checkout_url'] ?? null;

                return response()->json([
                    'status' => 'success',
                    'checkout_url' => $checkoutUrl
                ]);
            }

            return $this->logAndReturnApiError($response);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Error during payment initialization');
        }
    }

    /**
     * Handle Chapa webhook.
     */
    public function handleWebhook(Request $request)
    {
        $rawPayload = $request->getContent();
        $canonicalPayload = json_encode(json_decode($rawPayload, true));
        $computedSignature = hash_hmac('sha256', $canonicalPayload, env('CHAPA_WEBHOOK_SECRET'));

        Log::info('Webhook Details', [
            'headers' => $request->headers->all(),
            'raw_payload' => $rawPayload,
            'canonical_payload' => $canonicalPayload,
            'webhook_signature' => $request->header('X-Chapa-Signature'),
            'computed_signature' => $computedSignature,
        ]);

        if (!hash_equals($request->header('X-Chapa-Signature'), $computedSignature)) {
            Log::error('Invalid webhook signature');
            return response('Invalid signature', 400);
        }

        Log::info('Webhook verified successfully', $request->all());

        return response('Webhook verified', 200);
    }

    // Helper Methods

    /**
     * Prepare data for Chapa API requests.
     */
    private function preparePostData(array $validated)
    {
        return [
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'callback_url' => $validated['callback_url'],
            'tx_ref' => 'chewatatest-' . time()
        ];
    }

    /**
     * Make the payment initialization request to Chapa.
     */
    private function initializePaymentRequest(array $postData)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY')
        ])->post('https://api.chapa.co/v1/transaction/initialize', $postData);
    }

    /**
     * Generate a QR code for the given URL.
     */
    private function generateQrCode(string $url)
    {
        return base64_encode(QrCode::format('png')->size(200)->generate($url));
    }

    /**
     * Log and handle API errors.
     */
    private function logAndReturnApiError($response)
    {
        Log::error('Chapa API Error', [
            'status_code' => $response->status(),
            'response_body' => $response->body(),
            'response_data' => $response->json(),
        ]);

        return response()->json([
            'status' => 'failure',
            'message' => 'Error initializing payment. Please try again.'
        ]);
    }

    /**
     * Handle exceptions and return a standardized error response.
     */
    private function handleException(\Exception $e, string $contextMessage)
    {
        Log::error($contextMessage . ': ' . $e->getMessage(), ['exception' => $e]);

        return response()->json([
            'status' => 'failure',
            'message' => $contextMessage
        ]);
    }

    /**
     * Return a standardized error response.
     */
    private function errorResponse(string $message)
    {
        return response()->json([
            'status' => 'failure',
            'message' => $message
        ]);
    }
}
