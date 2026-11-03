<?php

namespace App\Enums;

/**
 * Types d'actes d'état civil gérés par SIGAEC.
 */
enum TypeActe: string
{
    case NAISSANCE = 'NAISSANCE';
    case MARIAGE = 'MARIAGE';
    case DECES = 'DECES';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::NAISSANCE => 'Naissance',
            self::MARIAGE => 'Mariage',
            self::DECES => 'Décès',
        };
    }
}
