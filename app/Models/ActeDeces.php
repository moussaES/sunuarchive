<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle ActeDeces (Héritage d'Acte - Spécifique Décès).
 * 
 * Contient les mentions propres aux décès : date, heure et lieu du décès,
 * profession, domicile et date de déclaration.
 */
class ActeDeces extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'acte_deces';

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
        'date_deces',
        'heure_deces',
        'lieu_deces',
        'profession',
        'domicile',
        'date_declaration',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_deces' => 'date',
        'date_declaration' => 'date',
    ];

    /**
     * Acte parent générique.
     */
    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class, 'id_acte', 'id_acte');
    }
}
