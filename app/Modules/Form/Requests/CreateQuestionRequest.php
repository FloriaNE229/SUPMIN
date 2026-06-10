<?php

namespace App\Modules\Form\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Form\Enums\QuestionTypeEnum;

class CreateQuestionRequest extends FormRequest
{
    public function rules()
    {
        return [
            'label' => 'required|string|max:255',

            // ✅ ICI
            'type' => ['required', Rule::in(QuestionTypeEnum::values())],

            'options' => 'nullable|array',
            'is_required' => 'boolean',
        ];
    }
}