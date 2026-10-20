@extends('layouts.base')

@section('titre', 'Connexion')

@section('contenu')
    <div class="bloc">
        <h1>Connexion</h1>

        {{-- Affiché en cas d'échec. Contient le marqueur attendu par Hydra. --}}
        @if (! empty($erreur))
            <p class="message-erreur">{{ $erreur }}</p>
        @endif

        {{--
            Formulaire ciblé par Hydra : méthode POST vers /login, champs nommés
            exactement « username » et « password » (cahier des charges §4.4).
            Le jeton @csrf protège les vrais navigateurs ; /login est malgré tout
            exclu de la vérification CSRF pour permettre l'exercice (voir bootstrap/app.php).
        --}}
        <form method="POST" action="/login">
            @csrf

            <label for="username">Identifiant</label>
            <input type="text" id="username" name="username" autofocus>

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password">

            <button type="submit">Se connecter</button>
        </form>
    </div>
@endsection
