<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use App\Services\TelegramService;

class TelegramUpdateHandler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tg:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegramService = app(TelegramService::class);
        $updateId = 0;
        $updates = $telegramService->getUpdates($updateId);
        foreach ($updates['result'] as $update) {

            $telegramService->sendMessage($update['message']['chat']['id'], 'Hello, ' . $update['message']['from']['first_name']);
            $updateId = $update['update_id'];
        }
        $updates = $telegramService->getUpdates($updateId+1);
        return Command::SUCCESS;
    }
}
