<?php

namespace App\Modules\Fichiers\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Acte;
use App\Models\FichierNumerique;
use App\Modules\Fichiers\Requests\AssocierFichierRequest;
use App\Modules\Fichiers\Requests\RemplacerFichierRequest;
use App\Modules\Fichiers\Services\FichierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Contrôleur du module Fichiers numériques.
 * 
 * Assure la distribution contrôlée des flux, l'association de reproductions numériques,
 * le remplacement tracé et la vérification cryptographique d'intégrité SHA-256.
 */
class FichierController extends Controller
{
    public function __construct(
        protected FichierService $fichierService
    ) {}

    /**
     * Sert la copie numérique en streaming sécurisé (Section 7).
     * Journalise la consultation et interdit tout accès direct hors contrôleur.
     */
    public function show(Request $request, int $id): BinaryFileResponse
    {
        $fichier = FichierNumerique::findOrFail($id);
        return $this->fichierService->servirFluxFichier($fichier, $request->user());
    }

    /**
     * Associe un fichier numérique numérisé à un acte d'état civil.
     */
    public function store(AssocierFichierRequest $request): RedirectResponse
    {
        $acte = Acte::findOrFail($request->validated('id_acte'));
        $fichier = $this->fichierService->associerFichier(
            acte: $acte,
            uploadedFile: $request->file('fichier'),
            donnees: $request->validated(),
            auteur: $request->user()
        );

        return redirect()->route('actes.show', $acte->id_acte)
            ->with('succes', "Copie numérique '{$fichier->nom_fichier}' associée avec succès. Empreinte SHA-256 calculée et scellée.");
    }

    /**
     * Remplace une copie numérique existante avec justification obligatoire.
     */
    public function remplacer(RemplacerFichierRequest $request, int $id): RedirectResponse
    {
        $ancienFichier = FichierNumerique::with('acte')->findOrFail($id);
        $nouveauFichier = $this->fichierService->remplacerFichier(
            ancienFichier: $ancienFichier,
            uploadedFile: $request->file('fichier'),
            justification: $request->validated('justification_remplacement'),
            donnees: $request->validated(),
            auteur: $request->user()
        );

        return redirect()->route('actes.show', $ancienFichier->id_acte)
            ->with('succes', "La copie numérique a été remplacée par '{$nouveauFichier->nom_fichier}'. L'ancienne version est conservée dans l'historique d'audit.");
    }

    /**
     * Vérifie l'intégrité cryptographique SHA-256 du fichier sélectionné.
     */
    public function verifierIntegrite(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $fichier = FichierNumerique::findOrFail($id);
        $resultat = $this->fichierService->verifierIntegrite($fichier, $request->user());

        if ($request->wantsJson()) {
            return response()->json($resultat);
        }

        $typeFlash = $resultat['valide'] ? 'succes' : 'erreur';
        return redirect()->route('actes.show', $fichier->id_acte)
            ->with($typeFlash, "Contrôle d'intégrité [{$resultat['statut']}] : {$resultat['message']}");
    }
}
