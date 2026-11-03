<?php

namespace App\Modules\Registres\Services;

use App\Enums\EtatMateriel;
use App\Enums\PresencePhysique;
use App\Enums\TypeActe;
use App\Models\Registre;
use App\Models\Utilisateur;
use App\Services\Journal\JournalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service métier du module Registres (Mairie de Tattaguine).
 * 
 * Centralise l'inventaire archivistique, la recherche multicritère,
 * les contrôles de cohérence et l'export CSV officiel.
 */
class RegistreService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Retourne la requête filtrée des registres avec comptage des actes indexés.
     * 
     * @param array<string, mixed> $filtres
     * @return Builder
     */
    public function construireRequete(array $filtres = []): Builder
    {
        $query = Registre::query()->withCount('actes');

        if (!empty($filtres['type'])) {
            $query->where('type', $filtres['type']);
        }

        if (!empty($filtres['etat_materiel'])) {
            $query->where('etat_materiel', $filtres['etat_materiel']);
        }

        if (!empty($filtres['presence'])) {
            $query->where('presence', $filtres['presence']);
        }

        if (isset($filtres['inventaire_complet']) && $filtres['inventaire_complet'] !== '') {
            $query->where('inventaire_complet', (bool)$filtres['inventaire_complet']);
        }

        if (!empty($filtres['recherche'])) {
            $terme = '%' . trim($filtres['recherche']) . '%';
            $query->where(function (Builder $q) use ($terme) {
                $q->where('cote', 'LIKE', $terme)
                  ->orWhere('localisation', 'LIKE', $terme)
                  ->orWhere('observations', 'LIKE', $terme);
            });
        }

        return $query->orderBy('annee_debut', 'desc')->orderBy('cote', 'asc');
    }

    /**
     * Liste tous les registres correspondant aux filtres.
     * 
     * @param array<string, mixed> $filtres
     * @return Collection<int, Registre>
     */
    public function lister(array $filtres = []): Collection
    {
        return $this->construireRequete($filtres)->get();
    }

    /**
     * Liste les registres avec pagination.
     * 
     * @param array<string, mixed> $filtres
     * @param int $parPage
     * @return LengthAwarePaginator
     */
    public function paginer(array $filtres = [], int $parPage = 10): LengthAwarePaginator
    {
        return $this->construireRequete($filtres)->paginate($parPage)->withQueryString();
    }

    /**
     * Recherche un registre par son identifiant unique.
     */
    public function trouverParId(int $id): Registre
    {
        return Registre::withCount('actes')->findOrFail($id);
    }

    /**
     * Enregistre un nouveau registre papier et journalise l'opération.
     */
    public function creer(array $donnees, Utilisateur $auteur): Registre
    {
        $registre = Registre::create($donnees);

        $this->journalService->operationDocumentaire(
            action: 'CREATION_REGISTRE',
            typeReference: 'REGISTRE',
            idReference: $registre->id_registre,
            user: $auteur
        );

        return $registre;
    }

    /**
     * Met à jour une fiche registre et trace la modification.
     */
    public function mettreAJour(Registre $registre, array $donnees, Utilisateur $auteur): Registre
    {
        $registre->update($donnees);

        $this->journalService->operationDocumentaire(
            action: 'MODIFICATION_REGISTRE',
            typeReference: 'REGISTRE',
            idReference: $registre->id_registre,
            user: $auteur
        );

        return $registre;
    }

    /**
     * Génère le flux CSV de l'inventaire des registres (conforme Section 9.3 et 11.10).
     * 
     * @param array<string, mixed> $filtres
     * @param Utilisateur|null $auteur
     * @return string Contenu CSV encodé en UTF-8 avec BOM
     */
    public function exporterCsv(array $filtres = [], ?Utilisateur $auteur = null): string
    {
        $registres = $this->lister($filtres);

        $handle = fopen('php://temp', 'r+');

        // Écriture du BOM UTF-8 pour ouverture directe sous Excel
        fputs($handle, "\xEF\xBB\xBF");

        // En-têtes CSV
        fputcsv($handle, [
            'Cote archivistique',
            'Type d\'acte',
            'Année début',
            'Année fin',
            'Volume',
            'Nombre de pages',
            'Actes répertoriés',
            'Actes indexés système',
            'Emplacement physique',
            'État matériel',
            'Présence physique',
            'Inventaire complet',
            'Date du dernier contrôle',
            'Observations archivistiques',
        ], ';');

        foreach ($registres as $reg) {
            fputcsv($handle, [
                $reg->cote,
                $reg->type instanceof TypeActe ? $reg->type->libelle() : $reg->type,
                $reg->annee_debut,
                $reg->annee_fin,
                $reg->numero_volume ?? '1',
                $reg->nombre_pages ?? '',
                $reg->nombre_actes ?? 'Non vérifié',
                $reg->actes_count ?? 0,
                $reg->localisation ?? 'Non renseigné',
                $reg->etat_materiel instanceof EtatMateriel ? $reg->etat_materiel->libelle() : $reg->etat_materiel,
                $reg->presence instanceof PresencePhysique ? $reg->presence->libelle() : $reg->presence,
                $reg->estInventorie() ? 'Oui (Vérifié)' : 'Non (En cours)',
                $reg->date_inventaire ? $reg->date_inventaire->format('d/m/Y') : 'Non contrôlé',
                $reg->observations ?? '',
            ], ';');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($auteur) {
            $this->journalService->operationDocumentaire(
                action: 'EXPORT_CSV_REGISTRES',
                typeReference: 'REGISTRE',
                idReference: null,
                user: $auteur
            );
        }

        return $csv;
    }
}
