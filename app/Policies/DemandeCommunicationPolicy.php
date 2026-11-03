<?php

namespace App\Policies;

use App\Models\DemandeCommunication;
use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour les demandes de communication administrative.
 */
class DemandeCommunicationPolicy
{
    /**
     * Détermine si l'agent peut lister les demandes de communication.
     */
    public function viewAny(Utilisateur $user): bool
    {
        return $user->aDroit('demandes.saisir') || $user->aDroit('demandes.decider');
    }

    /**
     * Détermine si l'agent peut créer / saisir une nouvelle demande.
     */
    public function create(Utilisateur $user): bool
    {
        return $user->aDroit('demandes.saisir');
    }

    /**
     * Détermine si l'agent a le pouvoir de statuer / décider sur une demande.
     */
    public function decide(Utilisateur $user, DemandeCommunication $demande): bool
    {
        return $user->aDroit('demandes.decider');
    }
}
