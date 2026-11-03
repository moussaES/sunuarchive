<?php

namespace Database\Seeders;

use App\Models\JournalActivite;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder du journal d'activité SIGAEC (Traçabilité obligatoire).
 * 
 * Enregistre des événements types sans jamais contenir le texte intégral
 * d'un acte ni de données nominatives.
 */
class JournalActiviteSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Utilisateur::where('identifiant', 'admin.tech')->first() ?? Utilisateur::first();
        $archiviste = Utilisateur::where('identifiant', 'archiviste1')->first() ?? $admin;

        $evenements = [
            [
                'id_utilisateur' => $admin->id_utilisateur,
                'identifiant_saisi' => 'admin.tech',
                'date_heure' => '2026-02-01 08:30:15',
                'operation' => 'CONNEXION',
                'type_reference' => 'SYSTEME',
                'id_reference' => null,
                'resultat' => 'REUSSI',
            ],
            [
                'id_utilisateur' => $archiviste->id_utilisateur,
                'identifiant_saisi' => 'archiviste1',
                'date_heure' => '2026-02-01 09:15:00',
                'operation' => 'CREATION_REGISTRE',
                'type_reference' => 'REGISTRE',
                'id_reference' => 1,
                'resultat' => 'REUSSI',
            ],
            [
                'id_utilisateur' => $archiviste->id_utilisateur,
                'identifiant_saisi' => 'archiviste1',
                'date_heure' => '2026-02-01 10:00:22',
                'operation' => 'INDEXATION_ACTE',
                'type_reference' => 'ACTE',
                'id_reference' => 1,
                'resultat' => 'REUSSI',
            ],
            [
                'id_utilisateur' => null,
                'identifiant_saisi' => 'inconnu_test',
                'date_heure' => '2026-02-02 11:45:10',
                'operation' => 'TENTATIVE_CONNEXION',
                'type_reference' => 'SECURITE',
                'id_reference' => null,
                'resultat' => 'ECHEC',
            ],
            [
                'id_utilisateur' => $archiviste->id_utilisateur,
                'identifiant_saisi' => 'archiviste1',
                'date_heure' => '2026-02-02 14:20:00',
                'operation' => 'CONSULTATION_FICHIER',
                'type_reference' => 'FICHIER',
                'id_reference' => 1,
                'resultat' => 'ACCORDE',
            ],
        ];

        foreach ($evenements as $evt) {
            // Le journal étant en écriture seule (triggers MySQL), on n'exécute pas d'UPDATE
            $existe = DB::table('journal_activite')
                ->where('operation', $evt['operation'])
                ->where('date_heure', $evt['date_heure'])
                ->exists();

            if (!$existe) {
                DB::table('journal_activite')->insert($evt);
            }
        }
    }
}
