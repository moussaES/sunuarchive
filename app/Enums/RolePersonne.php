<?php

namespace App\Enums;

/**
 * Rôle d'une personne physique indexée dans un acte d'état civil.
 */
enum RolePersonne: string
{
    case ENFANT = 'ENFANT';
    case PERE = 'PERE';
    case MERE = 'MERE';
    case EPOUX = 'EPOUX';
    case EPOUSE = 'EPOUSE';
    case DEFUNT = 'DEFUNT';
    case DECLARANT = 'DECLARANT';
    case TEMOIN = 'TEMOIN';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::ENFANT => 'Enfant',
            self::PERE => 'Père',
            self::MERE => 'Mère',
            self::EPOUX => 'Époux',
            self::EPOUSE => 'Épouse',
            self::DEFUNT => 'Défunt',
            self::DECLARANT => 'Déclarant',
            self::TEMOIN => 'Témoin',
        };
    }
}
