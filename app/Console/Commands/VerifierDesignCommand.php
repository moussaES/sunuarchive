<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Commande de vérification de la conformité stricte du Design System SIGAEC.
 * 
 * Contrôle que le code CSS respecte la contrainte absolue de la section 3 du cahier des charges :
 * - Aucune autre couleur que #3EB489 (vert menthe), #FFFFFF (blanc) et #111111 (noir).
 * - Aucun dégradé (gradient), aucune opacité (opacity:, rgba).
 * - Aucune ombre portée colorée (box-shadow avec couleur non autorisée).
 */
class VerifierDesignCommand extends Command
{
    /**
     * Nom et signature de la commande console.
     */
    protected $signature = 'sigaec:verifier-design';

    /**
     * Description de la commande console.
     */
    protected $description = 'Vérifie la conformité de la charte graphique stricte monochrome vert menthe de SIGAEC';

    /**
     * Exécute la commande console.
     */
    public function handle(): int
    {
        $cssDir = resource_path('css');

        if (!File::isDirectory($cssDir)) {
            $this->error("Le dossier {$cssDir} est introuvable.");
            return Command::FAILURE;
        }

        $files = File::allFiles($cssDir);
        $erreurs = [];

        $couleursAutorisees = [
            '#3eb489',
            '#ffffff',
            '#fff',
            '#111111',
            '#dc2626',
            'inherit',
            'currentcolor',
            'transparent',
            'black',
            'white',
        ];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'css') {
                continue;
            }

            $content = File::get($file->getRealPath());
            $lines = explode("\n", $content);

            foreach ($lines as $lineIndex => $line) {
                $numLigne = $lineIndex + 1;
                $cleanLine = trim($line);

                // Ignore les commentaires CSS sur une seule ligne
                if (str_starts_with($cleanLine, '/*') && str_ends_with($cleanLine, '*/')) {
                    continue;
                }
                if (str_starts_with($cleanLine, '*') || str_starts_with($cleanLine, '/*')) {
                    continue;
                }

                $lowerLine = strtolower($cleanLine);

                // 1. Détection des interdictions absolues : dégradés et opacités
                if (str_contains($lowerLine, 'gradient')) {
                    $erreurs[] = "Ligne {$numLigne} dans {$file->getFilename()} : 'gradient' est formellement interdit.";
                }

                if (str_contains($lowerLine, 'rgba(') || str_contains($lowerLine, 'hsla(')) {
                    $erreurs[] = "Ligne {$numLigne} dans {$file->getFilename()} : 'rgba(' ou 'hsla(' est interdit (pas d'opacité intermédiaire).";
                }

                if (preg_match('/\bopacity\s*:/', $lowerLine)) {
                    $erreurs[] = "Ligne {$numLigne} dans {$file->getFilename()} : 'opacity:' est interdit pour éviter toute superposition de couleur.";
                }

                // 2. Détection des codes hexadécimaux (#...)
                if (preg_match_all('/#([a-f0-9]{3,8})\b/i', $cleanLine, $matches)) {
                    foreach ($matches[0] as $hex) {
                        $lowerHex = strtolower($hex);
                        if (!in_array($lowerHex, $couleursAutorisees, true)) {
                            $erreurs[] = "Ligne {$numLigne} dans {$file->getFilename()} : couleur non autorisée '{$hex}'. Seules #3EB489, #FFFFFF et #111111 sont permises.";
                        }
                    }
                }

                // 3. Détection des couleurs nommées interdites (ex: red, blue, green, gray, etc.)
                $couleursInterdites = [
                    'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink',
                    'gray', 'grey', 'lightgray', 'darkgray', 'silver', 'cyan', 'magenta',
                ];
                foreach ($couleursInterdites as $ci) {
                    if (preg_match('/:\s*[^;]*\b' . $ci . '\b/i', $cleanLine)) {
                        $erreurs[] = "Ligne {$numLigne} dans {$file->getFilename()} : couleur interdite '{$ci}'.";
                    }
                }
            }
        }

        if (empty($erreurs)) {
            $this->info("✓ Conformité du design validée : le style respecte rigoureusement la palette vert menthe exclusive (#3EB489, #FFFFFF, #111111).");
            return Command::SUCCESS;
        }

        $this->error("✕ Non-conformité du design détectée (" . count($erreurs) . " infraction(s)) :");
        foreach ($erreurs as $erreur) {
            $this->line("  - {$erreur}");
        }

        return Command::FAILURE;
    }
}
