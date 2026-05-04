<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::create('recommendations', function (Blueprint $table) {

     // 🔥 supprimer si existe
        if (Schema::hasColumn('recommendations', 'reference')) {
            $table->dropColumn('reference');
        }
    });

    Schema::table('recommendations', function (Blueprint $table) {
        // 🔥 recréer propre
        $table->string('reference', 50)->unique()->after('question_id');
    

    $table->uuid('id')->primary();

    // relations
    $table->uuid('mission_id');
    $table->uuid('question_id')->nullable();

    $table->foreignId('responsable_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('creee_par')->constrained('users')->cascadeOnDelete();
    $table->foreignId('validee_par')->nullable()->constrained('users')->nullOnDelete();

    //  contenu
    $table->string('reference', 50)->unique();
    $table->string('reference', 50)->unique()->after('question_id');
    $table->string('intitule');
    $table->text('description');

    //  enums
    $table->enum('priorite', ['critique', 'majeur', 'mineur']);

    $table->date('delai_realisation');

    $table->enum('statut', [
        'formulee',
        'transmise',
        'en_cours',
        'mise_en_oeuvre',
        'cloturee',
        'reportee',
        'non_mise_en_oeuvre'
    ])->default('formulee');

    $table->smallInteger('nb_reports')->default(0);

    //  self relation
    $table->uuid('recommandation_parente_id')->nullable();

    $table->timestamps();

    //  FK
    $table->foreign('mission_id')->references('id')->on('missions')->cascadeOnDelete();
    $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
    $table->foreign('recommandation_parente_id')
        ->references('id')->on('recommendations')
        ->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
        Schema::table('recommendations', function (Blueprint $table) {
        $table->dropColumn('reference');
    });
    }
};
