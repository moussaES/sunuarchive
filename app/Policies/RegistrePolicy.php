<?php

namespace App\Policies;

use App\Models\Registre;
use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour la gestion des registres d'archives.
 */
class RegistrePolicy
{
    /**
     * Détermine si l'agent peut lister les registres.
     */
    public function viewAny(Utilisateur $user): bool
    {
        return $user->aDroit('registres.consulter');
    }

    /**
     * Détermine si l'agent peut consulter le détail d'un registre.
     */
    public function view(Utilisateur $user, Registre $registre): bool
    {
        return $user->aDroit('registres.consulter');
    }

    /**
     * Détermine si l'agent peut créer une fiche registre.
     */
    public function create(Utilisateur $user): bool
    {
        return $user->aDroit('registres.creer');
    }

    /**
     * Détermine si l'agent peut modifier un registre.
     */
    public function update(Utilisateur $user, Registre $registre): bool
    {
        return $user->aDroit('registres.modifier');
    }

    /**
     * Suppression formellement interdite depuis l'interface ordinaire (Section 1).
     */
    public function delete(Utilisateur $user, Registre $registre): bool
    {
        return false;
    }
}
