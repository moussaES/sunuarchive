<?php

namespace Database\Seeders;

use App\Models\Acte;
use App\Models\FichierNumerique;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Seeder des fichiers numériques pour SIGAEC.
 * 
 * Génère des reproductions numériques factices avec convention de nommage officielle,
 * stockage privé dans storage/app/archives et empreintes cryptographiques SHA-256 réelles.
 */
class FichierNumeriqueSeeder extends Seeder
{
    public function run(): void
    {
        $disque = Storage::disk('archives');

        // Récupère les premiers actes de chaque type
        $actes = Acte::with('registre')->take(6)->get();

        foreach ($actes as $index => $acte) {
            $regCode = 'REG' . str_pad((string)$acte->id_registre, 3, '0', STR_PAD_LEFT);
            $typeAbr = match($acte->type->value) {
                'NAISSANCE' => 'NAI',
                'MARIAGE' => 'MAR',
                'DECES' => 'DEC',
                default => 'ACT',
            };
            $actRef = 'ACT' . str_pad($acte->numero_acte, 4, '0', STR_PAD_LEFT);
            $format = ($index % 2 === 0) ? 'tif' : 'pdf';
            $nomFichier = "TAT-EC-{$typeAbr}-{$acte->annee}-{$regCode}-{$actRef}-P001.{$format}";
            $dossierRelatif = "{$typeAbr}/{$acte->annee}/{$regCode}";
            $cheminRelatif = "{$dossierRelatif}/{$nomFichier}";

            // Contenu fictif du fichier de consultation
            $contenuFictif = "REPRODUCTION NUMERIQUE DE CONSULTATION SIGAEC - MAIRIE DE TATTAGUINE\n"
                . "Acte : {$acte->identifiant_acte} - Type : {$acte->type->value} - Registre : {$acte->registre->cote}\n"
                . "Mention : Copie sans valeur d'acte officiel. Le registre papier reste la référence.\n"
                . "Timestamp : " . now()->toIso8601String() . "\n";

            $disque->put($cheminRelatif, $contenuFictif);
            $cheminPhysique = $disque->path($cheminRelatif);
            $taille = filesize($cheminPhysique);
            $sha256 = hash_file('sha256', $cheminPhysique);

            $fichier = FichierNumerique::updateOrCreate(
                ['nom_fichier' => $nomFichier],
                [
                    'id_acte' => $acte->id_acte,
                    'nom_origine' => "scan_original_{$actRef}.{$format}",
                    'format' => $format,
                    'taille_octets' => $taille,
                    'empreinte_sha256' => $sha256,
                    'chemin_stockage' => $cheminRelatif,
                    'date_numerisation' => '2026-02-15',
                    'numero_page' => 1,
                    'actif' => true,
                ]
            );

            // Pour le premier acte, on simule un historique de remplacement
            if ($index === 0) {
                $nomAncienFichier = "TAT-EC-{$typeAbr}-{$acte->annee}-{$regCode}-{$actRef}-P001_v1.{$format}";
                $cheminAncien = "{$dossierRelatif}/{$nomAncienFichier}";
                $contenuAncien = "VERSION INITIALE OBSOLETE (SCAN DEGRADE 150 DPI)\n";
                $disque->put($cheminAncien, $contenuAncien);
                $tailleAncien = filesize($disque->path($cheminAncien));
                $shaAncien = hash_file('sha256', $disque->path($cheminAncien));

                $ancienFichier = FichierNumerique::updateOrCreate(
                    ['nom_fichier' => $nomAncienFichier],
                    [
                        'id_acte' => $acte->id_acte,
                        'nom_origine' => "scan_brouillon.{$format}",
                        'format' => $format,
                        'taille_octets' => $tailleAncien,
                        'empreinte_sha256' => $shaAncien,
                        'chemin_stockage' => $cheminAncien,
                        'date_numerisation' => '2026-01-20',
                        'numero_page' => 1,
                        'actif' => false,
                    ]
                );

                // Lie le fichier actif au fichier remplacé
                $fichier->update([
                    'remplace_fichier_id' => $ancienFichier->id_fichier,
                    'justification_remplacement' => 'Numérisation initiale tronquée à 150 DPI. Nouveau scan pleine page haute résolution 400 DPI conforme.',
                ]);
            }
        }
    }
}
