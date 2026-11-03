<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — SIGAEC (Mairie de Tattaguine)</title>
    <link rel="stylesheet" href="{{ asset('css/sigaec.css') }}">
    <style>
        :root { --menthe: #3EB489; --blanc: #FFFFFF; --noir: #111111; }
        .page-connexion {
            display: flex;
            min-height: 100vh;
        }
        .panneau-menthe {
            flex: 1;
            background-color: var(--menthe);
            color: var(--noir);
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .panneau-blanc {
            flex: 1;
            background-color: var(--blanc);
            color: var(--noir);
            padding: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .carte-connexion {
            width: 100%;
            max-width: 440px;
            border: 2px solid var(--noir);
            background-color: var(--blanc);
            padding: 36px 32px;
        }
        @media (max-width: 768px) {
            .page-connexion {
                flex-direction: column;
            }
            .panneau-menthe, .panneau-blanc {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="page-connexion">
        {{-- Panneau gauche vert menthe (Section 9.1) --}}
        <div class="panneau-menthe">
            <div>
                <div style="margin-bottom: 20px;">
                    <img src="{{ asset('images/logo-tattaguine.jpg') }}" alt="Armoiries de la Mairie de Tattaguine" style="height: 96px; width: auto; object-fit: contain; border: 2px solid var(--noir); background-color: var(--blanc); padding: 4px;">
                </div>
                <h1 style="font-size: 32px; font-weight: 900; margin-bottom: 8px; border: none; padding: 0;">SIGAEC</h1>
                <div style="font-size: 18px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">
                    Mairie de Tattaguine
                </div>
                <div style="font-size: 14px; font-weight: 600; margin-top: 4px;">
                    Région de Fatick · République du Sénégal
                </div>
            </div>

            <div style="margin: 40px 0;">
                <div style="border: 2px solid var(--noir); padding: 18px; background-color: var(--blanc);">
                    <div style="font-weight: 800; font-size: 15px; margin-bottom: 6px;">
                        Système de gestion et de conservation des archives de l'état civil
                    </div>
                    <p style="font-size: 13px; margin: 0;">
                        Ce logiciel permet l'inventaire des registres physiques, l'indexation et la consultation sécurisée des actes de naissance, mariage et décès.
                    </p>
                </div>
            </div>

            <div>
                <div style="font-weight: 800; font-size: 13px; text-transform: uppercase; border-top: 2px solid var(--noir); padding-top: 16px;">
                    ■ Application locale : aucune connexion Internet requise
                </div>
                <div style="font-size: 12px; margin-top: 4px;">
                    Données hautement confidentielles conservées exclusivement sur le poste municipal.
                </div>
            </div>
        </div>

        {{-- Panneau droit blanc (Formulaire de connexion) --}}
        <div class="panneau-blanc">
            <div class="carte-connexion">
                <div style="text-align: center; margin-bottom: 18px;">
                    <img src="{{ asset('images/logo-tattaguine.jpg') }}" alt="Logo Mairie de Tattaguine" style="height: 76px; width: auto; object-fit: contain; border: 1px solid var(--noir); background-color: var(--blanc); padding: 4px;">
                </div>
                <h2 class="titre-section" style="margin-bottom: 8px; border-bottom: 4px solid var(--menthe); text-align: center;">
                    Accès aux archives
                </h2>
                <p style="font-size: 13px; margin-bottom: 24px; font-weight: 600; text-align: center;">
                    Veuillez saisir vos identifiants d'agent communal pour vous connecter.
                </p>

                {{-- Alertes --}}
                @if(session('erreur'))
                    <div class="alerte alerte--erreur" role="alert" style="margin-bottom: 20px;">
                        <span>✕</span>
                        <span>{{ session('erreur') }}</span>
                    </div>
                @endif

                @if(session('succes'))
                    <div class="alerte alerte--succes" role="status" style="margin-bottom: 20px;">
                        <span>✓</span>
                        <span>{{ session('succes') }}</span>
                    </div>
                @endif

                <form action="{{ route('connexion.traiter') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="identifiant" class="form-label">Identifiant de l'agent :</label>
                        <input
                            type="text"
                            name="identifiant"
                            id="identifiant"
                            class="form-control"
                            value="{{ old('identifiant') }}"
                            placeholder="Ex : archiviste1 ou admin.tech"
                            required
                            autofocus
                        >
                        @error('identifiant')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe :</label>
                        <input
                            type="password"
                            name="mot_de_passe"
                            id="mot_de_passe"
                            class="form-control"
                            placeholder="••••••••••••"
                            required
                        >
                        @error('mot_de_passe')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div style="margin: 24px 0 16px 0;">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            Se connecter au système
                        </button>
                    </div>
                </form>

                {{-- Rappel de sécurité obligatoire (Section 9.1) --}}
                <div style="border-top: 1px solid var(--noir); padding-top: 16px; margin-top: 24px; font-size: 12px;">
                    <div style="font-weight: 800; margin-bottom: 4px;">Règle de sécurité stricte :</div>
                    <div>
                        Le compte est automatiquement <strong>verrouillé après 5 tentatives infructueuses</strong>. Déblocage réservé à l'administrateur technique.
                    </div>
                    <div style="margin-top: 6px;">
                        Déconnexion automatique après <strong>15 minutes d'inactivité</strong>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
