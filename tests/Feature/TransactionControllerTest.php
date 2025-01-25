<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Chapa\Chapa\Facades\Chapa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TransactionControllerTest extends TestCase
{
    // Test the transfer method
    public function testTransfer()
    {
        // Mock Chapa::initializePayment response
        $mockedPaymentResponse = [
            'status' => 'success',
            'data' => [
                'checkout_url' => 'https://checkout.chapa.co'
            ]
        ];

        // Mock the response from Chapa
        Chapa::shouldReceive('initializePayment')
            ->once()
            ->andReturn($mockedPaymentResponse);

        // Make a request to the transfer method with mock data
        $response = $this->postJson('/api/transfer', [
            'id' => 1,
            'amount' => 100,
            'recipient_id' => 2,
            'status' => 'pending',
            'transaction_type' => 'transfer',
            'reference' => 'unique-ref-123',
            'description' => 'Payment for services',
        ]);

        // Assert the response redirects to the Chapa checkout URL
        $response->assertRedirect('https://checkout.chapa.co');
    }

    // Test chapaCallback method
    public function testChapaCallback()
    {
        // Mock the response from Chapa API
        $mockedChapaResponse = [
            'status' => 'success',
            'data' => [
                'amount' => 100
            ]
        ];

        // Mock the API call to Chapa's transaction verification endpoint
        Http::fake([
            'https://api.chapa.co/v1/transaction/verify/*' => Http::response($mockedChapaResponse, 200),
        ]);

        // Simulate a callback request
        $response = $this->postJson('/api/callback', [
            'transaction_id' => 'unique-ref-123',
        ]);

        // Assert that the wallet was updated successfully
        $response->assertJson(['message' => 'Wallet updated successfully']);
    }
}

