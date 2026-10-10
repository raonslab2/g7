<?php

namespace Modules\Raonslab\TravelLab\Http\Requests\Workflow;

class UpdateTravelCartRequest extends WorkflowRequest
{
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:'.(int) config('sirsoft-ecommerce.cart.max_quantity', 99)]];
    }
}
