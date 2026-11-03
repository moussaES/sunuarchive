<?php

namespace App\Policies;

use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour l'administration des comptes utilisateurs.
 */
class UtilisateurPolicy
{
    /**
     * Détermine si l'agent peut gérer les comptes utilisateurs (Admin technique seul).
     */
    public function viewAny(Utilisateur $user): bool
    {
        return $user->aDroit('utilisateurs.gerer');
    }

    /**
     * Détermine si l'agent peut créer un compte utilisateur.
     */
    public function create(Utilisateur $user): bool
    {
        return $user->aDroit('utilisateurs.gerer');
    }

    /**
     * Détermine si l'agent peut modifier ou débloquer un compte utilisateur.
     */
    public function update(Utilisateur $user, Utilisateur $cible): bool
    {
        return $user->aDroit('utilisateurs.gerer');
    }

    /**
     * Pas de suppression de compte : désactivation seule (Section 9).
     */
    public function delete(Utilisateur $user, Utilisateur $cible): bool
    {
        return false;
    }
}
