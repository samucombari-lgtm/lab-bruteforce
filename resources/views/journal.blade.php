@extends('layouts.base')

@section('titre', 'Journal des tentatives')

@section('contenu')
    <div class="bloc">
        <h1>Journal des tentatives de connexion</h1>
        <p>
            Les {{ config('lab.taille_journal') }} dernières tentatives. C'est ici
            que l'on voit l'attaque Hydra en direct (Mode A), puis son blocage (Mode B).
        </p>

        <table>
            <thead>
                <tr>
                    <th>Date et heure</th>
                    <th>Identifiant</th>
                    <th>Adresse IP</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tentatives as $tentative)
                    <tr>
                        <td>{{ $tentative->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $tentative->username }}</td>
                        <td>{{ $tentative->ip_address }}</td>
                        <td>
                            @if ($tentative->success)
                                <span class="etat-succes">Réussie</span>
                            @else
                                <span class="etat-echec">Échec</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Aucune tentative enregistrée pour le moment.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="liens">
            <a href="/">Retour à l'accueil</a>
        </div>
    </div>
@endsection
