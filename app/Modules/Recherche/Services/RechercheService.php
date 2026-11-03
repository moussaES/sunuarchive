<?php

namespace App\Modules\Recherche\Services;

use App\Enums\RolePersonne;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service métier de recherche simple et multicritères avancée d'actes d'état civil.
 * 
 * Assure :
 * - La recherche simple par mot-clé global (numéro, cote, identifiant, nom selon droits)
 * - La recherche avancée multicritères (type, année, plage, numéro, registre, nom, prénom)
 * - Le contrôle d'accès aux données nominatives conformément aux habilitations
 * - La restitution des métadonnées archivistiques (registre, emplacement physique)
 * - La traçabilité de chaque recherche au journal d'activité.
 */
class RechercheService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Exécute une recherche d'actes selon les filtres fournis et les droits de l'utilisateur.
     */
    public function rechercher(array $criteres, Utilisateur $user, int $parPage = 10): LengthAwarePaginator
    {
        $peutRechercherParNom = $user->aDroit('actes.recherche_nominative');

        $query = Acte::with([
            'registre',
            'personnes',
            'fichierActif',
            'acteNaissance',
            'acteMariage',
            'acteDeces',
        ]);

        // 1. Recherche simple globale (q)
        if (!empty($criteres['q'])) {
            $q = trim($criteres['q']);
            $query->where(function (Builder $sub) use ($q, $peutRechercherParNom) {
                $sub->where('numero_acte', 'like', "%{$q}%")
                    ->orWhere('identifiant_acte', 'like', "%{$q}%")
                    ->orWhere('numero_jugement', 'like', "%{$q}%")
                    ->orWhere('cote', 'like', "%{$q}%")
                    ->orWhereHas('registre', function (Builder $reg) use ($q) {
                        $reg->where('cote', 'like', "%{$q}%")
                            ->orWhere('localisation', 'like', "%{$q}%");
                    });

                if (is_numeric($q) && strlen($q) === 4) {
                    $sub->orWhere('annee', (int) $q)
                        ->orWhere('annee_jugement', (int) $q);
                }

                // Recherche nominative uniquement si habilité
                if ($peutRechercherParNom) {
                    $sub->orWhereHas('personnes', function (Builder $p) use ($q) {
                        $p->where('nom', 'like', "%{$q}%")
                            ->orWhere('prenoms', 'like', "%{$q}%");
                    });
                }
            });
        }

        // 2. Filtres avancés
        if (!empty($criteres['type'])) {
            $query->where('type', $criteres['type']);
        }

        if (!empty($criteres['annee'])) {
            $query->where('annee', (int) $criteres['annee']);
        }

        if (!empty($criteres['annee_debut'])) {
            $query->where('annee', '>=', (int) $criteres['annee_debut']);
        }

        if (!empty($criteres['annee_fin'])) {
            $query->where('annee', '<=', (int) $criteres['annee_fin']);
        }

        if (!empty($criteres['numero_acte'])) {
            $query->where('numero_acte', 'like', '%' . trim($criteres['numero_acte']) . '%');
        }

        if (!empty($criteres['id_registre'])) {
            $query->where('id_registre', (int) $criteres['id_registre']);
        }

        // Filtre présence de copie numérique
        if (isset($criteres['avec_copie']) && $criteres['avec_copie'] !== '') {
            if ($criteres['avec_copie'] === '1') {
                $query->whereHas('fichiers', fn($f) => $f->where('actif', true));
            } elseif ($criteres['avec_copie'] === '0') {
                $query->whereDoesntHave('fichiers', fn($f) => $f->where('actif', true));
            }
        }

        // 3. Filtres nominatifs stricts (nom, prénoms) conditionnés à l'habilitation
        if ($peutRechercherParNom) {
            if (!empty($criteres['nom'])) {
                $nom = strtoupper(trim($criteres['nom']));
                $query->whereHas('personnes', fn(Builder $p) => $p->where('nom', 'like', "%{$nom}%"));
            }

            if (!empty($criteres['prenoms'])) {
                $prenoms = trim($criteres['prenoms']);
                $query->whereHas('personnes', fn(Builder $p) => $p->where('prenoms', 'like', "%{$prenoms}%"));
            }
        }

        // Tri chronologique et par numéro
        $query->orderBy('annee', 'desc')
            ->orderBy('id_registre', 'asc')
            ->orderBy('numero_acte', 'asc');

        $resultats = $query->paginate($parPage)->withQueryString();

        // 4. Traçabilité de l'opération au journal d'audit
        $resumeCriteres = $this->formaterResumeCriteres($criteres);
        $this->journalService->rechercheActes(
            criteres: "CRITERES: {$resumeCriteres} (RÉSULTATS: {$resultats->total()})",
            user: $user
        );

        return $resultats;
    }

    /**
     * Détermine le libellé de la ou des personnes principales mentionnées dans l'acte.
     * Masque les identités si l'utilisateur ne dispose pas du droit nominatif.
     */
    public function resoudrePersonnePrincipale(Acte $acte, Utilisateur $user): string
    {
        if (!$user->aDroit('actes.recherche_nominative')) {
            return '[Donnée nominative réservée]';
        }

        $personnes = $acte->personnes;
        if ($personnes->isEmpty()) {
            return '—';
        }

        if ($acte->type === TypeActe::NAISSANCE) {
            $enfant = $personnes->firstWhere('pivot.role', RolePersonne::ENFANT->value);
            return $enfant ? $enfant->nom_complet : $personnes->first()->nom_complet;
        }

        if ($acte->type === TypeActe::MARIAGE) {
            $epoux = $personnes->firstWhere('pivot.role', RolePersonne::EPOUX->value);
            $epouse = $personnes->firstWhere('pivot.role', RolePersonne::EPOUSE->value);

            if ($epoux && $epouse) {
                return "{$epoux->nom_complet} & {$epouse->nom_complet}";
            }
            return $epoux ? $epoux->nom_complet : ($epouse ? $epouse->nom_complet : $personnes->first()->nom_complet);
        }

        if ($acte->type === TypeActe::DECES) {
            $defunt = $personnes->firstWhere('pivot.role', RolePersonne::DEFUNT->value);
            return $defunt ? $defunt->nom_complet : $personnes->first()->nom_complet;
        }

        return $personnes->first()->nom_complet;
    }

    /**
     * Résume les critères sous forme compacte pour le journal.
     */
    protected function formaterResumeCriteres(array $criteres): string
    {
        $segments = [];
        foreach (['q', 'type', 'annee', 'numero_acte', 'id_registre', 'nom'] as $cle) {
            if (!empty($criteres[$cle])) {
                $segments[] = "{$cle}={$criteres[$cle]}";
            }
        }
        return empty($segments) ? 'TOUS' : implode(';', $segments);
    }
}
