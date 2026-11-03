<?php

namespace App\Modules\Demandes\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DemandeCommunication;
use App\Modules\Demandes\Requests\CreerDemandeRequest;
use App\Modules\Demandes\Services\DemandeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur du module Demandes de communication.
 */
class DemandeController extends Controller
{
    public function __construct(
        protected DemandeService $demandeService
    ) {}

    /**
     * Enregistre une nouvelle demande de communication (Section 9.6).
     */
    public function store(CreerDemandeRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('demandes.saisir')) {
            abort(403, "Vous n'avez pas l'autorisation de saisir une demande de communication.");
        }

        $demande = $this->demandeService->creer($request->validated(), $user);

        return redirect()->back()
            ->with('succes', "Demande de communication {$demande->numero_demande} enregistrée avec succès.");
    }

    /**
     * Enregistre la décision prise sur une demande (Section 9.6).
     */
    public function decider(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        if (!$user->aDroit('demandes.decider')) {
            abort(403, "Vous n'avez pas l'habilitation pour statuer sur les demandes de communication.");
        }

        $request->validate([
            'decision' => ['required', 'string', 'in:ACCEPTEE,ACCORDEE,REFUSEE'],
        ]);

        $demande = DemandeCommunication::findOrFail($id);
        $this->demandeService->decider($demande, $request->input('decision'), $user);

        $decisionLibelle = ($request->input('decision') === 'REFUSEE') ? 'refusée' : 'accordée';

        return redirect()->back()
            ->with('succes', "La demande {$demande->numero_demande} a été {$decisionLibelle} par {$user->nom_complet}.");
    }
}
