@extends('layouts.app')

@section('titre', 'Administration générale & Sécurité — Mairie de Tattaguine')

@section('content')
    {{-- En-tête de la page d'administration --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="titre-page" style="margin-bottom: 4px;">Administration & Sécurité du système</h1>
            <p style="margin: 0; font-size: 14px; font-weight: 600;">
                Supervision du journal d'activité en écriture seule, sauvegardes locales, comptes agents et réquisitions.
            </p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            @if($onglet === 'sauvegardes' && $peutGererSauvegardes)
                <button type="button" class="btn btn-primary" data-modal-open="modal-sauvegarde-manuelle">
                    💾 Lancer une sauvegarde
                </button>
                <button type="button" class="btn btn-secondary" data-modal-open="modal-test-restauration">
                    🔍 Tester une restauration
                </button>
            @elseif($onglet === 'utilisateurs' && $peutGererUsers)
                <button type="button" class="btn btn-primary" data-modal-open="modal-nouvel-utilisateur">
                    + Nouvel agent
                </button>
            @elseif($onglet === 'demandes' && auth()->user()->aDroit('demandes.saisir'))
                <button type="button" class="btn btn-primary" data-modal-open="modal-nouvelle-demande">
                    + Nouvelle demande
                </button>
            @endif
        </div>
    </div>

    {{-- Onglets de navigation administrative --}}
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--noir); margin-bottom: 24px; flex-wrap: wrap;" role="tablist" aria-label="Sections d'administration">
        @if($peutVoirJournal)
            <a href="{{ route('admin.index', ['onglet' => 'journal']) }}"
               class="btn {{ $onglet === 'journal' ? 'btn-primary' : 'btn-secondary' }}"
               style="text-decoration: none; min-height: 40px; padding: 8px 18px;"
               role="tab"
               aria-selected="{{ $onglet === 'journal' ? 'true' : 'false' }}">
                📜 Journal d'activité (Audit)
            </a>
        @endif

        @if($peutGererSauvegardes)
            <a href="{{ route('admin.index', ['onglet' => 'sauvegardes']) }}"
               class="btn {{ $onglet === 'sauvegardes' ? 'btn-primary' : 'btn-secondary' }}"
               style="text-decoration: none; min-height: 40px; padding: 8px 18px;"
               role="tab"
               aria-selected="{{ $onglet === 'sauvegardes' ? 'true' : 'false' }}">
                💾 Sauvegardes & Restauration
            </a>
        @endif

        @if($peutGererUsers)
            <a href="{{ route('admin.index', ['onglet' => 'utilisateurs']) }}"
               class="btn {{ $onglet === 'utilisateurs' ? 'btn-primary' : 'btn-secondary' }}"
               style="text-decoration: none; min-height: 40px; padding: 8px 18px;"
               role="tab"
               aria-selected="{{ $onglet === 'utilisateurs' ? 'true' : 'false' }}">
                👥 Comptes agents ({{ $utilisateurs->count() }})
            </a>
        @endif

        @if($peutDeciderDemandes || auth()->user()->aDroit('demandes.saisir'))
            <a href="{{ route('admin.index', ['onglet' => 'demandes']) }}"
               class="btn {{ $onglet === 'demandes' ? 'btn-primary' : 'btn-secondary' }}"
               style="text-decoration: none; min-height: 40px; padding: 8px 18px;"
               role="tab"
               aria-selected="{{ $onglet === 'demandes' ? 'true' : 'false' }}">
                📋 Demandes de communication ({{ $demandes->count() }})
            </a>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- ONGLET 1 : JOURNAL D'ACTIVITÉ EN ÉCRITURE SEULE                          --}}
    {{-- ========================================================================= --}}
    @if($onglet === 'journal' && $peutVoirJournal)
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 14px 18px; margin-bottom: 20px;">
            <div style="font-weight: 800; font-size: 14px;">
                Protection juridique & Immuabilité de la trace d'audit (Section 6 & 9.4)
            </div>
            <div style="font-size: 13px; margin-top: 4px;">
                Ce registre d'audit est scellé en écriture seule par déclencheur de base de données (trigger MySQL). Aucune entrée ne peut être modifiée ni purgée. Toute consultation, tentative de connexion et altération documentaire y est enregistrée en continu.
            </div>
        </div>

        {{-- Barre de filtres du journal --}}
        <div style="border: 1px solid var(--noir); background-color: var(--blanc); padding: 16px; margin-bottom: 24px;">
            <form action="{{ route('admin.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: flex-end;">
                <input type="hidden" name="onglet" value="journal">

                <div>
                    <label for="filtre_date_debut" class="form-label" style="font-size: 12px; margin-bottom: 2px;">Date début :</label>
                    <input type="date" name="date_debut" id="filtre_date_debut" class="form-control" value="{{ request('date_debut') }}" style="min-height: 38px; padding: 6px 10px; font-size: 13px;">
                </div>

                <div>
                    <label for="filtre_date_fin" class="form-label" style="font-size: 12px; margin-bottom: 2px;">Date fin :</label>
                    <input type="date" name="date_fin" id="filtre_date_fin" class="form-control" value="{{ request('date_fin') }}" style="min-height: 38px; padding: 6px 10px; font-size: 13px;">
                </div>

                <div>
                    <label for="filtre_identifiant" class="form-label" style="font-size: 12px; margin-bottom: 2px;">Agent / Identifiant :</label>
                    <input type="text" name="identifiant" id="filtre_identifiant" class="form-control" placeholder="Ex: archiviste1" value="{{ request('identifiant') }}" style="min-height: 38px; padding: 6px 10px; font-size: 13px;">
                </div>

                <div>
                    <label for="filtre_operation" class="form-label" style="font-size: 12px; margin-bottom: 2px;">Opération :</label>
                    <input type="text" name="operation" id="filtre_operation" class="form-control" placeholder="Ex: CONNEXION, ACTE..." value="{{ request('operation') }}" style="min-height: 38px; padding: 6px 10px; font-size: 13px;">
                </div>

                <div>
                    <label for="filtre_resultat" class="form-label" style="font-size: 12px; margin-bottom: 2px;">Résultat :</label>
                    <select name="resultat" id="filtre_resultat" class="form-select" style="min-height: 38px; padding: 6px 10px; font-size: 13px;">
                        <option value="">Tous les résultats</option>
                        <option value="REUSSI" {{ request('resultat') === 'REUSSI' ? 'selected' : '' }}>REUSSI (Opération conforme)</option>
                        <option value="ACCORDE" {{ request('resultat') === 'ACCORDE' ? 'selected' : '' }}>ACCORDE (Consultation autorisée)</option>
                        <option value="ECHEC" {{ request('resultat') === 'ECHEC' ? 'selected' : '' }}>ECHEC (Erreur mot de passe)</option>
                        <option value="REFUSE" {{ request('resultat') === 'REFUSE' ? 'selected' : '' }}>REFUSE (Accès interdit 403)</option>
                    </select>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="min-height: 38px; padding: 6px 14px; font-size: 13px; width: 100%;">
                        Filtrer
                    </button>
                    @if(request()->hasAny(['date_debut', 'date_fin', 'identifiant', 'operation', 'resultat']))
                        <a href="{{ route('admin.index', ['onglet' => 'journal']) }}" class="btn btn-secondary" style="min-height: 38px; padding: 6px 10px; font-size: 13px; text-decoration: none;" title="Effacer les filtres">
                            ✕
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tableau des événements --}}
        @if($journal && $journal->count() > 0)
            <div class="table-responsive">
                <table class="table-sigaec" aria-label="Journal d'audit de sécurité">
                    <thead>
                        <tr>
                            <th style="width: 170px;">Date & Heure</th>
                            <th>Agent municipal</th>
                            <th style="width: 120px;">Adresse IP</th>
                            <th>Opération tracée</th>
                            <th>Réf. documentaire</th>
                            <th style="width: 110px; text-align: center;">Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($journal as $entree)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; font-size: 13px;">
                                        {{ $entree->date_heure ? $entree->date_heure->format('d/m/Y H:i:s') : '—' }}
                                    </div>
                                    <div style="font-size: 11px;">#{{ $entree->id_journal }}</div>
                                </td>
                                <td>
                                    @if($entree->utilisateur)
                                        <div style="font-weight: 700;">{{ $entree->utilisateur->nom_complet }}</div>
                                        <div style="font-size: 11px;">
                                            {{ $entree->utilisateur->identifiant }} ({{ $entree->utilisateur->profil->libelle() }})
                                        </div>
                                    @elseif($entree->identifiant_saisi)
                                        <div style="font-weight: 700;">Saisie : {{ $entree->identifiant_saisi }}</div>
                                        <div style="font-size: 11px; font-style: italic;">Non authentifié</div>
                                    @else
                                        <div style="font-style: italic;">Système interne</div>
                                    @endif
                                </td>
                                <td>
                                    <code style="font-size: 12px; font-weight: 700;">{{ $entree->adresse_ip ?? '127.0.0.1' }}</code>
                                </td>
                                <td>
                                    <span style="border: 1px solid var(--noir); padding: 2px 6px; font-size: 11px; font-weight: 800; background-color: var(--blanc);">
                                        {{ $entree->operation }}
                                    </span>
                                </td>
                                <td>
                                    @if($entree->type_reference && $entree->id_reference)
                                        <div style="font-size: 12px; font-weight: 700;">
                                            {{ $entree->type_reference }} #{{ $entree->id_reference }}
                                        </div>
                                    @else
                                        <span style="font-size: 12px; color: var(--noir);">—</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($entree->resultat === 'REUSSI' || $entree->resultat === 'SUCCES')
                                        <x-statut type="positif" texte="Réussi" />
                                    @elseif($entree->resultat === 'ACCORDE')
                                        <x-statut type="positif" texte="Accordé" />
                                    @elseif($entree->resultat === 'ECHEC')
                                        <x-statut type="critique" texte="Échec" />
                                    @elseif($entree->resultat === 'REFUSE')
                                        <x-statut type="attention" texte="Refusé" />
                                    @else
                                        <x-statut type="attente" texte="{{ $entree->resultat }}" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 16px; margin-bottom: 32px;">
                {{ $journal->links() }}
            </div>
        @else
            <div style="border: 1px solid var(--noir); padding: 24px; text-align: center; background-color: var(--blanc); margin-bottom: 24px;">
                <p style="margin: 0; font-weight: 700;">Aucun événement d'audit ne correspond aux filtres indiqués.</p>
            </div>
        @endif
    @endif

    {{-- ========================================================================= --}}
    {{-- ONGLET 2 : SAUVEGARDES AUTOMATIQUES ET RESTAURATION SANDBOX               --}}
    {{-- ========================================================================= --}}
    @if($onglet === 'sauvegardes' && $peutGererSauvegardes)
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 16px 20px; margin-bottom: 24px;">
            <div style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">
                Politique municipale de sauvegarde locale & Continuité d'activité (Section 9.6)
            </div>
            <div style="font-size: 13px; line-height: 1.6;">
                Les sauvegardes sont exécutées automatiquement <strong>tous les jours ouvrés à 07:30</strong> (via la commande <code>sigaec:sauvegarde</code>). Chaque archive regroupe l'export SQL complet de la base MySQL, le miroir physique des actes numérisés et un fichier <strong>manifeste SHA-256</strong> garantissant l'intégrité cryptographique absolue. La politique locale applique une <strong>rétention de 7 versions glissantes</strong>.
            </div>
        </div>

        {{-- Tableau récapitulatif des archives disponibles --}}
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 class="titre-section" style="margin: 0; border: none; padding: 0;">Archives de sauvegarde scellées</h2>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-primary" data-modal-open="modal-sauvegarde-manuelle" style="font-size: 13px; min-height: 38px; padding: 6px 14px;">
                    + Sauvegarder maintenant
                </button>
                <button type="button" class="btn btn-secondary" data-modal-open="modal-test-restauration" style="font-size: 13px; min-height: 38px; padding: 6px 14px;">
                    ✓ Tester une restauration
                </button>
            </div>
        </div>

        @if($sauvegardes->count() > 0)
            <div class="table-responsive">
                <table class="table-sigaec" aria-label="Liste des sauvegardes de données">
                    <thead>
                        <tr>
                            <th>Date & Heure</th>
                            <th>Fichier d'archive</th>
                            <th style="width: 140px;">Support</th>
                            <th style="width: 100px;">Taille</th>
                            <th>Empreinte SHA-256 scellée</th>
                            <th style="width: 130px; text-align: center;">Test restauration</th>
                            <th style="width: 110px; text-align: center;">Résultat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sauvegardes as $sauv)
                            <tr>
                                <td>
                                    <div style="font-weight: 700;">{{ $sauv->date_sauvegarde->format('d/m/Y') }}</div>
                                    <div style="font-size: 12px;">{{ $sauv->date_sauvegarde->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    <code style="font-weight: 700; font-size: 13px;">{{ $sauv->nom_fichier }}</code>
                                    <div style="font-size: 11px; margin-top: 2px;">
                                        Initié par : {{ $sauv->utilisateur ? $sauv->utilisateur->nom_complet : 'Système planifié (Cron)' }}
                                    </div>
                                </td>
                                <td>
                                    <span style="border: 1px solid var(--noir); padding: 2px 6px; font-size: 11px; font-weight: 700; background-color: var(--blanc);">
                                        {{ $sauv->support }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">{{ $sauv->taille_formatee }}</span>
                                </td>
                                <td>
                                    <div style="font-family: monospace; font-size: 11px; word-break: break-all; max-width: 260px;" title="{{ $sauv->empreinte_sha256 }}">
                                        {{ substr($sauv->empreinte_sha256, 0, 16) }}...{{ substr($sauv->empreinte_sha256, -12) }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    @if($sauv->verifiee)
                                        <x-statut type="positif" texte="Validé 100%" />
                                    @else
                                        <x-statut type="attente" texte="Non testée" />
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($sauv->resultat === 'REUSSIE')
                                        <x-statut type="positif" texte="Réussie" />
                                    @else
                                        <x-statut type="critique" texte="Échec" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="border: 1px solid var(--noir); padding: 24px; text-align: center; background-color: var(--blanc); margin-bottom: 24px;">
                <p style="margin: 0; font-weight: 700;">Aucune archive de sauvegarde n'a encore été enregistrée.</p>
                <div style="margin-top: 12px;">
                    <button type="button" class="btn btn-primary" data-modal-open="modal-sauvegarde-manuelle">
                        Créer la première sauvegarde du système
                    </button>
                </div>
            </div>
        @endif

        {{-- Modale : Confirmation Sauvegarde Manuelle --}}
        <x-modal id="modal-sauvegarde-manuelle" titre="Lancer une sauvegarde système complète">
            <form action="{{ route('admin.sauvegardes.lancer') }}" method="POST">
                @csrf
                <p style="font-size: 14px; margin-bottom: 16px;">
                    Cette opération effectue immédiatement une sauvegarde complète sans perturber le fonctionnement de l'état civil :
                </p>

                <ul style="padding-left: 20px; font-size: 13px; margin-bottom: 16px; line-height: 1.6;">
                    <li>Dump SQL intégral de la base de données MySQL <code>sigaec</code></li>
                    <li>Miroir des fichiers numérisés stockés dans le coffre privé <code>archives</code></li>
                    <li>Calcul et scellement du manifeste cryptographique SHA-256</li>
                    <li>Rotation automatique assurant la rétention des 7 dernières versions.</li>
                </ul>

                <div style="border: 1px solid var(--noir); background-color: var(--menthe); padding: 10px 14px; font-size: 12px; font-weight: 700; margin-bottom: 20px;">
                    L'archive générée sera immédiatement disponible dans la liste des sauvegardes.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-sauvegarde-manuelle">Annuler</button>
                    <button type="submit" class="btn btn-primary">Exécuter la sauvegarde</button>
                </div>
            </form>
        </x-modal>

        {{-- Modale : Test de restauration Sandbox --}}
        <x-modal id="modal-test-restauration" titre="Tester la restauration d'une archive (Bac à sable)">
            <form action="{{ route('admin.sauvegardes.tester') }}" method="POST">
                @csrf
                <p style="font-size: 14px; margin-bottom: 16px;">
                    Conformément aux exigences de conservation de la Mairie de Tattaguine, les archives doivent subir des tests périodiques de lisibilité et d'intégrité :
                </p>

                <ul style="padding-left: 20px; font-size: 13px; margin-bottom: 16px; line-height: 1.6;">
                    <li>Extraction de la dernière archive disponible dans un dossier temporaire isolé</li>
                    <li>Vérification de la présence et de la syntaxe de l'export SQL</li>
                    <li>Recalcul automatique de chaque empreinte SHA-256 et comparaison avec le manifeste scellé</li>
                    <li>Mise à jour de l'indicateur de conformité trimestrielle sur le tableau de bord.</li>
                </ul>

                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-test-restauration">Annuler</button>
                    <button type="submit" class="btn btn-primary">Lancer le test de restauration</button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- ========================================================================= --}}
    {{-- ONGLET 3 : COMPTES UTILISATEURS & HABILITATIONS                           --}}
    {{-- ========================================================================= --}}
    @if($onglet === 'utilisateurs' && $peutGererUsers)
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 14px 18px; margin-bottom: 20px;">
            <div style="font-weight: 800; font-size: 14px;">
                Règles de sécurité des comptes & Politique des mots de passe (Section 7)
            </div>
            <div style="font-size: 13px; margin-top: 4px; line-height: 1.6;">
                Tout mot de passe doit comporter au moins 12 caractères comprenant majuscules, minuscules et chiffres. Un compte est automatiquement <strong>verrouillé après 5 échecs consécutifs</strong>. Conformément aux principes d'audit, <strong>aucun compte utilisateur ne peut être supprimé physiquement</strong> ; ils sont désactivés pour préserver l'historique complet des signatures d'actes.
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 class="titre-section" style="margin: 0; border: none; padding: 0;">Agents municipaux habilités</h2>
            <button type="button" class="btn btn-primary" data-modal-open="modal-nouvel-utilisateur" style="font-size: 13px; min-height: 38px; padding: 6px 14px;">
                + Créer un compte agent
            </button>
        </div>

        <div class="table-responsive">
            <table class="table-sigaec" aria-label="Liste des comptes utilisateurs">
                <thead>
                    <tr>
                        <th>Identifiant</th>
                        <th>Nom complet</th>
                        <th>Profil & Habilitations</th>
                        <th style="width: 120px; text-align: center;">État du compte</th>
                        <th style="width: 110px; text-align: center;">Échecs (max 5)</th>
                        <th style="width: 120px;">Créé le</th>
                        <th style="width: 150px;">Dernière activité</th>
                        <th style="min-width: 240px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($utilisateurs as $u)
                        <tr>
                            <td>
                                <code style="font-weight: 800; font-size: 14px;">{{ $u->identifiant }}</code>
                                @if($u->id_utilisateur === auth()->id())
                                    <span style="font-size: 10px; font-weight: 800; border: 1px solid var(--noir); padding: 1px 4px; margin-left: 4px; background-color: var(--menthe);">VOUS</span>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ $u->nom_complet }}</div>
                            </td>
                            <td>
                                <span style="border: 1px solid var(--noir); padding: 2px 6px; font-size: 11px; font-weight: 800; background-color: var(--blanc);">
                                    {{ $u->profil->libelle() }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                @if($u->etat_compte === \App\Enums\EtatCompte::ACTIF)
                                    <x-statut type="positif" texte="Actif" />
                                @elseif($u->etat_compte === \App\Enums\EtatCompte::VERROUILLE)
                                    <x-statut type="critique" texte="Verrouillé" />
                                @else
                                    <x-statut type="attention" texte="Désactivé" />
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($u->tentatives_echec > 0)
                                    <strong style="color: var(--noir);">{{ $u->tentatives_echec }} / 5</strong>
                                @else
                                    <span style="color: var(--noir);">0</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size: 12px;">{{ $u->date_creation ? $u->date_creation->format('d/m/Y') : '—' }}</span>
                            </td>
                            <td>
                                <span style="font-size: 12px;">
                                    {{ $u->derniere_activite ? $u->derniere_activite->format('d/m/Y H:i') : 'Jamais connecté' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-secondary" data-modal-open="modal-modifier-{{ $u->id_utilisateur }}" style="font-size: 11px; min-height: 30px; padding: 3px 8px;">
                                        ✏ Modifier
                                    </button>

                                    @if($u->etat_compte === \App\Enums\EtatCompte::VERROUILLE || $u->tentatives_echec >= 5)
                                        <button type="button" class="btn btn-secondary" data-modal-open="modal-debloquer-{{ $u->id_utilisateur }}" style="font-size: 11px; min-height: 30px; padding: 3px 8px;">
                                            🔓 Débloquer
                                        </button>
                                    @endif

                                    @if($u->id_utilisateur !== auth()->id())
                                        @if($u->etat_compte === \App\Enums\EtatCompte::ACTIF)
                                            <button type="button" class="btn btn-danger" data-modal-open="modal-desactiver-{{ $u->id_utilisateur }}" style="font-size: 11px; min-height: 30px; padding: 3px 8px;">
                                                Désactiver
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary" data-modal-open="modal-reactiver-{{ $u->id_utilisateur }}" style="font-size: 11px; min-height: 30px; padding: 3px 8px;">
                                                Réactiver
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-danger" data-modal-open="modal-supprimer-{{ $u->id_utilisateur }}" style="font-size: 11px; min-height: 30px; padding: 3px 8px;">
                                            🗑 Supprimer
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Modale : Création d'un nouvel agent municipal --}}
        <x-modal id="modal-nouvel-utilisateur" titre="Créer un compte agent communal">
            <form action="{{ route('admin.utilisateurs.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="nouvel_identifiant" class="form-label">Identifiant de connexion :</label>
                    <input type="text" name="identifiant" id="nouvel_identifiant" class="form-control" placeholder="Ex: amadou.ba" required maxlength="50">
                    <div class="form-hint">Doit être unique et sans espaces.</div>
                </div>

                <div class="form-group">
                    <label for="nouvel_nom" class="form-label">Nom complet de l'agent :</label>
                    <input type="text" name="nom_complet" id="nouvel_nom" class="form-control" placeholder="Ex: Amadou Ba" required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="nouveau_profil" class="form-label">Profil & Niveaux d'habilitation :</label>
                    <select name="profil" id="nouveau_profil" class="form-select" required>
                        @foreach(\App\Enums\Profil::cases() as $profilCase)
                            <option value="{{ $profilCase->value }}">{{ $profilCase->libelle() }} ({{ $profilCase->value }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="nouveau_mdp" class="form-label">Mot de passe temporaire :</label>
                    <input type="password" name="mot_de_passe" id="nouveau_mdp" class="form-control" placeholder="Minimum 12 caractères" required minlength="12">
                    <div class="form-hint">
                        Règle stricte : 12 caractères minimum avec au moins 1 majuscule, 1 minuscule et 1 chiffre.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-nouvel-utilisateur">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer le compte</button>
                </div>
            </form>
        </x-modal>

        {{-- Modales pour chaque utilisateur : Modification, Déblocage, Désactivation, Réactivation, Suppression --}}
        @foreach($utilisateurs as $u)
            {{-- Modale : Modifier les informations de l'agent --}}
            <x-modal id="modal-modifier-{{ $u->id_utilisateur }}" titre="Modifier les informations de {{ $u->nom_complet }}">
                <form action="{{ route('admin.utilisateurs.update', $u->id_utilisateur) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="mod_identifiant_{{ $u->id_utilisateur }}" class="form-label">Identifiant de connexion : *</label>
                        <input
                            type="text"
                            name="identifiant"
                            id="mod_identifiant_{{ $u->id_utilisateur }}"
                            class="form-control"
                            value="{{ $u->identifiant }}"
                            required
                            maxlength="50"
                        >
                        <div class="form-hint">Doit être unique et sans espaces.</div>
                    </div>

                    <div class="form-group">
                        <label for="mod_nom_{{ $u->id_utilisateur }}" class="form-label">Nom complet de l'agent : *</label>
                        <input
                            type="text"
                            name="nom_complet"
                            id="mod_nom_{{ $u->id_utilisateur }}"
                            class="form-control"
                            value="{{ $u->nom_complet }}"
                            required
                            maxlength="150"
                        >
                    </div>

                    <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                        <div class="form-group" style="flex: 1; min-width: 220px;">
                            <label for="mod_profil_{{ $u->id_utilisateur }}" class="form-label">Profil & Habilitation : *</label>
                            <select name="profil" id="mod_profil_{{ $u->id_utilisateur }}" class="form-select" required {{ $u->id_utilisateur === auth()->id() ? 'disabled' : '' }}>
                                @foreach(\App\Enums\Profil::cases() as $profilCase)
                                    <option value="{{ $profilCase->value }}" {{ $u->profil === $profilCase ? 'selected' : '' }}>
                                        {{ $profilCase->libelle() }}
                                    </option>
                                @endforeach
                            </select>
                            @if($u->id_utilisateur === auth()->id())
                                <input type="hidden" name="profil" value="{{ $u->profil->value }}">
                                <div class="form-hint">Vous ne pouvez pas rétrograder votre propre compte administrateur.</div>
                            @endif
                        </div>

                        <div class="form-group" style="flex: 1; min-width: 200px;">
                            <label for="mod_etat_{{ $u->id_utilisateur }}" class="form-label">État du compte : *</label>
                            <select name="etat_compte" id="mod_etat_{{ $u->id_utilisateur }}" class="form-select" required {{ $u->id_utilisateur === auth()->id() ? 'disabled' : '' }}>
                                @foreach(\App\Enums\EtatCompte::cases() as $etatCase)
                                    <option value="{{ $etatCase->value }}" {{ $u->etat_compte === $etatCase ? 'selected' : '' }}>
                                        {{ $etatCase->libelle() }}
                                    </option>
                                @endforeach
                            </select>
                            @if($u->id_utilisateur === auth()->id())
                                <input type="hidden" name="etat_compte" value="{{ $u->etat_compte->value }}">
                                <div class="form-hint">Vous ne pouvez pas désactiver votre propre compte administrateur.</div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mod_mdp_{{ $u->id_utilisateur }}" class="form-label">Modifier le mot de passe (optionnel) :</label>
                        <input
                            type="password"
                            name="mot_de_passe"
                            id="mod_mdp_{{ $u->id_utilisateur }}"
                            class="form-control"
                            placeholder="Laisser vide pour conserver le mot de passe actuel"
                            minlength="12"
                        >
                        <div class="form-hint">
                            Laisser vide si inchangé. Si renseigné : minimum 12 caractères avec au moins 1 majuscule, 1 minuscule et 1 chiffre.
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                        <button type="button" class="btn btn-secondary" data-modal-close="modal-modifier-{{ $u->id_utilisateur }}">
                            Annuler
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </x-modal>

            @if($u->etat_compte === \App\Enums\EtatCompte::VERROUILLE || $u->tentatives_echec >= 5)
                <x-modal id="modal-debloquer-{{ $u->id_utilisateur }}" titre="Débloquer le compte de {{ $u->nom_complet }}">
                    <form action="{{ route('admin.utilisateurs.debloquer', $u->id_utilisateur) }}" method="POST">
                        @csrf
                        <p style="font-size: 14px; margin-bottom: 16px;">
                            Le compte <strong>{{ $u->identifiant }}</strong> a été verrouillé suite à 5 tentatives infructueuses de mot de passe.
                        </p>
                        <p style="font-size: 13px; margin-bottom: 20px;">
                            Le déblocage réinitialisera le compteur d'échecs à zéro et restaurera immédiatement l'accès actif de l'agent.
                        </p>
                        <div style="display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-secondary" data-modal-close="modal-debloquer-{{ $u->id_utilisateur }}">Annuler</button>
                            <button type="submit" class="btn btn-primary">Confirmer le déblocage</button>
                        </div>
                    </form>
                </x-modal>
            @endif

            @if($u->id_utilisateur !== auth()->id())
                @if($u->etat_compte === \App\Enums\EtatCompte::ACTIF)
                    <x-modal id="modal-desactiver-{{ $u->id_utilisateur }}" titre="Désactiver le compte de {{ $u->nom_complet }}">
                        <form action="{{ route('admin.utilisateurs.basculer', $u->id_utilisateur) }}" method="POST">
                            @csrf
                            <p style="font-size: 14px; margin-bottom: 16px;">
                                Êtes-vous certain de vouloir désactiver le compte <strong>{{ $u->identifiant }}</strong> ?
                            </p>
                            <div style="border: 1px solid var(--noir); background-color: var(--blanc); padding: 12px; font-size: 13px; margin-bottom: 20px;">
                                L'agent ne pourra plus se connecter. Ses actions passées restent rigoureusement scellées dans le journal d'activité. Le compte pourra être réactivé à tout moment.
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button type="button" class="btn btn-secondary" data-modal-close="modal-desactiver-{{ $u->id_utilisateur }}">Annuler</button>
                                <button type="submit" class="btn btn-danger">Désactiver le compte</button>
                            </div>
                        </form>
                    </x-modal>
                @else
                    <x-modal id="modal-reactiver-{{ $u->id_utilisateur }}" titre="Réactiver le compte de {{ $u->nom_complet }}">
                        <form action="{{ route('admin.utilisateurs.basculer', $u->id_utilisateur) }}" method="POST">
                            @csrf
                            <p style="font-size: 14px; margin-bottom: 16px;">
                                Réactiver immédiatement les droits d'accès de <strong>{{ $u->nom_complet }}</strong> (<code>{{ $u->identifiant }}</code>) ?
                            </p>
                            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                                <button type="button" class="btn btn-secondary" data-modal-close="modal-reactiver-{{ $u->id_utilisateur }}">Annuler</button>
                                <button type="submit" class="btn btn-primary">Réactiver le compte</button>
                            </div>
                        </form>
                    </x-modal>
                @endif

                {{-- Modale : Supprimer définitivement l'agent --}}
                <x-modal id="modal-supprimer-{{ $u->id_utilisateur }}" titre="Supprimer définitivement l'agent {{ $u->nom_complet }}">
                    <form action="{{ route('admin.utilisateurs.destroy', $u->id_utilisateur) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <div style="font-size: 14px; margin-bottom: 16px;">
                            Êtes-vous sûr de vouloir supprimer définitivement le compte de l'agent <strong>{{ $u->nom_complet }}</strong> (<code>{{ $u->identifiant }}</code>) ?
                        </div>

                        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 14px; font-size: 13px; margin-bottom: 20px; line-height: 1.6;">
                            <strong>Attention :</strong> Cette action supprimera définitivement le compte et ses accès applicatifs. Les traces d'opérations passées enregistrées au journal d'audit restent rigoureusement scellées sous l'identifiant <em>{{ $u->identifiant }}</em>.
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 12px;">
                            <button type="button" class="btn btn-secondary" data-modal-close="modal-supprimer-{{ $u->id_utilisateur }}">
                                Annuler
                            </button>
                            <button type="submit" class="btn btn-danger">
                                Confirmer la suppression
                            </button>
                        </div>
                    </form>
                </x-modal>
            @endif
        @endforeach
    @endif

    {{-- ========================================================================= --}}
    {{-- ONGLET 4 : DEMANDES DE COMMUNICATION ADMINISTRATIVE & JUDICIAIRE          --}}
    {{-- ========================================================================= --}}
    @if($onglet === 'demandes' && ($peutDeciderDemandes || auth()->user()->aDroit('demandes.saisir')))
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 14px 18px; margin-bottom: 20px;">
            <div style="font-weight: 800; font-size: 14px;">
                Registre des demandes de communication & Réquisitions d'état civil (Section 5 & 9.6)
            </div>
            <div style="font-size: 13px; margin-top: 4px; line-height: 1.6;">
                En application du cadre légal sénégalais sur l'état civil, la communication de documents ou extraits à des tiers ou réquisitions judiciaires (Tribunal, Police, Gendarmerie) doit obligatoirement faire l'objet d'un enregistrement formel et d'une décision par l'Officier d'état civil ou le Responsable habilité.
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 class="titre-section" style="margin: 0; border: none; padding: 0;">Demandes et réquisitions enregistrées</h2>
            @if(auth()->user()->aDroit('demandes.saisir'))
                <button type="button" class="btn btn-primary" data-modal-open="modal-nouvelle-demande" style="font-size: 13px; min-height: 38px; padding: 6px 14px;">
                    + Enregistrer une demande
                </button>
            @endif
        </div>

        @if($demandes->count() > 0)
            <div class="table-responsive">
                <table class="table-sigaec" aria-label="Liste des demandes de communication">
                    <thead>
                        <tr>
                            <th style="width: 120px;">N° Demande</th>
                            <th style="width: 110px;">Date</th>
                            <th>Service demandeur / Requérant</th>
                            <th>Objet de la réquisition</th>
                            <th>Actes sollicités</th>
                            <th style="width: 130px; text-align: center;">Décision</th>
                            <th>Traitement</th>
                            @if($peutDeciderDemandes)
                                <th style="width: 130px; text-align: center;">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($demandes as $demande)
                            <tr>
                                <td>
                                    <code style="font-weight: 800; font-size: 14px;">{{ $demande->numero_demande }}</code>
                                </td>
                                <td>
                                    <span style="font-size: 13px; font-weight: 700;">
                                        {{ $demande->date_demande ? $demande->date_demande->format('d/m/Y') : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="font-size: 14px;">{{ $demande->service_demandeur }}</strong>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">{{ $demande->objet }}</div>
                                </td>
                                <td>
                                    @if($demande->actes->count() > 0)
                                        <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                            @foreach($demande->actes as $acte)
                                                <a href="{{ route('actes.show', $acte->id_acte) }}" style="font-size: 11px; font-weight: 800; border: 1px solid var(--noir); padding: 1px 5px; background-color: var(--blanc); text-decoration: none;">
                                                    Acte {{ $acte->numero_acte }}/{{ $acte->annee }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="font-size: 12px; font-style: italic;">Non spécifiés</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($demande->decision === 'ACCEPTEE')
                                        <x-statut type="positif" texte="Accordée" />
                                    @elseif($demande->decision === 'REFUSEE')
                                        <x-statut type="critique" texte="Refusée" />
                                    @else
                                        <x-statut type="attention" texte="En attente" />
                                    @endif
                                </td>
                                <td>
                                    @if($demande->decision !== 'EN_ATTENTE')
                                        <div style="font-size: 12px; font-weight: 700;">
                                            {{ $demande->date_traitement ? $demande->date_traitement->format('d/m/Y') : '—' }}
                                        </div>
                                        <div style="font-size: 11px;">
                                            Par : {{ $demande->agentResponsable ? $demande->agentResponsable->nom_complet : 'Responsable' }}
                                        </div>
                                    @else
                                        <span style="font-size: 12px; font-style: italic;">En cours d'instruction</span>
                                    @endif
                                </td>
                                @if($peutDeciderDemandes)
                                    <td style="text-align: center;">
                                        @if($demande->decision === 'EN_ATTENTE')
                                            <button type="button" class="btn btn-secondary" data-modal-open="modal-decider-{{ $demande->id_demande }}" style="font-size: 12px; min-height: 32px; padding: 4px 10px;">
                                                Statuer...
                                            </button>
                                        @else
                                            <span style="font-size: 11px; font-weight: 700;">Traité ✓</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="border: 1px solid var(--noir); padding: 24px; text-align: center; background-color: var(--blanc); margin-bottom: 24px;">
                <p style="margin: 0; font-weight: 700;">Aucune demande de communication d'actes n'est actuellement enregistrée.</p>
                @if(auth()->user()->aDroit('demandes.saisir'))
                    <div style="margin-top: 12px;">
                        <button type="button" class="btn btn-primary" data-modal-open="modal-nouvelle-demande">
                            Enregistrer une première demande
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Modale : Saisie d'une nouvelle demande de communication --}}
        @if(auth()->user()->aDroit('demandes.saisir'))
            <x-modal id="modal-nouvelle-demande" titre="Nouvelle demande de communication d'actes">
                <form action="{{ route('demandes.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="demande_service_demandeur" class="form-label">Service demandeur ou Requérant :</label>
                        <input type="text" name="service_demandeur" id="demande_service_demandeur" class="form-control" placeholder="Ex: Tribunal d'Instance de Fatick, Brigade de Gendarmerie..." required maxlength="150">
                    </div>

                    <div class="form-group">
                        <label for="demande_objet" class="form-label">Objet officiel de la communication :</label>
                        <textarea name="objet" id="demande_objet" class="form-textarea" placeholder="Ex: Réquisition n° 42/2026 dans le cadre de la vérification de nationalité..." required maxlength="255"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="demande_date" class="form-label">Date de la demande :</label>
                        <input type="date" name="date_demande" id="demande_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    @if(isset($actesDisponibles) && $actesDisponibles->count() > 0)
                        <div class="form-group">
                            <label class="form-label">Sélectionner un ou plusieurs actes concernés (optionnel) :</label>
                            <div style="max-height: 160px; overflow-y: auto; border: 1px solid var(--noir); padding: 10px; background-color: var(--blanc);">
                                @foreach($actesDisponibles as $acteDisp)
                                    <div style="margin-bottom: 6px;">
                                        <label style="font-size: 13px; font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                            <input type="checkbox" name="actes[]" value="{{ $acteDisp->id_acte }}">
                                            <span>
                                                <strong>Acte n° {{ $acteDisp->numero_acte }}/{{ $acteDisp->annee }}</strong>
                                                ({{ $acteDisp->type->libelle() }} - Registre {{ $acteDisp->registre->cote ?? 'N/A' }})
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                        <button type="button" class="btn btn-secondary" data-modal-close="modal-nouvelle-demande">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer la demande</button>
                    </div>
                </form>
            </x-modal>
        @endif

        {{-- Modale : Statuer sur chaque demande en attente --}}
        @if($peutDeciderDemandes)
            @foreach($demandes as $d)
                @if($d->decision === 'EN_ATTENTE')
                    <x-modal id="modal-decider-{{ $d->id_demande }}" titre="Statuer sur la demande {{ $d->numero_demande }}">
                        <form action="{{ route('demandes.decider', $d->id_demande) }}" method="POST">
                            @csrf
                            <div style="border: 1px solid var(--noir); background-color: var(--blanc); padding: 12px; margin-bottom: 16px;">
                                <div><strong>Requérant :</strong> {{ $d->service_demandeur }}</div>
                                <div style="margin-top: 4px;"><strong>Objet :</strong> {{ $d->objet }}</div>
                                <div style="margin-top: 4px;"><strong>Date de saisie :</strong> {{ $d->date_demande ? $d->date_demande->format('d/m/Y') : '—' }}</div>
                            </div>

                            <p style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">
                                Décision officielle de l'Officier d'état civil :
                            </p>

                            <div class="form-group" style="display: flex; flex-direction: column; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; cursor: pointer; border: 1px solid var(--noir); padding: 10px; background-color: var(--blanc);">
                                    <input type="radio" name="decision" value="ACCEPTEE" checked>
                                    <span>✓ ACCORDER la communication (Délivrance autorisée)</span>
                                </label>

                                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; cursor: pointer; border: 1px solid var(--noir); padding: 10px; background-color: var(--blanc);">
                                    <input type="radio" name="decision" value="REFUSEE">
                                    <span>✕ REFUSER la communication (Motif légal ou non-habilitation)</span>
                                </label>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                                <button type="button" class="btn btn-secondary" data-modal-close="modal-decider-{{ $d->id_demande }}">Annuler</button>
                                <button type="submit" class="btn btn-primary">Valider la décision</button>
                            </div>
                        </form>
                    </x-modal>
                @endif
            @endforeach
        @endif
    @endif
@endsection
