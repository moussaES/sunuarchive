<?php

namespace App\Console\Commands;

use App\Modules\Fichiers\Services\FichierService;
use Illuminate\Console\Command;

/**
 * Commande Artisan de vérification d'intégrité cryptographique des fichiers numériques SIGAEC.
 * 
 * Parcourt l'ensemble des copies numériques actives, vérifie leur présence physique
 * dans le disque privé 'archives' et recalcule leur empreinte SHA-256.
 * Signale immédiatement les fichiers manquants ou altérés (Section 8 et 11.6).
 */
class VerifierFichiersCommand extends Command
{
    /**
     * Nom et signature de la commande.
     */
    protected $signature = 'sigaec:verifier-fichiers';

    /**
     * Description de la commande.
     */
    protected $description = 'Contrôle l\'intégrité cryptographique SHA-256 de tous les fichiers numériques archivés';

    public function __construct(
        protected FichierService $fichierService
    ) {
        parent::__construct();
    }

    /**
     * Exécution de la commande.
     */
    public function handle(): int
    {
        $this->info("=====================================================================");
        $this->info(" SIGAEC — CONTRÔLE D'INTÉGRITÉ CRYPTOGRAPHIQUE DES FICHIERS NUMÉRIQUES");
        $this->info(" Mairie de Tattaguine — Disque privé d'archives");
        $this->info("=====================================================================");

        $resultats = $this->fichierService->verifierTousLesFichiers();

        if ($resultats['total'] === 0) {
            $this->warn("Aucun fichier numérique actif répertorié dans le système.");
            return self::SUCCESS;
        }

        $lignesTableau = [];
        foreach ($resultats['details'] as $det) {
            $statutFormate = match ($det['statut']) {
                'VALIDE' => '<info>[✓ VALIDE]</info>',
                'ALTERE' => '<error>[✕ ALTÉRÉ]</error>',
                'MANQUANT' => '<comment>[! MANQUANT]</comment>',
                default => $det['statut'],
            };

            $lignesTableau[] = [
                $det['id_fichier'],
                $det['nom_fichier'],
                $det['acte'],
                $det['cote_registre'],
                strtoupper($det['format']),
                $statutFormate,
                $det['message'],
            ];
        }

        $this->table(
            ['ID', 'Nom du fichier archivé', 'Acte', 'Registre', 'Format', 'Statut', 'Diagnostic'],
            $lignesTableau
        );

        $this->line("");
        $this->line(sprintf(
            "Bilan : <info>%d total</info> | <info>%d valide(s)</info> | <error>%d altéré(s)</error> | <comment>%d manquant(s)</comment>",
            $resultats['total'],
            $resultats['valides'],
            $resultats['alteres'],
            $resultats['manquants']
        ));

        if ($resultats['alteres'] > 0 || $resultats['manquants'] > 0) {
            $this->error("\nALERTE SÉCURITÉ : Des anomalies d'intégrité ont été détectées sur les copies numériques !");
            return self::FAILURE;
        }

        $this->info("\n✓ Intégrité parfaite : Tous les fichiers numériques sont présents et conformes à leur empreinte SHA-256 scellée.");
        return self::SUCCESS;
    }
}
