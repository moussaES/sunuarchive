<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Fournisseur de services pour l'architecture modulaire de SIGAEC.
 * 
 * Découvre et enregistre automatiquement les routes et composants
 * de chaque module métier (Auth, Registres, Actes, Fichiers, Recherche, etc.).
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Enregistre les liaisons de conteneur propres aux modules.
     */
    public function register(): void
    {
        //
    }

    /**
     * Charge automatiquement les fichiers routes.php de chaque module.
     */
    public function boot(): void
    {
        $modulesPath = app_path('Modules');

        if (!is_dir($modulesPath)) {
            return;
        }

        $modules = array_filter(scandir($modulesPath), function (string $item) use ($modulesPath): bool {
            return !in_array($item, ['.', '..'], true) && is_dir($modulesPath . DIRECTORY_SEPARATOR . $item);
        });

        foreach ($modules as $module) {
            $routesFile = $modulesPath . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'routes.php';
            if (file_exists($routesFile)) {
                Route::middleware('web')->group($routesFile);
            }
        }
    }
}
