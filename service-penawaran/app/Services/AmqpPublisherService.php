<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AmqpPublisherService
{
    public function publishViaHttp(string $routingKey, array $message, string $bearerToken): bool
    {
        $url = env('RABBITMQ_HTTP_URL');

        Log::debug('[AMQP] Sending', [
            'url'         => $url,
            'routing_key' => $routingKey,
            'message'     => $message,
        ]);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $bearerToken,
        ])->post($url, [
            'routing_key' => $routingKey,
            'message'     => $message,
        ]);

        Log::info('[AMQP] Publish result', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response->successful();
    }
}