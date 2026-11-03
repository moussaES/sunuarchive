<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ajoutant les informations optionnelles de jugement supplétif sur la table acte :
 * - numero_jugement
 * - date_jugement
 * - annee_jugement
 * 
 * Si ces champs ne sont pas renseignés (NULL), l'acte ne fait pas l'objet d'un jugement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('acte')) {
            Schema::table('acte', function (Blueprint $table) {
                if (!Schema::hasColumn('acte', 'numero_jugement')) {
                    $table->string('numero_jugement', 50)->nullable()->after('page_folio');
                }
                if (!Schema::hasColumn('acte', 'date_jugement')) {
                    $table->date('date_jugement')->nullable()->after('numero_jugement');
                }
                if (!Schema::hasColumn('acte', 'annee_jugement')) {
                    $table->unsignedSmallInteger('annee_jugement')->nullable()->after('date_jugement');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('acte')) {
            Schema::table('acte', function (Blueprint $table) {
                $cols = [];
                foreach (['numero_jugement', 'date_jugement', 'annee_jugement'] as $col) {
                    if (Schema::hasColumn('acte', $col)) {
                        $cols[] = $col;
                    }
                }
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
