<?php

namespace App\Enums;

/**
 * État physique et matériel de conservation d'un registre papier.
 */
enum EtatMateriel: string
{
    case BON = 'BON';
    case MOYEN = 'MOYEN';
    case DEGRADE = 'DEGRADE';
    case TRES_DEGRADE = 'TRES_DEGRADE';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::BON => 'Bon',
            self::MOYEN => 'Moyen',
            self::DEGRADE => 'Dégradé',
            self::TRES_DEGRADE => 'Très dégradé',
        };
    }

    /**
     * Retourne la classe de statut CSS correspondante.
     */
    public function classeStatut(): string
    {
        return match($this) {
            self::BON => 'positif',
            self::MOYEN => 'attente',
            self::DEGRADE => 'attention',
            self::TRES_DEGRADE => 'critique',
        };
    }
}
