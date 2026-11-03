<?php

namespace App\Policies;

use App\Models\Utilisateur;

/**
 * Règle d'autorisation (Policy) pour la consultation du journal d'activité.
 */
class JournalActivitePolicy
{
    /**
     * Seuls l'Admin technique et le Responsable habilité peuvent consulter le journal.
     */
    public function viewAny(Utilisateur $user): bool
    {
        return $user->aDroit('journal.consulter');
    }

    /**
     * Modification strictement interdite à quiconque.
     */
    public function update(Utilisateur $user): bool
    {
        return false;
    }

    /**
     * Suppression strictement interdite à quiconque.
     */
    public function delete(Utilisateur $user): bool
    {
        return false;
    }
}
