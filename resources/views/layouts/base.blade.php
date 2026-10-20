<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titre', 'Cible Brute Force')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    {{--
        Bandeau d'avertissement affiché sur TOUTES les pages (cahier des charges §6).
        Il rappelle le cadre d'usage et indique le mode actif de l'application.
    --}}
    <div class="bandeau-avertissement">
        ⚠️ Cible d'entraînement — laboratoire isolé uniquement. Ne jamais déployer
        sur un réseau réel.
        <span class="mode-actif">
            Mode : {{ config('lab.mode') === 'protege' ? 'B — protégé' : 'A — vulnérable' }}
        </span>
    </div>

    <main class="contenu">
        @yield('contenu')
    </main>
</body>
</html>
