<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;  // Add this to use Http::fake
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testWebhook()
    {
        // Fake the external HTTP request to Telegram API
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200), // Mock a successful response
        ]);

        $response = $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 1405766519],
                'text' => '/start',
            ],
        ]);

        $response->assertStatus(200);
    }
}
