<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

class AddTravelCartRequest extends WorkflowRequest
{
    public function rules(): array
    {
        return [
            'departure_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.(int) config('sirsoft-ecommerce.cart.max_quantity', 99)],
        ];
    }
}
