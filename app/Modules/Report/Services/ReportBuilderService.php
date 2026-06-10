<?php

namespace App\Modules\Report\Services;

use App\Modules\Mission\Models\Mission;

class ReportBuilderService
{
    public function build($missionId)
    {
        $mission = Mission::with([
            'entity',
            'forms.sections.questions',
            'answers.question',
            'recommendations'
        ])->find($missionId);

        if (!$mission) {
            return null;
        }

        return [
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'status' => $mission->status,
                'entity' => $mission->entity?->name,
            ],

            'responses' => $this->formatResponses($mission),

            'recommendations' => $mission->recommendations->map(function ($rec) {
                return [
                    'title' => $rec->title,
                    'description' => $rec->description,
                    'priority' => $rec->priority,
                ];
            }),
        ];
    }

    private function formatResponses($mission)
    {
        return $mission->forms->map(function ($form) use ($mission) {

            return [
                'form_title' => $form->title,

                'sections' => $form->sections->map(function ($section) use ($mission) {

                    return [
                        'section_title' => $section->title,

                        'questions' => $section->questions->map(function ($question) use ($mission) {

                            $answer = $mission->answers
                                ->where('question_id', $question->id)
                                ->first();

                            return [
                                'question' => $question->label,
                                'type' => $question->type,
                                'answer' => $answer?->value,
                            ];
                        })
                    ];
                })
            ];
        });
    }
}