<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal de SIGAEC.
 * 
 * Initialise l'intégralité du jeu de données fictives nécessaires au fonctionnement,
 * aux démonstrations et aux tests de recette de la Mairie de Tattaguine.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Exécute les seeders de la base de données.
     */
    public function run(): void
    {
        $this->call([
            UtilisateurSeeder::class,
            RegistreSeeder::class,
            ActeSeeder::class,
            FichierNumeriqueSeeder::class,
            DemandeCommunicationSeeder::class,
            JournalActiviteSeeder::class,
            SauvegardeSeeder::class,
        ]);
    }
}
