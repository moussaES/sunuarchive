<?php

namespace App\Models;

use App\Enums\EtatVerification;
use App\Enums\TypeActe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle Acte (Classe racine abstraite du domaine des actes d'état civil).
 * 
 * Stocke les métadonnées documentaires communes à tous les actes
 * (numéro officiel, année, registre d'origine, état de vérification, cote).
 */
class Acte extends Model
{
    /**
     * Nom de la table MySQL associée.
     */
    protected $table = 'acte';

    /**
     * Clé primaire informatique distincte du numéro officiel d'acte.
     */
    protected $primaryKey = 'id_acte';

    /**
     * Pas d'horodatage Eloquent natif.
     */
    public $timestamps = false;

    /**
     * Attributs assignables en masse.
     */
    protected $fillable = [
        'identifiant_acte',
        'id_registre',
        'type',
        'numero_acte',
        'annee',
        'date_enregistrement',
        'page_folio',
        'numero_jugement',
        'date_jugement',
        'annee_jugement',
        'cote',
        'etat_verification',
        'observations',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'type' => TypeActe::class,
        'etat_verification' => EtatVerification::class,
        'annee' => 'integer',
        'date_enregistrement' => 'date',
        'date_jugement' => 'date',
        'annee_jugement' => 'integer',
    ];

    /**
     * Registre papier d'appartenance de l'acte.
     */
    public function registre(): BelongsTo
    {
        return $this->belongsTo(Registre::class, 'id_registre', 'id_registre');
    }

    /**
     * Données spécifiques si l'acte est une naissance.
     */
    public function acteNaissance(): HasOne
    {
        return $this->hasOne(ActeNaissance::class, 'id_acte', 'id_acte');
    }

    /**
     * Données spécifiques si l'acte est un mariage.
     */
    public function acteMariage(): HasOne
    {
        return $this->hasOne(ActeMariage::class, 'id_acte', 'id_acte');
    }

    /**
     * Données spécifiques si l'acte est un décès.
     */
    public function acteDeces(): HasOne
    {
        return $this->hasOne(ActeDeces::class, 'id_acte', 'id_acte');
    }

    /**
     * Fichiers numériques associés (copies de consultation).
     */
    public function fichiers(): HasMany
    {
        return $this->hasMany(FichierNumerique::class, 'id_acte', 'id_acte');
    }

    /**
     * Fichier numérique actuellement actif.
     */
    public function fichierActif(): HasOne
    {
        return $this->hasOne(FichierNumerique::class, 'id_acte', 'id_acte')->where('actif', true);
    }

    /**
     * Personnes indexées rattachées à cet acte avec leur rôle (enfant, époux, etc.).
     */
    public function personnes(): BelongsToMany
    {
        return $this->belongsToMany(PersonneIndexee::class, 'acte_personne', 'id_acte', 'id_personne')
            ->withPivot('role');
    }

    /**
     * Demandes de communication administratives portant sur cet acte.
     */
    public function demandes(): BelongsToMany
    {
        return $this->belongsToMany(DemandeCommunication::class, 'demande_acte', 'id_acte', 'id_demande');
    }

    /**
     * Indique si l'acte dispose d'au moins un fichier numérique actif (Section 3.4).
     */
    public function possedeImage(): bool
    {
        return $this->fichiers()->where('actif', true)->exists();
    }

    /**
     * Indique si l'acte fait l'objet d'un jugement supplétif.
     */
    public function possedeJugement(): bool
    {
        return !empty($this->numero_jugement) || !empty($this->date_jugement) || !empty($this->annee_jugement);
    }

    /**
     * Retourne l'entité fille spécifique selon le type d'acte.
     */
    public function getDetailsSpecifiquesAttribute(): Model|null
    {
        return match($this->type) {
            TypeActe::NAISSANCE => $this->acteNaissance,
            TypeActe::MARIAGE => $this->acteMariage,
            TypeActe::DECES => $this->acteDeces,
            default => null,
        };
    }
}
