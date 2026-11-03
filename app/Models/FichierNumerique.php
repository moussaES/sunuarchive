<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Modèle FichierNumerique (Conservation et intégrité numérique des actes).
 * 
 * Enregistre les métadonnées d'une reproduction numérique (TIFF/PDF/JPEG),
 * son empreinte SHA-256 pour le contrôle d'intégrité, et son statut actif/archivé.
 */
class FichierNumerique extends Model
{
    /**
     * Nom de la table associée.
     */
    protected $table = 'fichier_numerique';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_fichier';

    /**
     * Pas de timestamps automatiques.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'id_acte',
        'nom_fichier',
        'nom_origine',
        'format',
        'taille_octets',
        'empreinte_sha256',
        'chemin_stockage',
        'date_numerisation',
        'numero_page',
        'actif',
        'remplace_fichier_id',
        'justification_remplacement',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'actif' => 'boolean',
        'numero_page' => 'integer',
        'taille_octets' => 'integer',
        'date_numerisation' => 'date',
    ];

    /**
     * Acte auquel ce fichier est rattaché.
     */
    public function acte(): BelongsTo
    {
        return $this->belongsTo(Acte::class, 'id_acte', 'id_acte');
    }

    /**
     * Fichier précédent s'il s'agit d'un remplacement.
     */
    public function fichierRemplace(): BelongsTo
    {
        return $this->belongsTo(self::class, 'remplace_fichier_id', 'id_fichier');
    }

    /**
     * Vérifie l'intégrité cryptographique du fichier par rapport au disque privé 'archives'.
     */
    public function verifierIntegrite(): bool
    {
        $disque = Storage::disk('archives');
        if (!$disque->exists($this->chemin_stockage)) {
            return false;
        }

        $cheminComplet = $disque->path($this->chemin_stockage);
        $empreinteCalculee = hash_file('sha256', $cheminComplet);

        return hash_equals(strtolower($this->empreinte_sha256), strtolower($empreinteCalculee));
    }
}
