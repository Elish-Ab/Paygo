<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService {
    protected $token;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
    }

    protected function execute($method, $params = [])
    {
        $url = sprintf('https://api.telegram.org/bot%s/%s', $this->token, $method);
        $request = Http::post($url, $params);

        return $request->json();
    }

    public function getUpdates(int $offset)
    {
        $response = $this->execute('getUpdates', ['offset' => $offset]);
        return $response;
    }

    public function sendMessage($chatID, $text){
        $mesage = $this->execute('sendMessage', [
            'chat_id' => $chatID,
            'text' => $text,
        ]);
        return $mesage;
    }
}
