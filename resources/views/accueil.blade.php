@extends('layouts.base')

@section('titre', 'Accueil')

@section('contenu')
    <div class="bloc">
        <h1>Bienvenue, {{ $utilisateur->username }}</h1>
        <p>
            Vous êtes connecté à la cible d'entraînement. Cette page n'est
            accessible qu'une fois authentifié.
        </p>

        <div class="liens">
            <a href="/journal">Voir le journal des tentatives</a>

            {{-- La déconnexion passe par POST pour rester protégée par le jeton CSRF. --}}
            <form method="POST" action="/logout" style="display:inline">
                @csrf
                <button type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
@endsection
