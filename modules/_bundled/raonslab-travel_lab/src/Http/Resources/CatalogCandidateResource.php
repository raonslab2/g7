<?php

namespace Modules\Raonslab\TravelLab\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Modules\Sirsoft\Ecommerce\Enums\ChargePolicyEnum;

class CatalogCandidateResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        $policy = $this->shippingPolicy;
        $ready = $policy !== null && $policy->is_active && ! $policy->is_default
            && $policy->countrySettings->contains(fn ($country) => $country->country_code === 'KR' && $country->is_active)
            && ! $policy->countrySettings->contains(fn ($country) => $country->charge_policy !== ChargePolicyEnum::FREE
                || $country->extra_fee_enabled || (float) $country->base_fee !== 0.0 || ! empty($country->api_endpoint));

        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'title' => $this->getLocalizedName(),
            'shipping_policy_ready' => $ready,
            'options' => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'option_name' => $option->getLocalizedOptionName(),
                'stock_quantity' => $option->stock_quantity,
                'is_active' => $option->is_active,
            ])->all(),
        ];
    }
}
