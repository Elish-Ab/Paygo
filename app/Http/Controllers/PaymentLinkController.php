<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentLinkController extends Controller
{
    /**
     * Generate a payment link and initialize the payment process.
     */
    public function generateLink(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|in:ETB,USD',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prepare the data to send to the Chapa API
        $postData = [
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency', 'ETB'),  // Default to ETB if no currency is provided
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'callback_url' => route('payment.verify'),  // Use your route name here
        ];

        // Log post data for debugging
        Log::info('Post Data:', $postData);

        // Call the initializePayment method with the postData
        $response = $this->initializePayment($postData);

        // Check if the response status is success
        if ($response['status'] === 'success') {
            // Store payment link and respond with success
            // For example, you could store the response in the database
            // PaymentLink::create(['link' => $response['payment_link']]);

            return response()->json([
                'message' => 'Payment link created successfully',
                'payment_link' => $response['payment_link']
            ], 200);
        }

        // If the response status is not success, return failure response
        return response()->json([
            'message' => 'Payment link creation failed',
            'details' => $response
        ], 500);
    }

    /**
     * Initialize the payment process by sending data to the payment provider's API.
     *
     * @param array $postData
     * @return array
     */
    private function initializePayment($postData)
    {
        try {
            $response = Http::post('https://api.chapa.co/initialize', $postData);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'payment_link' => $response->json()['payment_link']
                ];
            } else {
                Log::error('Chapa API Error:', ['response' => $response->body()]);
                return [
                    'status' => 'failure',
                    'message' => 'Error initializing payment. Please try again.'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error during payment initialization: ' . $e->getMessage());
            return [
                'status' => 'failure',
                'message' => 'Error during payment initialization. Please try again.'
            ];
        }
    }

}
