<?php

namespace Tests\Feature;

use App\Enums\Profil;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\FichierNumerique;
use App\Models\Utilisateur;
use App\Modules\Fichiers\Services\FichierService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 6 (Module Fichiers numériques).
 * 
 * Vérifie :
 * - La validation stricte de la convention de nommage officielle (Section 8)
 * - L'interdiction des noms de personnes physiques dans les noms de fichiers
 * - L'association d'un fichier numérique (TIFF, PDF, JPEG) avec calcul SHA-256 scellé
 * - L'autorisation d'association pour l'agent d'indexation
 * - Le remplacement tracé d'un fichier avec justification obligatoire
 * - La conservation de l'ancienne version inerte dans l'historique d'audit
 * - Le refus de remplacement pour un profil non habilité (agent d'indexation)
 * - La distribution en streaming sécurisé avec en-têtes et traçabilité au journal
 * - Le refus de consultation pour l'administrateur technique (403 + REFUSE)
 * - La détection d'intégrité (VALIDE, ALTÉRÉ, MANQUANT)
 * - L'exécution de la commande artisan sigaec:verifier-fichiers.
 */
class Phase6FichiersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite l'inactivité en test
        Utilisateur::query()->update(['derniere_activite' => now()]);

        // Nettoyage et restauration systématique de l'état des fichiers numériques
        FichierNumerique::where('id_fichier', '>', 7)->delete();
        (new \Database\Seeders\FichierNumeriqueSeeder)->run();
    }

    /**
     * Vérifie la validation stricte de la convention de nommage officielle (Section 8).
     */
    public function test_convention_nommage_officielle_valide_et_invalide(): void
    {
        $service = app(FichierService::class);

        // Noms valides
        $this->assertTrue($service->validerConformiteNom('TAT-EC-NAI-2020-REG001-ACT0042-P001.tif'));
        $this->assertTrue($service->validerConformiteNom('TAT-EC-MAR-2018-REG003-ACT0015-P002.pdf'));
        $this->assertTrue($service->validerConformiteNom('TAT-EC-DEC-2022-REG005-ACT0008-P001.jpg'));
        $this->assertTrue($service->validerConformiteNom('TAT-EC-NAI-1995-REG007-ACT0001-P003.jpeg'));

        // Noms invalides : présence de nom de personne (interdit Section 8)
        $this->assertFalse($service->validerConformiteNom('TAT-EC-NAI-2020-REG001-Mamadou-Diop.pdf'));
        $this->assertFalse($service->validerConformiteNom('scan_acte_42.pdf'));
        $this->assertFalse($service->validerConformiteNom('TAT-EC-DIV-2020-REG001-ACT0042-P001.pdf')); // Type DIV invalide
        $this->assertFalse($service->validerConformiteNom('TAT-EC-NAI-2020-REG001-ACT0042-P001.exe')); // Format interdit
    }

    /**
     * Vérifie la génération automatique d'un nom normalisé pour un acte donné.
     */
    public function test_generation_nom_normalise_pour_un_acte(): void
    {
        $service = app(FichierService::class);
        $acte = Acte::where('type', TypeActe::NAISSANCE)->first();
        $this->assertNotNull($acte);

        $nomGeneré = $service->genererNomNormalise($acte, 1, 'pdf');

        $this->assertTrue($service->validerConformiteNom($nomGeneré));
        $this->assertStringStartsWith('TAT-EC-NAI-', $nomGeneré);
        $this->assertStringEndsWith('-P001.pdf', $nomGeneré);
    }

    /**
     * Vérifie l'association d'un fichier numérique par un archiviste habilité.
     */
    public function test_association_d_un_fichier_numerique_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $acte = Acte::whereDoesntHave('fichiers')->first() ?? Acte::first();

        $fichierFactice = UploadedFile::fake()->createWithContent('copie_test.pdf', "CONTENU FACTICE PROPRE ARCHIVE\n");

        $response = $this->actingAs($archiviste)->post('/fichiers', [
            'id_acte' => $acte->id_acte,
            'fichier' => $fichierFactice,
            'date_numerisation' => '2026-03-01',
            'numero_page' => 1,
        ]);

        $response->assertRedirect("/actes/{$acte->id_acte}");

        $fichierCree = FichierNumerique::where('id_acte', $acte->id_acte)
            ->where('actif', true)
            ->latest('id_fichier')
            ->first();

        $this->assertNotNull($fichierCree);
        $this->assertTrue($fichierCree->actif);
        $this->assertEquals('copie_test.pdf', $fichierCree->nom_origine);

        // Vérification de la présence physique sur le disque privé archives
        $disque = Storage::disk('archives');
        $this->assertTrue($disque->exists($fichierCree->chemin_stockage));

        // Vérification de l'empreinte SHA-256 réelle
        $cheminComplet = $disque->path($fichierCree->chemin_stockage);
        $shaReel = hash_file('sha256', $cheminComplet);
        $this->assertEquals($shaReel, $fichierCree->empreinte_sha256);

        // Journalisation obligatoire (Section 8 & 11.8)
        $trace = DB::table('journal_activite')
            ->where('operation', 'ASSOCIATION_FICHIER')
            ->where('id_reference', $fichierCree->id_fichier)
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($trace, "L'association de fichier doit être inscrite au journal.");
    }

    /**
     * Vérifie que l'agent d'indexation peut associer un fichier (association seule).
     */
    public function test_association_fichier_par_agent_indexation_reussie(): void
    {
        $agentIdx = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->first();
        $acte = Acte::first();

        $fichierFactice = UploadedFile::fake()->createWithContent('scan_indexation.jpg', "CONTENU IMAGE REELLE\n");

        $response = $this->actingAs($agentIdx)->post('/fichiers', [
            'id_acte' => $acte->id_acte,
            'fichier' => $fichierFactice,
            'numero_page' => 2,
        ]);

        $response->assertRedirect("/actes/{$acte->id_acte}");
    }

    /**
     * Vérifie le remplacement de fichier avec justification obligatoire par un archiviste.
     */
    public function test_remplacement_de_fichier_avec_justification_par_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $ancienFichier = FichierNumerique::where('actif', true)->first();
        $this->assertNotNull($ancienFichier);

        $justification = "Numérisation initiale 150 DPI floue. Nouveau scan pleine page 400 DPI haute lisibilité conforme.";
        $nouveauFichierFactice = UploadedFile::fake()->createWithContent('nouveau_scan.pdf', "NOUVEAU CONTENU 400 DPI VALIDE\n");

        $response = $this->actingAs($archiviste)->post("/fichiers/{$ancienFichier->id_fichier}/remplacer", [
            'id_fichier' => $ancienFichier->id_fichier,
            'fichier' => $nouveauFichierFactice,
            'justification_remplacement' => $justification,
        ]);

        $response->assertRedirect();

        // 1. L'ancien fichier doit être passé inactif
        $ancienFichier->refresh();
        $this->assertFalse($ancienFichier->actif);

        // 2. Le nouveau fichier doit être actif, lié à l'ancien avec la justification
        $nouveauFichier = FichierNumerique::where('remplace_fichier_id', $ancienFichier->id_fichier)->first();
        $this->assertNotNull($nouveauFichier);
        $this->assertTrue($nouveauFichier->actif);
        $this->assertEquals($justification, $nouveauFichier->justification_remplacement);

        // 3. Les deux fichiers physiques doivent coexister sur le support privé
        $disque = Storage::disk('archives');
        $this->assertTrue($disque->exists($nouveauFichier->chemin_stockage));
        $this->assertTrue($disque->exists($ancienFichier->chemin_stockage));

        // 4. Journalisation du remplacement obligatoire
        $trace = DB::table('journal_activite')
            ->where('operation', 'REMPLACEMENT_FICHIER')
            ->where('id_reference', $nouveauFichier->id_fichier)
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($trace);
    }

    /**
     * Vérifie que le remplacement est strictement rejeté sans justification suffisante (< 10 caractères).
     */
    public function test_remplacement_refuse_si_justification_insuffisante(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $fichier = FichierNumerique::where('actif', true)->first();

        $response = $this->actingAs($archiviste)->post("/fichiers/{$fichier->id_fichier}/remplacer", [
            'id_fichier' => $fichier->id_fichier,
            'fichier' => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
            'justification_remplacement' => 'Court', // Moins de 10 caractères
        ]);

        $response->assertSessionHasErrors('justification_remplacement');
    }

    /**
     * Vérifie le refus 403 et la journalisation REFUSE lorsque l'agent d'indexation tente un remplacement
     * (l'agent d'indexation n'a que le droit d'association).
     */
    public function test_remplacement_refuse_pour_agent_indexation(): void
    {
        $agentIdx = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->first();
        $fichier = FichierNumerique::where('actif', true)->first();

        $response = $this->actingAs($agentIdx)->post("/fichiers/{$fichier->id_fichier}/remplacer", [
            'id_fichier' => $fichier->id_fichier,
            'fichier' => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
            'justification_remplacement' => 'Tentative de remplacement non autorisée.',
        ]);

        $response->assertStatus(403);

        $refus = DB::table('journal_activite')
            ->where('resultat', 'REFUSE')
            ->where('id_utilisateur', $agentIdx->id_utilisateur)
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($refus);
    }

    /**
     * Vérifie la distribution sécurisée en streaming et la journalisation de la consultation.
     */
    public function test_consultation_flux_securise_et_journalisation(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();
        $fichier = FichierNumerique::where('actif', true)->first();

        $response = $this->actingAs($consultant)->get("/fichiers/{$fichier->id_fichier}");

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotEmpty($response->headers->get('Content-Type'));

        // Vérification de la journalisation obligatoire de la consultation (Section 7 & 11.8)
        $trace = DB::table('journal_activite')
            ->where('operation', 'CONSULTATION_FICHIER')
            ->where('id_reference', $fichier->id_fichier)
            ->where('id_utilisateur', $consultant->id_utilisateur)
            ->first();

        $this->assertNotNull($trace, "La consultation du flux de fichier doit être inscrite au journal.");
    }

    /**
     * Vérifie le refus 403 lorsque l'administrateur technique tente d'accéder à la reproduction numérique.
     */
    public function test_acces_flux_refuse_pour_admin_technique(): void
    {
        $adminTech = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->first();
        $fichier = FichierNumerique::where('actif', true)->first();

        $response = $this->actingAs($adminTech)->get("/fichiers/{$fichier->id_fichier}");
        $response->assertStatus(403);

        $trace = DB::table('journal_activite')
            ->where('resultat', 'REFUSE')
            ->where('id_utilisateur', $adminTech->id_utilisateur)
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($trace);
    }

    /**
     * Vérifie le contrôle d'intégrité à l'écran (statut VALIDE, ALTÉRÉ, MANQUANT).
     */
    public function test_verification_integrite_detecte_fichier_valide_et_altere_et_manquant(): void
    {
        $service = app(FichierService::class);
        $disque = Storage::disk('archives');

        // 1. Fichier valide
        $fichierValide = FichierNumerique::where('actif', true)->first();
        $ctrl1 = $service->verifierIntegrite($fichierValide);
        $this->assertTrue($ctrl1['valide']);
        $this->assertEquals('VALIDE', $ctrl1['statut']);

        // 2. Fichier altéré (bit flip / modification contenu)
        $fichierAltere = FichierNumerique::create([
            'id_acte' => $fichierValide->id_acte,
            'nom_fichier' => 'TAT-EC-NAI-2020-REG001-ACT0099-P001.tif',
            'nom_origine' => 'original.tif',
            'format' => 'tif',
            'taille_octets' => 50,
            'empreinte_sha256' => hash('sha256', 'CONTENU_ORIGINAL_SCELLE'),
            'chemin_stockage' => 'NAI/2020/REG001/fichier_test_altere.tif',
            'actif' => true,
        ]);
        // Écrit un contenu différent sur le disque
        $disque->put($fichierAltere->chemin_stockage, 'CONTENU_MODIFIE_ILLICITEMENT');

        $ctrl2 = $service->verifierIntegrite($fichierAltere);
        $this->assertFalse($ctrl2['valide']);
        $this->assertEquals('ALTERE', $ctrl2['statut']);

        // 3. Fichier manquant
        $fichierManquant = FichierNumerique::create([
            'id_acte' => $fichierValide->id_acte,
            'nom_fichier' => 'TAT-EC-NAI-2020-REG001-ACT0098-P001.tif',
            'nom_origine' => 'original_manquant.tif',
            'format' => 'tif',
            'taille_octets' => 50,
            'empreinte_sha256' => hash('sha256', 'INTACT'),
            'chemin_stockage' => 'NAI/2020/REG001/fichier_fantome.tif',
            'actif' => true,
        ]);
        // Ne crée aucun fichier physique
        $ctrl3 = $service->verifierIntegrite($fichierManquant);
        $this->assertFalse($ctrl3['valide']);
        $this->assertEquals('MANQUANT', $ctrl3['statut']);

        // Nettoyage des fichiers temporaires de test
        $disque->delete($fichierAltere->chemin_stockage);
        $fichierAltere->delete();
        $fichierManquant->delete();
    }

    /**
     * Vérifie la commande artisan sigaec:verifier-fichiers.
     */
    public function test_commande_artisan_sigaec_verifier_fichiers(): void
    {
        $codeRetour = Artisan::call('sigaec:verifier-fichiers');
        $this->assertEquals(0, $codeRetour);

        $sortie = Artisan::output();
        $this->assertStringContainsString('CONTRÔLE D\'INTÉGRITÉ CRYPTOGRAPHIQUE', $sortie);
        $this->assertStringContainsString('Intégrité parfaite', $sortie);
    }
}
