<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des tentatives de connexion (cahier des charges §4.2 et §4.3).
 *
 * Chaque tentative, réussie ou non, y est enregistrée. C'est ce journal qui
 * permet de VOIR l'attaque Hydra en direct en Mode A, puis de la voir être
 * stoppée en Mode B.
 *
 * On indexe (ip_address, created_at) car le Mode B compte très souvent les
 * échecs récents d'une même adresse IP pour appliquer la limitation de débit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('ip_address', 45);
            $table->boolean('success')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index(['ip_address', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
