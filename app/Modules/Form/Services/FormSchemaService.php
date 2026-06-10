<?php

namespace App\Modules\Form\Services;

use Illuminate\Validation\ValidationException;

class FormSchemaService
{
    private array $allowedTypes = [
        'text',       // texte court
        'textarea',   // texte long
        'radio',      // choix unique
        'checkbox',   // choix multiple
        'select',     // liste déroulante
        'rating',     // échelle de notation
        'datetime',   // date/heure
        'file',       // pièce jointe
        'table',      // tableau de saisie
    ];

    /**
    
     *
     * @throws ValidationException
     */
    public function validate(array $data): void
    {
        /*
        |--------------------------------------------------------------------------
        | Root: sections
        |--------------------------------------------------------------------------
        */
        if (
            !array_key_exists('sections', $data) ||
            !is_array($data['sections']) ||
            empty($data['sections'])
        ) {
            throw ValidationException::withMessages([
                'sections' => [
                    'Le champ sections est obligatoire et doit être un tableau non vide.',
                ],
            ]);
        }

        foreach ($data['sections'] as $sectionIndex => $section) {

            /*
            |--------------------------------------------------------------------------
            | Section: title
            |--------------------------------------------------------------------------
            */
            if (
                !array_key_exists('title', $section) ||
                !is_string($section['title']) ||
                trim($section['title']) === ''
            ) {
                throw ValidationException::withMessages([
                    "sections.$sectionIndex.title" => [
                        'Le titre de la section est obligatoire.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Section: questions
            |--------------------------------------------------------------------------
            */
            if (
                !array_key_exists('questions', $section) ||
                !is_array($section['questions']) ||
                empty($section['questions'])
            ) {
                throw ValidationException::withMessages([
                    "sections.$sectionIndex.questions" => [
                        'Chaque section doit contenir au moins une question.',
                    ],
                ]);
            }

            foreach ($section['questions'] as $questionIndex => $question) {

                /*
                |--------------------------------------------------------------------------
                | Question: label
                |--------------------------------------------------------------------------
                */
                if (
                    !array_key_exists('label', $question) ||
                    !is_string($question['label']) ||
                    trim($question['label']) === ''
                ) {
                    throw ValidationException::withMessages([
                        "sections.$sectionIndex.questions.$questionIndex.label" => [
                            'Le label de la question est obligatoire.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Question: type
                |--------------------------------------------------------------------------
                */
                if (!array_key_exists('type', $question)) {
                    throw ValidationException::withMessages([
                        "sections.$sectionIndex.questions.$questionIndex.type" => [
                            'Le type de la question est obligatoire.',
                        ],
                    ]);
                }

                if (
                    !is_string($question['type']) ||
                    !in_array(
                        $question['type'],
                        $this->allowedTypes,
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        "sections.$sectionIndex.questions.$questionIndex.type" => [
                            'Type de question non autorisé.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Question: required
                |--------------------------------------------------------------------------
                */
                if (!array_key_exists('required', $question)) {
                    throw ValidationException::withMessages([
                        "sections.$sectionIndex.questions.$questionIndex.required" => [
                            'Le champ required est obligatoire.',
                        ],
                    ]);
                }

                if (!is_bool($question['required'])) {
                    throw ValidationException::withMessages([
                        "sections.$sectionIndex.questions.$questionIndex.required" => [
                            'Le champ required doit être true ou false.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Questions à choix : options obligatoires
                |--------------------------------------------------------------------------
                */
                if (
                    in_array(
                        $question['type'],
                        ['radio', 'checkbox', 'select'],
                        true
                    )
                ) {
                    if (
                        !array_key_exists('options', $question) ||
                        !is_array($question['options']) ||
                        empty($question['options'])
                    ) {
                        throw ValidationException::withMessages([
                            "sections.$sectionIndex.questions.$questionIndex.options" => [
                                'Les options sont obligatoires pour ce type de question.',
                            ],
                        ]);
                    }

                    foreach ($question['options'] as $optionIndex => $option) {
                        if (
                            !is_string($option) ||
                            trim($option) === ''
                        ) {
                            throw ValidationException::withMessages([
                                "sections.$sectionIndex.questions.$questionIndex.options.$optionIndex" => [
                                    'Chaque option doit être une chaîne non vide.',
                                ],
                            ]);
                        }
                    }
                }
            }
        }
    }
}