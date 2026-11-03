<?php

namespace Database\Seeders;

use App\Enums\EtatVerification;
use App\Enums\RolePersonne;
use App\Enums\TypeActe;
use App\Models\Acte;
use App\Models\ActeDeces;
use App\Models\ActeMariage;
use App\Models\ActeNaissance;
use App\Models\PersonneIndexee;
use App\Models\Registre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder des actes d'état civil pour SIGAEC (Mairie de Tattaguine).
 * 
 * Génère 42 actes fictifs complets (20 naissances, 12 mariages, 10 décès)
 * avec leurs fiches spécifiques, personnes indexées et rôles associés.
 */
class ActeSeeder extends Seeder
{
    public function run(): void
    {
        $regNai2015 = Registre::where('cote', 'EC-NAI-2015-REG001')->first();
        $regNai2020 = Registre::where('cote', 'EC-NAI-2020-REG002')->first();
        $regMar2018 = Registre::where('cote', 'EC-MAR-2018-REG003')->first();
        $regMar2022 = Registre::where('cote', 'EC-MAR-2022-REG004')->first();
        $regDec2019 = Registre::where('cote', 'EC-DEC-2019-REG005')->first();
        $regDec2023 = Registre::where('cote', 'EC-DEC-2023-REG006')->first();

        $noms = ['Faye', 'Sarr', 'Diouf', 'Ndiaye', 'Seck', 'Fall', 'Sow', 'Diallo', 'Cisse', 'Niang', 'Mbodj', 'Gueye', 'Badiane', 'Toure'];
        $prenomsM = ['Mamadou', 'Ibrahima', 'Ousmane', 'Babacar', 'Abdoulaye', 'Cheikh', 'Modou', 'Alioune', 'Lamine', 'Amadou'];
        $prenomsF = ['Fatou', 'Aminata', 'Awa', 'Mariama', 'Khady', 'Aissatou', 'Dieynaba', 'Coumba', 'Astou', 'Ndèye'];
        $villages = ['Tattaguine Sérère', 'Tattaguine Bambara', 'Diakhao', 'Gollere', 'Ndondol', 'Thioffior', 'Ngayokhem', 'Somb'];

        $indexCompteur = 1;

        // =========================================================================
        // 1. ACTES DE NAISSANCE (20 actes : 10 en 2015, 10 en 2020)
        // =========================================================================
        for ($i = 1; $i <= 20; $i++) {
            $annee = ($i <= 10) ? 2015 : 2020;
            $reg = ($i <= 10) ? $regNai2015 : $regNai2020;
            $numeroActe = str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $identifiant = sprintf('ACT-%06d', $indexCompteur++);
            $sexe = ($i % 2 === 0) ? 'Féminin' : 'Masculin';
            $nomEnfant = $noms[($i * 3) % count($noms)];
            $prenomEnfant = ($sexe === 'Féminin')
                ? $prenomsF[$i % count($prenomsF)]
                : $prenomsM[$i % count($prenomsM)];
            $village = $villages[$i % count($villages)];

            $etat = match($i % 5) {
                0 => EtatVerification::VERIFIE,
                1 => EtatVerification::A_VERIFIER,
                2 => EtatVerification::VERIFIE,
                3 => EtatVerification::INCOMPLET,
                default => EtatVerification::VERIFIE,
            };

            $acte = Acte::updateOrCreate(
                ['type' => TypeActe::NAISSANCE, 'numero_acte' => $numeroActe, 'annee' => $annee, 'id_registre' => $reg->id_registre],
                [
                    'identifiant_acte' => $identifiant,
                    'date_enregistrement' => sprintf('%04d-%02d-%02d', $annee, (($i % 12) + 1), min(28, $i + 1)),
                    'page_folio' => 'Folio ' . ($i * 2),
                    'cote' => $reg->cote,
                    'etat_verification' => $etat,
                    'observations' => ($i === 3) ? 'Mention marginale de reconnaissance portée en marge.' : null,
                ]
            );

            // Table spécifique acte_naissance
            ActeNaissance::updateOrCreate(
                ['id_acte' => $acte->id_acte],
                [
                    'type' => TypeActe::NAISSANCE->value,
                    'date_naissance' => sprintf('%04d-%02d-%02d', $annee, (($i % 12) + 1), min(28, $i)),
                    'lieu_naissance' => $village,
                    'sexe' => $sexe,
                    'date_declaration' => sprintf('%04d-%02d-%02d', $annee, (($i % 12) + 1), min(28, $i + 1)),
                ]
            );

            // Personnes liées (Enfant, Père, Mère)
            $enfant = PersonneIndexee::firstOrCreate([
                'nom' => $nomEnfant,
                'prenoms' => $prenomEnfant,
                'lieu_naissance' => $village,
            ]);

            $pere = PersonneIndexee::firstOrCreate([
                'nom' => $nomEnfant,
                'prenoms' => $prenomsM[($i + 2) % count($prenomsM)],
                'lieu_naissance' => $village,
            ]);

            $mere = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($i + 4) % count($noms)],
                'prenoms' => $prenomsF[($i + 3) % count($prenomsF)],
                'lieu_naissance' => $village,
            ]);

            $acte->personnes()->syncWithoutDetaching([
                $enfant->id_personne => ['role' => RolePersonne::ENFANT->value],
                $pere->id_personne => ['role' => RolePersonne::PERE->value],
                $mere->id_personne => ['role' => RolePersonne::MERE->value],
            ]);
        }

        // =========================================================================
        // 2. ACTES DE MARIAGE (12 actes : 6 en 2018, 6 en 2022)
        // =========================================================================
        for ($j = 1; $j <= 12; $j++) {
            $annee = ($j <= 6) ? 2018 : 2022;
            $reg = ($j <= 6) ? $regMar2018 : $regMar2022;
            $numeroActe = str_pad((string)$j, 4, '0', STR_PAD_LEFT);
            $identifiant = sprintf('ACT-%06d', $indexCompteur++);
            $village = $villages[($j + 2) % count($villages)];

            $acte = Acte::updateOrCreate(
                ['type' => TypeActe::MARIAGE, 'numero_acte' => $numeroActe, 'annee' => $annee, 'id_registre' => $reg->id_registre],
                [
                    'identifiant_acte' => $identifiant,
                    'date_enregistrement' => sprintf('%04d-%02d-%02d', $annee, (($j % 12) + 1), min(28, $j + 3)),
                    'page_folio' => 'Folio ' . ($j * 3),
                    'cote' => $reg->cote,
                    'etat_verification' => ($j === 2) ? EtatVerification::A_VERIFIER : EtatVerification::VERIFIE,
                    'observations' => ($j === 1) ? 'Option monogamie avec séparation de biens.' : null,
                ]
            );

            ActeMariage::updateOrCreate(
                ['id_acte' => $acte->id_acte],
                [
                    'type' => TypeActe::MARIAGE->value,
                    'date_mariage' => sprintf('%04d-%02d-%02d', $annee, (($j % 12) + 1), min(28, $j + 2)),
                    'lieu_mariage' => 'Centre d\'état civil de ' . $village,
                    'regime_matrimonial' => ($j % 2 === 0) ? 'Polygamie (deux épouses)' : 'Monogamie',
                    'mentions_marginales' => null,
                ]
            );

            $epoux = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($j + 1) % count($noms)],
                'prenoms' => $prenomsM[($j + 1) % count($prenomsM)],
            ]);

            $epouse = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($j + 5) % count($noms)],
                'prenoms' => $prenomsF[($j + 2) % count($prenomsF)],
            ]);

            $temoin = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($j + 7) % count($noms)],
                'prenoms' => $prenomsM[($j + 4) % count($prenomsM)],
            ]);

            $acte->personnes()->syncWithoutDetaching([
                $epoux->id_personne => ['role' => RolePersonne::EPOUX->value],
                $epouse->id_personne => ['role' => RolePersonne::EPOUSE->value],
                $temoin->id_personne => ['role' => RolePersonne::TEMOIN->value],
            ]);
        }

        // =========================================================================
        // 3. ACTES DE DÉCÈS (10 actes : 5 en 2019, 5 en 2023)
        // =========================================================================
        for ($k = 1; $k <= 10; $k++) {
            $annee = ($k <= 5) ? 2019 : 2023;
            $reg = ($k <= 5) ? $regDec2019 : $regDec2023;
            $numeroActe = str_pad((string)$k, 4, '0', STR_PAD_LEFT);
            $identifiant = sprintf('ACT-%06d', $indexCompteur++);
            $village = $villages[($k + 4) % count($villages)];

            $acte = Acte::updateOrCreate(
                ['type' => TypeActe::DECES, 'numero_acte' => $numeroActe, 'annee' => $annee, 'id_registre' => $reg->id_registre],
                [
                    'identifiant_acte' => $identifiant,
                    'date_enregistrement' => sprintf('%04d-%02d-%02d', $annee, (($k % 12) + 1), min(28, $k + 5)),
                    'page_folio' => 'Folio ' . ($k * 2),
                    'cote' => $reg->cote,
                    'etat_verification' => ($k === 3) ? EtatVerification::ILLISIBLE : EtatVerification::VERIFIE,
                    'observations' => ($k === 3) ? 'Mention de l\'heure difficilement lisible à l\'encre délavée.' : null,
                ]
            );

            ActeDeces::updateOrCreate(
                ['id_acte' => $acte->id_acte],
                [
                    'type' => TypeActe::DECES->value,
                    'date_deces' => sprintf('%04d-%02d-%02d', $annee, (($k % 12) + 1), min(28, $k + 4)),
                    'heure_deces' => sprintf('%02d:30:00', 8 + ($k % 10)),
                    'lieu_deces' => $village,
                    'profession' => ($k % 2 === 0) ? 'Agriculteur' : 'Commerçant',
                    'domicile' => $village,
                    'date_declaration' => sprintf('%04d-%02d-%02d', $annee, (($k % 12) + 1), min(28, $k + 5)),
                ]
            );

            $defunt = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($k + 2) % count($noms)],
                'prenoms' => $prenomsM[($k + 3) % count($prenomsM)],
            ]);

            $declarant = PersonneIndexee::firstOrCreate([
                'nom' => $noms[($k + 6) % count($noms)],
                'prenoms' => $prenomsM[($k + 5) % count($prenomsM)],
            ]);

            $acte->personnes()->syncWithoutDetaching([
                $defunt->id_personne => ['role' => RolePersonne::DEFUNT->value],
                $declarant->id_personne => ['role' => RolePersonne::DECLARANT->value],
            ]);
        }
    }
}
