<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\Section;
use App\Modules\Form\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Mapper le type front → back
     */
    private function mapQuestionType(string $type): string
    {
        $mapping = [
            'texte_court'    => 'texte_court',
            'texte_long'     => 'texte_long',
            'choix_unique'   => 'choix_unique',
            'choix_multiple' => 'choix_multiple',
            'date'           => 'date',
            'nombre'         => 'note',
            'notation'       => 'note',
            'fichier'        => 'fichier',
            'liste'          => 'liste',
        ];

        return $mapping[$type] ?? 'texte_court';
    }

    /**
     * GET /forms
     */
    public function index(Request $request)
    {
        $query = Form::with(['sections.questions', 'mission']);

        if ($request->has('est_modele')) {
            $query->where('est_modele', filter_var($request->est_modele, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('mission_id')) {
            $query->where('mission_id', $request->mission_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $forms = $query->latest()->get()->map(function ($form) {
            return [
                'id'           => $form->id,
                'code'         => $form->code,
                'titre'        => $form->titre,
                'description'  => $form->description,
                'mission_id'   => $form->mission_id,
                'mission'      => $form->mission ? [
                    'id' => $form->mission->id,
                    'reference' => $form->mission->reference,
                    'objectif' => $form->mission->objectif,
                ] : null,
                'est_modele'   => (bool) $form->est_modele,
                'version'      => $form->version,
                'statut'       => $form->statut,
                'sections'     => $form->sections->count(),
                'questions'    => $form->sections->sum(fn($s) => $s->questions->count()),
                'cree'         => $form->created_at?->format('Y-m-d'),
                'created_at'   => $form->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $forms,
            'message' => 'Liste des formulaires',
            'errors'  => null,
        ]);
    }

    /**
     * GET /forms/{form}
     */
    public function show(Form $form)
    {
        $form->load(['sections.questions' => function ($q) {
            $q->orderBy('ordre');
        }, 'mission']);

        // Trier les sections par order
        $sections = $form->sections->sortBy('order')->values()->map(function ($section) {
            return [
                'id'          => $section->id,
                'titre'       => $section->title,
                'description' => $section->description,
                'order'       => $section->order,
                'questions'   => $section->questions->sortBy('ordre')->values()->map(function ($q) {
                    return [
                        'id'                  => $q->id,
                        'label'               => $q->libelle,
                        'description_aide'    => $q->description_aide,
                        'type'                => $q->type_question,
                        'options'             => $q->options,
                        'obligatoire'         => (bool) $q->est_obligatoire,
                        'condition_affichage' => $q->condition_affichage,
                        'ordre'               => $q->ordre,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $form->id,
                'code'        => $form->code,
                'titre'       => $form->titre,
                'description' => $form->description,
                'mission_id'  => $form->mission_id,
                'mission'     => $form->mission,
                'est_modele'  => (bool) $form->est_modele,
                'version'     => $form->version,
                'statut'      => $form->statut,
                'sections'    => $sections,
                'created_at'  => $form->created_at,
            ],
            'message' => 'Détail du formulaire',
            'errors'  => null,
        ]);
    }

    /**
     * POST /forms
     */
    public function store(Request $request)
    {
        $request->validate([
            'titre'              => 'required|string|max:255',
            'description'        => 'nullable|string',
            'mission_id'         => 'nullable|uuid|exists:missions,id',
            'est_modele'         => 'nullable|boolean',
            'sections'           => 'required|array|min:1',
            'sections.*.titre'   => 'required|string',
            'sections.*.questions' => 'required|array|min:1',
        ]);

        $form = DB::transaction(function () use ($request) {

            $code = 'FORM-' . strtoupper(Str::random(6));

            $form = Form::create([
                'id'          => (string) Str::uuid(),
                'code'        => $code,
                'mission_id'  => $request->mission_id,
                'titre'       => $request->titre,
                'description' => $request->description,
                'schema'      => ['sections' => $request->sections],
                'est_modele'  => $request->est_modele ?? false,
                'version'     => 1,
                'statut'      => 'brouillon',
                'user_id'     => $request->user()->id,
            ]);

            foreach ($request->input('sections', []) as $sIndex => $sectionData) {
                $section = Section::create([
                    'id'      => (string) Str::uuid(),
                    'form_id' => $form->id,
                    'title'   => $sectionData['titre'],
                    'order'   => $sIndex + 1,
                ]);

                foreach ($sectionData['questions'] as $qIndex => $q) {
                    Question::create([
                        'id'              => (string) Str::uuid(),
                        'section_id'      => $section->id,
                        'libelle'         => $q['label'] ?? 'Question sans libellé',
                        'type_question'   => $this->mapQuestionType($q['type'] ?? 'texte_court'),
                        'options'         => $q['options'] ?? null,
                        'est_obligatoire' => $q['obligatoire'] ?? false,
                        'ordre'           => $qIndex + 1,
                    ]);
                }
            }

            return $form;
        });

        return response()->json([
            'success' => true,
            'data'    => $form->load('sections.questions'),
            'message' => 'Formulaire créé',
            'errors'  => null,
        ], 201);
    }

    /**
     * PUT /forms/{form}
     * RG-FOR-004 : Un formulaire publié ne peut plus être modifié
     */
    public function update(Request $request, Form $form)
    {
        if ($form->statut === 'publie' || $form->statut === 'publié') {
            return response()->json([
                'success' => false,
                'message' => 'Un formulaire publié ne peut plus être modifié (RG-FOR-004). Créez une nouvelle version.',
                'errors'  => null,
            ], 422);
        }

        $request->validate([
            'titre'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'mission_id'  => 'nullable|uuid|exists:missions,id',
            'est_modele'  => 'nullable|boolean',
            'sections'    => 'sometimes|array',
        ]);

        DB::transaction(function () use ($request, $form) {
            $form->update($request->only(['titre', 'description', 'mission_id', 'est_modele']));

            if ($request->has('sections')) {
                // Supprimer anciennes sections + questions et recréer
                $form->sections()->each(function ($s) {
                    $s->questions()->delete();
                    $s->delete();
                });

                foreach ($request->input('sections') as $sIndex => $sectionData) {
                    $section = Section::create([
                        'id'      => (string) Str::uuid(),
                        'form_id' => $form->id,
                        'title'   => $sectionData['titre'],
                        'order'   => $sIndex + 1,
                    ]);

                    foreach ($sectionData['questions'] as $qIndex => $q) {
                        Question::create([
                            'id'              => (string) Str::uuid(),
                            'section_id'      => $section->id,
                            'libelle'         => $q['label'] ?? 'Question',
                            'type_question'   => $this->mapQuestionType($q['type'] ?? 'texte_court'),
                            'options'         => $q['options'] ?? null,
                            'est_obligatoire' => $q['obligatoire'] ?? false,
                            'ordre'           => $qIndex + 1,
                        ]);
                    }
                }

                $form->update(['schema' => ['sections' => $request->input('sections')]]);
            }
        });

        return response()->json([
            'success' => true,
            'data'    => $form->load('sections.questions'),
            'message' => 'Formulaire mis à jour',
            'errors'  => null,
        ]);
    }

    /**
     * PATCH /forms/{form}/publish
     * RG-FOR-004 : passe le formulaire en publié
     */
    public function publish(Form $form)
    {
        if ($form->statut === 'publie' || $form->statut === 'publié') {
            return response()->json([
                'success' => false,
                'message' => 'Ce formulaire est déjà publié.',
                'errors'  => null,
            ], 422);
        }

        $form->update(['statut' => 'publie']);

        return response()->json([
            'success' => true,
            'data'    => $form,
            'message' => 'Formulaire publié. Il ne peut plus être modifié (RG-FOR-004).',
            'errors'  => null,
        ]);
    }

    /**
     * POST /forms/{id}/duplicate
     * RG-FOR-003 : Un modèle peut être dupliqué
     */
    public function duplicate($id, Request $request)
    {
        $original = Form::with('sections.questions')->find($id);

        if (!$original) {
            return response()->json([
                'success' => false,
                'message' => 'Formulaire introuvable',
                'errors'  => null,
            ], 404);
        }

        $newForm = DB::transaction(function () use ($original, $request) {
            $form = Form::create([
                'id'          => (string) Str::uuid(),
                'code'        => $original->code . '-V' . ($original->version + 1),
                'mission_id'  => null,
                'titre'       => $original->titre . ' (copie)',
                'description' => $original->description,
                'schema'      => $original->schema,
                'est_modele'  => $original->est_modele,
                'version'     => 1,
                'statut'      => 'brouillon',
                'user_id'     => $request->user()->id,
            ]);

            foreach ($original->sections as $section) {
                $newSection = Section::create([
                    'id'          => (string) Str::uuid(),
                    'form_id'     => $form->id,
                    'title'       => $section->title,
                    'description' => $section->description,
                    'order'       => $section->order,
                ]);

                foreach ($section->questions as $question) {
                    Question::create([
                        'id'                  => (string) Str::uuid(),
                        'section_id'          => $newSection->id,
                        'libelle'             => $question->libelle,
                        'description_aide'    => $question->description_aide,
                        'type_question'       => $question->type_question,
                        'options'             => $question->options,
                        'est_obligatoire'     => $question->est_obligatoire,
                        'condition_affichage' => $question->condition_affichage,
                        'ordre'               => $question->ordre,
                    ]);
                }
            }

            return $form;
        });

        return response()->json([
            'success' => true,
            'data'    => $newForm->load('sections.questions'),
            'message' => 'Formulaire dupliqué avec succès',
            'errors'  => null,
        ], 201);
    }

    /**
     * DELETE /forms/{form}
     */
    public function destroy(Form $form)
    {
        if ($form->statut === 'publie' || $form->statut === 'publié') {
            return response()->json([
                'success' => false,
                'message' => 'Un formulaire publié ne peut pas être supprimé.',
                'errors'  => null,
            ], 422);
        }

        DB::transaction(function () use ($form) {
            $form->sections()->each(function ($s) {
                $s->questions()->delete();
                $s->delete();
            });
            $form->delete();
        });

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'Formulaire supprimé',
            'errors'  => null,
        ]);
    }
}