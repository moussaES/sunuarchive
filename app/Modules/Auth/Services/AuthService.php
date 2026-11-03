<?php

namespace App\Modules\Auth\Services;

use App\Enums\EtatCompte;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Service métier d'authentification et de sécurité des comptes SIGAEC.
 * 
 * Implémente :
 * - Le contrôle d'accès nominatif strict.
 * - Le verrouillage automatique après 5 échecs consécutifs (Section 7).
 * - La journalisation systématique de chaque tentative (succès ou échec).
 * - La réinitialisation du compteur après succès.
 */
class AuthService
{
    public const MAX_TENTATIVES = 5;

    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Tente d'authentifier un agent communal.
     * 
     * @param string $identifiant Identifiant nominatif
     * @param string $motDePasse Mot de passe en clair
     * @return array{succes: bool, message: string, user: Utilisateur|null}
     */
    public function tenterConnexion(string $identifiant, string $motDePasse): array
    {
        $user = Utilisateur::where('identifiant', $identifiant)->first();

        // 1. Compte inexistant
        if (!$user) {
            $this->journalService->connexionEchouee($identifiant, null);

            return [
                'succes' => false,
                'message' => 'Identifiant ou mot de passe incorrect.',
                'user' => null,
            ];
        }

        // 2. Compte déjà verrouillé
        if ($user->etat_compte === EtatCompte::VERROUILLE) {
            $this->journalService->connexionEchouee($identifiant, $user);

            return [
                'succes' => false,
                'message' => 'Ce compte est verrouillé suite à 5 tentatives infructueuses. Contactez l\'administrateur technique.',
                'user' => null,
            ];
        }

        // 3. Compte désactivé
        if ($user->etat_compte === EtatCompte::DESACTIVE) {
            $this->journalService->connexionEchouee($identifiant, $user);

            return [
                'succes' => false,
                'message' => 'Ce compte a été désactivé par l\'administration.',
                'user' => null,
            ];
        }

        // 4. Vérification du mot de passe
        if (!Hash::check($motDePasse, $user->mot_de_passe_hash)) {
            $user->increment('tentatives_echec');
            $tentatives = $user->tentatives_echec;

            // Déclenchement du verrouillage à 5 échecs
            if ($tentatives >= self::MAX_TENTATIVES) {
                $user->verrouiller();
                $this->journalService->connexionEchouee($identifiant, $user);

                return [
                    'succes' => false,
                    'message' => 'Compte verrouillé après 5 échecs consécutifs. Veuillez contacter l\'administrateur technique.',
                    'user' => null,
                ];
            }

            $restantes = self::MAX_TENTATIVES - $tentatives;
            $this->journalService->connexionEchouee($identifiant, $user);

            return [
                'succes' => false,
                'message' => "Identifiant ou mot de passe incorrect. Attention : plus que {$restantes} tentative(s) avant verrouillage.",
                'user' => null,
            ];
        }

        // 5. Succès de la connexion
        $user->update([
            'tentatives_echec' => 0,
            'derniere_activite' => now(),
        ]);

        Auth::login($user);
        $this->journalService->connexionReussie($user);

        return [
            'succes' => true,
            'message' => 'Connexion réussie.',
            'user' => $user,
        ];
    }

    /**
     * Déconnecte l'agent actif.
     */
    public function deconnecter(): void
    {
        if ($user = Auth::user()) {
            $this->journalService->deconnexion($user, 'VOLONTAIRE');
        }

        Auth::logout();
    }
}
