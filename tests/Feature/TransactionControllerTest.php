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

    public function testTransfer()
    {
        // Arrange: Create a user and transaction data
        $user = User::factory()->create();
        $data = [
            'amount' => 1000,
            'status' => 'pending',
            'transaction_type' => 'transfer',
        ];

        // Act: Act as the user and send a post request to the transfer endpoint
        $response = $this->actingAs($user)->post('/api/transaction/transfer', $data);

        // Assert: Check if the response is a redirect (302) to Chapa's payment page
        $response->assertStatus(302);
        $response->assertRedirect('https://checkout.chapa.co');  // Redirect to Chapa's payment page

        // Assert that the transaction job was dispatched
        Queue::assertPushed(Transaction::class);
    }

    public function testChapaCallback()
    {
        // Arrange: Create a user and a transaction
        $user = User::factory()->create();
        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $data = [
            'tx_ref' => $transaction->reference,  // Set the correct tx_ref
            'status' => 'completed',
        ];

        // Act: Send a post request to Chapa callback endpoint
        $response = $this->actingAs($user)->post('/api/transaction/chapa/callback', $data);

        // Assert: Check if the response is the expected status
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Transaction completed successfully']);
    }

    public function testChapaCallbackWithoutTxRef()
    {
        // Arrange: Create a user
        $user = User::factory()->create();
        $data = [
            'status' => 'completed',
        ];

        // Act: Send a post request to the Chapa callback endpoint
        $response = $this->actingAs($user)->post('/api/transaction/chapa/callback', $data);

        // Assert: Assert the response is a 400 Bad Request with the correct error message
        $response->assertStatus(400);
        $response->assertJson(['error' => 'tx_ref is required']);
    }

    public function testChapaCallbackTransactionNotFound()
    {
        // Arrange: Create a user
        $user = User::factory()->create();
        $data = [
            'tx_ref' => 'nonexistent_reference',  // This reference doesn't exist
            'status' => 'completed',
        ];

        // Act: Send a post request to the Chapa callback endpoint
        $response = $this->actingAs($user)->post('/api/transaction/chapa/callback', $data);

        // Assert: Assert the response is a 400 Bad Request with the correct error message
        $response->assertStatus(400);
        $response->assertJson(['message' => 'Transaction verification failed']);
    }
}
