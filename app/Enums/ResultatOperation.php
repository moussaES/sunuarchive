<?php

namespace App\Enums;

/**
 * Résultat consigné dans le journal d'activité pour une opération sensible.
 */
enum ResultatOperation: string
{
    case ACCORDE = 'ACCORDE';
    case REUSSI = 'REUSSI';
    case REFUSE = 'REFUSE';
    case ECHEC = 'ECHEC';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::ACCORDE => 'Accordé',
            self::REUSSI => 'Réussi',
            self::REFUSE => 'Refusé',
            self::ECHEC => 'Échec',
        };
    }

    /**
     * Retourne la classe de statut CSS correspondante.
     */
    public function classeStatut(): string
    {
        return match($this) {
            self::ACCORDE, self::REUSSI => 'positif',
            self::REFUSE => 'attention',
            self::ECHEC => 'critique',
        };
    }
}
