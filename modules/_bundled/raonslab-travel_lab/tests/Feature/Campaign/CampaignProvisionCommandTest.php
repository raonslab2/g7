<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature\Campaign;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Raonslab\TravelLab\Database\Seeders\DatabaseSeeder;
use Modules\Raonslab\TravelLab\Tests\CampaignTestCase;
use Modules\Sirsoft\Page\Models\Page;
use Modules\Sirsoft\Page\Services\PageService;

/**
 * 명시 실행 전용 캠페인 프로비저닝 — 실제 native PageService/PageRepository/UserRepository.
 *
 * @scenario case=provisioning
 */
class CampaignProvisionCommandTest extends CampaignTestCase
{
    private const COMMAND = 'raonslab-travel_lab:campaigns-provision';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'raonslab-travel_lab.campaigns.lab_provisioning' => true,
            'mail.default' => 'array', 'queue.default' => 'sync', 'scout.driver' => 'mysql-fulltext',
            'filesystems.default' => 'local', 'filesystems.disks.local.driver' => 'local',
        ]);
    }

    private function pageCount(): int
    {
        return DB::table('pages')->count();
    }

    /** @effects default_flag_false, flag_denied_zero_writes */
    public function test_default_configuration_is_disabled_and_denial_writes_nothing(): void
    {
        $actor = $this->pageAdmin();
        $definition = require dirname(__DIR__, 3).'/config/campaigns.php';
        $this->assertFalse($definition['lab_provisioning'], 'Campaign provisioning flag defaults to false');

        config(['raonslab-travel_lab.campaigns.lab_provisioning' => false]);
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $actor->id])->assertFailed();
        $this->assertSame(0, $this->pageCount());
    }

    /** @effects confirm_required, actor_required, unsafe_environment_denied, permission_required */
    public function test_each_guard_fails_closed_before_any_write(): void
    {
        $actor = $this->pageAdmin();
        $reader = $this->pageAdmin(['sirsoft-page.pages.read']);
        $member = $this->member();

        $this->artisan(self::COMMAND, ['--actor' => (string) $actor->id])->assertFailed();
        $this->artisan(self::COMMAND, ['--lab-confirm' => true])->assertFailed();
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => 'abc'])->assertFailed();
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => '999999'])->assertFailed();
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $reader->id])->assertFailed();
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $member->id])->assertFailed();
        foreach ([['mail.default', 'smtp'], ['queue.default', 'database'], ['scout.driver', 'meilisearch'], ['filesystems.disks.local.driver', 's3']] as [$key, $value]) {
            $previous = config($key);
            config([$key => $value]);
            $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $actor->id])->assertFailed();
            config([$key => $previous]);
        }
        $this->assertSame(0, $this->pageCount());
        $this->assertSame(0, DB::table('page_versions')->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    /** @effects creates_missing_through_native_service, actor_restored, no_tokens */
    public function test_creates_both_slots_through_native_service_and_restores_auth(): void
    {
        $actor = $this->pageAdmin();
        $this->assertFalse(Auth::guard()->hasUser());

        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $actor->id])
            ->expectsOutputToContain('created=2 skipped=0')->assertSuccessful();

        $this->assertFalse(Auth::guard()->hasUser(), 'Provisioning actor is not left on the guard');
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $pages = Page::query()->orderBy('id')->get();
        $this->assertSame([self::AUTUMN, self::WEEKEND], $pages->pluck('slug')->all());
        foreach ($pages as $page) {
            $this->assertTrue($page->published);
            $this->assertSame('text', $page->content_mode);
            $this->assertSame(1, $page->current_version);
            $this->assertSame($actor->id, (int) $page->created_by);
            $this->assertSame(['ko', 'en'], array_keys($page->content));
            $this->assertDoesNotMatchRegularExpression('/[0-9][0-9,]*\s*(원|KRW|₩)|https?:|<img/u', json_encode($page->content, JSON_UNESCAPED_UNICODE));
            $this->assertSame(1, DB::table('page_versions')->where('page_id', $page->id)->count());
        }
        $this->assertSame(0, DB::table('page_attachments')->count());
        $this->getJson(self::BASE.'/campaigns')->assertJsonCount(2, 'data.items');
    }

    /** @effects rerun_preserves_operator_edits, existing_draft_untouched, previous_actor_restored */
    public function test_rerun_skips_every_existing_page_untouched_including_drafts_and_edits(): void
    {
        $actor = $this->pageAdmin();
        $operator = $this->pageAdmin();
        // 다른 관리자가 먼저 만든 초안은 건드리지 않는다.
        $draft = $this->nativeCreate($operator, self::WEEKEND, ['published' => false, 'title' => ['ko' => '운영자 초안', 'en' => 'Operator draft']]);

        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $actor->id])->expectsOutputToContain('created=1 skipped=1')->assertSuccessful();
        $autumn = Page::where('slug', self::AUTUMN)->firstOrFail();
        $autumn = $this->asActor($operator, fn () => app(PageService::class)->updatePage($autumn, ['title' => ['ko' => '운영자 편집', 'en' => 'Operator edit'], 'content' => ['ko' => '운영자 본문', 'en' => 'Operator body']]));
        $autumn = $this->asActor($operator, fn () => app(PageService::class)->changePublishStatus($autumn, false));

        $before = DB::table('pages')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        $versionsBefore = DB::table('page_versions')->count();

        Auth::guard()->setUser($operator);
        $this->artisan(self::COMMAND, ['--lab-confirm' => true, '--actor' => (string) $actor->id])->expectsOutputToContain('created=0 skipped=2')->assertSuccessful();
        $this->assertSame($operator->id, Auth::guard()->id(), 'Previous guard actor restored');
        Auth::guard()->forgetUser();

        $this->assertSame($before, DB::table('pages')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all());
        $this->assertSame($versionsBefore, DB::table('page_versions')->count());
        $this->assertSame(2, $autumn->fresh()->current_version);
        $this->assertFalse($draft->fresh()->published);
        $this->assertSame('Operator draft', $draft->fresh()->title['en']);
    }

    /** @effects default_seed_creates_zero_pages */
    public function test_default_module_seeder_creates_no_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, $this->pageCount());
    }
}
