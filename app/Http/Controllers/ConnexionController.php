<?php

namespace App\Http\Controllers;

use App\Models\TentativeConnexion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Gère l'unique fonctionnalité de la cible : la connexion par identifiant et
 * mot de passe, ainsi que l'affichage de l'accueil et du journal.
 *
 * La logique de connexion s'adapte au mode configuré :
 *   - Mode A (vulnérable) : vérification du mot de passe uniquement.
 *   - Mode B (protégé)    : en plus, verrouillage du compte après trop d'échecs.
 * Dans les deux modes, chaque tentative est journalisée.
 */
class ConnexionController extends Controller
{
    /**
     * Marqueur d'échec attendu par Hydra : la page d'erreur doit contenir
     * exactement cette chaîne (cahier des charges §4.4).
     */
    private const MESSAGE_ECHEC = 'Identifiants incorrects';

    /**
     * Affiche le formulaire de connexion (GET /login).
     */
    public function afficherFormulaire(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/');
        }

        return view('connexion');
    }

    /**
     * Traite une tentative de connexion (POST /login).
     */
    public function seConnecter(Request $request): RedirectResponse|Response
    {
        $username = (string) $request->input('username');
        $password = (string) $request->input('password');
        $adresseIp = (string) $request->ip();

        $compte = User::where('username', $username)->first();
        $modeProtegeActif = config('lab.mode') === 'protege';

        if ($modeProtegeActif && $compte !== null && $compte->estVerrouille()) {
            return $this->refuser($username, $adresseIp);
        }

        $motDePasseCorrect = $compte !== null && Hash::check($password, $compte->password);

        if ($motDePasseCorrect) {
            return $this->accepter($compte, $adresseIp, $modeProtegeActif, $request);
        }

        if ($modeProtegeActif && $compte !== null) {
            $compte->enregistrerEchec(
                config('lab.max_tentatives_compte'),
                config('lab.duree_verrouillage_minutes'),
            );
        }

        return $this->refuser($username, $adresseIp);
    }

    /**
     * Déconnecte l'utilisateur (POST /logout).
     */
    public function seDeconnecter(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Page d'accueil, accessible seulement une fois connecté (GET /).
     */
    public function afficherAccueil(): View
    {
        return view('accueil', ['utilisateur' => Auth::user()]);
    }

    /**
     * Journal des dernières tentatives de connexion (GET /journal).
     */
    public function afficherJournal(): View
    {
        $tentatives = TentativeConnexion::query()
            ->orderByDesc('created_at')
            ->limit(config('lab.taille_journal'))
            ->get();

        return view('journal', ['tentatives' => $tentatives]);
    }

    /**
     * Connexion réussie : journalise, ouvre la session et redirige vers l'accueil.
     */
    private function accepter(User $compte, string $adresseIp, bool $modeProtegeActif, Request $request): RedirectResponse
    {
        if ($modeProtegeActif) {
            $compte->reinitialiserEchecs();
        }

        $this->journaliser($compte->username, $adresseIp, true);

        Auth::login($compte);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /**
     * Connexion refusée : journalise et renvoie la page d'erreur.
     *
     * On renvoie directement la vue (et non une redirection) avec le marqueur
     * d'échec dans le corps de la réponse, car c'est ce que Hydra inspecte.
     */
    private function refuser(string $username, string $adresseIp): Response
    {
        $this->journaliser($username, $adresseIp, false);

        return response()->view(
            'connexion',
            ['erreur' => self::MESSAGE_ECHEC],
            Response::HTTP_UNAUTHORIZED,
        );
    }

    /**
     * Enregistre une tentative dans le journal (table login_attempts).
     */
    private function journaliser(string $username, string $adresseIp, bool $reussie): void
    {
        TentativeConnexion::create([
            'username' => $username,
            'ip_address' => $adresseIp,
            'success' => $reussie,
        ]);
    }
}
