<?php

namespace App\Modules\Mission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Modules\Mission\Enums\MissionStatusEnum;

class UpdateMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $missionId = $this->route('mission');

        return [
            'reference' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('missions', 'reference')->ignore($missionId)
            ],

            'title' => ['sometimes', 'string', 'max:255'],

            'status' => ['sometimes', new Enum(MissionStatusEnum::class)],

            'entite_id' => ['sometimes', 'exists:entities,id'],
            'coordinateur_id' => ['sometimes', 'exists:users,id'],

            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],

            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'reference.unique' => 'Cette référence existe déjà.',
            'entite_id.exists' => 'Entité invalide.',
            'coordinateur_id.exists' => 'Utilisateur invalide.',
            'end_date.after' => 'Date de fin invalide.',
            'status.enum' => 'Statut invalide.',
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $mission = $this->route('mission');

            if ($this->status && $mission) {

                $currentStatus = MissionStatusEnum::tryFrom($mission->status);
                $newStatus = MissionStatusEnum::tryFrom($this->status);

                if ($currentStatus && $newStatus) {

                    if (!$currentStatus->canTransitionTo($newStatus)) {
                        $validator->errors()->add(
                            'status',
                            "Transition interdite de {$currentStatus->value} vers {$newStatus->value}"
                        );
                    }
                }
            }
        });
    }
}