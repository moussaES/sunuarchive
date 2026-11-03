<?php

namespace App\Modules\Rapports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rapports\Services\RapportsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contrôleur du module Rapports et Exports (Section 9.2 & 11.10).
 * 
 * Permet aux archivistes et responsables habilités de télécharger la synthèse en PDF et CSV.
 */
class RapportsController extends Controller
{
    public function __construct(
        protected RapportsService $rapportsService
    ) {}

    /**
     * Télécharge la synthèse de situation au format PDF officiel.
     */
    public function exporterPdf(Request $request): Response
    {
        $user = $request->user();
        if (!$user->aDroit('rapports.exporter')) {
            abort(403, "Vous n'avez pas l'autorisation d'exporter les rapports officiels.");
        }

        return $this->rapportsService->genererPdfSynthese($user);
    }

    /**
     * Télécharge le rapport d'inventaire au format CSV (avec UTF-8 BOM).
     */
    public function exporterCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        if (!$user->aDroit('rapports.exporter')) {
            abort(403, "Vous n'avez pas l'autorisation d'exporter les données d'inventaire.");
        }

        return $this->rapportsService->exporterCsvSynthese($user);
    }
}
