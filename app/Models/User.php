<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Compte utilisateur de la cible.
 *
 * L'identifiant de connexion est 'username' (et non un email) : l'application
 * se limite à un formulaire identifiant + mot de passe.
 *
 * Les colonnes 'failed_attempts' et 'locked_until' ne sont utilisées qu'en
 * Mode B (protégé) pour verrouiller temporairement un compte attaqué.
 */
class User extends Authenticatable
{
    protected $fillable = [
        'username',
        'password',
        'failed_attempts',
        'locked_until',
    ];

    /**
     * Masqué lors de la sérialisation (par exemple en JSON).
     */
    protected $hidden = [
        'password',
    ];

    /**
     * 'hashed' fait hacher automatiquement le mot de passe à l'écriture :
     * on n'enregistre donc jamais de mot de passe en clair en base.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Indique si le compte est verrouillé à l'instant présent (Mode B).
     */
    public function estVerrouille(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /**
     * Enregistre un échec de connexion et verrouille le compte si le seuil
     * configuré est atteint (Mode B uniquement).
     */
    public function enregistrerEchec(int $seuilVerrouillage, int $dureeVerrouillageMinutes): void
    {
        $this->failed_attempts++;

        if ($this->failed_attempts >= $seuilVerrouillage) {
            $this->locked_until = Carbon::now()->addMinutes($dureeVerrouillageMinutes);
        }

        $this->save();
    }

    /**
     * Remet le compteur d'échecs à zéro après une connexion réussie (Mode B).
     */
    public function reinitialiserEchecs(): void
    {
        $this->failed_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }
}
