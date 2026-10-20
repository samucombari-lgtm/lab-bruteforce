<?php

use App\Http\Controllers\ConnexionController;
use Illuminate\Support\Facades\Route;

/*
 * Routes de la cible brute force.
 *
 * - /login est public (sinon impossible de se connecter) et porte le middleware
 *   de limitation de débit, actif seulement en Mode B.
 * - /, /journal et /logout exigent une session ouverte (middleware 'auth').
 */

Route::get('/login', [ConnexionController::class, 'afficherFormulaire'])->name('login');

Route::post('/login', [ConnexionController::class, 'seConnecter'])
    ->middleware('limiter.tentatives');

Route::middleware('auth')->group(function () {
    Route::get('/', [ConnexionController::class, 'afficherAccueil']);
    Route::get('/journal', [ConnexionController::class, 'afficherJournal']);
    Route::post('/logout', [ConnexionController::class, 'seDeconnecter']);
});
