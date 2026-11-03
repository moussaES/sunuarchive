<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration permettant la suppression des comptes utilisateurs par l'administrateur
 * tout en conservant l'historique d'audit et les références dans le journal immuable.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Suppression de la contrainte bloquante sur le journal d'activité
        if (Schema::hasTable('journal_activite')) {
            try {
                DB::statement('ALTER TABLE journal_activite DROP FOREIGN KEY fk_journal_utilisateur');
            } catch (\Throwable $e) {
                // Déjà supprimée
            }
        }

        // 2. Suppression de la contrainte bloquante sur les demandes de communication
        if (Schema::hasTable('demande_communication')) {
            try {
                DB::statement('ALTER TABLE demande_communication DROP FOREIGN KEY fk_demande_utilisateur');
            } catch (\Throwable $e) {
                // Déjà supprimée
            }
            try {
                DB::statement('ALTER TABLE demande_communication MODIFY id_utilisateur INT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Colonne déjà nullable
            }
        }

        // 3. Suppression de la contrainte bloquante sur les sauvegardes
        if (Schema::hasTable('sauvegarde')) {
            try {
                DB::statement('ALTER TABLE sauvegarde DROP FOREIGN KEY fk_sauvegarde_utilisateur');
            } catch (\Throwable $e) {
                // Déjà supprimée
            }
            try {
                DB::statement('ALTER TABLE sauvegarde MODIFY id_utilisateur INT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // Colonne déjà nullable
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de réversion stricte requise
    }
};
