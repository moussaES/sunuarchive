<?php

namespace Tests\Feature;

use App\Enums\EtatVerification;
use App\Enums\Profil;
use App\Enums\RolePersonne;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\Registre;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 5 (Module Actes).
 * 
 * Vérifie :
 * - La consultation d'acte et l'obligation du bandeau légal
 * - La traçabilité intégrale de la consultation dans le journal d'activité
 * - La création des 3 types d'actes (Naissance, Mariage, Décès) avec sous-tables et indexation des personnes
 * - Le contrôle anti-doublon composite (type, numero_acte, annee, id_registre)
 * - La correction de transcription avec traçabilité obligatoire
 * - La protection des habilitations (403 et journalisation REFUSE)
 */
class Phase5ActesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Évite l'expiration par inactivité dans l'environnement de test
        Utilisateur::query()->update(['derniere_activite' => now()]);

        // Nettoyage préalable des actes de test pour assurer l'idempotence
        Acte::where('numero_acte', 'like', 'TEST-%')->delete();
    }

    /**
     * Vérifie que la fiche d'acte est accessible, comporte le bandeau légal
     * et consigne la consultation au journal d'activité.
     */
    public function test_la_fiche_d_acte_s_affiche_et_enregistre_la_consultation_au_journal(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $this->assertNotNull($archiviste);

        $acte = Acte::with('registre')->first();
        $this->assertNotNull($acte);

        $response = $this->actingAs($archiviste)->get("/actes/{$acte->id_acte}");

        $response->assertStatus(200);
        $response->assertSee('Copie de consultation, le registre papier reste le document de référence');
        $response->assertSee($acte->identifiant_acte);
        $response->assertSee($acte->numero_acte);
        $response->assertSee($acte->registre->cote);

        // Vérification de la journalisation obligatoire de la consultation (Section 9.5 & 11.8)
        $trace = DB::table('journal_activite')
            ->where('operation', 'CONSULTATION_ACTE')
            ->where('type_reference', 'ACTE')
            ->where('id_reference', $acte->id_acte)
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($trace, "La consultation de la fiche d'acte doit être consignée au journal.");
        $this->assertEquals('ACCORDE', $trace->resultat);
    }

    /**
     * Vérifie le refus 403 et la journalisation REFUSE lorsqu'un profil non habilité
     * tente de consulter une fiche d'acte (ex: Administrateur technique).
     */
    public function test_acces_fiche_acte_refuse_pour_profil_non_autorise(): void
    {
        $adminTech = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->first();
        $this->assertNotNull($adminTech);

        $acte = Acte::first();
        $this->assertNotNull($acte);

        $response = $this->actingAs($adminTech)->get("/actes/{$acte->id_acte}");
        $response->assertStatus(403);

        $refus = DB::table('journal_activite')
            ->where('resultat', 'REFUSE')
            ->where('id_utilisateur', $adminTech->id_utilisateur)
            ->orderByDesc('id_journal')
            ->first();

        $this->assertNotNull($refus, "La tentative d'accès non autorisée doit être inscrite en REFUSE.");
    }

    /**
     * Vérifie la création d'un acte de naissance avec sous-table acte_naissance
     * et indexation des personnes (enfant, père, mère).
     */
    public function test_creation_d_un_acte_de_naissance_avec_personnes_indexees(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $registre = Registre::where('type', TypeActe::NAISSANCE)->first();
        $this->assertNotNull($registre);

        $payload = [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => 'TEST-NAI-99',
            'annee' => $registre->annee_debut,
            'date_enregistrement' => '2015-06-15',
            'page_folio' => 'Folio 42',
            'etat_verification' => EtatVerification::VERIFIE->value,
            'observations' => 'Acte de test naissance créé sous contrôle.',

            // Mentions naissance
            'date_naissance' => '2015-06-10',
            'lieu_naissance' => 'Maternité de Tattaguine',
            'sexe' => 'Féminin',
            'date_declaration' => '2015-06-12',

            // Personnes liées
            'nom_enfant' => 'NDIAYE',
            'prenoms_enfant' => 'Aïssatou',
            'nom_pere' => 'NDIAYE',
            'prenoms_pere' => 'Ousmane',
            'nom_mere' => 'DIOP',
            'prenoms_mere' => 'Mariama',
        ];

        $response = $this->actingAs($archiviste)->post('/actes', $payload);
        $response->assertRedirect();

        $acteCree = Acte::where('numero_acte', 'TEST-NAI-99')
            ->where('annee', $registre->annee_debut)
            ->where('id_registre', $registre->id_registre)
            ->first();

        $this->assertNotNull($acteCree);
        $this->assertEquals(TypeActe::NAISSANCE, $acteCree->type);

        // Vérification de la table fille acte_naissance
        $this->assertNotNull($acteCree->acteNaissance);
        $this->assertEquals('Maternité de Tattaguine', $acteCree->acteNaissance->lieu_naissance);
        $this->assertEquals('Féminin', $acteCree->acteNaissance->sexe);

        // Vérification des personnes liées
        $personnes = $acteCree->personnes;
        $this->assertCount(3, $personnes);

        $enfant = $personnes->firstWhere('pivot.role', RolePersonne::ENFANT->value);
        $this->assertNotNull($enfant);
        $this->assertEquals('NDIAYE', $enfant->nom);
        $this->assertEquals('Aïssatou', $enfant->prenoms);

        $pere = $personnes->firstWhere('pivot.role', RolePersonne::PERE->value);
        $this->assertNotNull($pere);
        $this->assertEquals('Ousmane', $pere->prenoms);

        $mere = $personnes->firstWhere('pivot.role', RolePersonne::MERE->value);
        $this->assertNotNull($mere);
        $this->assertEquals('Mariama', $mere->prenoms);

        // Traçabilité au journal
        $journal = DB::table('journal_activite')
            ->where('operation', 'INDEXATION_ACTE')
            ->where('id_reference', $acteCree->id_acte)
            ->first();
        $this->assertNotNull($journal);
    }

    /**
     * Vérifie la création d'un acte de mariage avec sous-table et indexation des 2 époux et témoin.
     */
    public function test_creation_d_un_acte_de_mariage_avec_deux_epoux_et_temoin(): void
    {
        $agentIdx = Utilisateur::where('profil', Profil::AGENT_INDEXATION)->first();
        $registre = Registre::where('type', TypeActe::MARIAGE)->first();
        $this->assertNotNull($registre);

        $payload = [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::MARIAGE->value,
            'numero_acte' => 'TEST-MAR-99',
            'annee' => $registre->annee_debut,
            'date_enregistrement' => '2018-04-20',
            'page_folio' => 'Folio 18',
            'etat_verification' => EtatVerification::VERIFIE->value,
            'observations' => 'Célébration en présence des familles.',

            // Mentions mariage
            'date_mariage' => '2018-04-18',
            'lieu_mariage' => 'Centre d\'état civil de Tattaguine',
            'regime_matrimonial' => 'Monogamie',
            'mentions_marginales' => 'Aucune mention ultérieure.',

            // Personnes
            'nom_epoux' => 'SARR',
            'prenoms_epoux' => 'Babacar',
            'nom_epouse' => 'FALL',
            'prenoms_epouse' => 'Aminata',
            'nom_temoin' => 'SECK',
            'prenoms_temoin' => 'Mamadou',
        ];

        $response = $this->actingAs($agentIdx)->post('/actes', $payload);
        $response->assertRedirect();

        $acteCree = Acte::where('numero_acte', 'TEST-MAR-99')
            ->where('annee', $registre->annee_debut)
            ->first();

        $this->assertNotNull($acteCree);
        $this->assertNotNull($acteCree->acteMariage);
        $this->assertEquals('Monogamie', $acteCree->acteMariage->regime_matrimonial);

        $personnes = $acteCree->personnes;
        $this->assertCount(3, $personnes);
        $this->assertNotNull($personnes->firstWhere('pivot.role', RolePersonne::EPOUX->value));
        $this->assertNotNull($personnes->firstWhere('pivot.role', RolePersonne::EPOUSE->value));
        $this->assertNotNull($personnes->firstWhere('pivot.role', RolePersonne::TEMOIN->value));
    }

    /**
     * Vérifie la création d'un acte de décès avec sous-table et indexation du défunt et déclarant.
     */
    public function test_creation_d_un_acte_de_deces_avec_defunt_et_declarant(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $registre = Registre::where('type', TypeActe::DECES)->first();
        $this->assertNotNull($registre);

        $payload = [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::DECES->value,
            'numero_acte' => 'TEST-DEC-99',
            'annee' => $registre->annee_debut,
            'etat_verification' => EtatVerification::VERIFIE->value,

            // Mentions décès
            'date_deces' => '2019-11-05',
            'heure_deces' => '14h30',
            'lieu_deces' => 'Hôpital régional de Fatick',
            'profession' => 'Agriculteur',
            'domicile' => 'Village de Tattaguine Sérère',
            'date_declaration_deces' => '2019-11-06',

            // Personnes
            'nom_defunt' => 'FAYE',
            'prenoms_defunt' => 'Ibrahima',
            'nom_declarant_deces' => 'FAYE',
            'prenoms_declarant_deces' => 'Moussa',
        ];

        $response = $this->actingAs($archiviste)->post('/actes', $payload);
        $response->assertRedirect();

        $acteCree = Acte::where('numero_acte', 'TEST-DEC-99')
            ->where('annee', $registre->annee_debut)
            ->first();

        $this->assertNotNull($acteCree);
        $this->assertNotNull($acteCree->acteDeces);
        $this->assertEquals('Agriculteur', $acteCree->acteDeces->profession);

        $personnes = $acteCree->personnes;
        $this->assertCount(2, $personnes);
        $this->assertNotNull($personnes->firstWhere('pivot.role', RolePersonne::DEFUNT->value));
        $this->assertNotNull($personnes->firstWhere('pivot.role', RolePersonne::DECLARANT->value));
    }

    /**
     * Contrôle des doublons (Section 5 & 11.3) :
     * Un acte ayant le même quadruplet (type, numero_acte, annee, id_registre) est strictement rejeté.
     */
    public function test_controle_des_doublons_composite_rejette_le_doublon(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $registre = Registre::where('type', TypeActe::NAISSANCE)->first();
        $this->assertNotNull($registre);

        $numeroTest = 'TEST-DOUBLON-REJET';

        // 1. Création préalable
        $rep1 = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $numeroTest,
            'annee' => $registre->annee_debut,
            'etat_verification' => EtatVerification::A_VERIFIER->value,
        ]);
        $rep1->assertRedirect();

        // 2. Tentative d'enregistrement avec exactement les mêmes type, numéro, année et registre
        $rep2 = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $numeroTest,
            'annee' => $registre->annee_debut,
            'etat_verification' => EtatVerification::A_VERIFIER->value,
        ]);

        $rep2->assertSessionHasErrors('numero_acte');

        $erreur = session('errors')->first('numero_acte');
        $this->assertStringContainsString('Alerte doublon', $erreur);
    }

    /**
     * Contrôle des doublons (Section 5 & 11.3) :
     * Un même numéro d'acte dans une autre année ou un autre registre est parfaitement valide.
     */
    public function test_meme_numero_avec_une_autre_annee_ou_autre_registre_est_accepte(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $registresNaissance = Registre::where('type', TypeActe::NAISSANCE)->get();
        $this->assertGreaterThanOrEqual(2, $registresNaissance->count());

        $reg1 = $registresNaissance[0];
        $reg2 = $registresNaissance[1];
        $numeroPartage = 'TEST-NUM-DOUBLON-OK';

        // 1. Premier enregistrement dans le registre 1
        $rep1 = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $reg1->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $numeroPartage,
            'annee' => $reg1->annee_debut,
            'etat_verification' => EtatVerification::A_VERIFIER->value,
        ]);
        $rep1->assertRedirect();
        $rep1->assertSessionHasNoErrors();

        // 2. Deuxième enregistrement avec le MÊME numéro dans le registre 2 (autre année et registre)
        $rep2 = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $reg2->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $numeroPartage,
            'annee' => $reg2->annee_debut,
            'etat_verification' => EtatVerification::A_VERIFIER->value,
        ]);
        $rep2->assertRedirect();
        $rep2->assertSessionHasNoErrors();

        // Les deux actes coexistent parfaitement en base
        $nb = Acte::where('numero_acte', $numeroPartage)->count();
        $this->assertEquals(2, $nb, "Le même numéro d'acte doit pouvoir exister dans deux registres différents.");
    }

    /**
     * Vérifie la correction de transcription (Section 5 & 9.5) :
     * La mise à jour modifie l'acte et consigne obligatoirement CORRECTION_ACTE au journal.
     */
    public function test_correction_de_transcription_met_a_jour_les_donnees_et_laisse_une_trace_au_journal(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $acte = Acte::first();

        $nouveauFolio = 'Folio ' . rand(100, 999);
        $nouvelleObservation = 'Correction validée après relecture du registre physique original.';

        $response = $this->actingAs($archiviste)->put("/actes/{$acte->id_acte}", [
            'id_registre' => $acte->id_registre,
            'type' => $acte->type->value,
            'numero_acte' => $acte->numero_acte,
            'annee' => $acte->annee,
            'page_folio' => $nouveauFolio,
            'etat_verification' => EtatVerification::VERIFIE->value,
            'observations' => $nouvelleObservation,
        ]);

        $response->assertRedirect("/actes/{$acte->id_acte}");

        $acte->refresh();
        $this->assertEquals($nouveauFolio, $acte->page_folio);
        $this->assertEquals($nouvelleObservation, $acte->observations);

        // Vérification de la journalisation obligatoire de la correction
        $traceCorrection = DB::table('journal_activite')
            ->where('operation', 'CORRECTION_ACTE')
            ->where('type_reference', 'ACTE')
            ->where('id_reference', $acte->id_acte)
            ->where('id_utilisateur', $archiviste->id_utilisateur)
            ->first();

        $this->assertNotNull($traceCorrection, "La correction de transcription doit être inscrite au journal.");
    }

    /**
     * Vérifie que la correction de transcription sans id_registre ni type explicites (ou avec mentions spécifiques)
     * enregistre bien la transcription (acte_naissance + personnes) et affiche l'alerte de succès.
     */
    public function test_correction_transcription_complete_naissance_et_alerte_succes(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $acte = Acte::where('type', TypeActe::NAISSANCE)->first();
        $this->assertNotNull($acte);

        $response = $this->actingAs($archiviste)->put("/actes/{$acte->id_acte}", [
            'id_registre' => $acte->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => $acte->numero_acte,
            'annee' => $acte->annee,
            'page_folio' => 'Folio 88',
            'etat_verification' => EtatVerification::VERIFIE->value,
            'date_naissance' => '2015-05-20',
            'lieu_naissance' => 'Centre de santé de Tattaguine',
            'sexe' => 'Masculin',
            'nom_enfant' => 'DIOUF',
            'prenoms_enfant' => 'Cheikh',
            // Pas de nom_pere saisi : il doit reprendre automatiquement le nom de l'enfant (DIOUF)
            'prenoms_pere' => 'Modou',
            'nom_mere' => 'SARR',
            'prenoms_mere' => 'Fatou',
        ]);

        $response->assertRedirect("/actes/{$acte->id_acte}");
        $response->assertSessionHas('succes');

        $acte->refresh();
        $this->assertNotNull($acte->acteNaissance);
        $this->assertEquals('Centre de santé de Tattaguine', $acte->acteNaissance->lieu_naissance);
        $this->assertEquals('Masculin', $acte->acteNaissance->sexe);

        $enfant = $acte->personnes->firstWhere('pivot.role', RolePersonne::ENFANT->value);
        $this->assertNotNull($enfant);
        $this->assertEquals('DIOUF', $enfant->nom);
        $this->assertEquals('Cheikh', $enfant->prenoms);

        $pere = $acte->personnes->firstWhere('pivot.role', RolePersonne::PERE->value);
        $this->assertNotNull($pere);
        $this->assertEquals('DIOUF', $pere->nom);
        $this->assertEquals('Modou', $pere->prenoms);
    }

    /**
     * Vérifie que les champs de jugement (numero_jugement, date_jugement, annee_jugement)
     * sont optionnels, et qu'ils sont correctement enregistrés lorsqu'un acte comporte un jugement.
     */
    public function test_enregistrement_et_modification_acte_avec_et_sans_jugement(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $registre = Registre::where('type', TypeActe::NAISSANCE)->first();

        // 1. Création d'un acte AVEC jugement (saisi "142", formaté automatiquement en "0142")
        $repAvec = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => 'TEST-JUG-01',
            'annee' => $registre->annee_debut,
            'etat_verification' => EtatVerification::VERIFIE->value,
            'numero_jugement' => '142',
            'date_jugement' => '2021-03-15',
            'annee_jugement' => 2021,
            'nom_enfant' => 'FALL',
            'prenoms_enfant' => 'Moussa',
        ]);
        $repAvec->assertRedirect();

        $acteAvec = Acte::where('numero_acte', 'TEST-JUG-01')->first();
        $this->assertNotNull($acteAvec);
        $this->assertTrue($acteAvec->possedeJugement());
        $this->assertEquals('0142', $acteAvec->numero_jugement);
        $this->assertEquals('2021-03-15', $acteAvec->date_jugement->format('Y-m-d'));
        $this->assertEquals(2021, $acteAvec->annee_jugement);

        // 2. Création d'un acte SANS jugement (champs non renseignés)
        $repSans = $this->actingAs($archiviste)->post('/actes', [
            'id_registre' => $registre->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => 'TEST-JUG-02',
            'annee' => $registre->annee_debut,
            'etat_verification' => EtatVerification::VERIFIE->value,
        ]);
        $repSans->assertRedirect();

        $acteSans = Acte::where('numero_acte', 'TEST-JUG-02')->first();
        $this->assertNotNull($acteSans);
        $this->assertFalse($acteSans->possedeJugement());
        $this->assertNull($acteSans->numero_jugement);
        $this->assertNull($acteSans->date_jugement);
        $this->assertNull($acteSans->annee_jugement);
    }

    /**
     * Vérifie le refus 403 et la traçabilité lors d'une tentative de création ou modification
     * par un profil non habilité (ex: Agent de consultation).
     */
    public function test_creation_et_modification_refusees_si_profil_non_autorise(): void
    {
        $agentConsultation = Utilisateur::where('profil', Profil::AGENT_CONSULTATION)->first();
        $this->assertNotNull($agentConsultation);

        $acte = Acte::first();

        // 1. Tentative de création
        $repCreation = $this->actingAs($agentConsultation)->post('/actes', [
            'id_registre' => $acte->id_registre,
            'type' => TypeActe::NAISSANCE->value,
            'numero_acte' => 'PIRATE-01',
            'annee' => 2020,
            'etat_verification' => EtatVerification::A_VERIFIER->value,
        ]);
        $repCreation->assertStatus(403);

        // 2. Tentative de modification
        $repModif = $this->actingAs($agentConsultation)->put("/actes/{$acte->id_acte}", [
            'id_registre' => $acte->id_registre,
            'type' => $acte->type->value,
            'numero_acte' => $acte->numero_acte,
            'annee' => $acte->annee,
            'etat_verification' => EtatVerification::VERIFIE->value,
        ]);
        $repModif->assertStatus(403);
    }
}
