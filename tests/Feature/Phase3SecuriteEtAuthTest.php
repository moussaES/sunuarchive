<?php

namespace Tests\Feature;

use App\Enums\EtatCompte;
use App\Enums\Profil;
use App\Models\JournalActivite;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 3 (Authentification, verrouillage, inactivité, JournalService, habilitations).
 */
class Phase3SecuriteEtAuthTest extends TestCase
{
    /**
     * Vérifie que l'écran officiel de connexion s'affiche avec toutes les mentions requises.
     */
    public function test_la_page_de_connexion_s_affiche_correctement(): void
    {
        $response = $this->get('/connexion');
        $response->assertStatus(200);
        $response->assertSee('SIGAEC');
        $response->assertSee('Mairie de Tattaguine');
        $response->assertSee('Application locale : aucune connexion Internet requise');
        $response->assertSee('verrouillé après 5 tentatives infructueuses');
    }

    /**
     * Vérifie la connexion réussie avec des identifiants valides et la journalisation.
     */
    public function test_connexion_reussie_avec_identifiants_valides(): void
    {
        $compteInitialJournal = DB::table('journal_activite')->count();

        $response = $this->post('/connexion', [
            'identifiant' => 'archiviste1',
            'mot_de_passe' => 'Pass1234Tattaguine!',
        ]);

        $response->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals('archiviste1', Auth::user()->identifiant);

        // Vérifie l'inscription de l'événement de connexion dans le journal
        $dernierLog = DB::table('journal_activite')->orderByDesc('id_journal')->first();
        $this->assertNotNull($dernierLog);
        $this->assertEquals('CONNEXION', $dernierLog->operation);
        $this->assertEquals('REUSSI', $dernierLog->resultat);
        $this->assertEquals('archiviste1', $dernierLog->identifiant_saisi);
    }

    /**
     * Vérifie le verrouillage strict après 5 échecs consécutifs et l'alerte de sécurité.
     */
    public function test_verrouillage_du_compte_apres_cinq_echecs_consecutifs(): void
    {
        // Création d'un compte de test dédié au test de verrouillage
        $user = Utilisateur::updateOrCreate(
            ['identifiant' => 'agent.test.verrou'],
            [
                'nom_complet' => 'Agent Test Verrou',
                'mot_de_passe_hash' => Hash::make('Pass1234Tattaguine!'),
                'profil' => Profil::AGENT_INDEXATION,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => now()->toDateString(),
            ]
        );

        // 4 premières tentatives infructueuses
        for ($i = 1; $i <= 4; $i++) {
            $resp = $this->post('/connexion', [
                'identifiant' => 'agent.test.verrou',
                'mot_de_passe' => 'MauvaisMotDePasse!',
            ]);
            $resp->assertSessionHas('erreur');

            $user->refresh();
            $this->assertEquals($i, $user->tentatives_echec);
            $this->assertEquals(EtatCompte::ACTIF, $user->etat_compte);
        }

        // 5ème tentative infructueuse : déclenchement du verrouillage
        $resp5 = $this->post('/connexion', [
            'identifiant' => 'agent.test.verrou',
            'mot_de_passe' => 'MauvaisMotDePasse!',
        ]);

        $user->refresh();
        $this->assertEquals(5, $user->tentatives_echec);
        $this->assertEquals(EtatCompte::VERROUILLE, $user->etat_compte, "Le compte doit passer à l'état VERROUILLE.");

        // 6ème tentative avec le BON mot de passe : rejet car compte verrouillé
        $resp6 = $this->post('/connexion', [
            'identifiant' => 'agent.test.verrou',
            'mot_de_passe' => 'Pass1234Tattaguine!',
        ]);
        $resp6->assertSessionHas('erreur');
        $this->assertFalse(Auth::check(), "Un compte verrouillé ne peut pas se connecter.");

        // Vérifie qu'un événement ECHEC est consigné
        $dernierLog = DB::table('journal_activite')
            ->where('identifiant_saisi', 'agent.test.verrou')
            ->orderByDesc('id_journal')
            ->first();
        $this->assertNotNull($dernierLog);
        $this->assertEquals('ECHEC', $dernierLog->resultat);
    }

    /**
     * Vérifie la déconnexion automatique après 15 minutes d'inactivité.
     */
    public function test_deconnexion_automatique_apres_inactivite_de_15_minutes(): void
    {
        $user = Utilisateur::where('identifiant', 'archiviste1')->first();
        // Simule une inactivité de 20 minutes
        $user->update(['derniere_activite' => now()->subMinutes(20)]);

        $response = $this->actingAs($user)->get('/');

        // Doit déconnecter et rediriger vers la connexion avec message d'inactivité
        $response->assertRedirect(route('connexion'));
        $this->assertFalse(Auth::check());

        // Restaure la dernière activité pour que les tests suivants ne soient pas invalidés
        $user->update(['derniere_activite' => now()]);

        // Vérifie l'inscription de l'inactivité au journal
        $dernierLog = DB::table('journal_activite')
            ->where('id_utilisateur', $user->id_utilisateur)
            ->where('operation', 'DECONNEXION_INACTIVITE_15MIN')
            ->first();
        $this->assertNotNull($dernierLog, "La déconnexion pour inactivité doit être journalisée.");
    }

    /**
     * Vérifie qu'un accès forcé non autorisé génère une 403 ET un événement REFUSE au journal.
     */
    public function test_acces_non_autorise_retourne_403_et_consigne_refuse_au_journal(): void
    {
        // Un agent de consultation n'a pas le droit 'registres.creer'
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();
        $this->assertNotNull($consultant);

        // Appel d'une route protégée
        $response = $this->actingAs($consultant)->get('/test-protection-droit');
        $response->assertStatus(403);

        // Vérifie l'inscription obligatoire d'un événement REFUSE au journal (Section 6)
        $logRefus = DB::table('journal_activite')
            ->where('id_utilisateur', $consultant->id_utilisateur)
            ->where('resultat', 'REFUSE')
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($logRefus, "Une tentative d'accès 403 doit obligatoirement générer un événement REFUSE.");
        $this->assertEquals('ACCES_INTERDIT', $logRefus->operation);
    }

    /**
     * Vérifie la déconnexion manuelle.
     */
    public function test_deconnexion_volontaire(): void
    {
        $user = Utilisateur::where('identifiant', 'archiviste1')->first();
        $this->actingAs($user);

        $response = $this->post('/deconnexion');
        $response->assertRedirect(route('connexion'));
        $this->assertFalse(Auth::check());

        $log = DB::table('journal_activite')
            ->where('id_utilisateur', $user->id_utilisateur)
            ->where('operation', 'DECONNEXION_VOLONTAIRE')
            ->orderByDesc('id_journal')
            ->first();
        $this->assertNotNull($log);
    }
}
