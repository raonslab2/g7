<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Services\ConsultationService;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ConsultationIntakeTest extends ModuleTestCase
{
    #[Test]
    /**
     * @scenario case=config_fail_closed
     *
     * @effects config_exposes_disabled_values, intake_fails_closed
     */
    public function config_is_empty_and_intake_fails_closed_without_explicit_server_values(): void
    {
        $this->getJson('/api/modules/raonslab-product/consultations/config')
            ->assertOk()
            ->assertJsonPath('data.intake_enabled', false)
            ->assertJsonPath('data.consent_version', '')
            ->assertJsonPath('data.privacy_copy', '')
            ->assertJsonPath('data.privacy_policy_url', '')
            ->assertJsonPath('data.privacy_links', [])
            ->assertJsonPath('data.privacy_contact', '')
            ->assertJsonPath('data.retention_notice', '');

        $this->postConsultation([], 'synthetic-key-00000001')->assertStatus(503);
        $this->assertDatabaseCount('raonslab_product_consultations', 0);
    }

    #[Test]
    /**
     * @scenario case=request_guards
     *
     * @effects same_origin_enforced, idempotency_key_required
     */
    public function same_origin_and_idempotency_key_are_required(): void
    {
        $this->enableIntake();

        $this->withHeader('Idempotency-Key', 'synthetic-key-00000002')
            ->postJson('/api/modules/raonslab-product/consultations', $this->syntheticPayload())
            ->assertForbidden();

        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000003', 'https://other.example.test')
            ->assertForbidden();

        $this->withHeaders(['Origin' => 'http://localhost', 'Idempotency-Key' => ''])
            ->postJson('/api/modules/raonslab-product/consultations', $this->syntheticPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    #[Test]
    /**
     * @scenario case=validation_failure
     *
     * @effects invalid_consent_rejected, wrong_consent_version_rejected, oversized_message_rejected
     */
    public function validation_rejects_missing_consent_wrong_version_and_oversized_message(): void
    {
        $this->enableIntake();

        $response = $this->postConsultation($this->syntheticPayload([
            'privacy_consent' => false,
            'privacy_consent_version' => 'wrong-version',
            'message' => str_repeat('x', 5001),
        ]), 'synthetic-key-00000004');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['privacy_consent', 'privacy_consent_version', 'message']);
    }

    #[Test]
    /**
     * @scenario case=first_submission
     *
     * @effects submission_persisted_before_success, public_response_excludes_pii, pii_encrypted_at_rest
     */
    public function first_submission_is_201_and_public_response_never_echoes_pii(): void
    {
        $this->enableIntake();

        $response = $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000005');

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'NEW')
            ->assertJsonStructure(['data' => ['reference', 'status', 'received_at']]);
        $this->assertSame(['reference', 'status', 'received_at'], array_keys($response->json('data')));
        $this->assertStringNotContainsString('synthetic@example.test', $response->getContent());

        $raw = DB::table('raonslab_product_consultations')->first();
        $this->assertNotSame('Synthetic Visitor', $raw->contact_name);
        $this->assertNotSame('synthetic@example.test', $raw->email);
        $this->assertNotSame('This is synthetic consultation data used only by automated tests.', $raw->message);
        $this->assertSame(64, strlen($raw->idempotency_key_hash));
        $this->assertSame('NOT_CONFIGURED', $raw->mail_status);
    }

    #[Test]
    /**
     * @scenario case=idempotent_retry
     *
     * @effects idempotent_retry_reuses_reference, single_record_preserved
     */
    public function same_key_and_payload_returns_200_with_same_reference_and_one_row(): void
    {
        $this->enableIntake();
        $payload = $this->syntheticPayload();

        $first = $this->postConsultation($payload, 'synthetic-key-00000006')->assertCreated();
        $retry = $this->postConsultation($payload, 'synthetic-key-00000006')->assertOk();

        $this->assertSame($first->json('data.reference'), $retry->json('data.reference'));
        $this->assertDatabaseCount('raonslab_product_consultations', 1);
        $this->assertDatabaseCount('raonslab_product_consultation_histories', 1);
    }

    #[Test]
    /**
     * @scenario case=idempotency_conflict
     *
     * @effects idempotency_conflict_rejected, single_record_preserved
     */
    public function same_key_with_different_payload_returns_409_without_duplicate(): void
    {
        $this->enableIntake();
        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000007')->assertCreated();

        $this->postConsultation(
            $this->syntheticPayload(['message' => 'A different synthetic message.']),
            'synthetic-key-00000007',
        )->assertStatus(409);

        $this->assertDatabaseCount('raonslab_product_consultations', 1);
    }

    #[Test]
    /**
     * @scenario case=persistence_reload
     *
     * @effects record_persists_across_queries
     */
    public function stored_record_is_loaded_from_the_database_by_a_fresh_model_query(): void
    {
        $this->enableIntake();
        $response = $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000008')->assertCreated();

        $reference = $response->json('data.reference');
        unset($response);

        $this->assertSame(
            $reference,
            Consultation::query()->sole()->reference,
        );
    }

    #[Test]
    /**
     * @scenario case=storage_failure
     *
     * @effects storage_failure_returns_unavailable, failed_storage_creates_no_record
     */
    public function database_failure_returns_503_and_does_not_claim_success(): void
    {
        $this->enableIntake();
        $service = Mockery::mock(ConsultationService::class);
        $service->shouldReceive('submit')->once()->andThrow(new RuntimeException('synthetic storage failure'));
        $this->app->instance(ConsultationService::class, $service);

        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000009')
            ->assertStatus(503)
            ->assertJsonPath('success', false);
        $this->assertDatabaseCount('raonslab_product_consultations', 0);
    }

    #[Test]
    /**
     * @scenario case=log_mailer
     *
     * @effects log_mailer_not_marked_sent
     */
    public function log_mailer_is_not_reported_as_delivered(): void
    {
        $this->enableIntake();
        config([
            'raonslab-product-consultations.notification_to' => 'operator@example.test',
            'mail.default' => 'log',
            'mail.mailers.log.transport' => 'log',
        ]);

        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000010')->assertCreated();

        $this->assertSame('NOT_CONFIGURED', Consultation::query()->sole()->mail_status->value);
    }

    #[Test]
    /**
     * @scenario case=mail_failure
     *
     * @effects mail_failure_recorded_separately, stored_submission_remains_successful
     */
    public function mail_failure_is_recorded_separately_after_successful_storage(): void
    {
        $this->enableIntake();
        config([
            'raonslab-product-consultations.notification_to' => 'operator@example.test',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
        ]);
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('synthetic mail failure'));

        $this->postConsultation($this->syntheticPayload(), 'synthetic-key-00000011')->assertCreated();

        $consultation = Consultation::query()->sole();
        $this->assertSame('FAILED', $consultation->mail_status->value);
        $this->assertNotNull($consultation->mail_attempted_at);
    }

    #[Test]
    /**
     * @scenario case=public_throttle
     *
     * @effects public_rate_limit_enforced
     */
    public function public_store_is_throttled(): void
    {
        $this->enableIntake();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
                ->withHeaders(['Origin' => 'http://localhost', 'Idempotency-Key' => sprintf('throttle-key-%08d', $attempt)])
                ->postJson('/api/modules/raonslab-product/consultations', $this->syntheticPayload())
                ->assertCreated();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->withHeaders(['Origin' => 'http://localhost', 'Idempotency-Key' => 'throttle-key-00000011'])
            ->postJson('/api/modules/raonslab-product/consultations', $this->syntheticPayload())
            ->assertStatus(429);
    }
}
