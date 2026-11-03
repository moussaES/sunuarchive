<?php

namespace App\Models;

use App\Enums\ResultatOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Modèle JournalActivite (Traçabilité et audit des opérations).
 * 
 * Enregistre chaque opération sensible (connexion, consultation, modification, export).
 * EN ÉCRITURE SEULE : Aucune modification ni suppression n'est permise.
 */
class JournalActivite extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'journal_activite';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_journal';

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'id_utilisateur',
        'identifiant_saisi',
        'date_heure',
        'operation',
        'type_reference',
        'id_reference',
        'resultat',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_heure' => 'datetime',
    ];

    /**
     * Agent utilisateur ayant initié l'opération.
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Verrouillage absolu en écriture seule au niveau Eloquent (Section 7).
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException("Le journal d'activité est en lecture seule : modification interdite.");
        });

        static::deleting(function () {
            throw new RuntimeException("Le journal d'activité est en lecture seule : suppression interdite.");
        });
    }
}
