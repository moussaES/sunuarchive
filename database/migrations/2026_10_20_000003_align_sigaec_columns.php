<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration d'alignement des colonnes du schéma SIGAEC (Section 5 du prompt).
 * 
 * Ajoute les colonnes manquantes issues des spécifications détaillées :
 * - utilisateur : nom_complet, derniere_activite
 * - registre : inventaire_complet
 * - acte : identifiant_acte
 * - fichier_numerique : remplace_fichier_id, justification_remplacement
 * - journal_activite : identifiant_saisi
 */
return new class extends Migration
{
    /**
     * Exécute les migrations.
     */
    public function up(): void
    {
        // 1. Table utilisateur
        if (Schema::hasTable('utilisateur')) {
            Schema::table('utilisateur', function (Blueprint $table) {
                if (!Schema::hasColumn('utilisateur', 'nom_complet')) {
                    $table->string('nom_complet', 150)->nullable()->after('identifiant');
                }
                if (!Schema::hasColumn('utilisateur', 'derniere_activite')) {
                    $table->dateTime('derniere_activite')->nullable()->after('tentatives_echec');
                }
            });
        }

        // 2. Table registre
        if (Schema::hasTable('registre')) {
            Schema::table('registre', function (Blueprint $table) {
                if (!Schema::hasColumn('registre', 'inventaire_complet')) {
                    $table->boolean('inventaire_complet')->default(false)->after('presence');
                }
            });
        }

        // 3. Table acte
        if (Schema::hasTable('acte')) {
            Schema::table('acte', function (Blueprint $table) {
                if (!Schema::hasColumn('acte', 'identifiant_acte')) {
                    $table->string('identifiant_acte', 30)->nullable()->unique()->after('id_acte');
                }
            });
        }

        // 4. Table fichier_numerique
        if (Schema::hasTable('fichier_numerique')) {
            Schema::table('fichier_numerique', function (Blueprint $table) {
                if (!Schema::hasColumn('fichier_numerique', 'remplace_fichier_id')) {
                    $table->unsignedInteger('remplace_fichier_id')->nullable()->after('actif');
                }
                if (!Schema::hasColumn('fichier_numerique', 'justification_remplacement')) {
                    $table->text('justification_remplacement')->nullable()->after('remplace_fichier_id');
                }
            });
        }

        // 5. Table journal_activite
        if (Schema::hasTable('journal_activite')) {
            // Permet l'enregistrement des échecs de connexion pour identifiant inconnu (Section 5)
            DB::statement('ALTER TABLE journal_activite MODIFY id_utilisateur INT UNSIGNED NULL');

            Schema::table('journal_activite', function (Blueprint $table) {
                if (!Schema::hasColumn('journal_activite', 'identifiant_saisi')) {
                    $table->string('identifiant_saisi', 100)->nullable()->after('id_utilisateur');
                }
            });
        }
    }

    /**
     * Annule les migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('utilisateur')) {
            Schema::table('utilisateur', function (Blueprint $table) {
                $table->dropColumn(['nom_complet', 'derniere_activite']);
            });
        }
        if (Schema::hasTable('registre')) {
            Schema::table('registre', function (Blueprint $table) {
                $table->dropColumn('inventaire_complet');
            });
        }
        if (Schema::hasTable('acte')) {
            Schema::table('acte', function (Blueprint $table) {
                $table->dropColumn('identifiant_acte');
            });
        }
        if (Schema::hasTable('fichier_numerique')) {
            Schema::table('fichier_numerique', function (Blueprint $table) {
                $table->dropColumn(['remplace_fichier_id', 'justification_remplacement']);
            });
        }
        if (Schema::hasTable('journal_activite')) {
            Schema::table('journal_activite', function (Blueprint $table) {
                $table->dropColumn('identifiant_saisi');
            });
        }
    }
};
