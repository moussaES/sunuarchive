<?php

namespace App\Modules\Auth\Middleware;

use App\Services\Journal\JournalService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de sécurité : déconnexion automatique après 15 minutes d'inactivité.
 * 
 * Conforme à la section 7 du cahier des charges :
 * Chaque requête authentifiée met à jour l'horodatage de dernière activité.
 * Si l'inactivité dépasse 15 minutes, la session est invalidée et l'événement est consigné.
 */
class VerifierInactivite
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    /**
     * Traite la requête entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->derniere_activite) {
                $derniereActivite = \Carbon\Carbon::parse($user->derniere_activite);
                $inactifDepuisMinutes = abs(now()->diffInMinutes($derniereActivite));

                // Déconnexion après 15 minutes d'inactivité
                if ($inactifDepuisMinutes >= 15) {
                    $this->journalService->deconnexion($user, 'INACTIVITE_15MIN');

                    Auth::logout();
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    }

                    return redirect()->route('connexion')
                        ->with('erreur', 'Votre session a expiré après 15 minutes d\'inactivité. Veuillez vous reconnecter.');
                }
            }

            // Met à jour la dernière activité
            DB::table('utilisateur')
                ->where('id_utilisateur', $user->id_utilisateur)
                ->update(['derniere_activite' => now()]);
        }

        return $next($request);
    }
}
