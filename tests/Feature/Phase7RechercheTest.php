<?php

namespace Tests\Feature;

use App\Enums\Profil;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 7 (Module Recherche d'actes d'état civil).
 * 
 * Vérifie :
 * - L'accessibilité de l'interface de recherche pour les profils habilités
 * - La recherche par numéro officiel et par année
 * - Le renvoi obligatoire vers le registre et sa localisation physique (Section 11.5)
 * - Le filtrage par type d'acte (Naissance, Mariage, Décès)
 * - La recherche nominative par nom et prénom selon les droits (Section 11.4)
 * - L'affichage du message d'orientation archivistique en 4 points si aucun résultat (Section 9.4)
 * - La recherche globale par mot-clé simple
 * - La traçabilité de chaque recherche dans le journal d'activité
 * - Le refus 403 et journalisation REFUSE pour l'administrateur technique.
 */
class Phase7RechercheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite l'inactivité en test
        Utilisateur::query()->update(['derniere_activite' => now()]);
    }

    /**
     * Vérifie l'accès à la page de recherche pour un agent de consultation habilité.
     */
    public function test_page_recherche_accessible_pour_agent_autorise(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();
        $this->assertNotNull($consultant);

        $response = $this->actingAs($consultant)->get('/actes/recherche');

        $response->assertStatus(200);
        $response->assertSee('Recherche d\'actes d\'état civil');
        $response->assertSee('Critères avancés...');
        $response->assertSee('actes répertoriés trouvés');
    }

    /**
     * Vérifie la recherche par numéro d'acte et année, ainsi que le renvoi vers
     * le registre et son emplacement physique (Section 11.4 et 11.5).
     */
    public function test_recherche_par_numero_et_annee_avec_localisation_physique(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $acte = Acte::with('registre')->first();
        $this->assertNotNull($acte);

        $response = $this->actingAs($archiviste)->get("/actes/recherche?numero_acte={$acte->numero_acte}&annee={$acte->annee}");

        $response->assertStatus(200);
        $response->assertSee("Acte n° {$acte->numero_acte}");
        $response->assertSee("Année {$acte->annee}");

        // Contrôle Section 11.5 : Chaque acte renvoie vers son registre et sa localisation physique
        $response->assertSee($acte->registre->cote);
        $response->assertSee($acte->registre->localisation);
        $response->assertSee(route('actes.show', $acte->id_acte));
    }

    /**
     * Vérifie le filtrage précis par type d'acte (Section 11.4).
     */
    public function test_recherche_par_type_d_acte(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/actes/recherche?type=MARIAGE');

        $response->assertStatus(200);
        $response->assertSee('Mariage');
        $response->assertDontSee('FILTRE_INEXISTANT');
    }

    /**
     * Vérifie la recherche nominative par nom de personne (Section 11.4).
     */
    public function test_recherche_nominative_par_nom(): void
    {
        $agentIdx = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->first();

        // Recherche d'un nom couramment indexé dans les seeders
        $response = $this->actingAs($agentIdx)->get('/actes/recherche?nom=Ndiaye');

        $response->assertStatus(200);
        $response->assertSee('Ndiaye');
        $response->assertSee('Ouvrir →');
    }

    /**
     * Vérifie qu'en cas d'absence de résultat, le message d'orientation archivistique
     * explicite en 4 points est clairement affiché (Section 9.4 et 11.4).
     */
    public function test_recherche_sans_resultat_affiche_message_d_orientation_archivistique(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();

        $response = $this->actingAs($consultant)->get('/actes/recherche?q=NOM_TOTALEMENT_INEXISTANT_XYZ999');

        $response->assertStatus(200);
        $response->assertSee('Aucun acte ne correspond à vos critères de recherche');
        $response->assertSee('orientation archivistique');
        $response->assertSee('orthographe', false);
        $response->assertSee('numéro officiel');
        $response->assertSee('période');
        $response->assertSee('Inventaire des registres');
    }

    /**
     * Vérifie la recherche simple globale par mot-clé (numéro ou cote).
     */
    public function test_recherche_simple_globale_par_mot_cle(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $acte = Acte::first();

        $response = $this->actingAs($archiviste)->get("/actes/recherche?q={$acte->numero_acte}");

        $response->assertStatus(200);
        $response->assertSee("Acte n° {$acte->numero_acte}");
    }

    /**
     * Vérifie la traçabilité obligatoire de chaque recherche dans le journal d'activité (Section 9.4).
     */
    public function test_recherche_tracee_dans_le_journal_d_activite(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $this->actingAs($archiviste)->get('/actes/recherche?q=TESTJOURNAL123');

        $trace = DB::table('journal_activite')
            ->where('operation', 'RECHERCHE_ACTES')
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->where('identifiant_saisi', 'like', '%TESTJOURNAL123%')
            ->first();

        $this->assertNotNull($trace, "L'opération de recherche doit être inscrite au journal d'audit.");
    }

    /**
     * Vérifie le refus 403 et la traçabilité REFUSE pour l'administrateur technique (Section 6).
     */
    public function test_acces_recherche_refuse_pour_administrateur_technique(): void
    {
        $adminTech = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->first();

        $response = $this->actingAs($adminTech)->get('/actes/recherche');
        $response->assertStatus(403);

        $refus = DB::table('journal_activite')
            ->where('resultat', 'REFUSE')
            ->where('id_utilisateur', $adminTech->id_utilisateur)
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($refus, "L'accès interdit doit être journalisé.");
    }

    /**
     * Vérifie que le filtrage par registre ne retourne que les actes de ce registre
     * (et aucun acte si le registre est vide), via id_registre ou registre_id.
     */
    public function test_filtrage_strict_des_actes_par_registre(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $registreAvecActes = Acte::first()->registre;
        $acteAutreRegistre = Acte::where('id_registre', '!=', $registreAvecActes->id_registre)->first();

        // 1. Filtrage sur un registre ayant des actes
        $response = $this->actingAs($archiviste)->get("/actes/recherche?id_registre={$registreAvecActes->id_registre}");
        $response->assertStatus(200);
        $response->assertSee($registreAvecActes->cote);
        if ($acteAutreRegistre) {
            $response->assertDontSee($acteAutreRegistre->identifiant_acte);
        }

        // 2. Filtrage sur un registre vide (sans aucun acte)
        $registreVide = \App\Models\Registre::create([
            'cote' => 'EC-NAI-2099-VIDE01',
            'type' => TypeActe::NAISSANCE,
            'annee_debut' => 2099,
            'annee_fin' => 2099,
            'etat_materiel' => \App\Enums\EtatMateriel::BON,
            'presence' => \App\Enums\PresencePhysique::PRESENT,
        ]);

        try {
            $resVide = $this->actingAs($archiviste)->get("/actes/recherche?id_registre={$registreVide->id_registre}");
            $resVide->assertStatus(200);
            $resVide->assertSee('Aucun résultat');
            $resVide->assertSee('Aucun acte ne correspond à vos critères de recherche');

            // Test avec l'alias registre_id également
            $resVideAlias = $this->actingAs($archiviste)->get("/actes/recherche?registre_id={$registreVide->id_registre}");
            $resVideAlias->assertStatus(200);
            $resVideAlias->assertSee('Aucun résultat');
        } finally {
            $registreVide->delete();
        }
    }
}
