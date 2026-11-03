<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\ConnexionRequest;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur d'authentification SIGAEC.
 * 
 * Gère l'affichage de l'écran de connexion, le traitement des tentatives
 * et la déconnexion des agents. Toute la logique métier est déléguée à AuthService.
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Affiche l'écran officiel de connexion (Section 9.1).
     */
    public function afficherConnexion(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('tableau.index');
        }

        return view('modules.auth.connexion');
    }

    /**
     * Traite la soumission du formulaire d'authentification.
     */
    public function connecter(ConnexionRequest $request): RedirectResponse
    {
        $resultat = $this->authService->tenterConnexion(
            identifiant: $request->validated('identifiant'),
            motDePasse: $request->validated('mot_de_passe')
        );

        if (!$resultat['succes']) {
            return back()
                ->withInput($request->only('identifiant'))
                ->with('erreur', $resultat['message']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tableau.index'))
            ->with('succes', 'Bienvenue, ' . $resultat['user']->nom_complet . '. Session ouverte avec succès.');
    }

    /**
     * Déconnecte l'agent actif et invalide la session.
     */
    public function deconnecter(Request $request): RedirectResponse
    {
        $this->authService->deconnecter();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('connexion')
            ->with('succes', 'Vous avez été déconnecté avec succès.');
    }
}
