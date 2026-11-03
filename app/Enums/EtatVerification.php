<?php

namespace App\Enums;

/**
 * État de vérification de la transcription d'un acte d'état civil.
 */
enum EtatVerification: string
{
    case A_VERIFIER = 'A_VERIFIER';
    case VERIFIE = 'VERIFIE';
    case ILLISIBLE = 'ILLISIBLE';
    case INCOMPLET = 'INCOMPLET';

    /**
     * Retourne le libellé utilisateur en français.
     */
    public function libelle(): string
    {
        return match($this) {
            self::A_VERIFIER => 'À vérifier',
            self::VERIFIE => 'Vérifié',
            self::ILLISIBLE => 'Illisible',
            self::INCOMPLET => 'Incomplet',
        };
    }

    /**
     * Retourne la classe de statut CSS correspondante.
     */
    public function classeStatut(): string
    {
        return match($this) {
            self::VERIFIE => 'positif',
            self::A_VERIFIER => 'attention',
            self::INCOMPLET => 'attente',
            self::ILLISIBLE => 'critique',
        };
    }
}
