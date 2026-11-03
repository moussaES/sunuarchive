<?php

namespace Database\Seeders;

use App\Enums\EtatMateriel;
use App\Enums\PresencePhysique;
use App\Enums\TypeActe;
use App\Models\Registre;
use Illuminate\Database\Seeder;

/**
 * Seeder des registres d'archives d'état civil de la Mairie de Tattaguine.
 * 
 * Génère 7 registres représentatifs couvrant les 3 types d'actes et
 * différents états matériels (Bon, Moyen, Dégradé, Très dégradé).
 */
class RegistreSeeder extends Seeder
{
    public function run(): void
    {
        $registres = [
            [
                'cote' => 'EC-NAI-2015-REG001',
                'type' => TypeActe::NAISSANCE,
                'annee_debut' => 2015,
                'annee_fin' => 2015,
                'numero_volume' => '1',
                'nombre_pages' => 120,
                'nombre_actes' => 60,
                'localisation' => 'Salle d\'archives / Armoire A / Étagère 1',
                'etat_materiel' => EtatMateriel::BON,
                'presence' => PresencePhysique::PRESENT,
                'inventaire_complet' => true,
                'observations' => 'Reliure d\'origine en bon état général.',
                'date_inventaire' => '2026-01-15',
            ],
            [
                'cote' => 'EC-NAI-2020-REG002',
                'type' => TypeActe::NAISSANCE,
                'annee_debut' => 2020,
                'annee_fin' => 2020,
                'numero_volume' => '1',
                'nombre_pages' => 100,
                'nombre_actes' => 45,
                'localisation' => 'Salle d\'archives / Armoire A / Étagère 2',
                'etat_materiel' => EtatMateriel::BON,
                'presence' => PresencePhysique::PRESENT,
                'inventaire_complet' => true,
                'observations' => 'Registre complet et vérifié.',
                'date_inventaire' => '2026-02-10',
            ],
            [
                'cote' => 'EC-MAR-2018-REG003',
                'type' => TypeActe::MARIAGE,
                'annee_debut' => 2018,
                'annee_fin' => 2018,
                'numero_volume' => '1',
                'nombre_pages' => 80,
                'nombre_actes' => 30,
                'localisation' => 'Salle d\'archives / Armoire B / Étagère 1',
                'etat_materiel' => EtatMateriel::MOYEN,
                'presence' => PresencePhysique::PRESENT,
                'inventaire_complet' => true,
                'observations' => 'Quelques feuillets jaunis en fin de registre.',
                'date_inventaire' => '2026-01-20',
            ],
            [
                'cote' => 'EC-MAR-2022-REG004',
                'type' => TypeActe::MARIAGE,
                'annee_debut' => 2022,
                'annee_fin' => 2022,
                'numero_volume' => '2',
                'nombre_pages' => 70,
                'nombre_actes' => null,
                'localisation' => 'Salle d\'archives / Armoire B / Étagère 2',
                'etat_materiel' => EtatMateriel::BON,
                'presence' => PresencePhysique::PRESENT,
                'inventaire_complet' => false,
                'observations' => 'Inventaire pièce à pièce en cours de traitement.',
                'date_inventaire' => null,
            ],
            [
                'cote' => 'EC-DEC-2019-REG005',
                'type' => TypeActe::DECES,
                'annee_debut' => 2019,
                'annee_fin' => 2019,
                'numero_volume' => '1',
                'nombre_pages' => 60,
                'nombre_actes' => 25,
                'localisation' => 'Salle d\'archives / Armoire C / Étagère 1',
                'etat_materiel' => EtatMateriel::DEGRADE,
                'presence' => PresencePhysique::A_VERIFIER,
                'inventaire_complet' => true,
                'observations' => 'Traces d\'humidité sur la couverture cartonnée. Consolidation recommandée.',
                'date_inventaire' => '2026-01-25',
            ],
            [
                'cote' => 'EC-DEC-2023-REG006',
                'type' => TypeActe::DECES,
                'annee_debut' => 2023,
                'annee_fin' => 2023,
                'numero_volume' => '1',
                'nombre_pages' => 50,
                'nombre_actes' => null,
                'localisation' => 'Atelier de reliure / Boîte de conservation R4',
                'etat_materiel' => EtatMateriel::TRES_DEGRADE,
                'presence' => PresencePhysique::TEMPORAIREMENT_DEPLACE,
                'inventaire_complet' => false,
                'observations' => 'Déplacé temporairement pour reconditionnement d\'urgence.',
                'date_inventaire' => null,
            ],
            [
                'cote' => 'EC-NAI-2010-REG007',
                'type' => TypeActe::NAISSANCE,
                'annee_debut' => 2010,
                'annee_fin' => 2010,
                'numero_volume' => '1',
                'nombre_pages' => 90,
                'nombre_actes' => null,
                'localisation' => 'Salle d\'archives / Armoire A / Étagère 4',
                'etat_materiel' => EtatMateriel::MOYEN,
                'presence' => PresencePhysique::MANQUANT,
                'inventaire_complet' => false,
                'observations' => 'Signalé manquant lors du récolement décennal.',
                'date_inventaire' => null,
            ],
        ];

        foreach ($registres as $data) {
            Registre::updateOrCreate(
                ['cote' => $data['cote']],
                $data
            );
        }
    }
}
