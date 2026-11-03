<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modèle DemandeCommunication.
 * 
 * Registre de suivi des demandes de consultation ou de communication d'actes
 * formulées par les services administratifs, judiciaires ou les usagers.
 */
class DemandeCommunication extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'demande_communication';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_demande';

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'numero_demande',
        'id_utilisateur',
        'date_demande',
        'service_demandeur',
        'objet',
        'decision',
        'date_traitement',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_demande' => 'date',
        'date_traitement' => 'date',
    ];

    /**
     * Agent responsable du traitement de la demande.
     */
    public function agentResponsable(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Actes concernés par cette demande.
     */
    public function actes(): BelongsToMany
    {
        return $this->belongsToMany(Acte::class, 'demande_acte', 'id_demande', 'id_acte');
    }
}
