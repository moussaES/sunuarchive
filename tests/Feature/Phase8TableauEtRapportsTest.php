<?php

namespace Tests\Feature;

use App\Enums\Profil;
use App\Models\Registre;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 8 (Tableau de bord et Rapports).
 * 
 * Vérifie :
 * - L'affichage du tableau de bord avec les 4 indicateurs consolidés (Section 9.2)
 * - La progression de numérisation par type (barres noir/menthe)
 * - La liste des « Registres à surveiller » (état dégradé ou présence anormale)
 * - Le bandeau d'état du système (dernière sauvegarde, dernier test, intégrité)
 * - L'exportation de la synthèse d'inventaire en CSV avec encodage UTF-8 et BOM (Section 11.10)
 * - La génération du rapport officiel en PDF via DomPDF
 * - La protection des droits (refus 403 et journalisation REFUSE pour profils non habilités).
 */
class Phase8TableauEtRapportsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite l'inactivité en test
        Utilisateur::query()->update(['derniere_activite' => now()]);
    }

    /**
     * Vérifie que le tableau de bord s'affiche et calcule les 4 indicateurs clés.
     */
    public function test_tableau_de_bord_accessible_et_affiche_les_quatre_indicateurs(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $this->assertNotNull($archiviste);

        $response = $this->actingAs($archiviste)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Tableau de bord de conservation');

        // Les 4 indicateurs de la section 9.2
        $response->assertSee('1. Registres inventoriés');
        $response->assertSee('2. Actes indexés en base');
        $response->assertSee('3. Fichiers numérisés');
        $response->assertSee('4. Fiches d\'actes à vérifier', false);
    }

    /**
     * Vérifie le calcul et l'affichage de la progression de numérisation par type.
     */
    public function test_progression_de_numerisation_par_type_est_calculee(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();

        $response = $this->actingAs($consultant)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Progression de numérisation');
        $response->assertSee('Acte de Naissance');
        $response->assertSee('Acte de Mariage');
        $response->assertSee('Acte de Décès');
    }

    /**
     * Vérifie que la liste « Registres à surveiller » filtre correctement les registres anormaux.
     */
    public function test_liste_registres_a_surveiller_affiche_les_registres_degrades(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/');

        $response->assertStatus(200);
        $response->assertSee('Registres physiques à surveiller');

        // Registre 5 est 'DEGRADE' dans les seeders
        $regDegrade = Registre::where('etat_materiel', 'DEGRADE')->first();
        if ($regDegrade) {
            $response->assertSee($regDegrade->cote);
        }
    }

    /**
     * Vérifie la présence du bandeau d'état du système (sauvegardes et intégrité SHA-256).
     */
    public function test_bandeau_etat_du_systeme_affiche_sauvegardes_et_integrite(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/');

        $response->assertStatus(200);
        $response->assertSee('État du système');
        $response->assertSee('Dernière sauvegarde automatique');
        $response->assertSee('Dernier test de restauration');
        $response->assertSee('Intégrité cryptographique SHA-256');
    }

    /**
     * Vérifie l'export CSV d'inventaire avec UTF-8 BOM et traçabilité au journal.
     */
    public function test_export_csv_synthese_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/rapports/synthese-csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Vérification du BOM UTF-8 (\xEF\xBB\xBF) pour compatibilité Excel
        $contenu = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenu);
        $this->assertStringContainsString('Cote archivistique', $contenu);

        // Journalisation obligatoire (Section 11.8)
        $trace = DB::table('journal_activite')
            ->where('operation', 'EXPORT_CSV_RAPPORT')
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($trace);
    }

    /**
     * Vérifie la génération du rapport officiel en PDF et sa conformité.
     */
    public function test_export_pdf_synthese_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();

        $response = $this->actingAs($archiviste)->get('/rapports/synthese-pdf');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');

        // Signature du format PDF standard (%PDF-)
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        // Journalisation obligatoire (Section 11.8)
        $trace = DB::table('journal_activite')
            ->where('operation', 'EXPORT_PDF_RAPPORT')
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($trace);
    }

    /**
     * Vérifie le refus 403 et journalisation REFUSE pour un agent sans droit d'export.
     */
    public function test_exports_refuses_pour_agent_consultation(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();

        // 1. Export CSV
        $repCsv = $this->actingAs($consultant)->get('/rapports/synthese-csv');
        $repCsv->assertStatus(403);

        // 2. Export PDF
        $repPdf = $this->actingAs($consultant)->get('/rapports/synthese-pdf');
        $repPdf->assertStatus(403);

        $refus = DB::table('journal_activite')
            ->where('resultat', 'REFUSE')
            ->where('id_utilisateur', $consultant->id_utilisateur)
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($refus);
    }
}
