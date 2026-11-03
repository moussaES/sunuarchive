<?php

namespace App\Console\Commands;

use App\Models\Sauvegarde;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Commande Artisan de sauvegarde automatisée des données et copies numériques SIGAEC.
 * 
 * Exporte la base de données et les fichiers d'archives vers un support externe ou isolé,
 * génère un manifeste cryptographique SHA-256 scellé et enregistre l'opération en base (Section 10).
 */
class SauvegardeCommand extends Command
{
    protected $signature = 'sigaec:sauvegarde {--support=DISQUE_EXTERNE : Type ou étiquette du support de sauvegarde}';
    protected $description = 'Exécute la sauvegarde complète de la base de données et des archives numériques avec manifeste SHA-256';

    public function __construct(
        protected JournalService $journalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("=====================================================================");
        $this->info(" SIGAEC — SAUVEGARDE AUTOMATIQUE DU SYSTÈME MUNICIPAL D'ARCHIVES");
        $this->info(" Mairie de Tattaguine (Section 10 du cahier des charges)");
        $this->info("=====================================================================");

        $cheminEnv = env('SAUVEGARDE_CHEMIN', storage_path('sauvegardes'));
        $dossierRacineSauvegarde = (str_starts_with($cheminEnv, '/') || (strlen($cheminEnv) > 2 && $cheminEnv[1] === ':'))
            ? $cheminEnv
            : base_path($cheminEnv);
        if (!File::isDirectory($dossierRacineSauvegarde)) {
            File::makeDirectory($dossierRacineSauvegarde, 0755, true);
        }

        $horodatage = date('Ymd_His') . '_' . substr(str_replace('.', '', (string)microtime(true)), -4);
        $dossierSauvegarde = $dossierRacineSauvegarde . DIRECTORY_SEPARATOR . "SIGAEC_BACKUP_{$horodatage}";
        if (!File::isDirectory($dossierSauvegarde)) {
            File::makeDirectory($dossierSauvegarde, 0755, true);
        }

        $this->line("1. Exportation de la base de données...");
        $cheminSql = $dossierSauvegarde . DIRECTORY_SEPARATOR . 'database_dump.sql';
        $this->exporterBaseDeDonnees($cheminSql);
        $this->info("   ✓ Base de données exportée : database_dump.sql");

        $this->line("2. Copie miroir des archives numériques scellées...");
        $dossierSourceArchives = Storage::disk('archives')->path('');
        $dossierCibleArchives = $dossierSauvegarde . DIRECTORY_SEPARATOR . 'archives';
        if (File::isDirectory($dossierSourceArchives)) {
            File::copyDirectory($dossierSourceArchives, $dossierCibleArchives);
        } else {
            File::makeDirectory($dossierCibleArchives, 0755, true);
        }
        $this->info("   ✓ Copies numériques répliquées vers le support de sauvegarde");

        $this->line("3. Génération du manifeste cryptographique SHA-256...");
        $manifeste = [
            'logiciel' => 'SIGAEC Mairie de Tattaguine',
            'version' => '1.0',
            'date_creation' => now()->toIso8601String(),
            'support' => $this->option('support'),
            'empreintes' => [],
        ];

        // Calcul des empreintes pour chaque fichier de la sauvegarde
        $fichiersSauvegardes = File::allFiles($dossierSauvegarde);
        $tailleTotale = 0;

        $dossierSauvegardeAbsolu = realpath($dossierSauvegarde) ?: $dossierSauvegarde;
        $dossierSauvegardeNormalise = rtrim(str_replace(['\\', '/'], '/', $dossierSauvegardeAbsolu), '/') . '/';

        foreach ($fichiersSauvegardes as $fichier) {
            $tailleTotale += $fichier->getSize();
            $fichierCheminNormalise = str_replace(['\\', '/'], '/', $fichier->getRealPath());
            $cheminRelatifNormalise = str_replace($dossierSauvegardeNormalise, '', $fichierCheminNormalise);
            $manifeste['empreintes'][$cheminRelatifNormalise] = hash_file('sha256', $fichier->getRealPath());
        }

        $cheminManifeste = $dossierSauvegarde . DIRECTORY_SEPARATOR . 'manifeste_sha256.json';
        File::put($cheminManifeste, json_encode($manifeste, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info("   ✓ Manifeste scellé créé : manifeste_sha256.json (" . count($manifeste['empreintes']) . " fichiers)");

        // 4. Enregistrement dans la table sauvegarde
        $adminTech = Utilisateur::where('profil', 'ADMIN_TECHNIQUE')->first();
        $idAuteur = $adminTech ? $adminTech->id_utilisateur : 1;

        $sauvegardeModel = Sauvegarde::create([
            'id_utilisateur' => $idAuteur,
            'date_sauvegarde' => now(),
            'support' => $this->option('support'),
            'taille' => $tailleTotale,
            'resultat' => 'REUSSIE',
            'verifiee' => false,
        ]);

        // 5. Inscription au journal d'audit
        $this->journalService->operationDocumentaire(
            action: 'SAUVEGARDE_SYSTEME',
            typeReference: 'SAUVEGARDE',
            idReference: $sauvegardeModel->id_sauvegarde,
            user: $adminTech ?? Utilisateur::first()
        );

        $this->line("");
        $this->info(sprintf(
            "✓ Sauvegarde réussie avec succès (Taille : %s Mo) dans : %s",
            round($tailleTotale / (1024 * 1024), 2),
            $dossierSauvegarde
        ));

        // 6. Rotation des sauvegardes (conserver les 7 plus récentes)
        $this->appliquerRotation($dossierRacineSauvegarde, 7);

        return self::SUCCESS;
    }

    /**
     * Exporte la structure et les données de toutes les tables MySQL sous forme de script SQL complet.
     */
    protected function exporterBaseDeDonnees(string $cheminCible): void
    {
        $handle = fopen($cheminCible, 'w');
        fwrite($handle, "-- SIGAEC DUMP DE SAUVEGARDE - MAIRIE DE TATTAGUINE\n");
        fwrite($handle, "-- Date : " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $propertyKey = "Tables_in_{$dbName}";

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->$propertyKey ?? array_values((array)$tableObj)[0];

            // Ne pas dumper les migrations ou sessions système
            if (in_array($tableName, ['migrations', 'sessions'])) {
                continue;
            }

            // Structure de la table
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sqlCreate = $createTable[0]->{'Create Table'} ?? '';
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle, $sqlCreate . ";\n\n");

            // Données
            $rows = DB::table($tableName)->get();
            if ($rows->isNotEmpty()) {
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $cols = array_map(fn($col) => "`{$col}`", array_keys($rowArray));
                    $vals = array_map(function ($val) {
                        if ($val === null) return 'NULL';
                        return DB::getPdo()->quote((string)$val);
                    }, array_values($rowArray));

                    $insertSql = "INSERT INTO `{$tableName}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                    fwrite($handle, $insertSql);
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Conserve les $maxVersions sauvegardes les plus récentes et purge les plus anciennes.
     */
    protected function appliquerRotation(string $dossierRacine, int $maxVersions): void
    {
        $dossiers = File::directories($dossierRacine);
        if (count($dossiers) > $maxVersions) {
            rsort($dossiers); // Tri décroissant (les plus récents en premier)
            $aSupprimer = array_slice($dossiers, $maxVersions);
            foreach ($aSupprimer as $vieuxDossier) {
                File::deleteDirectory($vieuxDossier);
            }
        }
    }
}
