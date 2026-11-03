<?php

namespace App\Modules\Rapports\Services;

use App\Models\Registre;
use App\Models\Utilisateur;
use App\Modules\Tableau\Services\TableauService;
use App\Services\Journal\JournalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service métier de génération des rapports et exports SIGAEC (Section 9.2 & 11.10).
 * 
 * Assure les exports CSV et PDF conformes à la charte et à la politique de confidentialité.
 */
class RapportsService
{
    public function __construct(
        protected TableauService $tableauService,
        protected JournalService $journalService
    ) {}

    /**
     * Génère le rapport PDF officiel de synthèse des archives municipales de Tattaguine.
     */
    public function genererPdfSynthese(Utilisateur $demandeur): Response
    {
        $donnees = $this->tableauService->obtenirDonneesTableau();

        $pdf = Pdf::loadView('modules.rapports.pdf_synthese', [
            'donnees' => $donnees,
            'demandeur' => $demandeur,
        ]);

        $pdf->setPaper('a4', 'portrait');

        // Journalisation de l'export
        $this->journalService->operationDocumentaire(
            action: 'EXPORT_PDF_RAPPORT',
            typeReference: 'RAPPORT',
            idReference: null,
            user: $demandeur
        );

        $nomFichier = 'SIGAEC_Rapport_Situation_Tattaguine_' . date('Ymd_His') . '.pdf';

        return $pdf->download($nomFichier);
    }

    /**
     * Exporte la synthèse d'inventaire au format CSV avec encodage UTF-8 et BOM (Section 11.10).
     */
    public function exporterCsvSynthese(Utilisateur $demandeur): StreamedResponse
    {
        $registres = Registre::withCount('actes')->orderBy('cote', 'asc')->get();

        $nomFichier = 'sigaec_synthese_inventaire_' . date('Ymd_His') . '.csv';

        // Journalisation obligatoire de l'export
        $this->journalService->operationDocumentaire(
            action: 'EXPORT_CSV_RAPPORT',
            typeReference: 'RAPPORT',
            idReference: null,
            user: $demandeur
        );

        return response()->streamDownload(function () use ($registres) {
            $handle = fopen('php://output', 'w');

            // Insertion du BOM UTF-8 pour Excel
            fputs($handle, "\xEF\xBB\xBF");

            // En-têtes CSV
            fputcsv($handle, [
                'Cote archivistique',
                'Type de registre',
                'Année début',
                'Année fin',
                'Numéro de volume',
                'Nombre de pages',
                'Actes répertoriés',
                'Localisation physique',
                'État matériel',
                'Présence physique',
                'Statut inventaire',
            ], ';');

            foreach ($registres as $reg) {
                fputcsv($handle, [
                    $reg->cote,
                    $reg->type->libelle(),
                    $reg->annee_debut,
                    $reg->annee_fin,
                    $reg->numero_volume,
                    $reg->nombre_pages,
                    $reg->actes_count,
                    $reg->localisation,
                    $reg->etat_materiel->libelle(),
                    $reg->presence->libelle(),
                    $reg->inventaire_complet ? 'Complet' : 'À traiter',
                ], ';');
            }

            fclose($handle);
        }, $nomFichier, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
