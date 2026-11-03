<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 0 (socle technique et architecture modulaire).
 */
class Phase0StructureTest extends TestCase
{
    /**
     * Vérifie que la base de données configurée (MySQL sigaec) est opérationnelle.
     */
    public function test_la_base_de_donnees_est_accessible(): void
    {
        $result = DB::select('SELECT 1 as test');
        $this->assertNotEmpty($result);
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Vérifie que les 9 modules métiers requis sont créés avec leur fichier de routes.
     */
    public function test_les_modules_metiers_sont_presents_avec_leurs_routes(): void
    {
        $modulesAttendus = [
            'Auth',
            'Registres',
            'Actes',
            'Fichiers',
            'Recherche',
            'Tableau',
            'Rapports',
            'Demandes',
            'Admin',
        ];

        foreach ($modulesAttendus as $module) {
            $dir = app_path("Modules/{$module}");
            $routes = app_path("Modules/{$module}/routes.php");

            $this->assertTrue(File::isDirectory($dir), "Le dossier du module {$module} doit exister.");
            $this->assertTrue(File::isFile($routes), "Le fichier routes.php du module {$module} doit exister.");
        }
    }

    /**
     * Vérifie la présence du disque privé 'archives' dans la configuration.
     */
    public function test_le_disque_prive_archives_est_configure(): void
    {
        $disque = Config::get('filesystems.disks.archives');
        $this->assertNotNull($disque, "Le disque 'archives' doit être configuré.");
        $this->assertEquals('local', $disque['driver']);
        $this->assertEquals('private', $disque['visibility']);
    }

    /**
     * Vérifie la configuration centralisée des droits et des 5 profils utilisateurs.
     */
    public function test_la_configuration_des_profils_et_droits_est_complete(): void
    {
        $profils = Config::get('droits.profils');
        $this->assertIsArray($profils);

        $profilsAttendus = [
            'ADMIN_TECHNIQUE',
            'ARCHIVISTE',
            'AGENT_INDEXATION',
            'AGENT_CONSULTATION',
            'RESPONSABLE_HABILITE',
        ];

        foreach ($profilsAttendus as $codeProfil) {
            $this->assertArrayHasKey($codeProfil, $profils, "Le profil {$codeProfil} doit être défini dans config/droits.php.");
            $this->assertArrayHasKey('droits', $profils[$codeProfil]);
            $this->assertArrayHasKey('libelle', $profils[$codeProfil]);
        }
    }

    /**
     * Vérifie l'existence du registre des décisions d'architecture.
     */
    public function test_le_document_de_decisions_existe(): void
    {
        $this->assertFileExists(base_path('docs/DECISIONS.md'));
    }

    /**
     * Vérifie que la base de données MySQL contient bien les tables SIGAEC.
     */
    public function test_les_tables_sigaec_existent_en_base(): void
    {
        $tablesAttendues = [
            'registre',
            'acte',
            'utilisateur',
            'personne_indexee',
            'fichier_numerique',
            'journal_activite',
            'demande_communication',
            'sauvegarde',
        ];

        foreach ($tablesAttendues as $table) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Schema::hasTable($table),
                "La table {$table} doit exister dans la base de données MySQL sigaec."
            );
        }
    }
}
