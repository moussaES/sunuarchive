<?php

namespace Database\Seeders;

use App\Models\Sauvegarde;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;

/**
 * Seeder de l'historique des sauvegardes.
 */
class SauvegardeSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Utilisateur::where('identifiant', 'admin.tech')->first()
            ?? Utilisateur::first();

        $sauvegardes = [
            [
                'id_utilisateur' => $admin->id_utilisateur,
                'date_sauvegarde' => '2026-02-10 07:30:00',
                'support' => 'Disque externe sécurisé (USB-01)',
                'taille' => 14582900, // ~14.5 Mo
                'resultat' => 'SUCCES',
                'verifiee' => true,
            ],
            [
                'id_utilisateur' => $admin->id_utilisateur,
                'date_sauvegarde' => '2026-02-17 07:30:00',
                'support' => 'Disque externe sécurisé (USB-02)',
                'taille' => 15240800, // ~15.2 Mo
                'resultat' => 'SUCCES',
                'verifiee' => true,
            ],
        ];

        foreach ($sauvegardes as $data) {
            Sauvegarde::firstOrCreate(
                ['date_sauvegarde' => $data['date_sauvegarde']],
                $data
            );
        }
    }
}
