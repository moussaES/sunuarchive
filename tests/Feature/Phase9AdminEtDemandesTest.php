<?php

namespace Tests\Feature;

use App\Enums\EtatCompte;
use App\Enums\Profil;
use App\Models\Acte;
use App\Models\DemandeCommunication;
use App\Models\JournalActivite;
use App\Models\Sauvegarde;
use App\Models\Utilisateur;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 9 (Administration générale, Journal d'audit, Sauvegardes et Demandes).
 * 
 * Vérifie :
 * - L'accès au module d'administration selon les habilitations
 * - La consultation et le filtrage du journal d'activité
 * - La création de comptes agents avec politique de mot de passe forte (≥ 12 caractères)
 * - La désactivation, réactivation et le déblocage de compte
 * - La saisie d'une demande de communication d'actes par l'archiviste
 * - La prise de décision (accordée / refusée) par le Responsable habilité
 * - La commande artisan de sauvegarde automatique avec manifeste SHA-256 et miroir
 * - La commande artisan de test de restauration en bac à sable
 * - L'immuabilité absolue du journal d'activité par déclencheur MySQL (trigger write-only).
 */
class Phase9AdminEtDemandesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite le déclenchement de la déconnexion automatique en test
        Utilisateur::query()->update(['derniere_activite' => now()]);
    }

    /**
     * Vérifie que l'Administrateur technique et le Responsable habilité accèdent à l'administration.
     */
    public function test_acces_admin_autorise_pour_admin_technique_et_responsable(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();
        $responseAdmin = $this->actingAs($admin)->get('/admin');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Administration');
        $responseAdmin->assertSee('Journal d\'activité (Audit)', false);
        $responseAdmin->assertSee('Sauvegardes');
        $responseAdmin->assertSee('Comptes agents');

        $responsable = Utilisateur::where('profil', Profil::RESPONSABLE_HABILITE)->firstOrFail();
        $responseResp = $this->actingAs($responsable)->get('/admin');
        $responseResp->assertStatus(200);
        $responseResp->assertSee('Demandes de communication', false);
    }

    /**
     * Vérifie que l'Agent de consultation n'a pas accès à l'administration (403).
     */
    public function test_acces_admin_refuse_pour_agent_consultation(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->firstOrFail();
        $response = $this->actingAs($consultant)->get('/admin');
        $response->assertStatus(403);
    }

    /**
     * Vérifie la consultation et le filtrage du journal d'audit.
     */
    public function test_journal_consultation_et_filtrage(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        // Insère une entrée de test dans le journal
        JournalActivite::create([
            'date_heure' => now(),
            'id_utilisateur' => $admin->id_utilisateur,
            'identifiant_saisi' => $admin->identifiant,
            'adresse_ip' => '127.0.0.1',
            'operation' => 'TEST_FILTRAGE_JOURNAL',
            'type_reference' => 'SYSTEME',
            'id_reference' => 999,
            'resultat' => 'REUSSI',
        ]);

        $response = $this->actingAs($admin)->get('/admin?onglet=journal&operation=TEST_FILTRAGE_JOURNAL');
        $response->assertStatus(200);
        $response->assertSee('TEST_FILTRAGE_JOURNAL');
        $response->assertSee('Réussi');
    }

    /**
     * Vérifie la création d'un utilisateur avec validation stricte du mot de passe.
     */
    public function test_creation_utilisateur_par_admin_avec_mot_de_passe_valide(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        $loginUnique = 'test.agent.' . time();

        $response = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'identifiant' => $loginUnique,
            'nom_complet' => 'Moussa Ndiaye Test',
            'profil' => Profil::ARCHIVISTE->value,
            'mot_de_passe' => 'Tattaguine2026!Securise',
        ]);

        $response->assertRedirect('/admin?onglet=utilisateurs');
        $response->assertSessionHas('succes');

        $this->assertDatabaseHas('utilisateur', [
            'identifiant' => $loginUnique,
            'nom_complet' => 'Moussa Ndiaye Test',
            'profil' => Profil::ARCHIVISTE->value,
            'etat_compte' => EtatCompte::ACTIF->value,
        ]);

        // Vérifie la traçabilité au journal (résultat REUSSI)
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $admin->id_utilisateur,
            'operation' => 'CREATION_COMPTE',
            'type_reference' => 'UTILISATEUR',
            'resultat' => 'REUSSI',
        ]);
    }

    /**
     * Vérifie le rejet de création d'utilisateur si le mot de passe ne respecte pas la politique de sécurité.
     */
    public function test_creation_utilisateur_rejette_mot_de_passe_faible(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        // 1. Trop court (< 12 caractères)
        $response1 = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'identifiant' => 'court.test',
            'nom_complet' => 'Test Court',
            'profil' => Profil::AGENT_INDEXATION->value,
            'mot_de_passe' => 'Pass123!',
        ]);
        $response1->assertSessionHasErrors('mot_de_passe');

        // 2. Sans majuscule
        $response2 = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'identifiant' => 'sansmaj.test',
            'nom_complet' => 'Test Sans Maj',
            'profil' => Profil::AGENT_INDEXATION->value,
            'mot_de_passe' => 'pass1234tattaguine',
        ]);
        $response2->assertSessionHasErrors('mot_de_passe');
    }

    /**
     * Vérifie la désactivation et la réactivation d'un compte agent sans suppression physique.
     */
    public function test_desactivation_et_reactivation_utilisateur(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();
        $cible = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->firstOrFail();

        // 1. Désactivation
        $responseDesact = $this->actingAs($admin)->post("/admin/utilisateurs/{$cible->id_utilisateur}/basculer");
        $responseDesact->assertRedirect('/admin?onglet=utilisateurs');
        $responseDesact->assertSessionHas('succes');

        $cible->refresh();
        $this->assertEquals(EtatCompte::DESACTIVE, $cible->etat_compte);
        $this->assertNotNull($cible->date_desactivation);

        // 2. Réactivation
        $responseReact = $this->actingAs($admin)->post("/admin/utilisateurs/{$cible->id_utilisateur}/basculer");
        $responseReact->assertRedirect('/admin?onglet=utilisateurs');
        $responseReact->assertSessionHas('succes');

        $cible->refresh();
        $this->assertEquals(EtatCompte::ACTIF, $cible->etat_compte);
        $this->assertNull($cible->date_desactivation);

        // 3. Auto-désactivation interdite
        $responseAuto = $this->actingAs($admin)->post("/admin/utilisateurs/{$admin->id_utilisateur}/basculer");
        $responseAuto->assertSessionHas('erreur');
    }

    /**
     * Vérifie le déblocage administratif d'un compte verrouillé par échecs consécutifs.
     */
    public function test_deblocage_compte_apres_verrouillage(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();
        $cible = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->firstOrFail();

        // Simule 5 échecs et état VERROUILLE
        $cible->update([
            'etat_compte' => EtatCompte::VERROUILLE,
            'tentatives_echec' => 5,
        ]);

        $response = $this->actingAs($admin)->post("/admin/utilisateurs/{$cible->id_utilisateur}/debloquer");
        $response->assertRedirect('/admin?onglet=utilisateurs');
        $response->assertSessionHas('succes');

        $cible->refresh();
        $this->assertEquals(EtatCompte::ACTIF, $cible->etat_compte);
        $this->assertEquals(0, $cible->tentatives_echec);

        // Traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $admin->id_utilisateur,
            'operation' => 'DEBLOCAGE_COMPTE',
            'type_reference' => 'UTILISATEUR',
            'id_reference' => $cible->id_utilisateur,
        ]);
    }

    /**
     * Vérifie la modification des informations d'un agent par l'administrateur.
     */
    public function test_modification_utilisateur_par_admin(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();
        $agent = Utilisateur::create([
            'identifiant' => 'agent.test.modif.' . uniqid(),
            'nom_complet' => 'Agent Avant Modif',
            'mot_de_passe_hash' => Hash::make('Pass1234Tattaguine!'),
            'profil' => Profil::AGENT_INDEXATION,
            'etat_compte' => EtatCompte::ACTIF,
            'tentatives_echec' => 0,
            'date_creation' => now()->toDateString(),
        ]);

        $nouveauId = 'agent.modifie.' . uniqid();

        $response = $this->actingAs($admin)->put("/admin/utilisateurs/{$agent->id_utilisateur}", [
            'identifiant' => $nouveauId,
            'nom_complet' => 'Agent Modifié Officiel',
            'profil' => Profil::AGENT_CONSULTATION->value,
            'etat_compte' => EtatCompte::ACTIF->value,
            'mot_de_passe' => 'NouveauPass2026!',
        ]);

        $response->assertRedirect('/admin?onglet=utilisateurs');
        $response->assertSessionHas('succes');

        $agent->refresh();
        $this->assertEquals($nouveauId, $agent->identifiant);
        $this->assertEquals('Agent Modifié Officiel', $agent->nom_complet);
        $this->assertEquals(Profil::AGENT_CONSULTATION, $agent->profil);
        $this->assertTrue(Hash::check('NouveauPass2026!', $agent->mot_de_passe_hash));

        // Traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $admin->id_utilisateur,
            'operation' => 'MODIFICATION_COMPTE',
            'type_reference' => 'UTILISATEUR',
            'id_reference' => $agent->id_utilisateur,
        ]);
    }

    /**
     * Vérifie la suppression définitive d'un compte agent par l'administrateur.
     */
    public function test_suppression_utilisateur_par_admin(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        $agentASupprimer = Utilisateur::create([
            'identifiant' => 'agent.a.supprimer.' . uniqid(),
            'nom_complet' => 'Agent Temporaire Supprimable',
            'mot_de_passe_hash' => Hash::make('Pass1234Tattaguine!'),
            'profil' => Profil::AGENT_CONSULTATION,
            'etat_compte' => EtatCompte::ACTIF,
            'tentatives_echec' => 0,
            'date_creation' => now()->toDateString(),
        ]);

        $idSupprime = $agentASupprimer->id_utilisateur;

        $response = $this->actingAs($admin)->delete("/admin/utilisateurs/{$idSupprime}");
        $response->assertRedirect('/admin?onglet=utilisateurs');
        $response->assertSessionHas('succes');

        $this->assertDatabaseMissing('utilisateur', [
            'id_utilisateur' => $idSupprime,
        ]);

        // Traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $admin->id_utilisateur,
            'operation' => 'SUPPRESSION_COMPTE',
            'type_reference' => 'UTILISATEUR',
            'id_reference' => $idSupprime,
        ]);
    }

    /**
     * Vérifie qu'un administrateur ne peut pas supprimer son propre compte.
     */
    public function test_suppression_son_propre_compte_refusee(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        $response = $this->actingAs($admin)->delete("/admin/utilisateurs/{$admin->id_utilisateur}");
        $response->assertSessionHas('erreur');

        $this->assertDatabaseHas('utilisateur', [
            'id_utilisateur' => $admin->id_utilisateur,
        ]);
    }

    /**
     * Vérifie que la modification et la suppression sont refusées aux utilisateurs non administrateurs.
     */
    public function test_modification_et_suppression_refusees_si_non_habilite(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->firstOrFail();
        $cible = Utilisateur::where('profil', Profil::ARCHIVISTE)->firstOrFail();

        // 1. Modification refusée (403)
        $resModif = $this->actingAs($consultant)->put("/admin/utilisateurs/{$cible->id_utilisateur}", [
            'identifiant' => 'pirate',
            'nom_complet' => 'Nom Pirate',
            'profil' => Profil::ADMIN_TECHNIQUE->value,
            'etat_compte' => EtatCompte::ACTIF->value,
        ]);
        $resModif->assertStatus(403);

        // 2. Suppression refusée (403)
        $resSuppr = $this->actingAs($consultant)->delete("/admin/utilisateurs/{$cible->id_utilisateur}");
        $resSuppr->assertStatus(403);
    }

    /**
     * Vérifie la saisie d'une demande de communication d'actes par un agent habilité.
     */
    public function test_saisie_demande_communication_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->firstOrFail();
        $acte = Acte::firstOrFail();

        $response = $this->actingAs($archiviste)->post('/demandes', [
            'service_demandeur' => 'Tribunal d\'Instance de Fatick',
            'objet' => 'Réquisition judiciaire n° 2026/89 pour vérification de filiation',
            'date_demande' => '2026-10-20',
            'actes' => [$acte->id_acte],
        ]);

        $response->assertSessionHas('succes');

        $this->assertDatabaseHas('demande_communication', [
            'service_demandeur' => 'Tribunal d\'Instance de Fatick',
            'decision' => 'EN_ATTENTE',
        ]);

        $demande = DemandeCommunication::where('service_demandeur', 'Tribunal d\'Instance de Fatick')->latest('id_demande')->firstOrFail();
        $this->assertStringStartsWith('DEM-', $demande->numero_demande);
        $this->assertTrue($demande->actes->contains($acte->id_acte));

        // Traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $archiviste->id_utilisateur,
            'operation' => 'CREATION_DEMANDE',
            'type_reference' => 'DEMANDE',
            'id_reference' => $demande->id_demande,
        ]);
    }

    /**
     * Vérifie la décision prise sur une demande par le Responsable habilité.
     */
    public function test_decision_demande_par_responsable_habilite(): void
    {
        $responsable = Utilisateur::where('profil', Profil::RESPONSABLE_HABILITE)->firstOrFail();
        $demande = DemandeCommunication::where('decision', 'EN_ATTENTE')->first();

        if (!$demande) {
            $demande = DemandeCommunication::create([
                'numero_demande' => 'DEM-9999',
                'id_utilisateur' => $responsable->id_utilisateur,
                'date_demande' => now()->toDateString(),
                'service_demandeur' => 'Commissariat Central',
                'objet' => 'Enquête administrative',
                'decision' => 'EN_ATTENTE',
            ]);
        }

        $response = $this->actingAs($responsable)->post("/demandes/{$demande->id_demande}/decider", [
            'decision' => 'ACCEPTEE',
        ]);

        $response->assertSessionHas('succes');

        $demande->refresh();
        $this->assertEquals('ACCEPTEE', $demande->decision);
        $this->assertNotNull($demande->date_traitement);
        $this->assertEquals($responsable->id_utilisateur, $demande->id_utilisateur);

        // Traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $responsable->id_utilisateur,
            'operation' => 'DECISION_DEMANDE_ACCEPTEE',
            'type_reference' => 'DEMANDE',
            'id_reference' => $demande->id_demande,
        ]);
    }

    /**
     * Vérifie qu'un agent non habilité ne peut pas décider d'une demande (403).
     */
    public function test_decision_demande_refusee_si_profil_non_habilite(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->firstOrFail();
        $demande = DemandeCommunication::firstOrFail();

        $response = $this->actingAs($consultant)->post("/demandes/{$demande->id_demande}/decider", [
            'decision' => 'ACCEPTEE',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Vérifie la commande artisan sigaec:sauvegarde (dump MySQL, miroir fichiers, manifeste SHA-256).
     */
    public function test_commande_artisan_sauvegarde(): void
    {
        $code = Artisan::call('sigaec:sauvegarde', ['--support' => 'TEST_CONSOLE']);
        $this->assertEquals(0, $code);

        $derniereSauvegarde = Sauvegarde::where('support', 'TEST_CONSOLE')->latest('id_sauvegarde')->first();
        $this->assertNotNull($derniereSauvegarde);
        $this->assertEquals('REUSSIE', $derniereSauvegarde->resultat);
        $this->assertEquals(64, strlen($derniereSauvegarde->empreinte_sha256));

        // Dossier de sauvegarde existant avec manifeste
        $cheminEnv = env('SAUVEGARDE_CHEMIN', storage_path('sauvegardes'));
        $dossierSauvegardes = (str_starts_with($cheminEnv, '/') || (strlen($cheminEnv) > 2 && $cheminEnv[1] === ':'))
            ? $cheminEnv
            : base_path($cheminEnv);
        $this->assertTrue(File::isDirectory($dossierSauvegardes));
        $dossiers = File::directories($dossierSauvegardes);
        $this->assertNotEmpty($dossiers);
        $dernierDossier = end($dossiers);
        $this->assertFileExists($dernierDossier . DIRECTORY_SEPARATOR . 'manifeste_sha256.json');
    }

    /**
     * Vérifie la commande artisan sigaec:restaurer-test (bac à sable temporaire et contrôle SHA-256).
     */
    public function test_commande_artisan_restaurer_test(): void
    {
        // Assure au moins une sauvegarde
        Artisan::call('sigaec:sauvegarde', ['--support' => 'TEST_RESTORE']);

        $code = Artisan::call('sigaec:restaurer-test');
        $this->assertEquals(0, $code);

        // La sauvegarde doit être marquée comme vérifiée
        $derniere = Sauvegarde::orderByDesc('id_sauvegarde')->first();
        $this->assertNotNull($derniere);
        $this->assertTrue((bool) $derniere->verifiee);
    }

    /**
     * Vérifie l'immuabilité absolue du journal d'activité via le déclencheur MySQL (Section 6 & 9.4).
     */
    public function test_journal_activite_immuable_trigger_mysql(): void
    {
        $journal = JournalActivite::firstOrFail();

        // 1. Toute tentative d'UPDATE direct doit être bloquée par le trigger MySQL
        $exceptionUpdate = false;
        try {
            DB::table('journal_activite')
                ->where('id_journal', $journal->id_journal)
                ->update(['operation' => 'MODIFICATION_INTERDITE']);
        } catch (QueryException $e) {
            $exceptionUpdate = true;
            $this->assertMatchesRegularExpression('/lecture seule|interdite|immuable/i', $e->getMessage());
        }
        $this->assertTrue($exceptionUpdate, "Le trigger MySQL doit interdire la mise à jour du journal d'activité.");

        // 2. Toute tentative de DELETE direct doit être bloquée par le trigger MySQL
        $exceptionDelete = false;
        try {
            DB::table('journal_activite')
                ->where('id_journal', $journal->id_journal)
                ->delete();
        } catch (QueryException $e) {
            $exceptionDelete = true;
            $this->assertMatchesRegularExpression('/lecture seule|interdite|immuable/i', $e->getMessage());
        }
        $this->assertTrue($exceptionDelete, "Le trigger MySQL doit interdire la suppression dans le journal d'activité.");
    }
}
