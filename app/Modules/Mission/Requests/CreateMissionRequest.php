<?php

namespace App\Modules\Mission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class CreateMissionRequest extends FormRequest
{
    /**
     * Autoriser la requête
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Informations principales de la mission
            |--------------------------------------------------------------------------
            */
            'reference' => [
                'required',
                'string',
                'max:50',
                'unique:missions,reference',
            ],

            'entity_id' => [
                'required',
                'uuid',
                'exists:entites,id',
            ],

            'objectif' => [
                'required',
                'string',
            ],

            'axes_prioritaires' => [
                'nullable',
                'array',
            ],

            'date_debut' => [
                'required',
                'date',
            ],

            'date_fin_prevue' => [
                'required',
                'date',
                'after_or_equal:date_debut',
            ],

            'date_fin_effective' => [
                'nullable',
                'date',
            ],

            'statut' => [
                'required',
                Rule::in([
                    'planifiee',
                    'en_cours',
                    'suspendue',
                    'cloturee',
                ]),
            ],

            'annee_supervision' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Formulaires associés
            |--------------------------------------------------------------------------
            */
            'form_ids' => [
                'nullable',
                'array',
            ],

            'form_ids.*' => [
                'uuid',
            ],

            /*
            |--------------------------------------------------------------------------
            | Ancien format : liste simple d'agents
            |--------------------------------------------------------------------------
            */
            'agent_ids' => [
                'nullable',
                'array',
            ],

            'agent_ids.*' => [
                'uuid',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::find($value);

                    if (!$user || !$user->hasRole('agent', 'sanctum')) {
                        $fail("L'utilisateur sélectionné doit avoir le rôle agent.");
                    }
                },
            ],

            /*
            |--------------------------------------------------------------------------
            | Nouveau format : agents avec rôle
            | Exemple :
            | "agents": {
            |   "uuid-user-1": "leader",
            |   "uuid-user-2": "membre"
            | }
            |--------------------------------------------------------------------------
            */
            'agents' => [
                'nullable',
                'array',
            ],

            'agents.*' => [
                Rule::in(['leader', 'membre']),
            ],
        ];
    }

    /**
     * Messages personnalisés
     */
    public function messages(): array
    {
        return [
            'entity_id.exists' => "L'entité sélectionnée n'existe pas.",
            'reference.unique' => 'Cette référence de mission existe déjà.',
            'date_fin_prevue.after_or_equal' =>
                'La date de fin prévue doit être postérieure ou égale à la date de début.',
            'agents.*.in' =>
                'Le rôle de chaque agent doit être leader ou membre.',
        ];
    }
}