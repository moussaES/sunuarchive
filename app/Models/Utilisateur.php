<?php

namespace App\Models;

use App\Enums\EtatCompte;
use App\Enums\Profil;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;

/**
 * Modèle Utilisateur (Module Sécurité et Habilitations).
 * 
 * Représente un compte individuel d'agent de la Mairie de Tattaguine.
 * Aucun compte partagé n'est autorisé.
 */
class Utilisateur extends Authenticatable
{
    use Notifiable;

    /**
     * Nom de la table associée.
     */
    protected $table = 'utilisateur';

    /**
     * Clé primaire.
     */
    protected $primaryKey = 'id_utilisateur';

    /**
     * Pas de timestamps automatiques created_at/updated_at.
     */
    public $timestamps = false;

    /**
     * Champs assignables en masse.
     */
    protected $fillable = [
        'identifiant',
        'nom_complet',
        'mot_de_passe_hash',
        'profil',
        'etat_compte',
        'tentatives_echec',
        'derniere_activite',
        'date_creation',
        'date_desactivation',
    ];

    /**
     * Attributs masqués pour la sérialisation.
     */
    protected $hidden = [
        'mot_de_passe_hash',
    ];

    /**
     * Casts d'attributs.
     */
    protected $casts = [
        'profil' => Profil::class,
        'etat_compte' => EtatCompte::class,
        'tentatives_echec' => 'integer',
        'date_creation' => 'date',
        'derniere_activite' => 'datetime',
        'date_desactivation' => 'date',
    ];

    /**
     * Spécifie la colonne du mot de passe haché pour l'authentification Laravel.
     */
    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe_hash';
    }

    /**
     * Clé d'authentification.
     */
    public function getAuthIdentifierName(): string
    {
        return 'id_utilisateur';
    }

    /**
     * Vérifie si le compte est actif.
     */
    public function estActif(): bool
    {
        return $this->etat_compte === EtatCompte::ACTIF;
    }

    /**
     * Vérifie si l'utilisateur possède un droit spécifique selon sa matrice de profil.
     */
    public function aDroit(string $droit): bool
    {
        if (!$this->estActif()) {
            return false;
        }

        $codeProfil = $this->profil instanceof Profil ? $this->profil->value : (string) $this->profil;
        $droits = Config::get("droits.profils.{$codeProfil}.droits", []);

        return in_array($droit, $droits, true);
    }

    /**
     * Verrouille le compte suite à 5 tentatives infructueuses de connexion.
     */
    public function verrouiller(): void
    {
        $this->update([
            'etat_compte' => EtatCompte::VERROUILLE,
        ]);
    }
}
