<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle ActeNaissance (Héritage d'Acte - Spécifique Naissance).
 * 
 * Contient les mentions propres aux naissances : date et lieu de naissance,
 * sexe porté sur l'acte et date de déclaration.
 */
class ActeNaissance extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'acte_naissance';

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
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'date_declaration',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_naissance' => 'date',
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
