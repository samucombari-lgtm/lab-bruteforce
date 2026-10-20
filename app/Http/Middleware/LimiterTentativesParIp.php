<?php

namespace App\Http\Middleware;

use App\Models\TentativeConnexion;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Limitation du débit des tentatives de connexion par adresse IP (Mode B).
 *
 * En Mode A (vulnérable), ce middleware laisse tout passer : c'est justement
 * l'absence de limite qui rend l'attaque Hydra possible.
 *
 * En Mode B (protégé), il compte les échecs récents de l'adresse IP source ;
 * au-delà du seuil configuré, il refuse la requête avec un code HTTP 429
 * (« Too Many Requests ») avant même d'atteindre le contrôleur de connexion.
 * C'est cette coupure qui fait « s'arrêter » l'attaque dans le journal.
 */
class LimiterTentativesParIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $modeProtegeActif = config('lab.mode') === 'protege';

        if (! $modeProtegeActif) {
            return $next($request);
        }

        $adresseIp = $request->ip();
        $fenetreSecondes = config('lab.fenetre_secondes');
        $seuil = config('lab.max_tentatives_ip');

        $echecsRecents = TentativeConnexion::compterEchecsRecentsParIp($adresseIp, $fenetreSecondes);

        if ($echecsRecents >= $seuil) {
            return response(
                "Trop de tentatives depuis cette adresse. Réessayez plus tard.",
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        return $next($request);
    }
}
