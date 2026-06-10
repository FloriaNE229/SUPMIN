<?php

namespace App\Modules\Entities\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEntityRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
        ];
    }
}