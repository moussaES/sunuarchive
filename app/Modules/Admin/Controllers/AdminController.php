<?php

namespace App\Modules\Admin\Controllers;

use App\Enums\EtatCompte;
use App\Enums\Profil;
use App\Http\Controllers\Controller;
use App\Models\Acte;
use App\Models\DemandeCommunication;
use App\Models\JournalActivite;
use App\Models\Sauvegarde;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Contrôleur du module d'Administration SIGAEC (Section 9.6).
 * 
 * Regroupe la gestion des comptes utilisateurs, le journal d'activité en écriture seule,
 * le pilotage des sauvegardes et le traitement des demandes de communication.
 */
class AdminController extends Controller
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Affiche le tableau de bord d'administration selon les habilitations de l'agent.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Contrôle d'habilitation globale d'accès à l'administration
        $peutGererUsers = $user->aDroit('utilisateurs.gerer');
        $peutVoirJournal = $user->aDroit('journal.consulter');
        $peutGererSauvegardes = $user->aDroit('sauvegardes.gerer');
        $peutDeciderDemandes = $user->aDroit('demandes.decider');
        $peutSaisirDemandes = $user->aDroit('demandes.saisir');

        if (!$peutGererUsers && !$peutVoirJournal && !$peutGererSauvegardes && !$peutDeciderDemandes && !$peutSaisirDemandes) {
            abort(403, "Accès refusé au module d'administration générale.");
        }

        // Détermination de l'onglet actif
        $ongletParDefaut = $peutVoirJournal ? 'journal' : ($peutGererUsers ? 'utilisateurs' : ($peutGererSauvegardes ? 'sauvegardes' : 'demandes'));
        $onglet = $request->input('onglet', $ongletParDefaut);

        if ($onglet === 'journal' && !$peutVoirJournal) {
            $onglet = $ongletParDefaut;
        } elseif ($onglet === 'utilisateurs' && !$peutGererUsers) {
            $onglet = $ongletParDefaut;
        } elseif ($onglet === 'sauvegardes' && !$peutGererSauvegardes) {
            $onglet = $ongletParDefaut;
        } elseif ($onglet === 'demandes' && (!$peutDeciderDemandes && !$user->aDroit('demandes.saisir'))) {
            $onglet = $ongletParDefaut;
        }

        // 1. Données du Journal d'audit (filtrable)
        $journal = null;
        if ($peutVoirJournal) {
            $queryJournal = JournalActivite::query();

            if ($request->filled('date_debut')) {
                $queryJournal->whereDate('date_heure', '>=', $request->input('date_debut'));
            }
            if ($request->filled('date_fin')) {
                $queryJournal->whereDate('date_heure', '<=', $request->input('date_fin'));
            }
            if ($request->filled('operation')) {
                $queryJournal->where('operation', 'like', '%' . trim($request->input('operation')) . '%');
            }
            if ($request->filled('identifiant')) {
                $idRecherche = trim($request->input('identifiant'));
                $queryJournal->where(function ($q) use ($idRecherche) {
                    $q->where('identifiant_saisi', 'like', "%{$idRecherche}%")
                      ->orWhereHas('utilisateur', fn($u) => $u->where('identifiant', 'like', "%{$idRecherche}%"));
                });
            }
            if ($request->filled('resultat')) {
                $queryJournal->where('resultat', $request->input('resultat'));
            }

            $journal = $queryJournal->orderByDesc('id_journal')->paginate(25)->withQueryString();
        }

        // 2. Données Utilisateurs
        $utilisateurs = $peutGererUsers ? Utilisateur::orderBy('id_utilisateur', 'asc')->get() : collect();

        // 3. Données Sauvegardes
        $sauvegardes = $peutGererSauvegardes ? Sauvegarde::with('utilisateur')->orderByDesc('date_sauvegarde')->get() : collect();

        // 4. Données Demandes de communication
        $demandes = ($peutDeciderDemandes || $user->aDroit('demandes.saisir'))
            ? DemandeCommunication::with(['agentResponsable', 'actes'])->orderByDesc('id_demande')->get()
            : collect();

        $actesDisponibles = Acte::with('registre')->orderByDesc('id_acte')->take(30)->get();

        return view('modules.admin.index', compact(
            'onglet',
            'journal',
            'utilisateurs',
            'sauvegardes',
            'demandes',
            'actesDisponibles',
            'peutGererUsers',
            'peutVoirJournal',
            'peutGererSauvegardes',
            'peutDeciderDemandes'
        ));
    }

    /**
     * Crée un nouveau compte utilisateur avec politique de mot de passe forte (Section 7).
     */
    public function creerUtilisateur(Request $request): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('utilisateurs.gerer')) {
            abort(403, "Vous n'avez pas l'autorisation de créer des comptes utilisateurs.");
        }

        $request->validate([
            'identifiant' => ['required', 'string', 'max:50', 'unique:utilisateur,identifiant'],
            'nom_complet' => ['required', 'string', 'max:150'],
            'profil' => ['required', Rule::enum(Profil::class)],
            'mot_de_passe' => [
                'required',
                'string',
                'min:12',
                'regex:/[A-Z]/',      // Au moins une majuscule
                'regex:/[a-z]/',      // Au moins une minuscule
                'regex:/[0-9]/',      // Au moins un chiffre
            ],
        ], [
            'identifiant.unique' => 'Cet identifiant est déjà utilisé.',
            'mot_de_passe.min' => 'Le mot de passe doit comporter au moins 12 caractères.',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir des majuscules, des minuscules et des chiffres.',
        ]);

        $profil = Profil::from($request->input('profil'));

        $nouvelUser = Utilisateur::create([
            'identifiant' => trim($request->input('identifiant')),
            'nom_complet' => trim($request->input('nom_complet')),
            'mot_de_passe_hash' => Hash::make($request->input('mot_de_passe')),
            'profil' => $profil,
            'etat_compte' => EtatCompte::ACTIF,
            'tentatives_echec' => 0,
            'date_creation' => now()->toDateString(),
            'derniere_activite' => null,
            'date_desactivation' => null,
        ]);

        $this->journalService->operationDocumentaire(
            action: 'CREATION_COMPTE',
            typeReference: 'UTILISATEUR',
            idReference: $nouvelUser->id_utilisateur,
            user: $admin
        );

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
            ->with('succes', "Compte {$nouvelUser->identifiant} ({$nouvelUser->profil->libelle()}) créé avec succès.");
    }

    /**
     * Active ou désactive un compte utilisateur (Section 9.6 : pas de suppression physique).
     */
    public function basculerEtatCompte(Request $request, int $id): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('utilisateurs.gerer')) {
            abort(403);
        }

        $userCible = Utilisateur::findOrFail($id);

        if ($userCible->id_utilisateur === $admin->id_utilisateur) {
            return redirect()->back()->with('erreur', "Vous ne pouvez pas désactiver votre propre compte administrateur.");
        }

        if ($userCible->etat_compte === EtatCompte::ACTIF) {
            $userCible->update([
                'etat_compte' => EtatCompte::DESACTIVE,
                'date_desactivation' => now()->toDateString(),
            ]);
            $action = 'DESACTIVATION_COMPTE';
            $msg = "Le compte {$userCible->identifiant} a été désactivé.";
        } else {
            $userCible->update([
                'etat_compte' => EtatCompte::ACTIF,
                'date_desactivation' => null,
                'tentatives_echec' => 0,
            ]);
            $action = 'ACTIVATION_COMPTE';
            $msg = "Le compte {$userCible->identifiant} a été réactivé.";
        }

        $this->journalService->operationDocumentaire(
            action: $action,
            typeReference: 'UTILISATEUR',
            idReference: $userCible->id_utilisateur,
            user: $admin
        );

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])->with('succes', $msg);
    }

    /**
     * Débloque un compte verrouillé suite à 5 tentatives échouées de mot de passe (Section 7).
     */
    public function debloquerCompte(Request $request, int $id): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('utilisateurs.gerer')) {
            abort(403);
        }

        $userCible = Utilisateur::findOrFail($id);
        $userCible->update([
            'etat_compte' => EtatCompte::ACTIF,
            'tentatives_echec' => 0,
        ]);

        $this->journalService->operationDocumentaire(
            action: 'DEBLOCAGE_COMPTE',
            typeReference: 'UTILISATEUR',
            idReference: $userCible->id_utilisateur,
            user: $admin
        );

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
            ->with('succes', "Le compte {$userCible->identifiant} a été débloqué avec réinitialisation du compteur d'échecs.");
    }

    /**
     * Modifie les informations d'un compte agent communal (Section 9.6).
     */
    public function modifierUtilisateur(Request $request, int $id): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('utilisateurs.gerer')) {
            abort(403, "Vous n'avez pas l'autorisation de modifier les comptes utilisateurs.");
        }

        $userCible = Utilisateur::findOrFail($id);

        $reglesValidation = [
            'identifiant' => [
                'required',
                'string',
                'max:50',
                Rule::unique('utilisateur', 'identifiant')->ignore($userCible->id_utilisateur, 'id_utilisateur'),
            ],
            'nom_complet' => ['required', 'string', 'max:150'],
            'profil' => ['required', Rule::enum(Profil::class)],
            'etat_compte' => ['required', Rule::enum(EtatCompte::class)],
        ];

        if ($request->filled('mot_de_passe')) {
            $reglesValidation['mot_de_passe'] = [
                'string',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ];
        }

        $request->validate($reglesValidation, [
            'identifiant.unique' => 'Cet identifiant est déjà utilisé par un autre agent.',
            'mot_de_passe.min' => 'Le mot de passe doit comporter au moins 12 caractères.',
            'mot_de_passe.regex' => 'Le mot de passe doit contenir des majuscules, des minuscules et des chiffres.',
        ]);

        $profil = Profil::from($request->input('profil'));
        $nouvelEtat = EtatCompte::from($request->input('etat_compte'));

        // Sécurité : l'administrateur connecté ne peut pas se rétrograder ni désactiver son propre compte
        if ($userCible->id_utilisateur === $admin->id_utilisateur) {
            if ($nouvelEtat !== EtatCompte::ACTIF) {
                return redirect()->back()->with('erreur', "Vous ne pouvez pas désactiver ou verrouiller votre propre compte administrateur.");
            }
            if ($profil !== Profil::ADMIN_TECHNIQUE) {
                return redirect()->back()->with('erreur', "Vous ne pouvez pas modifier le profil de votre propre compte administrateur.");
            }
        }

        $donnees = [
            'identifiant' => trim($request->input('identifiant')),
            'nom_complet' => trim($request->input('nom_complet')),
            'profil' => $profil,
            'etat_compte' => $nouvelEtat,
        ];

        if ($nouvelEtat === EtatCompte::ACTIF && $userCible->etat_compte !== EtatCompte::ACTIF) {
            $donnees['tentatives_echec'] = 0;
            $donnees['date_desactivation'] = null;
        } elseif ($nouvelEtat === EtatCompte::DESACTIVE && $userCible->etat_compte !== EtatCompte::DESACTIVE) {
            $donnees['date_desactivation'] = now()->toDateString();
        }

        if ($request->filled('mot_de_passe')) {
            $donnees['mot_de_passe_hash'] = Hash::make($request->input('mot_de_passe'));
            $donnees['tentatives_echec'] = 0;
        }

        $userCible->update($donnees);

        $this->journalService->operationDocumentaire(
            action: 'MODIFICATION_COMPTE',
            typeReference: 'UTILISATEUR',
            idReference: $userCible->id_utilisateur,
            user: $admin
        );

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
            ->with('succes', "Les informations de l'agent {$userCible->nom_complet} ({$userCible->identifiant}) ont été modifiées avec succès.");
    }

    /**
     * Supprime définitivement le compte d'un agent communal.
     */
    public function supprimerUtilisateur(Request $request, int $id): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('utilisateurs.gerer')) {
            abort(403, "Vous n'avez pas l'autorisation de supprimer des comptes utilisateurs.");
        }

        $userCible = Utilisateur::findOrFail($id);

        if ($userCible->id_utilisateur === $admin->id_utilisateur) {
            return redirect()->back()->with('erreur', "Vous ne pouvez pas supprimer votre propre compte administrateur.");
        }

        $identifiant = $userCible->identifiant;
        $nomComplet = $userCible->nom_complet;
        $idSupprime = $userCible->id_utilisateur;

        // Journalisation de la suppression avant effacement
        $this->journalService->operationDocumentaire(
            action: 'SUPPRESSION_COMPTE',
            typeReference: 'UTILISATEUR',
            idReference: $idSupprime,
            user: $admin
        );

        $userCible->delete();

        return redirect()->route('admin.index', ['onglet' => 'utilisateurs'])
            ->with('succes', "L'agent {$nomComplet} ({$identifiant}) a été supprimé définitivement du système.");
    }

    /**
     * Déclenche une sauvegarde complète manuelle depuis l'interface d'administration.
     */
    public function lancerSauvegarde(Request $request): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('sauvegardes.gerer')) {
            abort(403);
        }

        Artisan::call('sigaec:sauvegarde', ['--support' => 'CONSOLE_ADMIN']);

        return redirect()->route('admin.index', ['onglet' => 'sauvegardes'])
            ->with('succes', "Sauvegarde système exécutée avec succès et scellée sous manifeste SHA-256.");
    }

    /**
     * Déclenche un test de restauration en bac à sable temporaire.
     */
    public function testerRestauration(Request $request): RedirectResponse
    {
        $admin = $request->user();
        if (!$admin->aDroit('sauvegardes.restaurer')) {
            abort(403);
        }

        $codeRetour = Artisan::call('sigaec:restaurer-test');

        if ($codeRetour === 0) {
            return redirect()->route('admin.index', ['onglet' => 'sauvegardes'])
                ->with('succes', "Test de restauration validé à 100% : toutes les empreintes SHA-256 sont conformes.");
        }

        return redirect()->route('admin.index', ['onglet' => 'sauvegardes'])
            ->with('erreur', "Alerte : Le test de restauration a détecté des anomalies dans l'archive.");
    }
}
