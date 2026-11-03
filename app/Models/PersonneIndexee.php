<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle PersonneIndexee.
 * 
 * Stocke l'identité des personnes physiques mentionnées dans les actes
 * pour permettre la recherche nominative autorisée.
 */
class PersonneIndexee extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'personne_indexee';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_personne';

    /**
     * Pas de timestamps.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_naissance' => 'date',
    ];

    /**
     * Actes dans lesquels cette personne apparaît, avec son rôle.
     */
    public function actes(): BelongsToMany
    {
        return $this->belongsToMany(Acte::class, 'acte_personne', 'id_personne', 'id_acte')
            ->withPivot('role');
    }

    /**
     * Accesseur pour afficher le nom complet usuel.
     */
    public function getNomCompletAttribute(): string
    {
        return trim(($this->prenoms ?? '') . ' ' . $this->nom);
    }
}
