<?php

namespace App\Modules\Actes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Registre;
use App\Modules\Actes\Requests\EnregistrerActeRequest;
use App\Modules\Actes\Services\ActeService;
use App\Services\Journal\JournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Contrôleur du module Actes.
 * 
 * Assure la consultation détaillée de la fiche d'acte avec son bandeau légal,
 * le traitement des formulaires pop-ups de création / correction et la traçabilité.
 */
class ActeController extends Controller
{
    public function __construct(
        protected ActeService $acteService,
        protected JournalService $journalService
    ) {}

    /**
     * Affiche la fiche descriptive complète de l'acte (Section 9.5).
     * Journalise chaque ouverture de fiche.
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        if ($user && !$user->aDroit('actes.consulter')) {
            abort(403, "Vous n'avez pas l'autorisation de consulter les fiches d'actes.");
        }

        $acte = $this->acteService->trouverParId($id);

        // Journalisation de la consultation d'acte (Section 9.5 & 11.8)
        if ($user) {
            $this->journalService->consultationActe($acte, $user);
        }

        $registres = Registre::orderBy('cote', 'asc')->get();

        return view('modules.actes.show', compact('acte', 'registres'));
    }

    /**
     * Enregistre un nouvel acte via formulaire pop-up modale.
     */
    public function store(EnregistrerActeRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('actes.creer')) {
            abort(403, "Vous n'avez pas l'autorisation de créer un acte.");
        }

        try {
            $acte = $this->acteService->creer($request->validated(), $user);

            return redirect()->route('actes.show', $acte->id_acte)
                ->with('succes', "Acte {$acte->identifiant_acte} (n° {$acte->numero_acte} · {$acte->annee}) et sa transcription ont été enregistrés avec succès dans la base de données.");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', "L'acte et sa transcription n'ont pas pu être enregistrés dans la base : " . $e->getMessage());
        }
    }

    /**
     * Corrige la transcription d'un acte existant via formulaire pop-up modale.
     */
    public function update(EnregistrerActeRequest $request, int $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('actes.modifier')) {
            abort(403, "Vous n'avez pas l'autorisation de modifier un acte.");
        }

        try {
            $acte = $this->acteService->trouverParId($id);
            $this->acteService->corrigerTranscription($acte, $request->validated(), $user);

            return redirect()->route('actes.show', $acte->id_acte)
                ->with('succes', "La transcription de l'acte {$acte->identifiant_acte} a été enregistrée avec succès dans la base de données. L'opération a été tracée au journal.");
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('erreur', "La correction de la transcription n'a pas pu être enregistrée : " . $e->getMessage());
        }
    }
}
