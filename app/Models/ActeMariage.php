<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle ActeMariage (Héritage d'Acte - Spécifique Mariage).
 * 
 * Contient les mentions propres aux mariages : date et lieu de célébration,
 * régime matrimonial et mentions marginales.
 */
class ActeMariage extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'acte_mariage';

    /**
     * Clé primaire liée à la table acte.
     */
    protected $primaryKey = 'id_acte';

    /**
     * Clé primaire non auto-incrémentée (relation 1-1).
     */
    public $incrementing = false;

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'id_acte',
        'type',
        'date_mariage',
        'lieu_mariage',
        'regime_matrimonial',
        'mentions_marginales',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_mariage' => 'date',
    ];

    /**
     * Acte parent générique.
     */
    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class, 'id_acte', 'id_acte');
    }
}
