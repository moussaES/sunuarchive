<?php

namespace App\Enums;

/**
 * État d'un compte utilisateur agent communal.
 */
enum EtatCompte: string
{
    case ACTIF = 'ACTIF';
    case VERROUILLE = 'VERROUILLE';
    case DESACTIVE = 'DESACTIVE';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::ACTIF => 'Actif',
            self::VERROUILLE => 'Verrouillé',
            self::DESACTIVE => 'Désactivé',
        };
    }

    /**
     * Retourne la classe de statut CSS correspondante.
     */
    public function classeStatut(): string
    {
        return match($this) {
            self::ACTIF => 'positif',
            self::VERROUILLE => 'attention',
            self::DESACTIVE => 'critique',
        };
    }
}
