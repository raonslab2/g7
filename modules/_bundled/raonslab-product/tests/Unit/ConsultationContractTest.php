<?php

namespace Modules\Raonslab\Product\Tests\Unit;

require_once __DIR__.'/../ModuleTestCase.php';

use Modules\Raonslab\Product\Http\Middleware\EnsureConsultationIntakeEnabled;
use Modules\Raonslab\Product\Http\Middleware\RequireSameOrigin;
use Modules\Raonslab\Product\Module;
use Modules\Raonslab\Product\Tests\ModuleTestCase;
use PHPUnit\Framework\Attributes\Test;

class ConsultationContractTest extends ModuleTestCase
{
    #[Test]
    /**
     * @scenario case=module_registration
     *
     * @effects extension_config_registered, admin_permissions_registered, admin_menu_registered, public_guards_registered
     */
    public function module_declares_config_permissions_menu_and_public_mutation_guards(): void
    {
        $module = new Module;

        $this->assertArrayHasKey('raonslab-product-consultations', $module->getConfig());
        $this->assertSame('raonslab-product.consultations.read', $module->getAdminMenus()[0]['permission']);

        $permissions = $module->getPermissions()['categories'][0]['permissions'];
        $this->assertSame(['admin'], $permissions[0]['roles']);
        $this->assertSame(['admin'], $permissions[1]['roles']);

        $middleware = collect($module->getMiddleware())->keyBy('class');
        $this->assertSame(
            ['api.modules.raonslab-product.consultations.store'],
            $middleware[RequireSameOrigin::class]['targets'],
        );
        $this->assertSame(
            ['api.modules.raonslab-product.consultations.store'],
            $middleware[EnsureConsultationIntakeEnabled::class]['targets'],
        );
    }

    #[Test]
    /**
     * @scenario case=admin_layout_contract
     *
     * @effects admin_layout_contract_validated
     */
    public function admin_layout_json_uses_supported_components_and_action_contracts(): void
    {
        foreach (['admin_consultation_list.json', 'admin_consultation_detail.json'] as $file) {
            $json = file_get_contents(dirname(__DIR__, 2).'/resources/layouts/admin/'.$file);
            $this->assertIsArray(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
            $this->assertStringNotContainsString('G7Core.actions.execute', $json);
            $this->assertStringNotContainsString('"handler": "api"', $json);
            $this->assertStringNotContainsString('"handler": "showToast"', $json);
        }
    }

    #[Test]
    /**
     * @scenario case=admin_pagination
     *
     * @effects list_pagination_controls_declared, list_context_preserved
     */
    public function list_layout_declares_accessible_pagination_and_preserves_list_context(): void
    {
        $layout = $this->layout('admin_consultation_list.json');

        $pagination = $this->nodesNamed($layout, 'Pagination');
        $this->assertCount(1, $pagination);
        $this->assertSame('{{consultations?.data?.meta?.current_page ?? 1}}', $pagination[0]['props']['currentPage']);
        $this->assertSame('{{consultations?.data?.meta?.last_page ?? 1}}', $pagination[0]['props']['totalPages']);
        $pageChange = $pagination[0]['actions'][0];
        $this->assertSame('onPageChange', $pageChange['event']);
        $this->assertSame('navigate', $pageChange['handler']);
        $this->assertTrue($pageChange['params']['mergeQuery']);
        $this->assertSame(['page' => '{{$args[0]}}'], $pageChange['params']['query']);

        $summary = $this->nodeById($layout, 'consultation_list_summary');
        foreach (['meta?.total', 'meta?.current_page', 'meta?.last_page', 'meta?.per_page'] as $field) {
            $this->assertStringContainsString($field, $summary['text']);
        }

        // 필터 변경은 재조회가 필요하다 — replaceUrl 은 데이터소스를 다시 부르지 않는다.
        $filter = $this->nodeById($layout, 'consultation_status_filter')['actions'][0];
        $this->assertSame('navigate', $filter['handler']);
        $this->assertTrue($filter['params']['mergeQuery']);
        $this->assertSame('', $filter['params']['query']['page']);
        $this->assertStringNotContainsString('"replaceUrl"', json_encode($layout));

        // 목록 클러스터 안의 모든 이동은 목록 상태(status/page/per_page)를 보존한다.
        foreach (['admin_consultation_list.json', 'admin_consultation_detail.json'] as $file) {
            foreach ($this->actionsWithHandler($this->layout($file), 'navigate') as $navigate) {
                $this->assertTrue($navigate['params']['mergeQuery'] ?? false, $file.' navigate must merge list query');
                $this->assertIsArray($navigate['params']['query'] ?? null, $file.' navigate must declare query');
                $this->assertStringNotContainsString('?', $navigate['params']['path']);
            }
        }
    }

    #[Test]
    /**
     * @scenario case=admin_layout_contract
     *
     * @effects list_rows_keyboard_accessible, list_states_distinct
     */
    public function list_rows_are_keyboard_accessible_and_states_are_distinct(): void
    {
        $layout = $this->layout('admin_consultation_list.json');
        $row = $this->nodeById($layout, 'consultation_row_{{idx}}');

        // 클릭 가능한 비대화형 Div 를 두지 않는다 — 상세 이동은 포커스 가능한 Button 이 맡는다.
        $this->assertArrayNotHasKey('actions', $row);
        $openButtons = array_values(array_filter(
            $this->nodesNamed($row, 'Button'),
            fn (array $node): bool => ($node['actions'][0]['handler'] ?? null) === 'navigate',
        ));
        $this->assertCount(1, $openButtons);
        $this->assertSame('button', $openButtons[0]['props']['type']);
        $this->assertStringContainsString('{{consultation.reference', $openButtons[0]['props']['aria-label']);
        $this->assertSame('/admin/consultations/{{consultation.reference}}', $openButtons[0]['actions'][0]['params']['path']);

        $loading = $this->nodeById($layout, 'consultation_list_loading');
        $error = $this->nodeById($layout, 'consultation_list_error');
        $ready = $this->nodeById($layout, 'consultation_list_ready');
        $empty = $this->nodeById($layout, 'consultation_empty');
        $this->assertSame('LoadingSpinner', $loading['name']);
        $this->assertStringContainsString('$loading === true', $loading['if']);
        $this->assertStringContainsString('_dataSourceErrors?.consultations != null', $error['if']);
        $this->assertStringContainsString('_dataSourceErrors?.consultations == null', $ready['if']);
        $this->assertStringContainsString('$loading !== true', $error['if']);
        $this->assertStringContainsString('$loading !== true', $ready['if']);
        $this->assertSame('EmptyState', $empty['name']);
        $this->assertStringContainsString('meta?.total ?? 0) === 0', $empty['if']);
        $this->assertSame('refetchDataSource', $this->actionsWithHandler($error, 'refetchDataSource')[0]['handler']);
        $this->assertNotNull($this->nodeById($ready, 'consultation_page_out_of_range'));
    }

    #[Test]
    /**
     * @scenario case=admin_layout_contract
     *
     * @effects detail_mutations_gated_by_server_ability, detail_submit_disabled_while_pending, detail_404_handled
     */
    public function detail_gates_mutations_on_server_abilities_and_disables_pending_submits(): void
    {
        $layout = $this->layout('admin_consultation_detail.json');

        $errorHandling = $layout['data_sources'][0]['errorHandling'];
        $this->assertSame('showErrorPage', $errorHandling['404']['handler']);
        $this->assertSame('showErrorPage', $errorHandling['403']['handler']);
        $this->assertStringContainsString('_dataSourceErrors?.consultation != null', $this->nodeById($layout, 'consultation_detail_error')['if']);

        foreach (['consultation_status_card', 'consultation_note_card'] as $card) {
            $this->assertSame('{{consultation?.data?.abilities?.can_manage === true}}', $this->nodeById($layout, $card)['if']);
        }
        $this->assertSame(
            '{{consultation?.data?.abilities?.can_manage !== true}}',
            $this->nodeById($layout, 'consultation_read_only_notice')['if'],
        );

        // 상태 선택지는 서버가 알려 준 다음 상태 하나뿐이다(클라이언트가 전이 규칙을 만들지 않는다).
        $this->assertStringNotContainsString('"QUALIFIED"', json_encode($layout));
        $this->assertStringContainsString('next_status', json_encode($this->nodeById($layout, 'consultation_status_submit')));

        foreach ([
            'consultation_status_submit' => 'raonConsultationStatusSaving',
            'consultation_note_submit' => 'raonConsultationNoteSaving',
        ] as $id => $flag) {
            $button = $this->nodeById($layout, $id);
            $this->assertSame('button', $button['props']['type']);
            $this->assertStringContainsString("_global.{$flag} === true", $button['props']['disabled']);

            $sequence = $button['actions'][0];
            $this->assertSame('sequence', $sequence['handler']);
            $this->assertSame(['target' => 'global', $flag => true], $sequence['actions'][0]['params']);
            $apiCall = $sequence['actions'][1];
            $this->assertSame('apiCall', $apiCall['handler']);
            foreach (['onSuccess', 'onError'] as $branch) {
                $this->assertSame(['target' => 'global', $flag => false], $apiCall[$branch][0]['params'], "{$id} {$branch} must release pending flag first");
            }
        }
    }

    /** @return array<string, mixed> */
    private function layout(string $file): array
    {
        return json_decode(
            file_get_contents(dirname(__DIR__, 2).'/resources/layouts/admin/'.$file),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function nodesNamed(array $tree, string $name): array
    {
        $found = [];
        $walk = function (mixed $node) use (&$walk, &$found, $name): void {
            if (! is_array($node)) {
                return;
            }
            if (($node['name'] ?? null) === $name && isset($node['type'])) {
                $found[] = $node;
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        return $found;
    }

    /** @return array<string, mixed> */
    private function nodeById(array $tree, string $id): array
    {
        $found = null;
        $walk = function (mixed $node) use (&$walk, &$found, $id): void {
            if ($found !== null || ! is_array($node)) {
                return;
            }
            if (($node['id'] ?? null) === $id) {
                $found = $node;

                return;
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($tree);
        $this->assertIsArray($found, "node {$id} must exist");

        return $found;
    }

    /** @return array<int, array<string, mixed>> */
    private function actionsWithHandler(array $tree, string $handler): array
    {
        $found = [];
        $walk = function (mixed $node) use (&$walk, &$found, $handler): void {
            if (! is_array($node)) {
                return;
            }
            if (($node['handler'] ?? null) === $handler) {
                $found[] = $node;
            }
            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($tree);

        return $found;
    }
}
