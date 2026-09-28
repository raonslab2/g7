<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use Modules\Sirsoft\Board\Models\Board;
use Modules\Sirsoft\Board\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class ConsultationIntakeTest extends ModuleTestCase
{
    #[Test]
    public function intake_fails_closed_without_https_or_explicit_privacy_config(): void
    {
        $this->getJson('/api/modules/raonslab-product/consultations/config')
            ->assertOk()->assertJsonPath('data.intake_enabled', false);

        $this->enableIntake();
        $this->withHeaders(['Origin' => 'http://localhost', 'Idempotency-Key' => 'synthetic-key-00000001'])
            ->postJson('/api/modules/raonslab-product/consultations', $this->syntheticPayload())
            ->assertForbidden();
    }

    #[Test]
    public function guest_submit_persists_one_private_board_post_and_returns_only_opaque_receipt(): void
    {
        $this->enableIntake();
        $notificationCount = DB::table('notifications')->count();

        $response = $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000002')
            ->assertCreated()
            ->assertJsonPath('data.status', 'NEW')
            ->assertJsonStructure(['data' => ['reference', 'status', 'received_at']]);

        $reference = $response->json('data.reference');
        $this->assertMatchesRegularExpression('/^RAON-[A-F0-9]{26}$/', $reference);
        $this->assertStringNotContainsString('synthetic@example.test', $response->getContent());

        $board = Board::where('slug', 'raon-consultations')->sole();
        $post = Post::where('board_id', $board->id)->sole();
        $this->assertFalse($board->is_active);
        $this->assertTrue($post->is_secret);
        $this->assertSame($reference, $post->title);
        $this->assertSame('NEW', $post->category);
        $this->assertStringContainsString('synthetic@example.test', $post->content);
        $this->assertDatabaseCount('raonslab_product_consultations', 0);
        $this->assertSame($notificationCount, DB::table('notifications')->count());
    }

    #[Test]
    public function duplicate_retry_returns_same_receipt_and_keeps_one_post(): void
    {
        $this->enableIntake();
        $payload = $this->syntheticPayload();

        $first = $this->postConsultation($payload, 'synthetic-key-00000003')->assertCreated();
        $retry = $this->postConsultation($payload, 'synthetic-key-00000003')->assertOk();

        $this->assertSame($first->json('data.reference'), $retry->json('data.reference'));
        $this->assertSame(1, Post::whereHas('board', fn ($query) => $query->where('slug', 'raon-consultations'))->count());
    }

    #[Test]
    public function reused_key_with_different_payload_is_rejected(): void
    {
        $this->enableIntake();
        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000004')->assertCreated();

        $this->postConsultation(
            $this->syntheticPayload(['message' => 'Different synthetic content.']),
            'synthetic-key-00000004',
        )->assertStatus(409);

        $this->assertSame(1, Post::whereHas('board', fn ($query) => $query->where('slug', 'raon-consultations'))->count());
    }

    #[Test]
    public function validation_and_legacy_data_guard_remain_enforced(): void
    {
        $this->enableIntake();
        $this->postConsultation($this->syntheticPayload(['privacy_consent' => false]), 'synthetic-key-00000005')
            ->assertStatus(422)->assertJsonValidationErrors(['privacy_consent']);

        DB::table('raonslab_product_consultations')->insert([
            'reference' => 'LEGACY-SYNTHETIC',
            'idempotency_key_hash' => str_repeat('a', 64),
            'payload_hash' => str_repeat('b', 64),
            'contact_name' => 'encrypted-synthetic-name',
            'email' => 'encrypted-synthetic-email',
            'message' => 'encrypted-synthetic-message',
            'privacy_consent_version' => 'synthetic-test-v1',
            'privacy_consented_at' => now(),
            'status' => 'NEW',
            'mail_status' => 'NOT_CONFIGURED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000006')->assertStatus(503);
    }

    #[Test]
    public function public_submission_rate_limit_is_enforced(): void
    {
        $this->enableIntake();
        RateLimiter::clear(sha1('|127.0.0.1'));
        RateLimiter::clear(sha1('|2001:db8::9999'));

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withServerVariables([
                'HTTPS' => 'on',
                'SERVER_PORT' => 443,
                'REMOTE_ADDR' => '2001:db8::9999',
            ])->withHeaders([
                'Origin' => 'https://localhost',
                'Idempotency-Key' => sprintf('rate-limit-key-%04d', $attempt),
            ])->postJson('https://localhost/api/modules/raonslab-product/consultations', $this->syntheticPayload())
                ->assertCreated();
        }

        $this->withServerVariables([
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'REMOTE_ADDR' => '2001:db8::9999',
        ])->withHeaders([
            'Origin' => 'https://localhost',
            'Idempotency-Key' => 'rate-limit-key-0011',
        ])->postJson('https://localhost/api/modules/raonslab-product/consultations', $this->syntheticPayload())
            ->assertStatus(429);
    }
}
