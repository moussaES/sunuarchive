<?php

namespace App\Models;

use App\Enums\EtatMateriel;
use App\Enums\PresencePhysique;
use App\Enums\TypeActe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle Registre (Module Registres - Inventaire et localisation).
 * 
 * Représente un registre d'état civil papier physique conservé dans
 * les magasins d'archives de la Mairie de Tattaguine.
 */
class Registre extends Model
{
    /**
     * Nom de la table MySQL associée.
     */
    protected $table = 'registre';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_registre';

    /**
     * La table ne gère pas created_at/updated_at automatiques.
     */
    public $timestamps = false;

    /**
     * Attributs assignables en masse.
     */
    protected $fillable = [
        'type',
        'annee_debut',
        'annee_fin',
        'numero_volume',
        'nombre_pages',
        'nombre_actes',
        'cote',
        'localisation',
        'etat_materiel',
        'presence',
        'inventaire_complet',
        'observations',
        'date_inventaire',
    ];

    /**
     * Conversions de types (Casts).
     */
    protected $casts = [
        'type' => TypeActe::class,
        'etat_materiel' => EtatMateriel::class,
        'presence' => PresencePhysique::class,
        'inventaire_complet' => 'boolean',
        'annee_debut' => 'integer',
        'annee_fin' => 'integer',
        'nombre_pages' => 'integer',
        'nombre_actes' => 'integer',
        'date_inventaire' => 'date',
    ];

    /**
     * Actes enregistrés dans ce registre.
     */
    public function actes(): HasMany
    {
        return $this->hasMany(Acte::class, 'id_registre', 'id_registre');
    }

    /**
     * Détermine si le registre est entièrement inventorié (Section 5 du cahier des charges).
     * Distingue un registre entièrement inventorié d'un registre dont le contenu reste à traiter.
     */
    public function estInventorie(): bool
    {
        return $this->inventaire_complet
            || (!is_null($this->nombre_actes) && !is_null($this->date_inventaire));
    }

    /**
     * Indique si le registre nécessite une surveillance particulière (dégradé ou déplacé).
     */
    public function aSurveiller(): bool
    {
        return in_array($this->etat_materiel, [EtatMateriel::DEGRADE, EtatMateriel::TRES_DEGRADE], true)
            || in_array($this->presence, [PresencePhysique::A_VERIFIER, PresencePhysique::MANQUANT, PresencePhysique::TEMPORAIREMENT_DEPLACE], true);
    }
}
