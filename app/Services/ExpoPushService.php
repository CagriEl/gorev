<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ExpoPushService
{
    private const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->pushTokens()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $messages = array_map(fn (string $token): array => [
            'to' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'task-assignments',
        ], $tokens);

        foreach (array_chunk($messages, 100) as $chunk) {
            $this->sendChunk($chunk);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function sendChunk(array $messages): void
    {
        $response = Http::timeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])
            ->post(self::ENDPOINT, $messages);

        if (! $response->successful()) {
            Log::warning('Expo push isteği başarısız.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $this->pruneInvalidTokens($response->json('data', []));
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function pruneInvalidTokens(array $results): void
    {
        foreach ($results as $result) {
            if (($result['status'] ?? '') !== 'error') {
                continue;
            }

            $error = $result['details']['error'] ?? null;
            if (! in_array($error, ['DeviceNotRegistered', 'InvalidCredentials'], true)) {
                continue;
            }

            $token = $result['details']['expoPushToken'] ?? null;
            if (is_string($token) && $token !== '') {
                PushToken::query()->where('token', $token)->delete();
            }
        }
    }
}
