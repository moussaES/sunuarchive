<?php

namespace App\Modules\Tableau\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tableau\Services\TableauService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur du Tableau de bord général SIGAEC.
 * 
 * Présente les 4 indicateurs consolidés, les jauges d'avancement de numérisation,
 * les registres physiques à surveiller et les alertes d'intégrité / sauvegarde.
 */
class TableauController extends Controller
{
    public function __construct(
        protected TableauService $tableauService
    ) {}

    /**
     * Affiche l'écran d'accueil et de pilotage général (Section 9.2).
     */
    public function index(Request $request): View
    {
        $donnees = $this->tableauService->obtenirDonneesTableau();

        return view('modules.tableau.index', $donnees);
    }
}
