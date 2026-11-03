<?php

namespace App\Modules\Registres\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Registre;
use App\Modules\Registres\Requests\EnregistrerRegistreRequest;
use App\Modules\Registres\Services\RegistreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Contrôleur du module Registres.
 * 
 * Assure la gestion de l'inventaire des registres physiques, l'affichage
 * du volet de détail, le traitement des formulaires pop-ups modales et l'export CSV.
 */
class RegistreController extends Controller
{
    public function __construct(
        protected RegistreService $registreService
    ) {}

    /**
     * Affiche l'inventaire des registres avec filtres et volet de détail (Section 9.3).
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user && !$user->aDroit('registres.consulter')) {
            app(\App\Services\Journal\JournalService::class)->accesRefuse("DROIT:registres.consulter SUR {$request->path()}", $user);
            abort(403, "Vous n'avez pas l'autorisation de consulter l'inventaire des registres.");
        }

        $filtres = $request->only(['type', 'etat_materiel', 'presence', 'inventaire_complet', 'recherche']);
        $registres = $this->registreService->paginer($filtres, 25);

        $registreSelectionne = null;
        if ($request->has('detail')) {
            $registreSelectionne = Registre::withCount('actes')->find($request->input('detail'));
        } elseif ($registres->isNotEmpty()) {
            $registreSelectionne = $registres->first();
        }

        return view('modules.registres.index', compact('registres', 'filtres', 'registreSelectionne'));
    }

    /**
     * Enregistre un nouveau registre physique via la fenêtre modale.
     */
    public function store(EnregistrerRegistreRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('registres.creer')) {
            abort(403, "Vous n'avez pas l'autorisation de créer un registre.");
        }

        try {
            $registre = $this->registreService->creer($request->validated(), $user);

            return redirect()->route('registres.index', ['detail' => $registre->id_registre])
                ->with('succes', "Registre {$registre->cote} enregistré avec succès dans la base de données.");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', "Le registre n'a pas pu être enregistré : " . $e->getMessage());
        }
    }

    /**
     * Met à jour un registre existant via la fenêtre modale.
     */
    public function update(EnregistrerRegistreRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('registres.modifier')) {
            abort(403, "Vous n'avez pas l'autorisation de modifier un registre.");
        }

        try {
            $registre = $this->registreService->trouverParId($id);
            $this->registreService->mettreAJour($registre, $request->validated(), $user);

            return redirect()->route('registres.index', ['detail' => $registre->id_registre])
                ->with('succes', "Fiche du registre {$registre->cote} mise à jour avec succès dans la base de données.");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', "La modification du registre n'a pas pu être enregistrée : " . $e->getMessage());
        }
    }

    /**
     * Exporte la liste filtrée des registres au format CSV officiel.
     */
    public function exporter(Request $request): Response
    {
        $user = $request->user();
        if (!$user->aDroit('rapports.exporter')) {
            abort(403, "Vous n'avez pas l'autorisation d'exporter les inventaires.");
        }

        $csv = $this->registreService->exporterCsv($request->all(), $user);
        $nomFichier = 'inventaire_registres_tattaguine_' . now()->format('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nomFichier}\"",
        ]);
    }
}
