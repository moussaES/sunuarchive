<?php

namespace App\Models;

use App\Enums\RolePersonne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot ActePersonne.
 * 
 * Associe une personne indexée à un acte d'état civil avec son rôle
 * (Enfant, Père, Mère, Époux, Épouse, Défunt, Déclarant, Témoin).
 */
class ActePersonne extends Pivot
{
    /**
     * Nom de la table pivot.
     */
    protected $table = 'acte_personne';

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'role' => RolePersonne::class,
    ];

    /**
     * Acte concerné.
     */
    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class, 'id_acte', 'id_acte');
    }

    /**
     * Personne concernée.
     */
    public function personne(): BelongsTo
    {
        return $this->belongsTo(PersonneIndexee::class, 'id_personne', 'id_personne');
    }
}
