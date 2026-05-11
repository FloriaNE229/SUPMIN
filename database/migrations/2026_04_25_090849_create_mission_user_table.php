<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    Schema::create('mission_user', function (Blueprint $table) {

        $table->uuid('mission_id');
        $table->foreign('mission_id')
            ->references('id')
            ->on('missions')
            ->cascadeOnDelete();

        $table->uuid('user_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            
        $table->string('role')->nullable(); // leader, membre

        $table->primary(['mission_id', 'user_id']);
    });
}
};