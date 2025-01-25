<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Chapa\Chapa\Facades\Chapa as Chapa;

class ChapaController extends Controller
{
    /**
     * Initialize Rave payment process
     * @return void
     */
    protected $reference;

    public function __construct(){
        $this->reference = Chapa::generateReference();

    }
    public function initialize()
    {
        //This generates a payment reference
        $reference = $this->reference;


        // Enter the details of the payment
        $data = [

            'amount' => 100,
            'email' => 'hi@negade.com',
            'tx_ref' => $reference,
            'currency' => "ETB",
            'callback_url' => route('callback',[$reference]),
            'first_name' => "Israel",
            'last_name' => "Goytom",
            "customization" => [
                "title" => 'Chapa Laravel Test',
                "description" => "I amma testing this"
            ]
        ];


        $payment = Chapa::initializePayment($data);


        if ($payment['status'] !== 'success') {
            // notify something went wrong
            return;
        }

        return redirect($payment['data']['checkout_url']);
    }

    /**
     * Obtain Rave callback information
     * @return void
     */
    public function callback(Request $request, $reference)
    {
        Log::info("Callback received with reference: {$reference}");

        try {
            $data = Chapa::verifyTransaction($reference);
            Log::info('Chapa verification response', ['response' => $data]);

            if ($data['status'] === 'success') {
                // Handle successful payment
                Log::info('Payment successful', ['data' => $data]);
                return response()->json(['message' => 'Payment successful', 'data' => $data]);
            }

            // Handle failed payment
            Log::error('Payment verification failed', ['response' => $data]);
            return response()->json(['message' => $data['message'], 'status' => $data['status']], 400);
        } catch (\Exception $e) {
            Log::error('Callback error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during verification'], 500);
        }
    }

}
