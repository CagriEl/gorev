<?php

namespace Tests\Unit;

use App\Models\PushToken;
use App\Models\User;
use App\Services\ExpoPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpoPushServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_push_to_user_tokens(): void
    {
        Http::fake([
            'exp.host/*' => Http::response(['data' => [['status' => 'ok']]], 200),
        ]);

        $user = User::factory()->create();
        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[test-token]',
            'platform' => 'android',
        ]);

        app(ExpoPushService::class)->sendToUser($user, 'Başlık', 'Mesaj', ['task_id' => 1]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && $body[0]['to'] === 'ExponentPushToken[test-token]'
                && $body[0]['title'] === 'Başlık'
                && $body[0]['body'] === 'Mesaj'
                && $body[0]['data']['task_id'] === 1;
        });
    }

    public function test_skips_request_when_user_has_no_tokens(): void
    {
        Http::fake();

        $user = User::factory()->create();

        app(ExpoPushService::class)->sendToUser($user, 'Başlık', 'Mesaj');

        Http::assertNothingSent();
    }

    public function test_prunes_invalid_tokens(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [[
                    'status' => 'error',
                    'details' => [
                        'error' => 'DeviceNotRegistered',
                        'expoPushToken' => 'ExponentPushToken[stale]',
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[stale]',
        ]);

        app(ExpoPushService::class)->sendToUser($user, 'Başlık', 'Mesaj');

        $this->assertDatabaseMissing('push_tokens', ['token' => 'ExponentPushToken[stale]']);
    }
}
