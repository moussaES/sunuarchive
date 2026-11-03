<?php

/**
 * Matrice des droits et habilitations SIGAEC.
 * Définie dans un fichier de configuration centralisé conformément à la section 6
 * du cahier des charges pour pouvoir être adaptée par la mairie de Tattaguine.
 */
return [
    'profils' => [
        'ADMIN_TECHNIQUE' => [
            'libelle' => 'Administrateur technique',
            'droits' => [
                'utilisateurs.gerer',
                'sauvegardes.gerer',
                'sauvegardes.restaurer',
                'journal.consulter',
            ],
        ],
        'ARCHIVISTE' => [
            'libelle' => 'Archiviste',
            'droits' => [
                'registres.consulter',
                'registres.creer',
                'registres.modifier',
                'actes.consulter',
                'actes.recherche_nominative',
                'actes.creer',
                'actes.modifier',
                'actes.indexer',
                'fichiers.consulter',
                'fichiers.associer',
                'fichiers.remplacer',
                'demandes.saisir',
                'rapports.exporter',
            ],
        ],
        'AGENT_INDEXATION' => [
            'libelle' => 'Agent d\'indexation',
            'droits' => [
                'registres.consulter',
                'actes.consulter',
                'actes.recherche_nominative',
                'actes.creer',
                'actes.modifier',
                'actes.indexer',
                'fichiers.consulter',
                'fichiers.associer', // association seule (pas de remplacement)
            ],
        ],
        'AGENT_CONSULTATION' => [
            'libelle' => 'Agent de consultation',
            'droits' => [
                'registres.consulter',
                'actes.consulter',
                'actes.recherche_nominative',
                'fichiers.consulter',
            ],
        ],
        'RESPONSABLE_HABILITE' => [
            'libelle' => 'Responsable habilité',
            'droits' => [
                'registres.consulter',
                'actes.consulter',
                'actes.recherche_nominative',
                'fichiers.consulter',
                'demandes.decider',
                'journal.consulter',
                'rapports.exporter',
            ],
        ],
    ],
];
