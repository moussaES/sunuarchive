<?php

namespace App\Policies;

use App\Models\FichierNumerique;
use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour l'accès aux copies numériques d'archives.
 */
class FichierNumeriquePolicy
{
    /**
     * Détermine si l'agent peut visualiser la reproduction numérique.
     */
    public function view(Utilisateur $user, FichierNumerique $fichier): bool
    {
        return $user->aDroit('fichiers.consulter');
    }

    /**
     * Détermine si l'agent peut associer un nouveau fichier numérique.
     */
    public function create(Utilisateur $user): bool
    {
        return $user->aDroit('fichiers.associer');
    }

    /**
     * Détermine si l'agent peut remplacer un fichier numérique (justification requise).
     */
    public function replace(Utilisateur $user, FichierNumerique $fichier): bool
    {
        return $user->aDroit('fichiers.remplacer');
    }

    /**
     * Suppression physique interdite (remplacement avec archivage uniquement).
     */
    public function delete(Utilisateur $user, FichierNumerique $fichier): bool
    {
        return false;
    }
}
