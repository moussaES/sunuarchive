<?php

namespace App\Modules\Actes\Services;

use App\Enums\RolePersonne;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\ActeDeces;
use App\Models\ActeMariage;
use App\Models\ActeNaissance;
use App\Models\PersonneIndexee;
use App\Models\Registre;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Support\Facades\DB;

/**
 * Service métier de gestion des actes d'état civil (Mairie de Tattaguine).
 * 
 * Assure la création différenciée (Naissance, Mariage, Décès),
 * le contrôle des doublons composites, l'indexation des personnes et
 * la traçabilité intégrale de toute correction documentaire.
 */
class ActeService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Recherche un acte par son identifiant informatique avec toutes ses relations.
     */
    public function trouverParId(int $id): Acte
    {
        return Acte::with([
            'registre',
            'acteNaissance',
            'acteMariage',
            'acteDeces',
            'personnes',
            'fichiers' => fn($q) => $q->orderBy('numero_page', 'asc'),
        ])->findOrFail($id);
    }

    /**
     * Génère un identifiant informatique unique et normalisé (ex: ACT-000045).
     */
    public function genererIdentifiantUnique(): string
    {
        $maxId = (int) Acte::max('id_acte');
        return sprintf('ACT-%06d', $maxId + 1);
    }

    /**
     * Détecte si un acte constitue un doublon composite (Section 5).
     */
    public function detecterDoublon(string|TypeActe $type, string $numeroActe, int $annee, int $idRegistre, ?int $exclureId = null): bool
    {
        $valeurType = $type instanceof TypeActe ? $type->value : (string) $type;

        return Acte::where('type', $valeurType)
            ->where('numero_acte', $numeroActe)
            ->where('annee', $annee)
            ->where('id_registre', $idRegistre)
            ->when($exclureId, fn($q) => $q->where('id_acte', '!=', $exclureId))
            ->exists();
    }

    /**
     * Normalise un numéro de jugement au format 4 chiffres avec zéros initiaux (ex: "42" -> "0042").
     */
    protected function formaterNumeroJugement(?string $numero): ?string
    {
        if ($numero === null || trim($numero) === '') {
            return null;
        }
        $propre = trim($numero);
        if (ctype_digit($propre) && strlen($propre) < 4) {
            return str_pad($propre, 4, '0', STR_PAD_LEFT);
        }
        return $propre;
    }

    /**
     * Crée un acte d'état civil complet avec ses tables filles et personnes indexées.
     */
    public function creer(array $donnees, Utilisateur $auteur): Acte
    {
        return DB::transaction(function () use ($donnees, $auteur) {
            $registre = Registre::findOrFail($donnees['id_registre']);

            $identifiantActe = $this->genererIdentifiantUnique();
            $type = $donnees['type'] instanceof TypeActe ? $donnees['type'] : TypeActe::from($donnees['type']);

            $dateJugement = !empty($donnees['date_jugement']) ? $donnees['date_jugement'] : null;
            $anneeJugement = !empty($donnees['annee_jugement']) ? (int) $donnees['annee_jugement'] : null;
            if (!$anneeJugement && $dateJugement) {
                $anneeJugement = (int) date('Y', strtotime($dateJugement));
            }

            // 1. Table principale acte
            $acte = Acte::create([
                'identifiant_acte' => $identifiantActe,
                'id_registre' => $registre->id_registre,
                'type' => $type,
                'numero_acte' => $donnees['numero_acte'],
                'annee' => (int) $donnees['annee'],
                'date_enregistrement' => $donnees['date_enregistrement'] ?? null,
                'page_folio' => $donnees['page_folio'] ?? null,
                'numero_jugement' => $this->formaterNumeroJugement($donnees['numero_jugement'] ?? null),
                'date_jugement' => $dateJugement,
                'annee_jugement' => $anneeJugement,
                'cote' => $registre->cote,
                'etat_verification' => $donnees['etat_verification'],
                'observations' => $donnees['observations'] ?? null,
            ]);

            // 2. Table spécifique selon le type d'acte
            if ($type === TypeActe::NAISSANCE) {
                ActeNaissance::create([
                    'id_acte' => $acte->id_acte,
                    'type' => TypeActe::NAISSANCE->value,
                    'date_naissance' => $donnees['date_naissance'] ?? null,
                    'lieu_naissance' => $donnees['lieu_naissance'] ?? null,
                    'sexe' => $donnees['sexe'] ?? null,
                    'date_declaration' => $donnees['date_declaration'] ?? null,
                ]);

                $this->indexerPersonnesNaissance($acte, $donnees);
            } elseif ($type === TypeActe::MARIAGE) {
                ActeMariage::create([
                    'id_acte' => $acte->id_acte,
                    'type' => TypeActe::MARIAGE->value,
                    'date_mariage' => $donnees['date_mariage'] ?? null,
                    'lieu_mariage' => $donnees['lieu_mariage'] ?? null,
                    'regime_matrimonial' => $donnees['regime_matrimonial'] ?? null,
                    'mentions_marginales' => $donnees['mentions_marginales'] ?? null,
                ]);

                $this->indexerPersonnesMariage($acte, $donnees);
            } elseif ($type === TypeActe::DECES) {
                $heureDeces = !empty($donnees['heure_deces']) ? str_replace('h', ':', trim($donnees['heure_deces'])) : null;
                if ($heureDeces && strlen($heureDeces) === 5) {
                    $heureDeces .= ':00';
                }

                ActeDeces::create([
                    'id_acte' => $acte->id_acte,
                    'type' => TypeActe::DECES->value,
                    'date_deces' => $donnees['date_deces'] ?? null,
                    'heure_deces' => $heureDeces,
                    'lieu_deces' => $donnees['lieu_deces'] ?? null,
                    'profession' => $donnees['profession'] ?? null,
                    'domicile' => $donnees['domicile'] ?? null,
                    'date_declaration' => $donnees['date_declaration_deces'] ?? null,
                ]);

                $this->indexerPersonnesDeces($acte, $donnees);
            }

            // Journalisation de la création
            $this->journalService->operationDocumentaire(
                action: 'INDEXATION_ACTE',
                typeReference: 'ACTE',
                idReference: $acte->id_acte,
                user: $auteur
            );

            return $acte;
        });
    }

    /**
     * Corrige la transcription d'un acte et consigne l'opération dans le journal.
     */
    public function corrigerTranscription(Acte $acte, array $donnees, Utilisateur $auteur): Acte
    {
        return DB::transaction(function () use ($acte, $donnees, $auteur) {
            $idRegistre = isset($donnees['id_registre']) ? (int) $donnees['id_registre'] : $acte->id_registre;
            $cote = $acte->cote;
            if ($idRegistre !== $acte->id_registre) {
                $nouveauRegistre = Registre::find($idRegistre);
                if ($nouveauRegistre) {
                    $cote = $nouveauRegistre->cote;
                }
            }

            $numeroJugement = array_key_exists('numero_jugement', $donnees)
                ? $this->formaterNumeroJugement($donnees['numero_jugement'] ?? null)
                : $acte->numero_jugement;
            $dateJugement = array_key_exists('date_jugement', $donnees)
                ? (!empty($donnees['date_jugement']) ? $donnees['date_jugement'] : null)
                : $acte->date_jugement;
            $anneeJugement = array_key_exists('annee_jugement', $donnees)
                ? (!empty($donnees['annee_jugement']) ? (int) $donnees['annee_jugement'] : null)
                : $acte->annee_jugement;
            if (!$anneeJugement && $dateJugement) {
                $anneeJugement = (int) (is_string($dateJugement) ? date('Y', strtotime($dateJugement)) : $dateJugement->format('Y'));
            }

            // Mise à jour de la table commune
            $acte->update([
                'id_registre' => $idRegistre,
                'cote' => $cote,
                'numero_acte' => $donnees['numero_acte'] ?? $acte->numero_acte,
                'annee' => (int) ($donnees['annee'] ?? $acte->annee),
                'date_enregistrement' => array_key_exists('date_enregistrement', $donnees) ? $donnees['date_enregistrement'] : $acte->date_enregistrement,
                'page_folio' => array_key_exists('page_folio', $donnees) ? $donnees['page_folio'] : $acte->page_folio,
                'numero_jugement' => $numeroJugement,
                'date_jugement' => $dateJugement,
                'annee_jugement' => $anneeJugement,
                'etat_verification' => $donnees['etat_verification'] ?? $acte->etat_verification,
                'observations' => array_key_exists('observations', $donnees) ? $donnees['observations'] : $acte->observations,
            ]);

            // Mise à jour ou création de la table spécifique selon le type d'acte
            if ($acte->type === TypeActe::NAISSANCE) {
                ActeNaissance::updateOrCreate(
                    ['id_acte' => $acte->id_acte],
                    [
                        'type' => TypeActe::NAISSANCE->value,
                        'date_naissance' => array_key_exists('date_naissance', $donnees) ? $donnees['date_naissance'] : $acte->acteNaissance?->date_naissance,
                        'lieu_naissance' => array_key_exists('lieu_naissance', $donnees) ? $donnees['lieu_naissance'] : $acte->acteNaissance?->lieu_naissance,
                        'sexe' => array_key_exists('sexe', $donnees) ? $donnees['sexe'] : $acte->acteNaissance?->sexe,
                        'date_declaration' => array_key_exists('date_declaration', $donnees) ? $donnees['date_declaration'] : $acte->acteNaissance?->date_declaration,
                    ]
                );
                $this->indexerPersonnesNaissance($acte, $donnees);
            } elseif ($acte->type === TypeActe::MARIAGE) {
                ActeMariage::updateOrCreate(
                    ['id_acte' => $acte->id_acte],
                    [
                        'type' => TypeActe::MARIAGE->value,
                        'date_mariage' => array_key_exists('date_mariage', $donnees) ? $donnees['date_mariage'] : $acte->acteMariage?->date_mariage,
                        'lieu_mariage' => array_key_exists('lieu_mariage', $donnees) ? $donnees['lieu_mariage'] : $acte->acteMariage?->lieu_mariage,
                        'regime_matrimonial' => array_key_exists('regime_matrimonial', $donnees) ? $donnees['regime_matrimonial'] : $acte->acteMariage?->regime_matrimonial,
                        'mentions_marginales' => array_key_exists('mentions_marginales', $donnees) ? $donnees['mentions_marginales'] : $acte->acteMariage?->mentions_marginales,
                    ]
                );
                $this->indexerPersonnesMariage($acte, $donnees);
            } elseif ($acte->type === TypeActe::DECES) {
                $heureDeces = array_key_exists('heure_deces', $donnees)
                    ? (!empty($donnees['heure_deces']) ? str_replace('h', ':', trim($donnees['heure_deces'])) : null)
                    : $acte->acteDeces?->heure_deces;
                if ($heureDeces && strlen($heureDeces) === 5) {
                    $heureDeces .= ':00';
                }

                ActeDeces::updateOrCreate(
                    ['id_acte' => $acte->id_acte],
                    [
                        'type' => TypeActe::DECES->value,
                        'date_deces' => array_key_exists('date_deces', $donnees) ? $donnees['date_deces'] : $acte->acteDeces?->date_deces,
                        'heure_deces' => $heureDeces,
                        'lieu_deces' => array_key_exists('lieu_deces', $donnees) ? $donnees['lieu_deces'] : $acte->acteDeces?->lieu_deces,
                        'profession' => array_key_exists('profession', $donnees) ? $donnees['profession'] : $acte->acteDeces?->profession,
                        'domicile' => array_key_exists('domicile', $donnees) ? $donnees['domicile'] : $acte->acteDeces?->domicile,
                        'date_declaration' => array_key_exists('date_declaration_deces', $donnees) ? $donnees['date_declaration_deces'] : $acte->acteDeces?->date_declaration,
                    ]
                );
                $this->indexerPersonnesDeces($acte, $donnees);
            }

            // Journalisation de la correction de transcription (Section 5)
            $this->journalService->operationDocumentaire(
                action: 'CORRECTION_ACTE',
                typeReference: 'ACTE',
                idReference: $acte->id_acte,
                user: $auteur
            );

            return $acte;
        });
    }

    /**
     * Met à jour ou crée la personne indexée associée à un rôle précis sur un acte.
     */
    protected function mettreAJourPersonneRole(Acte $acte, RolePersonne $role, ?string $nom, ?string $prenoms = null, ?string $dateNaissance = null, ?string $lieuNaissance = null): void
    {
        $nomPropre = strtoupper(trim((string) $nom));
        if ($nomPropre === '') {
            return;
        }

        $prenomsPropres = trim((string) $prenoms);

        // Retirer l'ancienne association pour ce rôle précis afin d'éviter l'accumulation de doublons sur le même rôle
        DB::table('acte_personne')
            ->where('id_acte', $acte->id_acte)
            ->where('role', $role->value)
            ->delete();

        $attributsRecherche = [
            'nom' => $nomPropre,
            'prenoms' => $prenomsPropres,
        ];

        $valeurs = [
            'nom' => $nomPropre,
            'prenoms' => $prenomsPropres,
        ];
        if ($dateNaissance) {
            $valeurs['date_naissance'] = $dateNaissance;
        }
        if ($lieuNaissance) {
            $valeurs['lieu_naissance'] = $lieuNaissance;
        }

        $personne = PersonneIndexee::updateOrCreate($attributsRecherche, $valeurs);

        $acte->personnes()->syncWithoutDetaching([
            $personne->id_personne => ['role' => $role->value],
        ]);
    }

    /**
     * Indexation des personnes d'un acte de naissance.
     */
    protected function indexerPersonnesNaissance(Acte $acte, array $donnees): void
    {
        $nomEnfant = $donnees['nom_enfant'] ?? null;
        if (empty($nomEnfant)) {
            $enfantExistant = $acte->personnes()->wherePivot('role', RolePersonne::ENFANT->value)->first();
            $nomEnfant = $enfantExistant?->nom;
        }

        if (!empty($donnees['nom_enfant'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::ENFANT,
                $donnees['nom_enfant'],
                $donnees['prenoms_enfant'] ?? '',
                $donnees['date_naissance'] ?? null,
                $donnees['lieu_naissance'] ?? null
            );
        }

        // Le nom du père reprend automatiquement le nom de l'enfant
        $nomPere = !empty($nomEnfant) ? $nomEnfant : ($donnees['nom_pere'] ?? null);
        if (!empty($donnees['prenoms_pere']) || !empty($donnees['nom_pere'])) {
            if (!empty($nomPere)) {
                $this->mettreAJourPersonneRole(
                    $acte,
                    RolePersonne::PERE,
                    $nomPere,
                    $donnees['prenoms_pere'] ?? ''
                );
            }
        }

        if (!empty($donnees['nom_mere'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::MERE,
                $donnees['nom_mere'],
                $donnees['prenoms_mere'] ?? ''
            );
        }

        if (!empty($donnees['nom_declarant'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::DECLARANT,
                $donnees['nom_declarant'],
                $donnees['prenoms_declarant'] ?? ''
            );
        }
    }

    /**
     * Indexation des personnes d'un acte de mariage.
     */
    protected function indexerPersonnesMariage(Acte $acte, array $donnees): void
    {
        if (!empty($donnees['nom_epoux'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::EPOUX,
                $donnees['nom_epoux'],
                $donnees['prenoms_epoux'] ?? ''
            );
        }

        if (!empty($donnees['nom_epouse'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::EPOUSE,
                $donnees['nom_epouse'],
                $donnees['prenoms_epouse'] ?? ''
            );
        }

        if (!empty($donnees['nom_temoin'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::TEMOIN,
                $donnees['nom_temoin'],
                $donnees['prenoms_temoin'] ?? ''
            );
        }
    }

    /**
     * Indexation des personnes d'un acte de décès.
     */
    protected function indexerPersonnesDeces(Acte $acte, array $donnees): void
    {
        if (!empty($donnees['nom_defunt'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::DEFUNT,
                $donnees['nom_defunt'],
                $donnees['prenoms_defunt'] ?? ''
            );
        }

        if (!empty($donnees['nom_declarant_deces'])) {
            $this->mettreAJourPersonneRole(
                $acte,
                RolePersonne::DECLARANT,
                $donnees['nom_declarant_deces'],
                $donnees['prenoms_declarant_deces'] ?? ''
            );
        }
    }
}
