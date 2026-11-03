<?php

namespace App\Services\Journal;

use App\Enums\ResultatOperation;
use App\Models\Acte;
use App\Models\JournalActivite;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

/**
 * Service centralisé de traçabilité et d'audit pour SIGAEC (Mairie de Tattaguine).
 * 
 * Enregistre chaque opération sensible dans la table journal_activite.
 * Conforme aux exigences de la section 6 et 7 du cahier des charges :
 * - Le journal est en écriture seule.
 * - Ne contient JAMAIS le texte intégral des actes ni de noms de personnes physiques.
 * - Stocke uniquement des identifiants et des références stables d'objets.
 */
class JournalService
{
    /**
     * Enregistre un événement dans le journal d'activité.
     * 
     * @param string $operation Nom normalisé de l'opération (CONNEXION, CONSULTATION, etc.)
     * @param string|null $typeReference Type d'entité concernée (REGISTRE, ACTE, FICHIER, UTILISATEUR, SYSTEME)
     * @param int|null $idReference Identifiant numérique de l'objet concerné
     * @param ResultatOperation|string $resultat Résultat de l'opération (ACCORDE, REUSSI, REFUSE, ECHEC)
     * @param string|null $identifiantSaisi Identifiant utilisateur ayant initié l'opération
     * @param int|null $idUtilisateur ID du compte utilisateur (nullable si inconnu)
     * @return JournalActivite L'entité journal créée
     */
    public function enregistrer(
        string $operation,
        ?string $typeReference = null,
        ?int $idReference = null,
        ResultatOperation|string $resultat = ResultatOperation::REUSSI,
        ?string $identifiantSaisi = null,
        ?int $idUtilisateur = null
    ): JournalActivite {
        $valeurResultat = $resultat instanceof ResultatOperation ? $resultat->value : (string) $resultat;

        // Si l'utilisateur n'est pas passé mais qu'une session existe
        if ($idUtilisateur === null && auth()->check()) {
            $user = auth()->user();
            $idUtilisateur = $user->id_utilisateur;
            if ($identifiantSaisi === null) {
                $identifiantSaisi = $user->identifiant;
            }
        }

        // Insertion directe via DB pour respecter l'écriture seule
        $id = DB::table('journal_activite')->insertGetId([
            'id_utilisateur' => $idUtilisateur,
            'identifiant_saisi' => $identifiantSaisi,
            'date_heure' => now(),
            'operation' => strtoupper($operation),
            'type_reference' => $typeReference ? strtoupper($typeReference) : null,
            'id_reference' => $idReference,
            'resultat' => $valeurResultat,
        ]);

        return JournalActivite::find($id);
    }

    /**
     * Trace une connexion réussie.
     */
    public function connexionReussie(Utilisateur $user): void
    {
        $this->enregistrer(
            operation: 'CONNEXION',
            typeReference: 'SYSTEME',
            idReference: null,
            resultat: ResultatOperation::REUSSI,
            identifiantSaisi: $user->identifiant,
            idUtilisateur: $user->id_utilisateur
        );
    }

    /**
     * Trace un échec de connexion (mauvais mot de passe ou compte verrouillé/inexistant).
     */
    public function connexionEchouee(string $identifiant, ?Utilisateur $user = null): void
    {
        $this->enregistrer(
            operation: 'TENTATIVE_CONNEXION',
            typeReference: 'SECURITE',
            idReference: null,
            resultat: ResultatOperation::ECHEC,
            identifiantSaisi: $identifiant,
            idUtilisateur: $user?->id_utilisateur
        );
    }

    /**
     * Trace une déconnexion volontaire ou par expiration d'inactivité.
     */
    public function deconnexion(Utilisateur $user, string $motif = 'VOLONTAIRE'): void
    {
        $this->enregistrer(
            operation: 'DECONNEXION_' . strtoupper($motif),
            typeReference: 'SYSTEME',
            idReference: null,
            resultat: ResultatOperation::REUSSI,
            identifiantSaisi: $user->identifiant,
            idUtilisateur: $user->id_utilisateur
        );
    }

    /**
     * Trace un accès refusé (tentative d'accès 403 à une ressource non autorisée).
     */
    public function accesRefuse(string $ressource, ?Utilisateur $user = null): void
    {
        $this->enregistrer(
            operation: 'ACCES_INTERDIT',
            typeReference: 'SECURITE',
            idReference: null,
            resultat: ResultatOperation::REFUSE,
            identifiantSaisi: $user?->identifiant ?? 'NON_AUTHENTIFIE',
            idUtilisateur: $user?->id_utilisateur
        );
    }

    /**
     * Trace l'ouverture et la consultation d'une fiche d'acte.
     */
    public function consultationActe(Acte $acte, Utilisateur $user): void
    {
        $this->enregistrer(
            operation: 'CONSULTATION_ACTE',
            typeReference: 'ACTE',
            idReference: $acte->id_acte,
            resultat: ResultatOperation::ACCORDE,
            identifiantSaisi: $user->identifiant,
            idUtilisateur: $user->id_utilisateur
        );
    }

    /**
     * Trace la consultation d'une copie numérique numérisée.
     */
    public function consultationFichier(int $idFichier, Utilisateur $user): void
    {
        $this->enregistrer(
            operation: 'CONSULTATION_FICHIER',
            typeReference: 'FICHIER',
            idReference: $idFichier,
            resultat: ResultatOperation::ACCORDE,
            identifiantSaisi: $user->identifiant,
            idUtilisateur: $user->id_utilisateur
        );
    }

    /**
     * Trace une modification ou création d'entité archivistique.
     */
    public function operationDocumentaire(string $action, string $typeReference, ?int $idReference, Utilisateur $user): void
    {
        $this->enregistrer(
            operation: strtoupper($action),
            typeReference: strtoupper($typeReference),
            idReference: $idReference,
            resultat: ResultatOperation::REUSSI,
            identifiantSaisi: $user->identifiant,
            idUtilisateur: $user->id_utilisateur
        );
    }

    /**
     * Trace l'exécution d'une recherche d'actes (Section 9.4).
     */
    public function rechercheActes(string $criteres, Utilisateur $user): void
    {
        $this->enregistrer(
            operation: 'RECHERCHE_ACTES',
            typeReference: 'RECHERCHE',
            idReference: null,
            resultat: ResultatOperation::REUSSI,
            identifiantSaisi: substr($criteres, 0, 100),
            idUtilisateur: $user->id_utilisateur
        );
    }
}
