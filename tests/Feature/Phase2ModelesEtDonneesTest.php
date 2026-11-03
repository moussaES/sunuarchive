<?php

namespace Tests\Feature;

use App\Enums\EtatMateriel;
use App\Enums\Profil;
use App\Enums\RolePersonne;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\FichierNumerique;
use App\Models\JournalActivite;
use App\Models\Registre;
use App\Models\Utilisateur;
use RuntimeException;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 2 (Modèles Eloquent, Enums, relations, intégrité et seeders).
 */
class Phase2ModelesEtDonneesTest extends TestCase
{
    /**
     * Vérifie que les 5 profils obligatoires disposent chacun d'un compte actif en base.
     */
    public function test_les_cinq_profils_utilisateurs_sont_presents(): void
    {
        $profilsAttendus = [
            Profil::ADMIN_TECHNIQUE,
            Profil::ARCHIVISTE,
            Profil::AGENT_INDEXATION,
            Profil::AGENT_CONSULTATION,
            Profil::RESPONSABLE_HABILITE,
        ];

        foreach ($profilsAttendus as $profil) {
            $user = Utilisateur::where('profil', $profil)->first();
            $this->assertNotNull($user, "Un utilisateur avec le profil {$profil->value} doit exister.");
            $this->assertTrue($user->estActif(), "Le compte {$user->identifiant} doit être actif.");
            $this->assertNotEmpty($user->mot_de_passe_hash);
        }
    }

    /**
     * Vérifie la présence d'au moins 6 registres diversifiés couvrant les 3 types d'actes.
     */
    public function test_les_registres_sont_au_nombre_requis_et_diversifies(): void
    {
        $nbRegistres = Registre::count();
        $this->assertGreaterThanOrEqual(6, $nbRegistres, "Il doit y avoir au moins 6 registres.");

        $typesPresents = Registre::distinct()->pluck('type')->map(fn($t) => $t instanceof TypeActe ? $t->value : $t)->toArray();
        $this->assertContains('NAISSANCE', $typesPresents);
        $this->assertContains('MARIAGE', $typesPresents);
        $this->assertContains('DECES', $typesPresents);

        // Vérifie la méthode métier estInventorie()
        $regComplet = Registre::where('inventaire_complet', true)->first();
        $this->assertNotNull($regComplet);
        $this->assertTrue($regComplet->estInventorie());
    }

    /**
     * Vérifie la présence d'au moins 40 actes avec leurs liaisons d'héritage et personnes indexées.
     */
    public function test_les_actes_couvrent_les_trois_types_avec_relations_valides(): void
    {
        $nbActes = Acte::count();
        $this->assertGreaterThanOrEqual(40, $nbActes, "Il doit y avoir au moins 40 actes indexés.");

        // 1. Acte de naissance
        $acteNai = Acte::where('type', TypeActe::NAISSANCE)->with(['acteNaissance', 'personnes'])->first();
        $this->assertNotNull($acteNai);
        $this->assertNotNull($acteNai->acteNaissance, "L'acte de naissance doit avoir sa fiche acte_naissance liée.");
        $this->assertNotEmpty($acteNai->personnes, "L'acte de naissance doit avoir des personnes indexées.");

        // 2. Acte de mariage
        $acteMar = Acte::where('type', TypeActe::MARIAGE)->with(['acteMariage', 'personnes'])->first();
        $this->assertNotNull($acteMar);
        $this->assertNotNull($acteMar->acteMariage, "L'acte de mariage doit avoir sa fiche acte_mariage liée.");

        // 3. Acte de décès
        $acteDec = Acte::where('type', TypeActe::DECES)->with(['acteDeces', 'personnes'])->first();
        $this->assertNotNull($acteDec);
        $this->assertNotNull($acteDec->acteDeces, "L'acte de décès doit avoir sa fiche acte_deces liée.");
    }

    /**
     * Vérifie la gestion des fichiers numériques, la convention de nommage et le contrôle SHA-256.
     */
    public function test_les_fichiers_numeriques_et_controle_integrite(): void
    {
        $fichier = FichierNumerique::where('actif', true)->first();
        $this->assertNotNull($fichier, "Au moins un fichier numérique actif doit exister.");

        // Convention de nommage : TAT-EC-[NAI|MAR|DEC]-[ANNEE]-[REGnnn]-[ACTnnnn]-[Pnnn].[ext]
        $regex = '/^TAT-EC-(NAI|MAR|DEC)-\d{4}-REG\d{3}-ACT\d{4}-P\d{3}\.(tif|pdf|jpg|jpeg)$/i';
        $this->assertMatchesRegularExpression($regex, $fichier->nom_fichier);

        // Contrôle d'intégrité SHA-256 réel
        $this->assertTrue($fichier->verifierIntegrite(), "L'empreinte SHA-256 du fichier physique doit correspondre à la base.");

        // Vérification de l'historique de remplacement
        $fichierAvecRemplacement = FichierNumerique::whereNotNull('remplace_fichier_id')->first();
        $this->assertNotNull($fichierAvecRemplacement, "Un fichier avec historique de remplacement doit être modélisé.");
        $this->assertNotNull($fichierAvecRemplacement->fichierRemplace);
        $this->assertFalse($fichierAvecRemplacement->fichierRemplace->actif, "L'ancien fichier remplacé doit être inactif.");
        $this->assertNotEmpty($fichierAvecRemplacement->justification_remplacement);
    }

    /**
     * Vérifie que le journal d'activité est protégé en écriture seule au niveau Eloquent.
     */
    public function test_le_journal_d_activite_est_immuable(): void
    {
        $entreeJournal = JournalActivite::first();
        $this->assertNotNull($entreeJournal);

        $this->expectException(RuntimeException::class);
        $entreeJournal->update(['resultat' => 'MODIFICATION_INTERDITE']);
    }

    /**
     * Vérifie la matrice des droits et la méthode aDroit() sur le modèle Utilisateur.
     */
    public function test_la_methode_a_droit_respecte_la_matrice_de_droits(): void
    {
        $archiviste = Utilisateur::where('profil', Profil::ARCHIVISTE)->first();
        $this->assertNotNull($archiviste);
        $this->assertTrue($archiviste->aDroit('registres.creer'));
        $this->assertTrue($archiviste->aDroit('actes.creer'));
        $this->assertFalse($archiviste->aDroit('utilisateurs.gerer'));

        $admin = Utilisateur::where('profil', Profil::ADMIN_TECHNIQUE)->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->aDroit('utilisateurs.gerer'));
        $this->assertTrue($admin->aDroit('sauvegardes.gerer'));
        $this->assertFalse($admin->aDroit('registres.creer'));
    }
}
