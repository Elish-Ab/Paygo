<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentLinkController extends Controller
{
    /**
     * Generate a payment link and initialize the payment process.
     */
    // public function generateLink(Request $request)
    // {
    //     // Validate incoming request
    //     $validator = Validator::make($request->all(), [
    //         'amount' => 'required|numeric|min:1',
    //         'currency' => 'nullable|string|in:ETB,USD',
    //         'email' => 'required|email',
    //         'phone' => 'required|string',
    //     ]);

    //     // If validation fails, return error response
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     // Prepare the data to send to the Chapa API
    //     $postData = [
    //         'amount' => $request->input('amount'),
    //         'currency' => $request->input('currency', 'ETB'),  // Default to ETB if no currency is provided
    //         'email' => $request->input('email'),
    //         'phone' => $request->input('phone'),
    //         'callback_url' => route('payment.verify'),  // Use your route name here
    //     ];

    //     // Log post data for debugging
    //     Log::info('Post Data:', $postData);

    //     // Call the initializePayment method with the postData
    //     $response = $this->initializePayment($postData);

    //     // Check if the response status is success
    //     if ($response['status'] === 'success') {
    //         // Store payment link and respond with success
    //         // For example, you could store the response in the database
    //         // PaymentLink::create(['link' => $response['payment_link']]);

    //         return response()->json([
    //             'message' => 'Payment link created successfully',
    //             'payment_link' => $response['payment_link']
    //         ], 200);
    //     }

    //     // If the response status is not success, return failure response
    //     return response()->json([
    //         'message' => 'Payment link creation failed',
    //         'details' => $response
    //     ], 500);
    // }

    /**
     * Initialize the payment process by sending data to the payment provider's API.
     *
     * @param array $postData
     * @return array
     */



     public function initializePayment(Request $request)
     {
         try {
             $postData = $request->all();

             // Generate a unique tx_ref based on current timestamp
             $postData['tx_ref'] = 'chewatatest-' . time();

             // Make the API request with the correct Authorization header
             $response = Http::withHeaders([
                 'Authorization' => 'Bearer ' . env('CHAPA_SECRET_KEY')
             ])->post('https://api.chapa.co/v1/transaction/initialize', $postData);

             if ($response->successful()) {
                 $responseData = $response->json();
                 Log::info('Chapa Payment Initialization Response:', $responseData);

                 // Return the checkout URL
                 return response()->json([
                     'status' => 'success',
                     'checkout_url' => $responseData['data']['checkout_url'] ?? null
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
         // Handle the payment success, failure, etc. based on $data

         return response('Webhook received successfully');
     }


}
