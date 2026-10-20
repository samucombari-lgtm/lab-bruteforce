<?php

/**
 * Configuration du laboratoire « cible brute force ».
 *
 * Toutes les valeurs proviennent du fichier .env : le mode de fonctionnement
 * et les seuils de protection ne sont donc jamais écrits en dur dans le code,
 * conformément au cahier des charges. On peut ainsi passer du mode vulnérable
 * au mode protégé sans toucher au code source.
 */

return [

    /*
     * Mode de fonctionnement de l'application.
     *
     * - 'vulnerable' (Mode A) : aucune protection, cible de l'attaque Hydra.
     * - 'protege'    (Mode B) : limitation de débit, verrouillage, journal.
     */
    'mode' => env('LAB_MODE', 'vulnerable'),

    /*
     * Mode B — Limitation du débit par adresse IP.
     *
     * Au-delà de 'max_tentatives_ip' échecs depuis la même adresse IP dans
     * une fenêtre de 'fenetre_secondes' secondes, les requêtes suivantes sont
     * refusées avec un code HTTP 429.
     */
    'max_tentatives_ip' => (int) env('LAB_MAX_TENTATIVES_IP', 5),
    'fenetre_secondes' => (int) env('LAB_FENETRE_SECONDES', 60),

    /*
     * Mode B — Verrouillage temporaire du compte visé.
     *
     * Après 'max_tentatives_compte' échecs consécutifs sur un même compte,
     * celui-ci est verrouillé pendant 'duree_verrouillage_minutes' minutes.
     */
    'max_tentatives_compte' => (int) env('LAB_MAX_TENTATIVES_COMPTE', 5),
    'duree_verrouillage_minutes' => (int) env('LAB_DUREE_VERROUILLAGE_MINUTES', 15),

    /*
     * Nombre de tentatives affichées sur la page /journal.
     */
    'taille_journal' => (int) env('LAB_TAILLE_JOURNAL', 50),

];
