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
        Schema::create('permissions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('guard_name');
    $table->timestamps();
});

Schema::create('model_has_permissions', function (Blueprint $table) {
    $table->uuid('permission_id');
    $table->string('model_type');
    $table->uuid('model_id');

    $table->primary(['permission_id', 'model_id', 'model_type']);
});

Schema::create('model_has_roles', function (Blueprint $table) {
    $table->uuid('role_id');
    $table->string('model_type');
    $table->uuid('model_id');

    $table->primary(['role_id', 'model_id', 'model_type']);
});

Schema::create('role_has_permissions', function (Blueprint $table) {
    $table->uuid('permission_id');
    $table->uuid('role_id');

    $table->primary(['permission_id', 'role_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spatie_permission_tables');
    }
};
