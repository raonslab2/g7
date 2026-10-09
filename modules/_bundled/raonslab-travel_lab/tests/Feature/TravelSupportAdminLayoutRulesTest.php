<?php

namespace Modules\Raonslab\TravelLab\Tests\Feature;

use App\Rules\NoExternalUrls;
use App\Rules\SafeLayoutExpressions;
use App\Rules\ValidLayoutStructure;
use App\Rules\WhitelistedEndpoint;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 관리자 레이아웃이 코어 레이아웃 저장 규칙(구조·엔드포인트·외부 URL·표현식)을 통과하는지 확인합니다.
 *
 * @scenario case=admin_layout_contract
 */
class TravelSupportAdminLayoutRulesTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function layouts(): array
    {
        $files = glob(dirname(__DIR__, 2).'/resources/layouts/admin/*.json') ?: [];

        return collect($files)->mapWithKeys(fn (string $f) => [basename($f) => [$f]])->all();
    }

    /**
     * @scenario case=admin_layout_contract
     *
     * @effects core_layout_store_rules_pass
     */
    #[Test]
    #[DataProvider('layouts')]
    public function admin_layout_passes_core_layout_store_rules(string $file): void
    {
        $content = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

        $validator = Validator::make(['content' => $content], [
            'content' => ['required', 'array', new ValidLayoutStructure, new WhitelistedEndpoint, new NoExternalUrls, new SafeLayoutExpressions],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all(), JSON_UNESCAPED_UNICODE));
    }
}
