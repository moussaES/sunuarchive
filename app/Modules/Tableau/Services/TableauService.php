<?php

namespace App\Modules\Tableau\Services;

use App\Enums\EtatMateriel;
use App\Enums\EtatVerification;
use App\Enums\PresencePhysique;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\FichierNumerique;
use App\Models\Registre;
use App\Models\Sauvegarde;
use App\Modules\Fichiers\Services\FichierService;

/**
 * Service métier de calcul des indicateurs et statistiques du Tableau de bord SIGAEC.
 * 
 * Toutes les valeurs sont calculées dynamiquement depuis la base de données (Section 9.2).
 */
class TableauService
{
    public function __construct(
        protected FichierService $fichierService
    ) {}

    /**
     * Calcule l'ensemble des données de synthèse pour l'affichage du tableau de bord.
     */
    public function obtenirDonneesTableau(): array
    {
        // 1. Les 4 indicateurs clés (Section 9.2)
        $totalRegistres = Registre::count();
        $registresInventories = Registre::where('inventaire_complet', true)->count();
        $pourcentageInventaire = $totalRegistres > 0 ? round(($registresInventories / $totalRegistres) * 100, 1) : 0;

        $totalActes = Acte::count();
        $totalFichiersActifs = FichierNumerique::where('actif', true)->count();

        $fichesAVerifier = Acte::whereIn('etat_verification', [
            EtatVerification::A_VERIFIER,
            EtatVerification::ILLISIBLE,
            EtatVerification::INCOMPLET,
        ])->count();

        // 2. Progression de la numérisation par type d'acte (barres menthe/noir)
        $typesActes = [
            'naissance' => TypeActe::NAISSANCE,
            'mariage' => TypeActe::MARIAGE,
            'deces' => TypeActe::DECES,
        ];

        $progression = [];
        foreach ($typesActes as $cle => $enumType) {
            $totalType = Acte::where('type', $enumType)->count();
            $numerisesType = Acte::where('type', $enumType)
                ->whereHas('fichiers', fn($q) => $q->where('actif', true))
                ->count();

            $pct = $totalType > 0 ? round(($numerisesType / $totalType) * 100, 1) : 0;

            $progression[$cle] = [
                'libelle' => $enumType->libelle(),
                'total' => $totalType,
                'numerises' => $numerisesType,
                'pourcentage' => $pct,
            ];
        }

        // 3. Liste des « Registres à surveiller » (état dégradé ou présence à vérifier)
        $registresASurveiller = Registre::whereIn('etat_materiel', [
            EtatMateriel::DEGRADE,
            EtatMateriel::TRES_DEGRADE,
        ])->orWhereIn('presence', [
            PresencePhysique::A_VERIFIER,
            PresencePhysique::MANQUANT,
            PresencePhysique::TEMPORAIREMENT_DEPLACE,
        ])
        ->orderBy('cote', 'asc')
        ->get();

        // 4. Bandeau de contrôle système (Sauvegardes, Restauration, Intégrité)
        $derniereSauvegarde = Sauvegarde::orderByDesc('date_sauvegarde')->first();
        $dernierTestRestauration = Sauvegarde::where('verifiee', true)
            ->orderByDesc('date_sauvegarde')
            ->first();

        $joursDepuisTest = $dernierTestRestauration
            ? abs(now()->diffInDays($dernierTestRestauration->date_sauvegarde))
            : null;

        $rappelTrimestrielRequis = ($joursDepuisTest === null || $joursDepuisTest > 90);

        // Audit d'intégrité rapide des fichiers
        $auditFichiers = $this->fichierService->verifierTousLesFichiers();
        $anomaliesFichiers = $auditFichiers['alteres'] + $auditFichiers['manquants'];

        return [
            'indicateurs' => [
                'total_registres' => $totalRegistres,
                'registres_inventories' => $registresInventories,
                'pourcentage_inventaire' => $pourcentageInventaire,
                'total_actes' => $totalActes,
                'total_fichiers_actifs' => $totalFichiersActifs,
                'fiches_a_verifier' => $fichesAVerifier,
            ],
            'progression' => $progression,
            'registres_a_surveiller' => $registresASurveiller,
            'etat_systeme' => [
                'derniere_sauvegarde' => $derniereSauvegarde,
                'dernier_test_restauration' => $dernierTestRestauration,
                'jours_depuis_test' => $joursDepuisTest,
                'rappel_trimestriel' => $rappelTrimestrielRequis,
                'fichiers_total' => $auditFichiers['total'],
                'fichiers_valides' => $auditFichiers['valides'],
                'fichiers_alteres' => $auditFichiers['alteres'],
                'fichiers_manquants' => $auditFichiers['manquants'],
                'anomalies_fichiers' => $anomaliesFichiers,
            ],
        ];
    }
}
