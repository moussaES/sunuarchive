<?php

namespace App\Console\Commands;

use App\Models\Sauvegarde;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Commande Artisan de test de restauration et vérification d'intégrité de sauvegarde (Section 10).
 * 
 * Restaure la dernière sauvegarde dans un environnement temporaire isolé,
 * compare les empreintes SHA-256 avec le manifeste scellé et consigne le résultat.
 */
class RestaurerTestCommand extends Command
{
    protected $signature = 'sigaec:restaurer-test';
    protected $description = 'Restaure la dernière sauvegarde dans un dossier temporaire et valide son intégrité';

    public function __construct(
        protected JournalService $journalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info("=====================================================================");
        $this->info(" SIGAEC — TEST PÉRIODIQUE DE RESTAURATION DES ARCHIVES MUNICIPALES");
        $this->info(" Mairie de Tattaguine (Section 10 du cahier des charges)");
        $this->info("=====================================================================");

        $cheminEnv = env('SAUVEGARDE_CHEMIN', storage_path('sauvegardes'));
        $dossierRacineSauvegarde = (str_starts_with($cheminEnv, '/') || (strlen($cheminEnv) > 2 && $cheminEnv[1] === ':'))
            ? $cheminEnv
            : base_path($cheminEnv);
        if (!File::isDirectory($dossierRacineSauvegarde)) {
            $this->error("Aucun répertoire de sauvegarde trouvé à l'emplacement configuré.");
            return self::FAILURE;
        }

        $dossiers = File::directories($dossierRacineSauvegarde);
        if (empty($dossiers)) {
            $this->error("Aucune archive de sauvegarde n'est disponible pour exécuter le test.");
            return self::FAILURE;
        }

        // Sélection du dossier le plus récent
        rsort($dossiers);
        $dernierDossier = $dossiers[0];
        $nomDossier = basename($dernierDossier);
        $this->line("Dernière sauvegarde détectée : <comment>{$nomDossier}</comment>");

        $cheminManifeste = $dernierDossier . DIRECTORY_SEPARATOR . 'manifeste_sha256.json';
        if (!File::exists($cheminManifeste)) {
            $this->error("ALERTE : Le manifeste cryptographique scellé 'manifeste_sha256.json' est absent de l'archive !");
            return self::FAILURE;
        }

        $manifesteData = json_decode(File::get($cheminManifeste), true);
        if (!$manifesteData || empty($manifesteData['empreintes'])) {
            $this->error("ALERTE : Le manifeste est corrompu ou vide.");
            return self::FAILURE;
        }

        // Dossier temporaire isolé de test
        $dossierTemporaire = storage_path('app/temp_restauration_test');
        if (File::isDirectory($dossierTemporaire)) {
            File::deleteDirectory($dossierTemporaire);
        }
        File::makeDirectory($dossierTemporaire, 0755, true);

        $this->line("1. Déploiement dans le bac à sable temporaire...");
        File::copyDirectory($dernierDossier, $dossierTemporaire);
        $this->info("   ✓ Données et copies déployées en environnement d'épreuve isolé");

        $this->line("2. Vérification cryptographique des empreintes SHA-256...");
        $totalControles = 0;
        $anomalies = 0;

        foreach ($manifesteData['empreintes'] as $cheminRelatif => $sha256Attendu) {
            $totalControles++;
            $cheminFichierTest = $dossierTemporaire . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cheminRelatif);

            if (!File::exists($cheminFichierTest)) {
                $this->error("   ✕ Fichier manquant : {$cheminRelatif}");
                $anomalies++;
                continue;
            }

            $sha256Calcule = hash_file('sha256', $cheminFichierTest);
            if (!hash_equals(strtolower($sha256Attendu), strtolower($sha256Calcule))) {
                $this->error("   ✕ Altération détectée sur : {$cheminRelatif}");
                $anomalies++;
            }
        }

        // Nettoyage impératif du bac à sable temporaire
        File::deleteDirectory($dossierTemporaire);

        $adminTech = Utilisateur::where('profil', 'ADMIN_TECHNIQUE')->first();
        $derniereSauvegardeModel = Sauvegarde::orderByDesc('date_sauvegarde')->first();

        if ($anomalies > 0) {
            $this->error("\nÉCHEC DU TEST DE RESTAURATION : {$anomalies} anomalie(s) sur {$totalControles} fichiers.");

            $this->journalService->operationDocumentaire(
                action: 'TEST_RESTAURATION_ECHEC',
                typeReference: 'SAUVEGARDE',
                idReference: $derniereSauvegardeModel?->id_sauvegarde,
                user: $adminTech ?? Utilisateur::first()
            );

            return self::FAILURE;
        }

        // Succès : mise à jour du modèle Sauvegarde
        if ($derniereSauvegardeModel) {
            $derniereSauvegardeModel->update(['verifiee' => true]);
        }

        // Journalisation du test réussi
        $this->journalService->operationDocumentaire(
            action: 'TEST_RESTAURATION_REUSSI',
            typeReference: 'SAUVEGARDE',
            idReference: $derniereSauvegardeModel?->id_sauvegarde,
            user: $adminTech ?? Utilisateur::first()
        );

        $this->line("");
        $this->info(sprintf(
            "✓ Test de restauration réussi à 100%% : %d fichiers vérifiés intègres sans aucune altération.",
            $totalControles
        ));
        $this->info("✓ La mention 'Vérifiée' a été validée pour la sauvegarde au tableau de bord.");

        return self::SUCCESS;
    }
}
