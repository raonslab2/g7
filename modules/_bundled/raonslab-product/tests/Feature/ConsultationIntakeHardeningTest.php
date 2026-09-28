<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Services\ConsultationNotificationService;
use Modules\Raonslab\Product\Services\ConsultationService;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * 공개 접수의 https fail-closed, 게시 주소 스킴, 오류 응답 구분, PII 없는 장애 기록을 고정합니다.
 */
class ConsultationIntakeHardeningTest extends ModuleTestCase
{
    #[Test]
    /**
     * @scenario case=https_fail_closed
     *
     * @effects plain_http_request_keeps_intake_closed, intake_fails_closed
     */
    public function intake_stays_closed_over_plain_http_even_when_every_value_is_configured(): void
    {
        $this->enableIntake();

        $this->getJson(self::STORE_PATH.'/config')
            ->assertOk()
            ->assertJsonPath('data.intake_enabled', false);

        $this->withHeaders(['Origin' => 'http://localhost', 'Idempotency-Key' => 'synthetic-http-key-0001'])
            ->postJson(self::STORE_PATH, $this->syntheticPayload())
            ->assertStatus(503)
            ->assertJsonPath('errors.reason', 'intake_disabled')
            ->assertJsonPath('errors.retryable', false);

        $this->assertDatabaseCount('raonslab_product_consultations', 0);

        // 같은 설정이 https 요청에서는 열린다 — 위 결과가 다른 누락값 때문이 아님을 확인합니다.
        $this->getSecureConfig()->assertJsonPath('data.intake_enabled', true);
    }

    #[Test]
    /**
     * @scenario case=https_fail_closed
     *
     * @effects untrusted_forwarded_proto_ignored, intake_fails_closed
     */
    public function untrusted_forwarded_proto_header_does_not_open_intake(): void
    {
        $this->enableIntake();

        $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])
            ->getJson(self::STORE_PATH.'/config')
            ->assertJsonPath('data.intake_enabled', false);

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'Origin' => 'http://localhost',
            'Idempotency-Key' => 'synthetic-xfp-key-00001',
        ])->postJson(self::STORE_PATH, $this->syntheticPayload())->assertStatus(503);

        $this->assertDatabaseCount('raonslab_product_consultations', 0);
    }

    #[Test]
    /**
     * @scenario case=https_fail_closed
     *
     * @effects public_site_url_must_be_https, intake_fails_closed
     */
    public function intake_requires_an_https_public_site_url(): void
    {
        foreach (['http://consult.example.test', '', '/relative', 'https://user@consult.example.test'] as $siteUrl) {
            $this->enableIntake();
            config(['app.url' => $siteUrl]);

            $this->getSecureConfig()->assertJsonPath('data.intake_enabled', false);
            $this->postConsultation($this->syntheticPayload(), 'synthetic-site-key-'.md5($siteUrl))
                ->assertStatus(503)
                ->assertJsonPath('errors.reason', 'intake_disabled');
        }

        $this->assertDatabaseCount('raonslab_product_consultations', 0);
    }

    /** @return array<string, array{string}> */
    public static function untrustedPolicyUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'javascript-slashes' => ['javascript://x%0aalert(1)'],
            'data' => ['data:text/html,<b>x</b>'],
            'relative' => ['/privacy'],
            'protocol-relative' => ['//example.test/privacy'],
            'plain-http' => ['http://example.test/privacy'],
            'userinfo' => ['https://user@example.test/privacy'],
            'whitespace' => ['https://example.test/pri vacy'],
            'backslash' => ['https:\\\\example.test/privacy'],
        ];
    }

    #[Test]
    #[DataProvider('untrustedPolicyUrls')]
    /**
     * @scenario case=url_scheme_policy
     *
     * @effects untrusted_policy_url_rejected, untrusted_url_not_published
     */
    public function untrusted_privacy_policy_url_keeps_intake_closed_and_is_not_published(string $url): void
    {
        $this->enableIntake();
        config(['raonslab-product-consultations.privacy_policy_url' => $url]);

        $this->getSecureConfig()
            ->assertJsonPath('data.intake_enabled', false)
            ->assertJsonPath('data.privacy_policy_url', '')
            ->assertJsonPath('data.privacy_links', []);
    }

    /** @return array<string, array{string, bool}> */
    public static function privacyContacts(): array
    {
        return [
            'email' => ['privacy@example.test', true],
            'phone-text' => ['Tel: 02-000-0000', true],
            'https-url' => ['https://example.test/privacy-contact', true],
            'text-with-https-url' => ['개인정보 담당 https://example.test/contact 로 문의', true],
            'http-url' => ['http://example.test/contact', false],
            'text-with-http-url' => ['문의: http://example.test/contact', false],
            'javascript' => ['javascript:alert(1)', false],
            'data' => ['data:text/html,x', false],
        ];
    }

    #[Test]
    #[DataProvider('privacyContacts')]
    /**
     * @scenario case=url_scheme_policy
     *
     * @effects contact_urls_must_be_https
     */
    public function privacy_contact_urls_must_be_trusted_https(string $contact, bool $open): void
    {
        $this->enableIntake();
        config(['raonslab-product-consultations.privacy_contact' => $contact]);

        $this->getSecureConfig()
            ->assertJsonPath('data.intake_enabled', $open)
            ->assertJsonPath('data.privacy_contact', $open ? $contact : '');
    }

    #[Test]
    /**
     * @scenario case=storage_failure
     *
     * @effects server_failure_logged_without_pii, server_failure_distinct_from_disabled
     */
    public function server_failure_is_logged_without_pii_and_answered_differently_from_disabled_intake(): void
    {
        $this->enableIntake();
        $service = Mockery::mock(ConsultationService::class);
        // 실제 DB 예외 메시지는 SQL 바인딩으로 PII 를 담을 수 있습니다 — 그 형태를 재현합니다.
        $service->shouldReceive('submit')->once()->andThrow(
            new RuntimeException("SQLSTATE[HY000]: insert values ('Synthetic Visitor', 'synthetic@example.test')")
        );
        $this->app->instance(ConsultationService::class, $service);

        Log::spy();

        $response = $this->postConsultation($this->syntheticPayload(), 'synthetic-log-key-00001')
            ->assertStatus(500)
            ->assertJsonPath('errors.reason', 'temporary_failure')
            ->assertJsonPath('errors.retryable', true);
        $incidentId = $response->json('errors.incident_id');
        $this->assertIsString($incidentId);
        $this->assertStringNotContainsString('synthetic@example.test', $response->getContent());
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());

        $logged = $this->capturedErrorLogs();
        $this->assertCount(1, $logged);
        $this->assertSame(['incident_id', 'exception_class', 'route'], array_keys($logged[0]['context']));
        $this->assertSame($incidentId, $logged[0]['context']['incident_id']);
        $this->assertSame(RuntimeException::class, $logged[0]['context']['exception_class']);
        $serialized = json_encode($logged, JSON_UNESCAPED_UNICODE);
        foreach (['synthetic@example.test', 'Synthetic Visitor', 'SQLSTATE', '010-0000-0000'] as $pii) {
            $this->assertStringNotContainsString($pii, $serialized);
        }
    }

    #[Test]
    /**
     * @scenario case=storage_failure
     *
     * @effects post_commit_failure_keeps_success, mail_not_reported_as_sent
     */
    public function failure_after_commit_keeps_the_saved_submission_successful_and_never_marks_mail_sent(): void
    {
        $this->enableIntake();
        config([
            'raonslab-product-consultations.notification_to' => 'operator@example.test',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
        ]);
        $notifications = Mockery::mock(ConsultationNotificationService::class);
        $notifications->shouldReceive('canAttemptDelivery')->andReturn(true);
        $notifications->shouldReceive('deliver')->once()->andThrow(new RuntimeException('synthetic post-commit failure'));
        $this->app->instance(ConsultationNotificationService::class, $notifications);

        Log::spy();

        $response = $this->postConsultation($this->syntheticPayload(), 'synthetic-commit-key-001')->assertCreated();

        $consultation = Consultation::query()->sole();
        $this->assertSame($response->json('data.reference'), $consultation->reference);
        $this->assertSame('PENDING', $consultation->mail_status->value);
        $this->assertNull($consultation->mail_sent_at);
        $this->assertSame(
            [['reference' => $consultation->reference, 'exception_class' => RuntimeException::class]],
            array_column($this->capturedErrorLogs(), 'context'),
        );
    }

    /** @return array<int, array{message: string, context: array<string, mixed>}> */
    private function capturedErrorLogs(): array
    {
        $logged = [];
        Log::shouldHaveReceived('error')->withArgs(function (string $message, array $context = []) use (&$logged): bool {
            $logged[] = ['message' => $message, 'context' => $context];

            return true;
        });

        return $logged;
    }
}
