<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Models\Section;
use App\Modules\Form\Models\Question;
use App\Modules\Form\Services\FormSchemaService;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    /**
     * POST /forms
     */
    public function store(Request $request, FormSchemaService $schema)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation du schéma JSON
        |--------------------------------------------------------------------------
        */
        $schema->validate($request->input('schema'));

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */
        $form = DB::transaction(function () use ($request) {

            // Création du formulaire
          $form = Form::create([
    'code' => $request->input('code'),
    'mission_id' => $request->input('mission_id'),
    'titre' => $request->input('titre'),
    'description' => $request->input('description'),
    'schema' => $request->input('schema'),
    'est_modele' => $request->input('est_modele', false),
    'version' => $request->input('version', 1),
    'statut' => $request->input('statut', 'brouillon'),
    'user_id' => $request->user()->id,
]);

            // Création des sections et questions
            foreach ($request->input('schema.sections', []) as $sIndex => $sectionData) {

                $section = Section::create([
                    'form_id' => $form->id,
                    'title' => $sectionData['title'],
                    'order' => $sIndex + 1,
                ]);

                foreach ($sectionData['questions'] as $qIndex => $questionData) {

                    Question::create([
    'section_id' => $section->id,
    'libelle' => $questionData['label'],
    'description_aide' => $questionData['description'] ?? null,
    'type_question' => $this->mapQuestionType($questionData['type']),
    'options' => $questionData['options'] ?? null,
    'est_obligatoire' => $questionData['required'],
    'condition_affichage' => $questionData['condition_affichage'] ?? null,
    'ordre' => $qIndex + 1,
    'validation_regles' => $questionData['validation_regles'] ?? null,
]);
                }
            }

            return $form;
        });

        return response()->json([
            'success' => true,
            'data' => $form->load('sections.questions'),
            'message' => 'Formulaire créé',
            'errors' => null,
        ], 201);
    }

    /**
     * GET /forms
     */
    public function index()
    {
        $forms = Form::with('sections.questions')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $forms,
            'message' => 'Liste des formulaires',
            'errors' => null,
        ]);
    }

    /**
     * POST /forms/{id}/duplicate
     */

public function duplicate($id, Request $request)
{
    $original = Form::with('sections.questions')->find($id);

    if (!$original) {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Formulaire introuvable',
            'errors' => null,
        ], 404);
    }

    $newForm = DB::transaction(function () use ($original, $request) {

        /*
        |--------------------------------------------------------------------------
        | Duplication du formulaire
        |--------------------------------------------------------------------------
        */
        $form = Form::create([
            'code' => $original->code . '-COPY',
            'mission_id' => null, // un template n'est pas lié à une mission
            'titre' => $original->titre . ' (copie)',
            'description' => $original->description,
            'schema' => $original->schema,
            'est_modele' => $original->est_modele,
            'version' => $original->version + 1,
            'statut' => 'brouillon', // valeur conforme à la migration
            'user_id' => $request->user()->id, // OBLIGATOIRE
        ]);

        /*
        |--------------------------------------------------------------------------
        | Duplication des sections et questions
        |--------------------------------------------------------------------------
        */
        foreach ($original->sections as $section) {

            $newSection = Section::create([
                'form_id' => $form->id,
                'title' => $section->title,
                'description' => $section->description,
                'order' => $section->order,
            ]);

            foreach ($section->questions as $question) {

                Question::create([
                    'section_id' => $newSection->id,
                    'libelle' => $question->libelle,
                    'description_aide' => $question->description_aide,
                    'type_question' => $question->type_question,
                    'options' => $question->options,
                    'est_obligatoire' => $question->est_obligatoire,
                    'condition_affichage' => $question->condition_affichage,
                    'ordre' => $question->ordre,
                    'validation_regles' => $question->validation_regles,
                ]);
            }
        }

        return $form;
    });

    return response()->json([
        'success' => true,
        'data' => $newForm->load('sections.questions'),
        'message' => 'Formulaire dupliqué avec succès',
        'errors' => null,
    ], 201);
}



private function mapQuestionType(string $type): string
{
    $mapping = [
        'text' => 'texte_court',
        'textarea' => 'texte_long',
        'number' => 'note',
        'select' => 'liste',
        'radio' => 'choix_unique',
        'checkbox' => 'choix_multiple',
        'date' => 'date',
        'file' => 'fichier',
        'table' => 'tableau',
        'image' => 'fichier',
    ];

    return $mapping[$type] ?? 'texte_court';
}
}