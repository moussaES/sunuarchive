<?php

namespace App\Enums;

/**
 * Profils et niveaux d'habilitation des agents communaux dans SIGAEC.
 */
enum Profil: string
{
    case ADMIN_TECHNIQUE = 'ADMIN_TECHNIQUE';
    case ARCHIVISTE = 'ARCHIVISTE';
    case AGENT_INDEXATION = 'AGENT_INDEXATION';
    case AGENT_CONSULTATION = 'AGENT_CONSULTATION';
    case RESPONSABLE_HABILITE = 'RESPONSABLE_HABILITE';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::ADMIN_TECHNIQUE => 'Administrateur technique',
            self::ARCHIVISTE => 'Archiviste',
            self::AGENT_INDEXATION => 'Agent d\'indexation',
            self::AGENT_CONSULTATION => 'Agent de consultation',
            self::RESPONSABLE_HABILITE => 'Responsable habilité',
        };
    }
}
