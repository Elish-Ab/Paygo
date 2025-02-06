<?php
namespace App\Http\Controllers;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Chapa\Chapa\Facades\Chapa;


class ChapaController extends Controller
{
    public function initialize(Request $request)
    {
        $chapa = new Chapa(env('CHAPA_SECRET_KEY'));

        $data = [
            'amount' => $request->amount,
            'currency' => 'ETB',
            'email' => $request->email,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'tx_ref' => uniqid(),
            'callback_url' => route('chapa.callback'),
        ];

        $transaction = $chapa->initialize($data);
        return redirect($transaction['checkout_url']);
    }

    public function callback(Request $request)
    {
        $chapa = new Chapa(env('CHAPA_SECRET_KEY'));

        $response = $chapa->verifyTransaction($request->tx_ref);

        if ($response['status'] === 'success') {
            // Update transaction status and user's wallet
            $transaction = Transaction::where('reference', $request->tx_ref)->first();
            $transaction->status = 'success';
            $transaction->save();

            return response()->json(['message' => 'Payment successful!']);
        }

        return response()->json(['message' => 'Payment failed.'], 400);
    }
}
