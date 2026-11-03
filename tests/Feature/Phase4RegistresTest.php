<?php

namespace Tests\Feature;

use App\Enums\EtatMateriel;
use App\Enums\PresencePhysique;
use App\Enums\Profil;
use App\Enums\TypeActe;
use App\Models\Registre;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 4 (Module Registres : inventaire, filtres, détail, pop-ups, CSV).
 */
class Phase4RegistresTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Utilisateur::query()->update(['derniere_activite' => now()]);
        Registre::where('cote', 'like', '%TEST999%')->delete();
        Registre::where('cote', 'EC-NAI-2015-REG001')->update(['etat_materiel' => EtatMateriel::BON->value]);
    }
    /**
     * Vérifie que la page d'inventaire des registres est accessible et affiche le tableau.
     */
    public function test_la_liste_des_registres_s_affiche_correctement(): void
    {
        $response = $this->get('/registres');
        $response->assertStatus(200);
        $response->assertSee('Inventaire des Registres');
        $response->assertSee('EC-NAI-2015-REG001');
        $response->assertSee('Cote archivistique');
    }

    /**
     * Vérifie le bon fonctionnement des filtres multicritères (type, état matériel).
     */
    public function test_le_filtrage_des_registres_par_type_et_etat(): void
    {
        $response = $this->get('/registres?type=NAISSANCE&etat_materiel=BON');
        $response->assertStatus(200);
        $response->assertSee('EC-NAI-2015-REG001');
        $response->assertDontSee('EC-DEC-2019-REG005'); // Décès Dégradé
    }

    /**
     * Vérifie que le volet de détail affiche l'ensemble des métadonnées archivistiques.
     */
    public function test_le_volet_de_detail_affiche_les_informations_completes(): void
    {
        $reg = Registre::where('cote', 'EC-NAI-2015-REG001')->first();
        $this->assertNotNull($reg);

        $response = $this->get("/registres?detail={$reg->id_registre}");
        $response->assertStatus(200);
        $response->assertSee('Volet de détail du registre');
        $response->assertSee($reg->cote);
        $response->assertSee($reg->localisation);
        $response->assertSee('Voir les actes de ce registre');
    }

    /**
     * Vérifie la création d'un registre par un archiviste habilité avec traçabilité.
     */
    public function test_creation_d_un_registre_par_archiviste_et_journalisation(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $this->assertNotNull($archiviste);

        $coteTest = 'EC-NAI-2024-TEST999';

        $response = $this->actingAs($archiviste)->post('/registres', [
            'cote' => $coteTest,
            'type' => TypeActe::NAISSANCE->value,
            'annee_debut' => 2024,
            'annee_fin' => 2024,
            'numero_volume' => '1',
            'nombre_pages' => 150,
            'nombre_actes' => 80,
            'localisation' => 'Salle d\'archives / Armoire D / Étagère 3',
            'etat_materiel' => EtatMateriel::BON->value,
            'presence' => PresencePhysique::PRESENT->value,
            'inventaire_complet' => 1,
            'observations' => 'Registre test créé avec succès via formulaire pop-up.',
            'date_inventaire' => '2026-03-01',
        ]);

        $response->assertRedirect();

        $registreCree = Registre::where('cote', $coteTest)->first();
        $this->assertNotNull($registreCree, "Le registre doit être persisté en base.");
        $this->assertEquals(2024, $registreCree->annee_debut);
        $this->assertTrue($registreCree->inventaire_complet);

        // Vérification de la journalisation obligatoire
        $log = DB::table('journal_activite')
            ->where('operation', 'CREATION_REGISTRE')
            ->where('id_reference', $registreCree->id_registre)
            ->first();

        $this->assertNotNull($log, "L'opération de création doit être inscrite au journal.");
        $this->assertEquals('REUSSI', $log->resultat);
    }

    /**
     * Vérifie le refus 403 et la traçabilité lors d'une tentative de création par un compte non habilité.
     */
    public function test_creation_registre_refusee_si_non_autorise(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();
        $this->assertNotNull($consultant);

        $response = $this->actingAs($consultant)->post('/registres', [
            'cote' => 'EC-NAI-2025-HACK',
            'type' => TypeActe::NAISSANCE->value,
            'annee_debut' => 2025,
            'annee_fin' => 2025,
            'etat_materiel' => EtatMateriel::BON->value,
            'presence' => PresencePhysique::PRESENT->value,
        ]);

        $response->assertStatus(403);

        // Trace REFUSE
        $log = DB::table('journal_activite')
            ->where('id_utilisateur', $consultant->id_utilisateur)
            ->where('resultat', 'REFUSE')
            ->orderByDesc('id_journal')
            ->first();
        $this->assertNotNull($log);
    }

    /**
     * Vérifie la mise à jour d'un registre par un archiviste habilité.
     */
    public function test_mise_a_jour_d_un_registre_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $reg = Registre::where('cote', 'EC-NAI-2015-REG001')->first() ?? Registre::first();
        $ancienEtat = $reg->etat_materiel;

        $response = $this->actingAs($archiviste)->put("/registres/{$reg->id_registre}", [
            'cote' => $reg->cote,
            'type' => $reg->type->value,
            'annee_debut' => $reg->annee_debut,
            'annee_fin' => $reg->annee_fin,
            'etat_materiel' => EtatMateriel::MOYEN->value,
            'presence' => PresencePhysique::PRESENT->value,
            'localisation' => 'Nouvelle localisation mise à jour',
            'observations' => 'Mise à jour suite au contrôle périodique.',
        ]);

        $response->assertRedirect();

        $reg->refresh();
        $this->assertEquals('Nouvelle localisation mise à jour', $reg->localisation);
        $this->assertEquals(EtatMateriel::MOYEN, $reg->etat_materiel);

        // Restaure l'état initial pour l'idempotence des tests suivants
        $reg->update(['etat_materiel' => $ancienEtat]);

        // Trace MODIFICATION_REGISTRE
        $log = DB::table('journal_activite')
            ->where('operation', 'MODIFICATION_REGISTRE')
            ->where('id_reference', $reg->id_registre)
            ->first();
        $this->assertNotNull($log);
    }

    /**
     * Vérifie le rejet des doublons de cote lors de l'enregistrement.
     */
    public function test_rejet_doublon_de_cote(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $regExistant = Registre::first();

        $response = $this->actingAs($archiviste)->post('/registres', [
            'cote' => $regExistant->cote, // doublon intentionnel
            'type' => TypeActe::NAISSANCE->value,
            'annee_debut' => 2024,
            'annee_fin' => 2024,
            'etat_materiel' => EtatMateriel::BON->value,
            'presence' => PresencePhysique::PRESENT->value,
        ]);

        $response->assertSessionHasErrors('cote');
    }

    /**
     * Vérifie l'export CSV officiel des registres (avec encodage UTF-8 BOM et en-têtes conformes).
     */
    public function test_export_csv_des_registres(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/registres/export/csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contenu = $response->getContent();

        // Vérification du BOM UTF-8
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu, "Le CSV doit débuter par le BOM UTF-8.");

        // Vérification des colonnes métier
        $this->assertStringContainsString('Cote archivistique', $contenu);
        $this->assertStringContainsString('Type d\'acte', $contenu);
        $this->assertStringContainsString('Année début', $contenu);
        $this->assertStringContainsString('Année fin', $contenu);
        $this->assertStringContainsString('EC-NAI-2015-REG001', $contenu);

        // Vérification de la journalisation de l'export
        $log = DB::table('journal_activite')
            ->where('operation', 'EXPORT_CSV_REGISTRES')
            ->first();
        $this->assertNotNull($log, "L'export CSV doit être consigné dans le journal.");
    }
}
