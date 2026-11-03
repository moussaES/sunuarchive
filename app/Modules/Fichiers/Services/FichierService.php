<?php

namespace App\Modules\Fichiers\Services;

use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\FichierNumerique;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Service métier de gestion des fichiers numériques et reproductions d'actes.
 * 
 * Assure :
 * - La validation stricte de la convention de nommage officielle (Section 8)
 * - Le stockage sécurisé dans le disque privé 'archives' (hors Web public)
 * - Le calcul de l'empreinte cryptographique SHA-256 à l'import
 * - Le contrôle d'intégrité à l'écran et la détection d'altération / fichier manquant
 * - La traçabilité de tout remplacement avec justification obligatoire et conservation de l'historique
 * - La distribution sécurisée des flux (streaming) avec journalisation systématique.
 */
class FichierService
{
    /**
     * Expression régulière officielle de validation de la convention de nommage SIGAEC.
     * Format : TAT-EC-[NAI|MAR|DEC]-[ANNEE]-[REGnnn]-[ACTnnnn]-[Pnnn].[ext]
     */
    public const REGEX_NOM_FICHIER = '/^TAT-EC-(NAI|MAR|DEC)-[0-9]{4}-REG[0-9]{3}-ACT[0-9]{4}-P[0-9]{3}\.(tif|tiff|pdf|jpg|jpeg)$/i';

    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Valide la conformité d'un nom de fichier par rapport à la convention officielle (Section 8).
     */
    public function validerConformiteNom(string $nomFichier): bool
    {
        return (bool) preg_match(self::REGEX_NOM_FICHIER, $nomFichier);
    }

    /**
     * Génère un nom normalisé conforme pour la copie numérique d'un acte.
     * Le nom du fichier ne contient JAMAIS le nom d'une personne physique (Section 8).
     */
    public function genererNomNormalise(Acte $acte, int $numeroPage, string $extension): string
    {
        $typeAbr = match ($acte->type) {
            TypeActe::NAISSANCE => 'NAI',
            TypeActe::MARIAGE => 'MAR',
            TypeActe::DECES => 'DEC',
            default => 'ACT',
        };

        $annee = (int) $acte->annee;
        $regCode = sprintf('REG%03d', $acte->id_registre);

        // Extraction des chiffres du numéro d'acte ou repli sur l'ID de l'acte
        $chiffresNum = preg_replace('/[^0-9]/', '', (string) $acte->numero_acte);
        $numPourFormat = ($chiffresNum !== '') ? (int) $chiffresNum : (int) $acte->id_acte;
        $actRef = sprintf('ACT%04d', $numPourFormat % 10000);

        $pageRef = sprintf('P%03d', $numeroPage);
        $ext = strtolower($extension);

        $nomNormalise = "TAT-EC-{$typeAbr}-{$annee}-{$regCode}-{$actRef}-{$pageRef}.{$ext}";

        if (!$this->validerConformiteNom($nomNormalise)) {
            throw new InvalidArgumentException("Le nom généré '{$nomNormalise}' ne respecte pas la convention officielle.");
        }

        return $nomNormalise;
    }

    /**
     * Associe un nouveau fichier numérisé à un acte d'état civil.
     */
    public function associerFichier(Acte $acte, UploadedFile $uploadedFile, array $donnees, Utilisateur $auteur): FichierNumerique
    {
        return DB::transaction(function () use ($acte, $uploadedFile, $donnees, $auteur) {
            $numeroPage = (int) ($donnees['numero_page'] ?? 1);
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'pdf';
            $extension = strtolower($extension);

            $nomNormalise = $this->genererNomNormalise($acte, $numeroPage, $extension);

            $typeAbr = match ($acte->type) {
                TypeActe::NAISSANCE => 'NAI',
                TypeActe::MARIAGE => 'MAR',
                TypeActe::DECES => 'DEC',
                default => 'ACT',
            };
            $regCode = sprintf('REG%03d', $acte->id_registre);
            $dossierRelatif = "{$typeAbr}/{$acte->annee}/{$regCode}";
            $cheminRelatif = "{$dossierRelatif}/{$nomNormalise}";

            $disque = Storage::disk('archives');

            // Si un fichier actif existe déjà pour cet acte et cette page, on le désactive
            $fichierExistant = FichierNumerique::where('id_acte', $acte->id_acte)
                ->where('numero_page', $numeroPage)
                ->where('actif', true)
                ->first();

            if ($fichierExistant) {
                $fichierExistant->update(['actif' => false]);
            }

            // Écriture sécurisée sur le disque privé archives
            $disque->putFileAs($dossierRelatif, $uploadedFile, $nomNormalise);

            $cheminPhysique = $disque->path($cheminRelatif);
            $taille = filesize($cheminPhysique);
            $sha256 = hash_file('sha256', $cheminPhysique);

            $fichier = FichierNumerique::create([
                'id_acte' => $acte->id_acte,
                'nom_fichier' => $nomNormalise,
                'nom_origine' => $uploadedFile->getClientOriginalName(),
                'format' => $extension,
                'taille_octets' => $taille,
                'empreinte_sha256' => $sha256,
                'chemin_stockage' => $cheminRelatif,
                'date_numerisation' => $donnees['date_numerisation'] ?? now()->toDateString(),
                'numero_page' => $numeroPage,
                'actif' => true,
            ]);

            // Inscription au journal d'audit
            $this->journalService->operationDocumentaire(
                action: 'ASSOCIATION_FICHIER',
                typeReference: 'FICHIER',
                idReference: $fichier->id_fichier,
                user: $auteur
            );

            return $fichier;
        });
    }

    /**
     * Remplace un fichier numérique existant avec justification obligatoire (Section 8).
     * L'ancien fichier est conservé avec actif = false et son chemin physique préservé.
     */
    public function remplacerFichier(
        FichierNumerique $ancienFichier,
        UploadedFile $uploadedFile,
        string $justification,
        array $donnees,
        Utilisateur $auteur
    ): FichierNumerique {
        return DB::transaction(function () use ($ancienFichier, $uploadedFile, $justification, $donnees, $auteur) {
            $disque = Storage::disk('archives');
            $acte = $ancienFichier->acte;

            // 1. Archivage physique de l'ancien fichier : on le renomme avec un suffixe horodaté
            // afin qu'il reste disponible et vérifiable dans l'historique sans conflit de nom ni d'unicité
            $infoNom = pathinfo($ancienFichier->nom_fichier);
            $ancienNomArchive = $infoNom['filename'] . '_v' . date('Ymd_His') . '.' . $infoNom['extension'];

            $infoChemin = pathinfo($ancienFichier->chemin_stockage);
            $nouveauCheminAncien = $infoChemin['dirname'] . '/' . $ancienNomArchive;

            if ($disque->exists($ancienFichier->chemin_stockage)) {
                $disque->move($ancienFichier->chemin_stockage, $nouveauCheminAncien);
            }

            // Met à jour l'ancien fichier en base (inactif, nom archivé, nouveau chemin)
            $ancienFichier->update([
                'actif' => false,
                'nom_fichier' => $ancienNomArchive,
                'chemin_stockage' => $nouveauCheminAncien,
            ]);

            // 2. Préparation et stockage du nouveau fichier sous le nom officiel
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'pdf');
            $nomNormalise = $this->genererNomNormalise($acte, $ancienFichier->numero_page, $extension);

            $typeAbr = match ($acte->type) {
                TypeActe::NAISSANCE => 'NAI',
                TypeActe::MARIAGE => 'MAR',
                TypeActe::DECES => 'DEC',
                default => 'ACT',
            };
            $regCode = sprintf('REG%03d', $acte->id_registre);
            $dossierRelatif = "{$typeAbr}/{$acte->annee}/{$regCode}";
            $cheminRelatif = "{$dossierRelatif}/{$nomNormalise}";

            $disque->putFileAs($dossierRelatif, $uploadedFile, $nomNormalise);

            $cheminPhysique = $disque->path($cheminRelatif);
            $taille = filesize($cheminPhysique);
            $sha256 = hash_file('sha256', $cheminPhysique);

            $nouveauFichier = FichierNumerique::create([
                'id_acte' => $acte->id_acte,
                'nom_fichier' => $nomNormalise,
                'nom_origine' => $uploadedFile->getClientOriginalName(),
                'format' => $extension,
                'taille_octets' => $taille,
                'empreinte_sha256' => $sha256,
                'chemin_stockage' => $cheminRelatif,
                'date_numerisation' => $donnees['date_numerisation'] ?? now()->toDateString(),
                'numero_page' => $ancienFichier->numero_page,
                'actif' => true,
                'remplace_fichier_id' => $ancienFichier->id_fichier,
                'justification_remplacement' => trim($justification),
            ]);

            // Inscription obligatoire au journal d'activité
            $this->journalService->operationDocumentaire(
                action: 'REMPLACEMENT_FICHIER',
                typeReference: 'FICHIER',
                idReference: $nouveauFichier->id_fichier,
                user: $auteur
            );

            return $nouveauFichier;
        });
    }

    /**
     * Contrôle l'intégrité cryptographique SHA-256 d'un fichier spécifique.
     */
    public function verifierIntegrite(FichierNumerique $fichier, ?Utilisateur $demandeur = null): array
    {
        $disque = Storage::disk('archives');

        if (!$disque->exists($fichier->chemin_stockage)) {
            $resultat = [
                'valide' => false,
                'statut' => 'MANQUANT',
                'message' => 'Le fichier physique est introuvable sur le disque privé d\'archives.',
                'sha256_attendu' => $fichier->empreinte_sha256,
                'sha256_calcule' => null,
            ];
        } else {
            $cheminPhysique = $disque->path($fichier->chemin_stockage);
            $sha256Calcule = hash_file('sha256', $cheminPhysique);

            if (hash_equals(strtolower($fichier->empreinte_sha256), strtolower($sha256Calcule))) {
                $resultat = [
                    'valide' => true,
                    'statut' => 'VALIDE',
                    'message' => 'Empreinte SHA-256 authentique et intègre.',
                    'sha256_attendu' => $fichier->empreinte_sha256,
                    'sha256_calcule' => $sha256Calcule,
                ];
            } else {
                $resultat = [
                    'valide' => false,
                    'statut' => 'ALTERE',
                    'message' => 'Alerte sécurité : L\'empreinte calculée diffère de l\'empreinte scellée à l\'import.',
                    'sha256_attendu' => $fichier->empreinte_sha256,
                    'sha256_calcule' => $sha256Calcule,
                ];
            }
        }

        if ($demandeur) {
            $this->journalService->operationDocumentaire(
                action: 'VERIFICATION_INTEGRITE',
                typeReference: 'FICHIER',
                idReference: $fichier->id_fichier,
                user: $demandeur
            );
        }

        return $resultat;
    }

    /**
     * Contrôle l'intégrité de l'ensemble des fichiers numériques du système.
     * Utilisé par la commande artisan sigaec:verifier-fichiers et le tableau de bord.
     */
    public function verifierTousLesFichiers(): array
    {
        $fichiers = FichierNumerique::with('acte.registre')
            ->where('actif', true)
            ->get();

        $valides = 0;
        $alteres = 0;
        $manquants = 0;
        $details = [];

        foreach ($fichiers as $fichier) {
            $ctrl = $this->verifierIntegrite($fichier);
            if ($ctrl['statut'] === 'VALIDE') {
                $valides++;
            } elseif ($ctrl['statut'] === 'ALTERE') {
                $alteres++;
            } else {
                $manquants++;
            }

            $details[] = [
                'id_fichier' => $fichier->id_fichier,
                'nom_fichier' => $fichier->nom_fichier,
                'acte' => $fichier->acte?->identifiant_acte ?? 'N/A',
                'cote_registre' => $fichier->acte?->registre?->cote ?? 'N/A',
                'format' => $fichier->format,
                'statut' => $ctrl['statut'],
                'message' => $ctrl['message'],
            ];
        }

        return [
            'total' => $fichiers->count(),
            'valides' => $valides,
            'alteres' => $alteres,
            'manquants' => $manquants,
            'details' => $details,
        ];
    }

    /**
     * Distribue le flux sécurisé du fichier numérique avec contrôle d'habilitation et traçabilité.
     */
    public function servirFluxFichier(FichierNumerique $fichier, Utilisateur $demandeur): BinaryFileResponse
    {
        if (!$demandeur->aDroit('fichiers.consulter')) {
            abort(403, "Vous n'avez pas l'autorisation de consulter les reproductions numériques d'actes.");
        }

        $disque = Storage::disk('archives');
        if (!$disque->exists($fichier->chemin_stockage)) {
            abort(404, "Le document numérique d'archive est introuvable sur le support sécurisé.");
        }

        // Journalisation obligatoire de la consultation de copie numérique (Section 7 & 11.8)
        $this->journalService->consultationFichier($fichier->id_fichier, $demandeur);

        $cheminPhysique = $disque->path($fichier->chemin_stockage);
        $mime = match (strtolower($fichier->format)) {
            'pdf' => 'application/pdf',
            'tif', 'tiff' => 'image/tiff',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file($cheminPhysique, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $fichier->nom_fichier . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, must-revalidate',
        ]);
    }
}
