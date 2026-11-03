<?php

namespace App\Modules\Recherche\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Registre;
use App\Modules\Recherche\Services\RechercheService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur du module Recherche d'actes d'état civil.
 * 
 * Permet l'exploration simple et avancée des actes répertoriés avec filtrage
 * selon les habilitations nominatives et affichage du volet d'orientation si aucun résultat.
 */
class RechercheController extends Controller
{
    public function __construct(
        protected RechercheService $rechercheService
    ) {}

    /**
     * Affiche l'écran de recherche et les résultats filtrés.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user && !$user->aDroit('actes.consulter')) {
            abort(403, "Vous n'avez pas l'autorisation d'accéder au module de recherche d'actes.");
        }

        $criteres = $request->only([
            'q',
            'type',
            'annee',
            'annee_debut',
            'annee_fin',
            'numero_acte',
            'id_registre',
            'nom',
            'prenoms',
            'avec_copie',
        ]);

        // Compatibilité descendante si le paramètre 'registre_id' est utilisé dans l'URL
        if (empty($criteres['id_registre']) && $request->filled('registre_id')) {
            $criteres['id_registre'] = $request->input('registre_id');
        }

        $resultats = $this->rechercheService->rechercher($criteres, $user);
        $registres = Registre::orderBy('cote', 'asc')->get();

        return view('modules.recherche.index', [
            'resultats' => $resultats,
            'registres' => $registres,
            'criteres' => $criteres,
            'rechercheService' => $this->rechercheService,
        ]);
    }
}
