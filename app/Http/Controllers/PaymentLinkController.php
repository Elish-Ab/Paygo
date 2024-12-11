<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Chapa\Chapa\Facades\Chapa;
use Exception;

class PaymentLinkController extends Controller
{
    public function generateLink(Request $request)
    {
        try {
            // Validate the incoming request data
            $validateData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
                'reference' => 'required|unique:payment_links,reference',
                'description' => 'nullable|string',
            ]);

            // Find the user
            $user = User::findOrFail($validateData['user_id']);

            // Generate a payment reference using Chapa
            $reference = Chapa::generateReference();

            // Prepare the payment data
            $data = [
                'amount' => $validateData['amount'],
                'email' => $user->email,
                'tx_ref' => $reference,
                'currency' => "ETB",
                'callback_url' => route('payment.callback', ['reference' => $reference]),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'customization' => [
                    'title' => $validateData['description'] ?? 'Payment Link',
                    'description' => $validateData['description'] ?? 'Payment for goods/services.',
                ],
            ];

            // Initialize the payment and get the response
            $payment = Chapa::initializePayment($data);

            if (!isset($payment['status']) || $payment['status'] !== 'success') {
                return response()->json(["message" => "Payment link creation failed"], 500);
            }

            // Save the payment link details to the database
            $paymentLink = PaymentLink::create([
                'user_id' => $user->id,
                'amount' => $validateData['amount'],
                'reference' => $reference,
                'description' => $validateData['description'],
                'is_paid' => false,
                'payment_url' => $payment['data']['checkout_url'],
            ]);

            return response()->json([
                "message" => "Payment link created successfully",
                "payment_url" => $payment['data']['checkout_url'],
                "payment_link" => $paymentLink,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error generating payment link: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(["message" => "An error occurred while generating the payment link."], 500);
        }
    }

    public function verifyPayment($reference)
{
    try {
        // Chapa API endpoint to verify payment
        $chapaUrl = "https://api.chapa.co/v1/transaction/verify/{$reference}";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $chapaUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . env('CHAPA_SECRET_KEY'),
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Log::error('CURL Error: ' . $err);
            return response()->json(['error' => 'CURL Error: ' . $err], 500);
        }

        $responseData = json_decode($response, true);

        if (isset($responseData['status']) && $responseData['status'] === 'success') {
            // Update the payment status in the database
            $paymentLink = PaymentLink::where('reference', $reference)->first();
            if ($paymentLink) {
                $paymentLink->update(['is_paid' => true]);
            }

            return response()->json([
                'message' => 'Payment verified successfully',
                'data' => $responseData['data'],
            ]);
        } else {
            return response()->json(['error' => $responseData['message'] ?? 'Payment verification failed'], 500);
        }
    } catch (Exception $e) {
        Log::error('Error verifying payment: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(["message" => "An error occurred while verifying the payment."], 500);
    }
}
    public function getUserPaymentLinks($userId)
    {
        try {
            $paymentLinks = PaymentLink::where('user_id', $userId)->get();
            return response()->json(['payment_links' => $paymentLinks], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving user payment links: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(["message" => "An error occurred while retrieving payment links."], 500);
        }
    }

    public function initializePayment(Request $request)
    {
        try {
            // Collect and validate data from the request
            $validateData = $request->validate([
                "amount" => "required|numeric|min:0.01",
                "currency" => "nullable|string|max:3|in:ETB,USD",
                "email" => "required|email",
                "first_name" => "nullable|string",
                "last_name" => "nullable|string",
                "phone_number" => "nullable|string",
                "customization_title" => "nullable|string",
                "customization_description" => "nullable|string",
                "hide_receipt" => "nullable|boolean",
            ]);

            $data = [
                "amount" => $validateData['amount'],
                "currency" => $validateData['currency'] ?? 'ETB',
                "email" => $validateData['email'],
                "first_name" => $validateData['first_name'] ?? '',
                "last_name" => $validateData['last_name'] ?? '',
                "phone_number" => $validateData['phone_number'] ?? '',
                "tx_ref" => uniqid('transaction-'),
                "callback_url" => route('payment.callback'),
                "return_url" => route('payment.return'),
                "customization" => [
                    "title" => $validateData['customization_title'] ?? 'Payment',
                    "description" => $validateData['customization_description'] ?? 'Description',
                ],
                "meta" => [
                    "hide_receipt" => $validateData['hide_receipt'] ?? false,
                ],
            ];

            // Chapa API endpoint
            $chapaUrl = 'https://api.chapa.co/v1/transaction/initialize';

            // Initialize CURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $chapaUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . env('CHAPA_SECRET_KEY'),
                    'Content-Type: application/json',
                ],
            ]);

            // Execute CURL request
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error('CURL Error: ' . $err);
                return response()->json(['error' => 'CURL Error: ' . $err], 500);
            }

            $responseData = json_decode($response, true);

            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                return redirect()->to($responseData['data']['checkout_url']);
            } else {
                Log::error('Payment initialization failed', [
                    'response' => $responseData
                ]);
                return response()->json(['error' => $responseData['message'] ?? 'Payment initialization failed'], 500);
            }
        } catch (Exception $e) {
            Log::error('Error initializing payment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(["message" => "An error occurred while initializing the payment."], 500);
        }
    }

    public function webhook(Request $request)
{
    try {
        // Validate the webhook payload
        $data = $request->all();
        if ($data['status'] === 'success') {
            $reference = $data['tx_ref'];
            $paymentLink = PaymentLink::where('reference', $reference)->first();
            if ($paymentLink) {
                $paymentLink->update(['is_paid' => true]);
            }
        }
        return response()->json(['message' => 'Webhook processed successfully'], 200);
    } catch (Exception $e) {
        Log::error('Error processing webhook: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(["message" => "An error occurred while processing the webhook."], 500);
    }
}

}

