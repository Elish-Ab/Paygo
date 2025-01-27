<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Telegram\Bot\TelegramClient;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $update = Telegram::getWebhookUpdate();

        if ($update->isType('message')) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();

            if ($text === '/start') {
                $response = "Welcome! You can:\n1. Link your bank account.\n2. Check wallet balance.\n3. Transfer money.";
            } elseif (str_starts_with($text, '/transfer')) {
                $response = $this->handleTransferCommand($chatId, $text);
            } else {
                $response = "Unknown command. Use /start for help.";
            }

            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $response,
            ]);
        }
    }

    private function handleTransferCommand($chatId, $text)
    {
        // Parse command and process transfer
        $parts = explode(' ', $text);
        if (count($parts) !== 3) {
            return "Invalid command. Use: /transfer <amount> <username>";
        }

        $amount = $parts[1];
        $toUsername = $parts[2];

        // Validate and process transaction here
        return "Transfer of $amount to $toUsername initiated.";
    }


}
