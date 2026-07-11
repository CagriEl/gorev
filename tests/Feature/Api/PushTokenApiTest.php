<?php

namespace Tests\Feature\Api;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_push_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
        ])->assertOk();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
            'device_name' => 'Pixel 8',
        ]);
    }

    public function test_registering_existing_token_reassigns_user(): void
    {
        $oldUser = User::factory()->create();
        $newUser = User::factory()->create();

        PushToken::query()->create([
            'user_id' => $oldUser->id,
            'token' => 'ExponentPushToken[shared]',
        ]);

        Sanctum::actingAs($newUser);

        $this->postJson('/api/v1/push-tokens', [
            'token' => 'ExponentPushToken[shared]',
            'platform' => 'ios',
        ])->assertOk();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $newUser->id,
            'token' => 'ExponentPushToken[shared]',
        ]);
        $this->assertDatabaseMissing('push_tokens', [
            'user_id' => $oldUser->id,
            'token' => 'ExponentPushToken[shared]',
        ]);
    }

    public function test_user_can_revoke_push_token(): void
    {
        $user = User::factory()->create();
        PushToken::query()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[revoke-me]',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/push-tokens/revoke', [
            'token' => 'ExponentPushToken[revoke-me]',
        ])->assertOk();

        $this->assertDatabaseMissing('push_tokens', [
            'token' => 'ExponentPushToken[revoke-me]',
        ]);
    }
}
