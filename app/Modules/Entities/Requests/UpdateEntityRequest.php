<?php

namespace App\Modules\Entities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntityRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
        ];
    }
}