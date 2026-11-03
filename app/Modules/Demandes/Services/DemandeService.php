<?php

namespace App\Modules\Demandes\Services;

use App\Models\DemandeCommunication;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service métier de gestion des demandes de communication administrative et judiciaire (Section 5 & 9.6).
 */
class DemandeService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Génère un numéro de demande unique et normalisé (ex: DEM-0004).
     */
    public function genererNumeroDemande(): string
    {
        $maxId = (int) DemandeCommunication::max('id_demande');
        return sprintf('DEM-%04d', $maxId + 1);
    }

    /**
     * Enregistre une nouvelle demande de communication formulée auprès de la mairie.
     */
    public function creer(array $donnees, Utilisateur $auteur): DemandeCommunication
    {
        return DB::transaction(function () use ($donnees, $auteur) {
            $numeroDemande = $this->genererNumeroDemande();

            $demande = DemandeCommunication::create([
                'numero_demande' => $numeroDemande,
                'id_utilisateur' => $auteur->id_utilisateur,
                'date_demande' => $donnees['date_demande'] ?? now()->toDateString(),
                'service_demandeur' => trim($donnees['service_demandeur']),
                'objet' => trim($donnees['objet']),
                'decision' => 'EN_ATTENTE',
                'date_traitement' => null,
            ]);

            if (!empty($donnees['actes'])) {
                $demande->actes()->sync($donnees['actes']);
            }

            // Inscription au journal
            $this->journalService->operationDocumentaire(
                action: 'CREATION_DEMANDE',
                typeReference: 'DEMANDE',
                idReference: $demande->id_demande,
                user: $auteur
            );

            return $demande;
        });
    }

    /**
     * Enregistre la décision prise par l'officier / responsable habilité.
     */
    public function decider(DemandeCommunication $demande, string $decision, Utilisateur $responsable): DemandeCommunication
    {
        $decisionNormalisee = strtoupper(trim($decision));
        if (!in_array($decisionNormalisee, ['ACCEPTEE', 'ACCORDEE', 'REFUSEE'])) {
            throw new InvalidArgumentException("Décision invalide. Valeurs acceptées : ACCEPTEE, REFUSEE.");
        }

        if ($decisionNormalisee === 'ACCORDEE') {
            $decisionNormalisee = 'ACCEPTEE';
        }

        return DB::transaction(function () use ($demande, $decisionNormalisee, $responsable) {
            $demande->update([
                'decision' => $decisionNormalisee,
                'date_traitement' => now()->toDateString(),
                'id_utilisateur' => $responsable->id_utilisateur,
            ]);

            // Inscription au journal
            $this->journalService->operationDocumentaire(
                action: "DECISION_DEMANDE_{$decisionNormalisee}",
                typeReference: 'DEMANDE',
                idReference: $demande->id_demande,
                user: $responsable
            );

            return $demande;
        });
    }

    /**
     * Retourne la liste des demandes de communication avec leurs actes associés.
     */
    public function lister(): Collection
    {
        return DemandeCommunication::with(['agentResponsable', 'actes'])
            ->orderByDesc('id_demande')
            ->get();
    }
}
