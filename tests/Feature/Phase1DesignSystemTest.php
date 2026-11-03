<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Tests de validation de la Phase 1 (Design system vert menthe, composants Blade et modales).
 */
class Phase1DesignSystemTest extends TestCase
{
    /**
     * Vérifie que la page de styleguide se charge avec succès (HTTP 200).
     */
    public function test_la_page_styleguide_est_accessible(): void
    {
        $response = $this->get('/styleguide');
        $response->assertStatus(200);
    }

    /**
     * Vérifie la présence du bandeau légal permanent obligatoire.
     */
    public function test_le_bandeau_officiel_est_affiche(): void
    {
        $response = $this->get('/styleguide');
        $response->assertSee('Copie de consultation, le registre papier reste le document de référence');
    }

    /**
     * Vérifie le rendu des 4 classes de statuts typographiques sans couleur chromatique tiers.
     */
    public function test_les_quatre_statuts_typographiques_sont_rendus(): void
    {
        $response = $this->get('/styleguide');
        $response->assertSee('statut--positif');
        $response->assertSee('statut--attente');
        $response->assertSee('statut--attention');
        $response->assertSee('statut--critique');
    }

    /**
     * Vérifie la présence du composant de fenêtre modale / pop-up pour formulaire.
     */
    public function test_la_fenetre_modale_est_presente_dans_le_rendu(): void
    {
        $response = $this->get('/styleguide');
        $response->assertSee('sigaec-modal');
        $response->assertSee('data-modal-open="modal-demo"', false);
        $response->assertSee('data-modal-close="modal-demo"', false);
    }

    /**
     * Vérifie que la commande console valide la conformité stricte de la charte monochrome.
     */
    public function test_la_commande_de_verification_du_design_reussit(): void
    {
        $exitCode = Artisan::call('sigaec:verifier-design');
        $this->assertEquals(0, $exitCode, "Le CSS ne doit comporter aucune couleur autre que #3EB489, #FFFFFF, #111111.");
    }
}
