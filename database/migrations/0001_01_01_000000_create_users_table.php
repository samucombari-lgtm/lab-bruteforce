<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des comptes utilisateurs de la cible.
 *
 * Volontairement minimale (cahier des charges §4.2) : l'application ne gère
 * qu'une connexion par identifiant + mot de passe. Il n'y a ni email, ni
 * inscription, ni réinitialisation de mot de passe.
 *
 * Les deux colonnes 'failed_attempts' et 'locked_until' ne servent qu'au
 * Mode B (protégé) pour verrouiller temporairement un compte attaqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
