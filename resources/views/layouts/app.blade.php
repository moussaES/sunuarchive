<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIGAEC - Gestion et conservation des archives de l'état civil - Mairie de Tattaguine">
    <title>@yield('titre', 'SIGAEC') — Mairie de Tattaguine</title>
    <link rel="stylesheet" href="{{ asset('css/sigaec.css') }}">
    <style>
        /* Fallback de sécurité si le fichier CSS n'est pas encore copié */
        :root { --menthe: #3EB489; --blanc: #FFFFFF; --noir: #111111; --rouge: #DC2626; }
    </style>
</head>
<body>
    <div class="layout-container">
        {{-- Barre latérale gauche (Vert menthe strict) --}}
        <aside class="sidebar" aria-label="Navigation principale">
            <div class="sidebar__brand">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <img src="{{ asset('images/logo-tattaguine.jpg') }}" alt="Logo Mairie de Tattaguine" style="height: 52px; width: auto; object-fit: contain; border: 1px solid var(--noir); background-color: var(--blanc); padding: 2px; flex-shrink: 0;">
                    <div>
                        <div class="sidebar__title">SIGAEC</div>
                        <div class="sidebar__subtitle">Mairie de Tattaguine</div>
                    </div>
                </div>
                <div style="font-size: 11px; margin-top: 4px; font-weight: 600;">
                    Système local autonome (sans Internet)
                </div>
            </div>

            <nav class="sidebar__nav">
                <a href="{{ url('/') }}" class="sidebar__link {{ request()->is('/') ? 'sidebar__link--active' : '' }}">
                    <span aria-hidden="true">■</span>
                    <span>Tableau de bord</span>
                </a>

                @if(!auth()->check() || auth()->user()->aDroit('registres.consulter'))
                    <a href="{{ url('/registres') }}" class="sidebar__link {{ request()->is('registres*') ? 'sidebar__link--active' : '' }}">
                        <span aria-hidden="true">■</span>
                        <span>Registres</span>
                    </a>
                @endif

                @if(!auth()->check() || auth()->user()->aDroit('actes.consulter'))
                    <a href="{{ url('/actes/recherche') }}" class="sidebar__link {{ request()->is('actes*') ? 'sidebar__link--active' : '' }}">
                        <span aria-hidden="true">■</span>
                        <span>Recherche d'actes</span>
                    </a>
                @endif

                @if(auth()->check() && (
                    auth()->user()->aDroit('utilisateurs.gerer') ||
                    auth()->user()->aDroit('journal.consulter') ||
                    auth()->user()->aDroit('sauvegardes.gerer') ||
                    auth()->user()->aDroit('demandes.decider') ||
                    auth()->user()->aDroit('demandes.saisir')
                ))
                    <a href="{{ url('/admin') }}" class="sidebar__link {{ request()->is('admin*') ? 'sidebar__link--active' : '' }}">
                        <span aria-hidden="true">■</span>
                        <span>Administration</span>
                    </a>
                @endif

                <a href="{{ url('/styleguide') }}" class="sidebar__link {{ request()->is('styleguide*') ? 'sidebar__link--active' : '' }}">
                    <span aria-hidden="true">■</span>
                    <span>Guide de styles UI</span>
                </a>
            </nav>

            <div class="sidebar__footer">
                @if(auth()->check())
                    <div class="sidebar__user">
                        <span>{{ auth()->user()->nom_complet ?? auth()->user()->identifiant }}</span>
                        <div>
                            <span class="sidebar__profile-badge">{{ auth()->user()->profil }}</span>
                        </div>
                    </div>
                    <form action="{{ url('/deconnexion') }}" method="POST" style="margin-top: 8px;">
                        @csrf
                        <button type="submit" class="btn btn-secondary" style="width: 100%; min-height: 38px; font-size: 13px;">
                            Se déconnecter
                        </button>
                    </form>
                @else
                    <div class="sidebar__user">
                        <span>Mode consultation</span>
                        <div>
                            <span class="sidebar__profile-badge">LOCAL</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" data-modal-open="modal-connexion" style="margin-top: 8px; font-size: 13px; width: 100%;">
                        Connexion agent
                    </button>
                    <a href="{{ route('connexion') }}" style="font-size: 11px; text-align: center; margin-top: 4px; display: block;">
                        Page pleine écran
                    </a>
                @endif
            </div>
        </aside>

        {{-- Zone de travail principale (Fond blanc) --}}
        <main class="main-content" id="contenu-principal">
            {{-- Bandeau permanent obligatoire (Section 1 du cahier des charges) --}}
            <x-bandeau-officiel />

            {{-- Notifications flash d'enregistrement et d'alerte --}}
            @if(session('succes'))
                <div class="alerte alerte--succes" role="status" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 18px; font-weight: 900;">✓</span>
                        <div>
                            <div style="font-size: 12px; text-transform: uppercase; font-weight: 900;">Confirmation d'enregistrement</div>
                            <div>{{ session('succes') }}</div>
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('.alerte').remove()" style="background: transparent; border: 1px solid var(--noir); color: var(--noir); font-weight: 900; padding: 2px 8px; cursor: pointer;">✕</button>
                </div>
            @endif

            @if(session('erreur'))
                <div class="alerte alerte--erreur" role="alert" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 18px; font-weight: 900;">⚠</span>
                        <div>
                            <div style="font-size: 12px; text-transform: uppercase; font-weight: 900;">Échec de l'opération — Information non enregistrée</div>
                            <div>{{ session('erreur') }}</div>
                        </div>
                    </div>
                    <button type="button" onclick="this.closest('.alerte').remove()" style="background: transparent; border: 1px solid var(--blanc); color: var(--blanc); font-weight: 900; padding: 2px 8px; cursor: pointer;">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div class="alerte alerte--erreur" role="alert" style="flex-direction: column; align-items: flex-start; gap: 8px;">
                    <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 18px; font-weight: 900;">⚠</span>
                            <span style="text-transform: uppercase; font-weight: 900;">Attention : L'information n'a pas pu être enregistrée dans la base de données</span>
                        </div>
                        <button type="button" onclick="this.closest('.alerte').remove()" style="background: transparent; border: 1px solid var(--blanc); color: var(--blanc); font-weight: 900; padding: 2px 8px; cursor: pointer;">✕</button>
                    </div>
                    <ul style="margin: 0; padding-left: 24px; font-size: 14px; font-weight: 600;">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Contenu de la vue --}}
            @yield('content')
        </main>
    </div>

    {{-- Fenêtre modale de connexion rapide pour agent --}}
    @guest
        <x-modal id="modal-connexion" titre="Connexion sécurisée — Agent communal">
            <div style="text-align: center; margin-bottom: 16px;">
                <img src="{{ asset('images/logo-tattaguine.jpg') }}" alt="Logo Mairie de Tattaguine" style="height: 64px; width: auto; object-fit: contain; border: 1px solid var(--noir); background-color: var(--blanc); padding: 3px;">
            </div>
            <form action="{{ route('connexion.traiter') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="modal_identifiant" class="form-label">Identifiant de l'agent :</label>
                    <input type="text" name="identifiant" id="modal_identifiant" class="form-control" placeholder="Ex: archiviste1" required>
                </div>

                <div class="form-group">
                    <label for="modal_mot_de_passe" class="form-label">Mot de passe :</label>
                    <input type="password" name="mot_de_passe" id="modal_mot_de_passe" class="form-control" placeholder="••••••••••••" required>
                </div>

                <div style="font-size: 12px; margin-bottom: 16px;">
                    Rappel : Compte verrouillé après 5 tentatives infructueuses.
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-connexion">Annuler</button>
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                </div>
            </form>
        </x-modal>
    @endguest

    {{-- Script vanilla JS pour la gestion universelle des popups modales et bascule de formulaires --}}
    <script>
        function basculerChampsTypeActe(typeValeur, prefixe) {
            ['NAISSANCE', 'MARIAGE', 'DECES'].forEach(function(t) {
                var bloc = document.getElementById(prefixe + '_bloc_' + t);
                if (bloc) {
                    bloc.style.display = (t === typeValeur) ? 'block' : 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Réouverture automatique de la modale en cas d'erreur de validation
            @if($errors->any() && old('_modal_source'))
                var modalErreur = document.getElementById(@json(old('_modal_source')));
                if (modalErreur && typeof modalErreur.showModal === 'function') {
                    modalErreur.showModal();
                }
            @endif

            // Ouverture des fenêtres modales
            document.querySelectorAll('[data-modal-open]').forEach(function(declencheur) {
                declencheur.addEventListener('click', function(e) {
                    e.preventDefault();
                    var cibleId = this.getAttribute('data-modal-open');
                    var modal = document.getElementById(cibleId);
                    if (modal && typeof modal.showModal === 'function') {
                        modal.showModal();
                        // Focus sur le premier champ de formulaire si présent
                        var premierChamp = modal.querySelector('input:not([type="hidden"]), select, textarea');
                        if (premierChamp) {
                            premierChamp.focus();
                        }
                    }
                });
            });

            // Fermeture des fenêtres modales par bouton
            document.querySelectorAll('[data-modal-close]').forEach(function(bouton) {
                bouton.addEventListener('click', function(e) {
                    e.preventDefault();
                    var cibleId = this.getAttribute('data-modal-close');
                    var modal = cibleId ? document.getElementById(cibleId) : this.closest('dialog');
                    if (modal && typeof modal.close === 'function') {
                        modal.close();
                    }
                });
            });

            // Fermeture par clic sur l'arrière-plan (backdrop)
            document.querySelectorAll('dialog.sigaec-modal').forEach(function(modal) {
                modal.addEventListener('click', function(e) {
                    var rect = modal.getBoundingClientRect();
                    var estDansCadre = (
                        rect.top <= e.clientY && e.clientY <= rect.top + rect.height &&
                        rect.left <= e.clientX && e.clientX <= rect.left + rect.width
                    );
                    if (!estDansCadre) {
                        modal.close();
                    }
                });
            });
        });
    </script>
</body>
</html>
