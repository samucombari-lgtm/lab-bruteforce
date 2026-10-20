<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crée les comptes de test de la cible.
 *
 * Les mots de passe sont VOLONTAIREMENT simples et très courants : c'est ce qui
 * permet à l'exercice de brute force d'aboutir en un temps raisonnable (cahier
 * des charges §3.3). Ils sont fournis en clair ici, mais stockés hachés en base
 * grâce au cast 'hashed' du modèle User : le brute force se fait donc côté
 * formulaire, jamais sur la base.
 *
 * À ne jamais reproduire en dehors de ce laboratoire isolé.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $comptesDeTest = [
            ['username' => 'admin', 'password' => 'password'],
            ['username' => 'samuel', 'password' => '123456'],
            ['username' => 'test', 'password' => 'azerty'],
        ];

        foreach ($comptesDeTest as $compte) {
            User::updateOrCreate(
                ['username' => $compte['username']],
                ['password' => $compte['password']],
            );
        }
    }
}
