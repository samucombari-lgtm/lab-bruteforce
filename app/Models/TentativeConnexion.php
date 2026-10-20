<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Une tentative de connexion enregistrée dans le journal (table login_attempts).
 *
 * Chaque tentative, réussie ou non, est tracée. C'est ce journal qui rend
 * l'attaque Hydra visible en Mode A, puis son blocage visible en Mode B.
 *
 * La table ne possède qu'une colonne de date ('created_at') : on désactive donc
 * 'updated_at', mais on laisse Laravel remplir 'created_at' automatiquement.
 */
class TentativeConnexion extends Model
{
    protected $table = 'login_attempts';

    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'ip_address',
        'success',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
        ];
    }

    /**
     * Compte les échecs récents venant d'une même adresse IP.
     *
     * Utilisé par le Mode B pour la limitation de débit : si ce nombre dépasse
     * le seuil configuré, les requêtes suivantes sont refusées (HTTP 429).
     */
    public static function compterEchecsRecentsParIp(string $adresseIp, int $fenetreSecondes): int
    {
        $debutFenetre = Carbon::now()->subSeconds($fenetreSecondes);

        return static::query()
            ->where('ip_address', $adresseIp)
            ->where('success', false)
            ->where('created_at', '>=', $debutFenetre)
            ->count();
    }
}
