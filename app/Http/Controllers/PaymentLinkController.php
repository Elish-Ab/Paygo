<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Http\Request;
use Chapa\Chapa\Facades\Chapa as Chapa;

class PaymentLinkController extends Controller
{
    public function generate_link(Request $request)
    {
        // Validate the incoming request data
        $validateData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'reference' => 'required|unique:payment_links,reference',
            'description' => 'nullable|string',
            'is_paid' => 'required|boolean',
        ]);

        // Find the user
        $user = User::findOrFail($validateData['user_id']);

        if ($user) {
            // Generate a payment reference using Chapa
            $reference = Chapa::generateReference();

            // Prepare the payment data
            $data = [
                'amount' => $validateData['amount'],
                'email' => $user->email,
                'tx_ref' => $reference,
                'currency' => "ETB",
                'callback_url' => route('callback', [$reference]),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'customization' => [
                    'title' => $validateData['description'] ?? 'Payment Link',
                    'description' => $validateData['description'] ?? 'Payment for goods/services.',
                ],
            ];

            // Initialize the payment and get the response
            $payment = Chapa::initializePayment($data);

            if ($payment['status'] !== 'success') {
                return response()->json(["message" => "Payment link creation failed"], 500);
            }

            // Save the payment link details to the database (assuming the PaymentLink model exists)
            $paymentLink = PaymentLink::create([
                'user_id' => $user->id,
                'amount' => $validateData['amount'],
                'reference' => $reference,
                'description' => $validateData['description'],
                'is_paid' => false,  // Initially, the payment is not completed
                'payment_url' => $payment['data']['checkout_url'],  // Store the generated payment URL
            ]);

            return response()->json([
                "message" => "Payment link created successfully",
                "payment_url" => $payment['data']['checkout_url']
            ], 200);
        } else {
            return response()->json(["message" => "User not found"], 404);
        }
    }
}
