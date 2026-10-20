<?php

use App\Http\Middleware\LimiterTentativesParIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Raccourci utilisable dans les routes : ->middleware('limiter.tentatives')
        $middleware->alias([
            'limiter.tentatives' => LimiterTentativesParIp::class,
        ]);

        // Choix délibéré de LABORATOIRE, jamais à reproduire en production.
        //
        // Laravel protège les formulaires par un jeton CSRF, ce qui empêche
        // Hydra de poster directement sur /login. Pour que l'exercice de brute
        // force soit possible, on exclut /login de la protection CSRF.
        //
        // Les navigateurs restent protégés : le formulaire Blade envoie quand
        // même son jeton. En Mode B, ce n'est pas le CSRF mais la limitation
        // de débit (code 429) et le verrouillage de compte qui démontrent la
        // défense.
        $middleware->validateCsrfTokens(except: [
            'login',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
