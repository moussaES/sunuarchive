<?php

namespace App\Policies;

use App\Models\Acte;
use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour la consultation et gestion des actes d'état civil.
 */
class ActePolicy
{
    /**
     * Détermine si l'agent peut rechercher et lister les actes.
     */
    public function viewAny(Utilisateur $user): bool
    {
        return $user->aDroit('actes.consulter');
    }

    /**
     * Détermine si l'agent peut consulter la fiche détaillée d'un acte.
     */
    public function view(Utilisateur $user, Acte $acte): bool
    {
        return $user->aDroit('actes.consulter');
    }

    /**
     * Détermine si l'agent peut créer un acte.
     */
    public function create(Utilisateur $user): bool
    {
        return $user->aDroit('actes.creer');
    }

    /**
     * Détermine si l'agent peut corriger la transcription d'un acte.
     */
    public function update(Utilisateur $user, Acte $acte): bool
    {
        return $user->aDroit('actes.modifier');
    }

    /**
     * Détermine si l'agent peut indexer des personnes dans l'acte.
     */
    public function indexer(Utilisateur $user, Acte $acte): bool
    {
        return $user->aDroit('actes.indexer');
    }

    /**
     * Aucune suppression physique d'acte autorisée.
     */
    public function delete(Utilisateur $user, Acte $acte): bool
    {
        return false;
    }
}
