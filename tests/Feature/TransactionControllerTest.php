<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testWebhook()
{
    $response = $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 1405766519],
            'text' => '/start',
        ],
    ]);

    $response->assertStatus(200);
}

}
