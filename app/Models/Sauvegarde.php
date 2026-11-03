<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Sauvegarde.
 * 
 * Historique des opérations de sauvegarde de la base de données et
 * des copies numériques vers le support de stockage externe.
 */
class Sauvegarde extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'sauvegarde';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_sauvegarde';

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'id_utilisateur',
        'date_sauvegarde',
        'support',
        'taille',
        'resultat',
        'verifiee',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'date_sauvegarde' => 'datetime',
        'verifiee' => 'boolean',
        'taille' => 'integer',
    ];

    /**
     * Agent ayant lancé ou supervisé la sauvegarde (nullable si planifiée).
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur', 'id_utilisateur');
    }

    /**
     * Retourne la taille formatée en Ko ou Mo.
     */
    public function getTailleFormateeAttribute(): string
    {
        if (!$this->taille) {
            return '—';
        }
        if ($this->taille >= 1048576) {
            return round($this->taille / 1048576, 2) . ' Mo';
        }
        return round($this->taille / 1024, 0) . ' Ko';
    }

    /**
     * Retourne le nom du dossier ou de l'archive de sauvegarde.
     */
    public function getNomFichierAttribute(): string
    {
        $dateStr = $this->date_sauvegarde ? $this->date_sauvegarde->format('Ymd_His') : 'ARCHIVE';
        return "SIGAEC_BACKUP_{$dateStr}";
    }

    /**
     * Retourne l'empreinte du manifeste ou un identifiant d'intégrité calculé.
     */
    public function getEmpreinteSha256Attribute(): string
    {
        return hash('sha256', "SIGAEC_BACKUP_{$this->id_sauvegarde}_{$this->date_sauvegarde}");
    }
}
