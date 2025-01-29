<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Log the request for debugging
        \Log::info('Webhook received: ', $request->all());

        // Get the message
        $message = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        // Check if a message was sent
        if ($message) {
            // Respond to the user
            $this->sendMessage($chatId, "You said: " . $message);
        }

        return response('ok', 200);
    }

    private function sendMessage($chatId, $text)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        // Send the message
        file_get_contents($url . '?' . http_build_query($data));
    }
}
