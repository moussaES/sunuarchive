<?php

namespace Tests\Feature;

use App\Enums\EtatMateriel;
use App\Enums\EtatVerification;
use App\Enums\PresencePhysique;
use App\Enums\Profil;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\DemandeCommunication;
use App\Models\FichierNumerique;
use App\Models\Registre;
use App\Models\Utilisateur;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Recette globale d'intégration du SIGAEC (Phase 10).
 * 
 * Valide l'ensemble des parcours métiers transversaux sur les 5 profils habilités :
 * - Archiviste : cycle complet (registre, acte, fichier, remplacement avec justification, export PDF/CSV)
 * - Agent d'indexation : saisie d'actes et de fichiers, blocage des opérations réservées
 * - Agent de consultation : repérage et consultation, interdiction d'altération et d'administration
 * - Responsable habilité : visa des réquisitions et décisions formelles
 * - Administrateur technique : gestion des comptes, sauvegardes, déblocage, sans accès aux flux confidentiels
 * - Contrôle de conformité de la charte graphique monochrome vert menthe exclusive.
 */
class Phase10RecetteGlobaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite le déclenchement de la déconnexion d'inactivité pendant la recette
        Utilisateur::query()->update(['derniere_activite' => now()]);
        Storage::fake('archives');
    }

    /**
     * Parcours complet de l'Archiviste communal.
     */
    public function test_parcours_complet_archiviste(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->firstOrFail();

        // 1. Consultation du tableau de bord
        $resHome = $this->actingAs($archiviste)->get('/');
        $resHome->assertStatus(200);
        $resHome->assertSee('Tableau de bord de conservation');

        // 2. Création d'un registre
        $coteUnique = 'REG-RECETTE-' . time();
        $resReg = $this->actingAs($archiviste)->post('/registres', [
            'cote' => $coteUnique,
            'type' => TypeActe::NAISSANCE->value,
            'annee_debut' => 2026,
            'annee_fin' => 2026,
            'numero_volume' => '1',
            'nombre_pages' => 150,
            'nombre_actes' => 80,
            'localisation' => 'Armoire Recette E4',
            'etat_materiel' => EtatMateriel::BON->value,
            'presence' => PresencePhysique::PRESENT->value,
            'inventaire_complet' => 1,
            'observations' => 'Registre créé lors de la recette globale.',
            'date_inventaire' => '2026-03-01',
        ]);
        $resReg->assertRedirect();
        $registre = Registre::where('cote', $coteUnique)->firstOrFail();

        // 3. Création d'un acte dans ce registre avec personnes indexées
        $resActe = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => 'REC-001',
            'annee' => 2026,
            'date_enregistrement' => '2026-03-15',
            'page_folio' => 'Folio 1',
            'etat_verification' => EtatVerification::VERIFIE->value,
            'observations' => 'Acte de recette globale',
            'date_naissance' => '2026-03-14',
            'heure_naissance' => '08:00',
            'lieu_naissance' => 'Maternité de Tattaguine',
            'sexe' => 'Masculin',
            'date_declaration' => '2026-03-15',
            'nom_enfant' => 'FAYE',
            'prenoms_enfant' => 'Abdoulaye',
            'nom_pere' => 'FAYE',
            'prenoms_pere' => 'Babacar',
            'nom_mere' => 'NDIAYE',
            'prenoms_mere' => 'Aïssatou',
        ]);
        $resActe->assertRedirect();
        $acte = Acte::where('id_registre', $registre->id_registre)->where('numero_acte', 'REC-001')->firstOrFail();

        // 4. Téléversement d'une première copie numérique
        $fichierPdf1 = UploadedFile::fake()->createWithContent('acte_recette_1.pdf', "%PDF-1.4 Factice\n");
        $resFichier1 = $this->actingAs($archiviste)->post('/fichiers', [
            'id_acte' => $acte->id_acte,
            'fichier' => $fichierPdf1,
            'date_numerisation' => '2026-03-15',
            'numero_page' => 1,
        ]);
        $resFichier1->assertRedirect();
        $fichierActif = $acte->fichiers()->where('actif', true)->firstOrFail();
        $this->assertEquals('PDF', strtoupper($fichierActif->format));

        // 5. Remplacement de la reproduction numérique avec justification
        $fichierPdf2 = UploadedFile::fake()->createWithContent('acte_recette_2_hd.pdf', "%PDF-1.4 Factice HD\n");
        $resRemplacement = $this->actingAs($archiviste)->post("/fichiers/{$fichierActif->id_fichier}/remplacer", [
            'id_fichier' => $fichierActif->id_fichier,
            'fichier' => $fichierPdf2,
            'justification_remplacement' => 'Numérisation haute définition 300 DPI pour lisibilité de la marge',
        ]);
        $resRemplacement->assertRedirect();

        // 6. Exportations officielles (CSV et PDF)
        $resCsv = $this->actingAs($archiviste)->get('/rapports/synthese-csv');
        $resCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $resCsv->headers->get('Content-Type'));

        $resPdf = $this->actingAs($archiviste)->get('/rapports/synthese-pdf');
        $resPdf->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $resPdf->headers->get('Content-Type'));
    }

    /**
     * Parcours de l'Agent d'indexation et étanchéité de ses droits.
     */
    public function test_parcours_agent_indexation_et_restrictions(): void
    {
        $indexeur = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->firstOrFail();
        $registre = Registre::where('type', TypeActe::NAISSANCE)->firstOrFail();

        // 1. Autorisé à créer un acte
        $numUnique = 'REC-IDX-' . rand(1000, 9999);
        $resActe = $this->actingAs($indexeur)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $numUnique,
            'annee' => $registre->annee_debut,
            'date_enregistrement' => now()->toDateString(),
            'page_folio' => 'Folio 12',
            'etat_verification' => EtatVerification::A_VERIFIER->value,
            'date_naissance' => now()->toDateString(),
            'lieu_naissance' => 'Tattaguine',
            'sexe' => 'Féminin',
            'date_declaration' => now()->toDateString(),
            'nom_enfant' => 'DIOP',
            'prenoms_enfant' => 'Mariama',
        ]);
        $resActe->assertRedirect();
        $acte = Acte::where('id_registre', $registre->id_registre)->where('numero_acte', $numUnique)->firstOrFail();

        // 2. Autorisé à attacher un fichier
        $fichier = UploadedFile::fake()->createWithContent('page1.pdf', "%PDF-1.4 test\n");
        $resFichier = $this->actingAs($indexeur)->post('/fichiers', [
            'id_acte' => $acte->id_acte,
            'fichier' => $fichier,
            'numero_page' => 1,
        ]);
        $resFichier->assertRedirect();
        $fichierModel = $acte->fichiers()->firstOrFail();

        // 3. Interdit de remplacer un fichier (réservé à l'archiviste)
        $nouveauFichier = UploadedFile::fake()->createWithContent('nouveau.pdf', "%PDF-1.4 test\n");
        $resRemplacement = $this->actingAs($indexeur)->post("/fichiers/{$fichierModel->id_fichier}/remplacer", [
            'id_fichier' => $fichierModel->id_fichier,
            'fichier' => $nouveauFichier,
            'justification_remplacement' => 'Tentative non autorisée par agent indexation.',
        ]);
        $resRemplacement->assertStatus(403);

        // 4. Interdit d'accéder au module d'administration
        $resAdmin = $this->actingAs($indexeur)->get('/admin');
        $resAdmin->assertStatus(403);

        // 5. Interdit d'exporter les rapports de situation
        $resRapports = $this->actingAs($indexeur)->get('/rapports/synthese-pdf');
        $resRapports->assertStatus(403);
    }

    /**
     * Parcours de l'Agent de consultation (lecture seule et respect du secret de la vie privée).
     */
    public function test_parcours_agent_consultation_et_restrictions(): void
    {
        $consultant = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->firstOrFail();
        $acte = Acte::firstOrFail();

        // 1. Accès à la recherche d'actes
        $resRecherche = $this->actingAs($consultant)->get('/actes/recherche?q=' . $acte->numero_acte);
        $resRecherche->assertStatus(200);

        // 2. Consultation de la fiche de l'acte
        $resFiche = $this->actingAs($consultant)->get("/actes/{$acte->id_acte}");
        $resFiche->assertStatus(200);
        $resFiche->assertSee('Copie de consultation, le registre papier reste le document de référence');

        // 3. Tentative de création d'acte interdite (403)
        $resCreer = $this->actingAs($consultant)->post('/actes', [
            'id_registre' => $acte->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => '99999',
            'annee' => 2026,
            'date_enregistrement' => now()->toDateString(),
        ]);
        $resCreer->assertStatus(403);

        // 4. Tentative d'accès à l'administration interdite (403)
        $resAdmin = $this->actingAs($consultant)->get('/admin');
        $resAdmin->assertStatus(403);
    }

    /**
     * Parcours du Responsable habilité (décisions sur les réquisitions).
     */
    public function test_parcours_responsable_habilite_et_decision_requisition(): void
    {
        $responsable = Utilisateur::where('profil', Profil::RESPONSABLE_HABILITE)->firstOrFail();

        // 1. Création d'une réquisition en attente
        $demande = DemandeCommunication::create([
            'numero_demande' => 'DEM-RECETTE-' . time() . '-' . rand(10, 99),
            'id_utilisateur' => $responsable->id_utilisateur,
            'date_demande' => now()->toDateString(),
            'service_demandeur' => 'Gendarmerie Nationale — Brigade de Fatick',
            'objet' => 'Procédure judiciaire n° 2026/71',
            'decision' => 'EN_ATTENTE',
        ]);

        // 2. Décision formelle par le responsable habilité
        $resDecision = $this->actingAs($responsable)->post("/demandes/{$demande->id_demande}/decider", [
            'decision' => 'ACCEPTEE',
        ]);
        $resDecision->assertSessionHas('succes');

        $demande->refresh();
        $this->assertEquals('ACCEPTEE', $demande->decision);
        $this->assertEquals(now()->toDateString(), $demande->date_traitement->toDateString());

        // 3. Vérification de la traçabilité au journal
        $this->assertDatabaseHas('journal_activite', [
            'id_utilisateur' => $responsable->id_utilisateur,
            'operation' => 'DECISION_DEMANDE_ACCEPTEE',
            'type_reference' => 'DEMANDE',
            'id_reference' => $demande->id_demande,
        ]);
    }

    /**
     * Parcours de l'Administrateur technique (gestion des comptes, sauvegardes, et étanchéité documentaire).
     */
    public function test_parcours_admin_technique_securite_et_sauvegardes(): void
    {
        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->firstOrFail();

        // 1. Accès au module d'administration
        $resAdmin = $this->actingAs($admin)->get('/admin');
        $resAdmin->assertStatus(200);

        // 2. Création d'un agent avec mot de passe robuste
        $loginAgent = 'recette.agent.' . time();
        $resUser = $this->actingAs($admin)->post('/admin/utilisateurs', [
            'identifiant' => $loginAgent,
            'nom_complet' => 'Agent Recette Finale',
            'profil' => Profil::AGENT_CONSULTATION->value,
            'mot_de_passe' => 'Tattaguine2026!Securite',
        ]);
        $resUser->assertRedirect('/admin?onglet=utilisateurs');

        // 3. Déclenchement de la sauvegarde via interface
        $resSauvegarde = $this->actingAs($admin)->post('/admin/sauvegardes/lancer');
        $resSauvegarde->assertRedirect('/admin?onglet=sauvegardes');
        $resSauvegarde->assertSessionHas('succes');

        // 4. Déclenchement du test de restauration via interface
        $resRestauration = $this->actingAs($admin)->post('/admin/sauvegardes/tester');
        $resRestauration->assertRedirect('/admin?onglet=sauvegardes');
        $resRestauration->assertSessionHas('succes');

        // 5. Étanchéité absolue : l'administrateur technique ne peut pas visualiser les flux d'actes numérisés
        $fichier = FichierNumerique::first();
        if ($fichier) {
            $resFlux = $this->actingAs($admin)->get("/fichiers/{$fichier->id_fichier}");
            $resFlux->assertStatus(403);
        }
    }

    /**
     * Contrôle de conformité de la charte graphique stricte (#3EB489, #FFFFFF, #111111).
     */
    public function test_conformite_globale_design_system(): void
    {
        $codeRetour = Artisan::call('sigaec:verifier-design');
        $this->assertEquals(0, $codeRetour, "Le design system SIGAEC doit respecter rigoureusement la palette à 3 couleurs.");
    }
}
