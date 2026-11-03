<?php

namespace App\Modules\Auth\Middleware;

use App\Services\Journal\JournalService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de contrôle d'habilitation unifiée SIGAEC.
 * 
 * Vérifie que l'agent authentifié dispose du droit requis dans config/droits.php.
 * Si le droit est absent : renvoie une réponse HTTP 403 et consigne obligatoirement
 * un événement REFUSE dans le journal d'activité (Section 6).
 */
class VerifierHabilitation
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Traite la requête entrante.
     */
    public function handle(Request $request, Closure $next, string $droit): Response
    {
        $user = $request->user();

        if (!$user || !$user->aDroit($droit)) {
            $this->journalService->accesRefuse("DROIT:{$droit} SUR {$request->path()}", $user);

            abort(403, "Accès refusé : votre profil ne dispose pas de l'habilitation requise ({$droit}).");
        }

        return $next($request);
    }
}
