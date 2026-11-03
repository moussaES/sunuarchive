<?php

namespace Database\Seeders;

use App\Models\Acte;
use App\Models\DemandeCommunication;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;

/**
 * Seeder des demandes de communication administrative.
 */
class DemandeCommunicationSeeder extends Seeder
{
    public function run(): void
    {
        $responsable = Utilisateur::where('identifiant', 'responsable1')->first()
            ?? Utilisateur::first();

        $actes = Acte::take(3)->get();

        $demandes = [
            [
                'numero_demande' => 'DEM-0001',
                'id_utilisateur' => $responsable->id_utilisateur,
                'date_demande' => '2026-02-01',
                'service_demandeur' => 'Tribunal d\'Instance de Fatick',
                'objet' => 'Communication d\'un acte de naissance pour procédure successorale',
                'decision' => 'ACCORDEE',
                'date_traitement' => '2026-02-03',
                'acte_index' => 0,
            ],
            [
                'numero_demande' => 'DEM-0002',
                'id_utilisateur' => $responsable->id_utilisateur,
                'date_demande' => '2026-02-15',
                'service_demandeur' => 'Sous-Préfecture de Tattaguine',
                'objet' => 'Vérification administrative d\'un acte de mariage',
                'decision' => 'EN_COURS',
                'date_traitement' => null,
                'acte_index' => 1,
            ],
            [
                'numero_demande' => 'DEM-0003',
                'id_utilisateur' => $responsable->id_utilisateur,
                'date_demande' => '2026-02-20',
                'service_demandeur' => 'Usager sans lien juridique direct',
                'objet' => 'Demande de consultation directe d\'acte de décès',
                'decision' => 'REFUSEE',
                'date_traitement' => '2026-02-22',
                'acte_index' => 2,
            ],
        ];

        foreach ($demandes as $d) {
            $acteAssocie = $actes[$d['acte_index']] ?? null;
            unset($d['acte_index']);

            $demande = DemandeCommunication::updateOrCreate(
                ['numero_demande' => $d['numero_demande']],
                $d
            );

            if ($acteAssocie) {
                $demande->actes()->syncWithoutDetaching([$acteAssocie->id_acte]);
            }
        }
    }
}
