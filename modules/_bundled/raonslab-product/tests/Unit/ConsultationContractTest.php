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
}
