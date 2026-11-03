<?php

namespace Database\Seeders;

use App\Enums\EtatCompte;
use App\Enums\Profil;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder des utilisateurs pour SIGAEC.
 * 
 * Initialise 5 comptes fictifs représentant les 5 profils d'habilitation
 * de la mairie de Tattaguine, avec mots de passe sécurisés (≥ 12 caractères).
 */
class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        $motDePasseCommun = Hash::make('Pass1234Tattaguine!');

        $utilisateurs = [
            [
                'identifiant' => 'admin.tech',
                'nom_complet' => 'Modou Fall (Admin Tech)',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::ADMIN_TECHNIQUE,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => '2026-01-10',
            ],
            [
                'identifiant' => 'archiviste1',
                'nom_complet' => 'Awa Ndiaye (Archiviste)',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::ARCHIVISTE,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => '2026-01-15',
            ],
            [
                'identifiant' => 'agent.index1',
                'nom_complet' => 'Cheikh Diop (Agent Indexation)',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::AGENT_INDEXATION,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => '2026-02-01',
            ],
            [
                'identifiant' => 'agent.consult1',
                'nom_complet' => 'Fatou Sow (Agent Consultation)',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::AGENT_CONSULTATION,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => '2026-02-10',
            ],
            [
                'identifiant' => 'responsable1',
                'nom_complet' => 'Ousmane Sarr (Responsable Habilité)',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::RESPONSABLE_HABILITE,
                'etat_compte' => EtatCompte::ACTIF,
                'tentatives_echec' => 0,
                'date_creation' => '2026-01-12',
            ],
        ];

        foreach ($utilisateurs as $data) {
            Utilisateur::updateOrCreate(
                ['identifiant' => $data['identifiant']],
                $data
            );
        }

        // Met à jour l'ancien compte admin s'il existait
        $ancienAdmin = Utilisateur::where('identifiant', 'admin')->first();
        if ($ancienAdmin) {
            $ancienAdmin->update([
                'nom_complet' => 'Administrateur Principal',
                'mot_de_passe_hash' => $motDePasseCommun,
                'profil' => Profil::ADMIN_TECHNIQUE,
                'etat_compte' => EtatCompte::ACTIF,
            ]);
        }
    }
}
