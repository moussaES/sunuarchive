<?php

namespace App\Enums;

/**
 * Présence et localisation physique du registre dans les magasins d'archives.
 */
enum PresencePhysique: string
{
    case PRESENT = 'PRESENT';
    case TEMPORAIREMENT_DEPLACE = 'TEMPORAIREMENT_DEPLACE';
    case MANQUANT = 'MANQUANT';
    case A_VERIFIER = 'A_VERIFIER';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::PRESENT => 'Présent',
            self::TEMPORAIREMENT_DEPLACE => 'Temporairement déplacé',
            self::MANQUANT => 'Manquant',
            self::A_VERIFIER => 'À vérifier',
        };
    }

    /**
     * Retourne la classe de statut CSS correspondante.
     */
    public function classeStatut(): string
    {
        return match($this) {
            self::PRESENT => 'positif',
            self::TEMPORAIREMENT_DEPLACE => 'attente',
            self::A_VERIFIER => 'attention',
            self::MANQUANT => 'critique',
        };
    }
}
