<?php

namespace Modules\Raonslab\Product\Tests\Feature;

require_once __DIR__.'/../ModuleTestCase.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\Product\Models\Consultation;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ConsultationAdminTest extends ModuleTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableIntake();
        $this->admin = $this->createAdminUser([
            'raonslab-product.consultations.read',
            'raonslab-product.consultations.manage',
        ]);
    }

    #[Test]
    /**
     * @scenario case=admin_authorization
     *
     * @effects admin_auth_required, admin_permission_required
     */
    public function guest_and_regular_user_cannot_access_admin_api(): void
    {
        $this->getJson('/api/modules/raonslab-product/admin/consultations')->assertUnauthorized();

        $this->actingAs($this->createRegularUser())
            ->getJson('/api/modules/raonslab-product/admin/consultations')
            ->assertForbidden();
    }

    #[Test]
    /**
     * @scenario case=admin_read_manage_separation
     *
     * @effects read_permission_allows_queries, manage_permission_required_for_mutations
     */
    public function read_only_admin_can_query_but_cannot_add_notes_or_change_status(): void
    {
        $consultation = $this->submit('admin-read-only-key');
        $reader = $this->createAdminUser([
            'raonslab-product.consultations.read',
        ]);

        $this->actingAs($reader)
            ->getJson('/api/modules/raonslab-product/admin/consultations')
            ->assertOk();

        $this->actingAs($reader)
            ->getJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}")
            ->assertOk();

        $this->actingAs($reader)
            ->postJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/notes", [
                'note' => 'This must not be stored.',
            ])->assertForbidden();

        $this->actingAs($reader)
            ->patchJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/status", [
                'status' => 'CONTACTED',
            ])->assertForbidden();

        $this->assertDatabaseCount('raonslab_product_consultation_histories', 1);
    }

    #[Test]
    /**
     * @scenario case=admin_read
     *
     * @effects admin_list_filter_works, admin_detail_contains_pii_for_authorized_user
     */
    public function admin_can_list_filter_and_view_consultation(): void
    {
        $new = $this->submit('admin-list-new-key');
        $contacted = $this->submit('admin-list-contacted-key');
        $contacted->forceFill(['status' => 'CONTACTED'])->save();

        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations?status=CONTACTED')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.reference', $contacted->reference);

        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations/'.$new->reference)
            ->assertOk()
            ->assertJsonPath('data.email', 'synthetic@example.test')
            ->assertJsonPath('data.message', 'This is synthetic consultation data used only by automated tests.')
            ->assertJsonPath('data.history.0.event_type', 'CREATED');
    }

    #[Test]
    /**
     * @scenario case=admin_mutation
     *
     * @effects internal_note_history_preserved, ordered_status_history_preserved, close_outcome_preserved, admin_sensitive_fields_encrypted
     */
    public function note_and_ordered_status_changes_are_preserved_in_history(): void
    {
        $consultation = $this->submit('admin-history-key');

        $this->actingAs($this->admin)
            ->postJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/notes", [
                'note' => 'Synthetic internal note.',
            ])
            ->assertOk()
            ->assertJsonPath('data.history.1.event_type', 'NOTE_ADDED');

        foreach (['CONTACTED', 'QUALIFIED'] as $status) {
            $this->actingAs($this->admin)
                ->patchJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/status", [
                    'status' => $status,
                ])->assertOk()->assertJsonPath('data.status', $status);
        }

        $this->actingAs($this->admin)
            ->patchJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/status", [
                'status' => 'CLOSED',
                'close_outcome' => 'Synthetic outcome',
            ])->assertOk()
            ->assertJsonPath('data.status', 'CLOSED')
            ->assertJsonPath('data.close_outcome', 'Synthetic outcome');

        $this->assertDatabaseCount('raonslab_product_consultation_histories', 5);
        $rawNote = DB::table('raonslab_product_consultation_histories')
            ->where('event_type', 'NOTE_ADDED')->value('note');
        $this->assertNotSame('Synthetic internal note.', $rawNote);
        $this->assertNotSame(
            'Synthetic outcome',
            DB::table('raonslab_product_consultations')->value('close_outcome'),
        );
    }

    #[Test]
    /**
     * @scenario case=invalid_status_transition
     *
     * @effects invalid_status_transition_rejected, close_requires_outcome
     */
    public function status_cannot_skip_steps_and_closed_requires_outcome(): void
    {
        $consultation = $this->submit('admin-invalid-status-key');

        $this->actingAs($this->admin)
            ->patchJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/status", [
                'status' => 'QUALIFIED',
            ])->assertStatus(422);

        $consultation->forceFill(['status' => 'QUALIFIED'])->save();
        $this->actingAs($this->admin)
            ->patchJson("/api/modules/raonslab-product/admin/consultations/{$consultation->reference}/status", [
                'status' => 'CLOSED',
            ])->assertStatus(422)
            ->assertJsonValidationErrors(['close_outcome']);
    }

    #[Test]
    /**
     * @scenario case=admin_pagination
     *
     * @effects admin_list_exposes_pagination_meta, admin_list_filter_works
     */
    public function list_exposes_total_page_and_per_page_for_navigation(): void
    {
        $first = $this->submit('admin-page-one-key');
        $this->submit('admin-page-two-key');
        $third = $this->submit('admin-page-three-key');
        $third->forceFill(['status' => 'CONTACTED'])->save();

        $page1 = $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.last_page', 2)
            ->assertJsonPath('data.meta.per_page', 2)
            ->assertJsonPath('data.meta.total', 3)
            ->assertJsonPath('data.meta.from', 1)
            ->assertJsonPath('data.meta.to', 2);

        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.data.0.reference', $first->reference);

        // 페이지 경계에서 행이 겹치거나 빠지지 않습니다.
        $this->assertNotContains($first->reference, array_column($page1->json('data.data'), 'reference'));

        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations?status=NEW&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.meta.last_page', 2)
            ->assertJsonCount(1, 'data.data');

        // 범위를 벗어난 페이지는 빈 목록 + 총 건수로 응답해 화면이 "빈 페이지" 와 "상담 없음" 을 구분합니다.
        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations?per_page=2&page=9')
            ->assertOk()
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.meta.total', 3);
    }

    #[Test]
    /**
     * @scenario case=admin_read_manage_separation
     *
     * @effects server_abilities_reflect_manage_permission, next_status_from_server
     */
    public function abilities_and_next_status_come_from_the_server(): void
    {
        $consultation = $this->submit('admin-abilities-key');
        $reader = $this->createAdminUser(['raonslab-product.consultations.read']);
        $base = '/api/modules/raonslab-product/admin/consultations';

        $this->actingAs($reader)->getJson($base)->assertJsonPath('data.abilities.can_manage', false);
        $this->actingAs($reader)->getJson("{$base}/{$consultation->reference}")
            ->assertJsonPath('data.abilities.can_manage', false)
            ->assertJsonPath('data.next_status', 'CONTACTED');

        $this->actingAs($this->admin)->getJson($base)->assertJsonPath('data.abilities.can_manage', true);
        $this->actingAs($this->admin)->getJson("{$base}/{$consultation->reference}")
            ->assertJsonPath('data.abilities.can_manage', true);

        $this->actingAs($this->admin)
            ->patchJson("{$base}/{$consultation->reference}/status", ['status' => 'CONTACTED'])
            ->assertOk()
            ->assertJsonPath('data.status', 'CONTACTED')
            ->assertJsonPath('data.next_status', 'QUALIFIED')
            ->assertJsonPath('data.abilities.can_manage', true);

        $consultation->forceFill(['status' => 'CLOSED'])->save();
        $this->actingAs($this->admin)->getJson("{$base}/{$consultation->reference}")
            ->assertJsonPath('data.next_status', null);
    }

    #[Test]
    /**
     * @scenario case=admin_read
     *
     * @effects missing_consultation_returns_404
     */
    public function unknown_reference_returns_404_for_the_detail_screen(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/api/modules/raonslab-product/admin/consultations/RAON-DOES-NOT-EXIST')
            ->assertNotFound();
    }

    private function submit(string $key): Consultation
    {
        $this->postConsultation($this->syntheticPayload(), str_pad($key, 16, '-'))->assertCreated();

        return Consultation::query()->latest('id')->firstOrFail();
    }
}
