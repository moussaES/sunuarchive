@extends('layouts.app')

@section('titre', "Acte de {$acte->type->libelle()} n° {$acte->numero_acte} · {$acte->annee}")

@section('content')
    {{-- Fil d'Ariane (Section 9.5) --}}
    <nav aria-label="Fil d'Ariane" style="margin-bottom: 16px; font-size: 13px; font-weight: 600;">
        <a href="{{ route('tableau.index') }}">Tableau de bord</a>
        <span style="margin: 0 6px;">&gt;</span>
        <a href="{{ route('registres.index', ['detail' => $acte->id_registre]) }}">Registre {{ $acte->registre->cote }}</a>
        <span style="margin: 0 6px;">&gt;</span>
        <span>Acte n° {{ $acte->numero_acte }} ({{ $acte->annee }})</span>
    </nav>

    {{-- En-tête de la fiche d'acte --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="titre-page" style="margin-bottom: 8px;">
                Acte de {{ $acte->type->libelle() }} n° {{ $acte->numero_acte }} · {{ $acte->annee }}
            </h1>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <span style="font-weight: 800; font-size: 13px; border: 1px solid var(--noir); padding: 2px 8px; background-color: var(--blanc);">
                    {{ $acte->identifiant_acte }}
                </span>
                <x-statut :type="$acte->etat_verification->classeStatut()" :texte="$acte->etat_verification->libelle()" />
                @if($acte->possedeImage())
                    <x-statut type="positif" texte="Copie numérique associée" />
                @else
                    <x-statut type="attente" texte="À numériser" />
                @endif
                @if($acte->possedeJugement())
                    <x-statut type="positif" texte="Acte sur jugement ({{ $acte->numero_jugement ?? $acte->annee_jugement }})" />
                @endif
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            @if(auth()->check() && auth()->user()->aDroit('actes.creer'))
                <button type="button" class="btn btn-secondary" data-modal-open="modal-creer-acte">
                    + Nouvel acte
                </button>
            @endif

            @if(auth()->check() && auth()->user()->aDroit('demandes.saisir'))
                <button type="button" class="btn btn-secondary" data-modal-open="modal-demande-communication">
                    Enregistrer une demande
                </button>
            @endif

            @if(auth()->check() && auth()->user()->aDroit('actes.modifier'))
                <button type="button" class="btn btn-primary" data-modal-open="modal-corriger-acte">
                    Corriger la transcription
                </button>
            @endif
        </div>
    </div>

    {{-- Disposition 2 colonnes : Fiche descriptive & Mentions à gauche, Copie numérique à droite --}}
    <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
        {{-- Colonne gauche (Informations d'archive et mentions légales) --}}
        <div style="flex: 2; min-width: 320px;">
            {{-- 1. Fiche descriptive commune --}}
            <x-carte titre="1. Références archivistiques et conservation physique">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 14px;">
                    <div>
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Registre d'origine</div>
                        <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">
                            <a href="{{ route('registres.index', ['detail' => $acte->id_registre]) }}">
                                {{ $acte->registre->cote }}
                            </a>
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Feuillet / Page</div>
                        <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                            {{ $acte->page_folio ?? 'Non précisé' }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Date d'enregistrement</div>
                        <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                            {{ $acte->date_enregistrement ? $acte->date_enregistrement->format('d/m/Y') : 'Non renseignée' }}
                        </div>
                    </div>

                    <div>
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Emplacement physique du registre</div>
                        <div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
                            {{ $acte->registre->localisation ?? 'Magasin central' }}
                        </div>
                    </div>
                </div>

                @if($acte->possedeJugement())
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--noir);">
                        <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">
                            Références du jugement supplétif / rectificatif :
                        </div>
                        <div style="display: flex; gap: 24px; flex-wrap: wrap; font-size: 14px;">
                            <div>
                                <strong>Numéro du jugement :</strong> {{ $acte->numero_jugement ?? '—' }}
                            </div>
                            <div>
                                <strong>Date du jugement :</strong> {{ $acte->date_jugement ? $acte->date_jugement->format('d/m/Y') : '—' }}
                            </div>
                            <div>
                                <strong>Année du jugement :</strong> {{ $acte->annee_jugement ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endif

                @if($acte->observations)
                    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--noir);">
                        <strong style="font-size: 13px;">Observations archivistiques :</strong>
                        <div style="margin-top: 4px; padding: 8px 12px; border: 1px solid var(--noir); background-color: var(--blanc); font-size: 13px;">
                            {{ $acte->observations }}
                        </div>
                    </div>
                @endif
            </x-carte>

            {{-- 2. Mentions propres au type d'acte (Héritage table-per-type) --}}
            @if($acte->type === App\Enums\TypeActe::NAISSANCE && $acte->acteNaissance)
                <x-carte titre="2. Mentions propres à l'acte de naissance">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 14px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Date de naissance</div>
                            <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">
                                {{ $acte->acteNaissance->date_naissance ? $acte->acteNaissance->date_naissance->format('d/m/Y') : 'Inconnue' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Lieu de naissance</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteNaissance->lieu_naissance ?? 'Non précisé' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Sexe mentionné</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteNaissance->sexe ?? 'Non mentionné' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Date de déclaration</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteNaissance->date_declaration ? $acte->acteNaissance->date_declaration->format('d/m/Y') : 'Non renseignée' }}
                            </div>
                        </div>
                    </div>
                </x-carte>
            @elseif($acte->type === App\Enums\TypeActe::MARIAGE && $acte->acteMariage)
                <x-carte titre="2. Mentions propres à l'acte de mariage">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 14px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Date du mariage</div>
                            <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">
                                {{ $acte->acteMariage->date_mariage ? $acte->acteMariage->date_mariage->format('d/m/Y') : 'Inconnue' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Lieu de célébration</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteMariage->lieu_mariage ?? 'Non précisé' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Régime matrimonial</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteMariage->regime_matrimonial ?? 'Monogamie par défaut' }}
                            </div>
                        </div>
                    </div>

                    @if($acte->acteMariage->mentions_marginales)
                        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--noir);">
                            <strong style="font-size: 13px;">Mentions marginales portées :</strong>
                            <div style="margin-top: 4px; padding: 8px 12px; border: 1px solid var(--noir); background-color: var(--blanc); font-size: 13px;">
                                {{ $acte->acteMariage->mentions_marginales }}
                            </div>
                        </div>
                    @endif
                </x-carte>
            @elseif($acte->type === App\Enums\TypeActe::DECES && $acte->acteDeces)
                <x-carte titre="2. Mentions propres à l'acte de décès">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 14px;">
                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Date du décès</div>
                            <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">
                                {{ $acte->acteDeces->date_deces ? $acte->acteDeces->date_deces->format('d/m/Y') : 'Inconnue' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Heure du décès</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteDeces->heure_deces ?? 'Non précisée' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Lieu du décès</div>
                            <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                                {{ $acte->acteDeces->lieu_deces ?? 'Non précisé' }}
                            </div>
                        </div>

                        <div>
                            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Profession & Domicile</div>
                            <div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
                                {{ $acte->acteDeces->profession ?? 'Sans profession' }} · {{ $acte->acteDeces->domicile ?? 'Non précisé' }}
                            </div>
                        </div>
                    </div>
                </x-carte>
            @endif

            {{-- 3. Personnes liées et indexées avec leur rôle (Section 5) --}}
            <x-carte titre="3. Personnes physiques mentionnées dans l'acte">
                @if($acte->personnes->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table-sigaec">
                            <thead>
                                <tr>
                                    <th>Rôle légal porté</th>
                                    <th>Nom et prénoms</th>
                                    <th>Date de naissance</th>
                                    <th>Lieu d'origine</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($acte->personnes as $personne)
                                    <tr>
                                        <td>
                                            <span style="font-weight: 800; border: 1px solid var(--noir); padding: 2px 6px; background-color: var(--menthe); color: var(--noir);">
                                                {{ $personne->pivot->role }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $personne->nom_complet }}</strong>
                                        </td>
                                        <td>{{ $personne->date_naissance ? $personne->date_naissance->format('d/m/Y') : '—' }}</td>
                                        <td>{{ $personne->lieu_naissance ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size: 13px; margin: 0;">
                        Aucune personne n'est encore indexée pour cet acte. Utilisez le bouton « Corriger la transcription » pour renseigner les identités.
                    </p>
                @endif
            </x-carte>
        </div>

        {{-- Colonne droite (Copie numérique de consultation & Intégrité) --}}
        <div style="flex: 1; min-width: 300px;">
            <x-carte titre="Copie numérique de consultation">
                @if($acte->fichierActif)
                    @php $fichier = $acte->fichierActif; @endphp
                    <div style="border: 2px solid var(--noir); padding: 12px; background-color: var(--blanc); margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-weight: 800; font-size: 14px;">FICHIER ACTIF</span>
                            <span style="border: 1px solid var(--noir); padding: 2px 6px; font-size: 11px; font-weight: 800; background-color: var(--menthe);">
                                {{ strtoupper($fichier->format) }}
                            </span>
                        </div>

                        <div style="font-size: 13px; word-break: break-all; font-weight: 700; margin-bottom: 6px;">
                            {{ $fichier->nom_fichier }}
                        </div>

                        <div style="font-size: 12px; display: flex; flex-direction: column; gap: 4px;">
                            <div><strong>Numérisé le :</strong> {{ $fichier->date_numerisation ? $fichier->date_numerisation->format('d/m/Y') : 'Non daté' }}</div>
                            <div><strong>Taille :</strong> {{ round(($fichier->taille_octets ?? 0) / 1024, 1) }} Ko</div>
                            <div><strong>Page :</strong> n° {{ $fichier->numero_page }}</div>
                        </div>

                        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--noir); font-size: 11px;">
                            <div style="font-weight: 700;">Empreinte SHA-256 :</div>
                            <code style="word-break: break-all; font-size: 10px; display: block; margin-top: 2px;">
                                {{ $fichier->empreinte_sha256 }}
                            </code>
                        </div>

                        <div style="margin-top: 10px;">
                            @if($fichier->verifierIntegrite())
                                <x-statut type="positif" texte="Intégrité SHA-256 vérifiée" />
                            @else
                                <x-statut type="critique" texte="Fichier manquant ou altéré" />
                            @endif
                        </div>
                    </div>

                    {{-- Actions sur le fichier --}}
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <a
                            href="{{ route('fichiers.show', $fichier->id_fichier) }}"
                            target="_blank"
                            class="btn btn-primary"
                            style="width: 100%; font-size: 13px; text-align: center; text-decoration: none;"
                        >
                            Visualiser la reproduction numérique
                        </a>

                        <form action="{{ route('fichiers.verifier-integrite', $fichier->id_fichier) }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 13px;">
                                ✓ Contrôler l'intégrité SHA-256
                            </button>
                        </form>

                        @if(auth()->check() && auth()->user()->aDroit('fichiers.remplacer'))
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-modal-open="modal-remplacer-fichier"
                                style="width: 100%; font-size: 13px;"
                            >
                                Remplacer (justification requise)
                            </button>
                        @endif
                    </div>
                @else
                    <div style="text-align: center; padding: 24px 16px; border: 1px dashed var(--noir); margin-bottom: 16px;">
                        <div style="font-weight: 800; font-size: 15px; margin-bottom: 4px;">
                            Aucune copie numérique associée
                        </div>
                        <p style="font-size: 13px; margin: 0;">
                            L'acte est répertorié au registre mais n'a pas encore fait l'objet d'une numérisation.
                        </p>
                    </div>

                    @if(auth()->check() && auth()->user()->aDroit('fichiers.associer'))
                        <button
                            type="button"
                            class="btn btn-primary"
                            data-modal-open="modal-associer-fichier"
                            style="width: 100%;"
                        >
                            + Associer un fichier numérisé
                        </button>
                    @endif
                @endif

                {{-- Historique des fichiers remplacés si existant --}}
                @php
                    $fichiersHistoriques = $acte->fichiers->where('actif', false);
                @endphp
                @if($fichiersHistoriques->isNotEmpty())
                    <div style="margin-top: 20px; padding-top: 16px; border-top: 2px solid var(--noir);">
                        <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px;">
                            Historique des versions archivées ({{ $fichiersHistoriques->count() }}) :
                        </div>
                        @foreach($fichiersHistoriques as $fh)
                            <div style="border: 1px solid var(--noir); padding: 8px 10px; margin-bottom: 8px; font-size: 12px; background-color: var(--blanc);">
                                <div style="font-weight: 700; word-break: break-all;">{{ $fh->nom_fichier }}</div>
                                <div style="font-size: 11px; margin-top: 2px;">Inactif depuis remplacement</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-carte>
        </div>
    </div>

    {{-- MODALE POP-UP : Correction de la transcription (Section 9.5 & Demande utilisateur) --}}
    @if(auth()->check() && auth()->user()->aDroit('actes.modifier'))
        <x-modal id="modal-corriger-acte" titre="Corriger la transcription de l'acte {{ $acte->identifiant_acte }}">
            <form action="{{ route('actes.update', $acte->id_acte) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_registre" value="{{ $acte->id_registre }}">
                <input type="hidden" name="type" value="{{ $acte->type->value }}">
                <input type="hidden" name="_modal_source" value="modal-corriger-acte">

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="mod_numero_acte" class="form-label">Numéro officiel de l'acte : *</label>
                        <input type="text" name="numero_acte" id="mod_numero_acte" class="form-control" value="{{ old('numero_acte', $acte->numero_acte) }}" required>
                    </div>

                    <div class="form-group" style="width: 140px;">
                        <label for="mod_annee" class="form-label">Année : *</label>
                        <input type="number" name="annee" id="mod_annee" class="form-control" value="{{ old('annee', $acte->annee) }}" required>
                    </div>

                    <div class="form-group" style="width: 160px;">
                        <label for="mod_etat_verif" class="form-label">Vérification : *</label>
                        <select name="etat_verification" id="mod_etat_verif" class="form-select" required>
                            @foreach(App\Enums\EtatVerification::cases() as $ev)
                                <option value="{{ $ev->value }}" {{ old('etat_verification', $acte->etat_verification->value) === $ev->value ? 'selected' : '' }}>
                                    {{ $ev->libelle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="mod_date_enregistre" class="form-label">Date d'enregistrement :</label>
                        <input type="date" name="date_enregistrement" id="mod_date_enregistre" class="form-control" value="{{ old('date_enregistrement', $acte->date_enregistrement ? $acte->date_enregistrement->format('Y-m-d') : '') }}">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="mod_page_folio" class="form-label">Page / Folio :</label>
                        <input type="text" name="page_folio" id="mod_page_folio" class="form-control" value="{{ old('page_folio', $acte->page_folio) }}">
                    </div>
                </div>

                {{-- Champs optionnels de Jugement --}}
                <div style="border: 1px dashed var(--noir); padding: 10px 14px; margin-bottom: 12px; background-color: var(--blanc);">
                    <div style="font-weight: 800; font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">
                        Jugement (optionnel — laisser vide si l'acte n'a pas de jugement) :
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="mod_numero_jugement" class="form-label">N° de jugement :</label>
                            <input type="text" name="numero_jugement" id="mod_numero_jugement" class="form-control" value="{{ old('numero_jugement', $acte->numero_jugement) }}" placeholder="Ex: 0114" inputmode="numeric" pattern="[0-9]*">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="mod_date_jugement" class="form-label">Date du jugement :</label>
                            <input type="date" name="date_jugement" id="mod_date_jugement" class="form-control" value="{{ old('date_jugement', $acte->date_jugement ? $acte->date_jugement->format('Y-m-d') : '') }}">
                        </div>
                        <div class="form-group" style="width: 130px; margin-bottom: 0;">
                            <label for="mod_annee_jugement" class="form-label">Année jugement :</label>
                            <input type="number" name="annee_jugement" id="mod_annee_jugement" class="form-control" value="{{ old('annee_jugement', $acte->annee_jugement) }}" placeholder="Ex: 2020">
                        </div>
                    </div>
                </div>

                {{-- Champs spécifiques selon le type d'acte --}}
                @if($acte->type === App\Enums\TypeActe::NAISSANCE)
                    <div style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 8px;">Mentions de naissance & filiation :</div>
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_date_naissance" class="form-label">Date de naissance :</label>
                                <input type="date" name="date_naissance" id="mod_date_naissance" class="form-control" value="{{ old('date_naissance', $acte->acteNaissance?->date_naissance ? $acte->acteNaissance->date_naissance->format('Y-m-d') : '') }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_lieu_naissance" class="form-label">Lieu de naissance :</label>
                                <input type="text" name="lieu_naissance" id="mod_lieu_naissance" class="form-control" value="{{ old('lieu_naissance', $acte->acteNaissance?->lieu_naissance) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_sexe" class="form-label">Sexe mentionné :</label>
                                <select name="sexe" id="mod_sexe" class="form-select">
                                    <option value="">Non mentionné</option>
                                    <option value="Masculin" {{ old('sexe', $acte->acteNaissance?->sexe) === 'Masculin' ? 'selected' : '' }}>Masculin</option>
                                    <option value="Féminin" {{ old('sexe', $acte->acteNaissance?->sexe) === 'Féminin' ? 'selected' : '' }}>Féminin</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_date_declaration" class="form-label">Date de déclaration :</label>
                                <input type="date" name="date_declaration" id="mod_date_declaration" class="form-control" value="{{ old('date_declaration', $acte->acteNaissance?->date_declaration ? $acte->acteNaissance->date_declaration->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        @php
                            $enfant = $acte->personnes->firstWhere('pivot.role', 'ENFANT');
                            $pere = $acte->personnes->firstWhere('pivot.role', 'PERE');
                            $mere = $acte->personnes->firstWhere('pivot.role', 'MERE');
                        @endphp
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_enfant" class="form-label">Nom de l'enfant :</label>
                                <input type="text" name="nom_enfant" id="mod_nom_enfant" class="form-control" value="{{ old('nom_enfant', $enfant?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_enfant" class="form-label">Prénoms de l'enfant :</label>
                                <input type="text" name="prenoms_enfant" id="mod_prenoms_enfant" class="form-control" value="{{ old('prenoms_enfant', $enfant?->prenoms) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_pere" class="form-label">Prénoms du père :</label>
                                <input type="text" name="prenoms_pere" id="mod_prenoms_pere" class="form-control" value="{{ old('prenoms_pere', $pere?->prenoms) }}" placeholder="Prénoms du père...">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_mere" class="form-label">Nom de la mère :</label>
                                <input type="text" name="nom_mere" id="mod_nom_mere" class="form-control" value="{{ old('nom_mere', $mere?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_mere" class="form-label">Prénoms de la mère :</label>
                                <input type="text" name="prenoms_mere" id="mod_prenoms_mere" class="form-control" value="{{ old('prenoms_mere', $mere?->prenoms) }}">
                            </div>
                        </div>
                    </div>
                @elseif($acte->type === App\Enums\TypeActe::MARIAGE)
                    <div style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 8px;">Mentions de mariage & époux :</div>
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_date_mariage" class="form-label">Date du mariage :</label>
                                <input type="date" name="date_mariage" id="mod_date_mariage" class="form-control" value="{{ old('date_mariage', $acte->acteMariage?->date_mariage ? $acte->acteMariage->date_mariage->format('Y-m-d') : '') }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_lieu_mariage" class="form-label">Lieu du mariage :</label>
                                <input type="text" name="lieu_mariage" id="mod_lieu_mariage" class="form-control" value="{{ old('lieu_mariage', $acte->acteMariage?->lieu_mariage) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_regime_matrimonial" class="form-label">Régime matrimonial :</label>
                                <input type="text" name="regime_matrimonial" id="mod_regime_matrimonial" class="form-control" value="{{ old('regime_matrimonial', $acte->acteMariage?->regime_matrimonial) }}" placeholder="Ex: Monogamie, Polygamie...">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_mentions_marginales" class="form-label">Mentions marginales :</label>
                                <input type="text" name="mentions_marginales" id="mod_mentions_marginales" class="form-control" value="{{ old('mentions_marginales', $acte->acteMariage?->mentions_marginales) }}">
                            </div>
                        </div>

                        @php
                            $epoux = $acte->personnes->firstWhere('pivot.role', 'EPOUX');
                            $epouse = $acte->personnes->firstWhere('pivot.role', 'EPOUSE');
                            $temoin = $acte->personnes->firstWhere('pivot.role', 'TEMOIN');
                        @endphp
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_epoux" class="form-label">Nom de l'époux :</label>
                                <input type="text" name="nom_epoux" id="mod_nom_epoux" class="form-control" value="{{ old('nom_epoux', $epoux?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_epoux" class="form-label">Prénoms de l'époux :</label>
                                <input type="text" name="prenoms_epoux" id="mod_prenoms_epoux" class="form-control" value="{{ old('prenoms_epoux', $epoux?->prenoms) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_epouse" class="form-label">Nom de l'épouse :</label>
                                <input type="text" name="nom_epouse" id="mod_nom_epouse" class="form-control" value="{{ old('nom_epouse', $epouse?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_epouse" class="form-label">Prénoms de l'épouse :</label>
                                <input type="text" name="prenoms_epouse" id="mod_prenoms_epouse" class="form-control" value="{{ old('prenoms_epouse', $epouse?->prenoms) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_temoin" class="form-label">Nom du témoin :</label>
                                <input type="text" name="nom_temoin" id="mod_nom_temoin" class="form-control" value="{{ old('nom_temoin', $temoin?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_temoin" class="form-label">Prénoms du témoin :</label>
                                <input type="text" name="prenoms_temoin" id="mod_prenoms_temoin" class="form-control" value="{{ old('prenoms_temoin', $temoin?->prenoms) }}">
                            </div>
                        </div>
                    </div>
                @elseif($acte->type === App\Enums\TypeActe::DECES)
                    <div style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 8px;">Mentions de décès :</div>
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_date_deces" class="form-label">Date du décès :</label>
                                <input type="date" name="date_deces" id="mod_date_deces" class="form-control" value="{{ old('date_deces', $acte->acteDeces?->date_deces ? $acte->acteDeces->date_deces->format('Y-m-d') : '') }}">
                            </div>
                            <div class="form-group" style="width: 140px;">
                                <label for="mod_heure_deces" class="form-label">Heure du décès :</label>
                                <input type="text" name="heure_deces" id="mod_heure_deces" class="form-control" value="{{ old('heure_deces', $acte->acteDeces?->heure_deces) }}" placeholder="Ex: 14:30">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_lieu_deces" class="form-label">Lieu du décès :</label>
                                <input type="text" name="lieu_deces" id="mod_lieu_deces" class="form-control" value="{{ old('lieu_deces', $acte->acteDeces?->lieu_deces) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_profession" class="form-label">Profession :</label>
                                <input type="text" name="profession" id="mod_profession" class="form-control" value="{{ old('profession', $acte->acteDeces?->profession) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_domicile" class="form-label">Domicile :</label>
                                <input type="text" name="domicile" id="mod_domicile" class="form-control" value="{{ old('domicile', $acte->acteDeces?->domicile) }}">
                            </div>
                        </div>

                        @php
                            $defunt = $acte->personnes->firstWhere('pivot.role', 'DEFUNT');
                            $declarant = $acte->personnes->firstWhere('pivot.role', 'DECLARANT');
                        @endphp
                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_defunt" class="form-label">Nom du défunt :</label>
                                <input type="text" name="nom_defunt" id="mod_nom_defunt" class="form-control" value="{{ old('nom_defunt', $defunt?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_defunt" class="form-label">Prénoms du défunt :</label>
                                <input type="text" name="prenoms_defunt" id="mod_prenoms_defunt" class="form-control" value="{{ old('prenoms_defunt', $defunt?->prenoms) }}">
                            </div>
                        </div>

                        <div style="display: flex; gap: 16px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_nom_declarant_deces" class="form-label">Nom du déclarant :</label>
                                <input type="text" name="nom_declarant_deces" id="mod_nom_declarant_deces" class="form-control" value="{{ old('nom_declarant_deces', $declarant?->nom) }}">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="mod_prenoms_declarant_deces" class="form-label">Prénoms du déclarant :</label>
                                <input type="text" name="prenoms_declarant_deces" id="mod_prenoms_declarant_deces" class="form-control" value="{{ old('prenoms_declarant_deces', $declarant?->prenoms) }}">
                            </div>
                        </div>
                    </div>
                @endif

                <div class="form-group" style="margin-top: 12px;">
                    <label for="mod_observations" class="form-label">Observations archivistiques :</label>
                    <textarea name="observations" id="mod_observations" class="form-textarea">{{ old('observations', $acte->observations) }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-corriger-acte">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Valider la correction tracée
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- MODALE POP-UP : Nouvel acte (accessible depuis la barre d'outils) --}}
    @if(auth()->check() && auth()->user()->aDroit('actes.creer'))
        <x-modal id="modal-creer-acte" titre="Indexation d'un nouvel acte d'état civil">
            <form action="{{ route('actes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_modal_source" value="modal-creer-acte">

                <div class="form-group">
                    <label for="creer_id_registre" class="form-label">Registre de rattachement : *</label>
                    <select name="id_registre" id="creer_id_registre" class="form-select" required>
                        @foreach($registres as $reg)
                            <option value="{{ $reg->id_registre }}" {{ $reg->id_registre === $acte->id_registre ? 'selected' : '' }}>
                                {{ $reg->cote }} ({{ $reg->type->libelle() }} · {{ $reg->annee_debut }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_type_acte" class="form-label">Type d'acte : *</label>
                        <select name="type" id="creer_type_acte" class="form-select" required onchange="basculerChampsTypeActe(this.value, 'creer_show')">
                            @foreach(App\Enums\TypeActe::cases() as $t)
                                <option value="{{ $t->value }}" {{ $t === $acte->type ? 'selected' : '' }}>{{ $t->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_num_acte" class="form-label">Numéro officiel : *</label>
                        <input type="text" name="numero_acte" id="creer_num_acte" class="form-control" placeholder="Ex: 0045" required>
                    </div>

                    <div class="form-group" style="width: 130px;">
                        <label for="creer_annee_acte" class="form-label">Année : *</label>
                        <input type="number" name="annee" id="creer_annee_acte" class="form-control" value="{{ $acte->annee }}" required>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_date_enreg" class="form-label">Date d'enregistrement :</label>
                        <input type="date" name="date_enregistrement" id="creer_date_enreg" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_page_fol" class="form-label">Folio / Page :</label>
                        <input type="text" name="page_folio" id="creer_page_fol" class="form-control" placeholder="Page 12">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_etat_v" class="form-label">État vérification : *</label>
                        <select name="etat_verification" id="creer_etat_v" class="form-select" required>
                            @foreach(App\Enums\EtatVerification::cases() as $ev)
                                <option value="{{ $ev->value }}">{{ $ev->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Champs optionnels de Jugement --}}
                <div style="border: 1px dashed var(--noir); padding: 10px 14px; margin-bottom: 12px; background-color: var(--blanc);">
                    <div style="font-weight: 800; font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">
                        Jugement (optionnel — laisser vide si l'acte n'a pas de jugement) :
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">N° de jugement :</label>
                            <input type="text" name="numero_jugement" class="form-control" placeholder="Ex: 0114" inputmode="numeric" pattern="[0-9]*">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Date du jugement :</label>
                            <input type="date" name="date_jugement" class="form-control">
                        </div>
                        <div class="form-group" style="width: 130px; margin-bottom: 0;">
                            <label class="form-label">Année jugement :</label>
                            <input type="number" name="annee_jugement" class="form-control" placeholder="Ex: 2020">
                        </div>
                    </div>
                </div>

                {{-- Champs de transcription selon le type d'acte --}}
                <div id="creer_show_bloc_NAISSANCE" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $acte->type === App\Enums\TypeActe::NAISSANCE ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de naissance :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'enfant :</label>
                            <input type="text" name="nom_enfant" class="form-control" placeholder="Ex: FAYE">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'enfant :</label>
                            <input type="text" name="prenoms_enfant" class="form-control" placeholder="Ex: Moussa">
                        </div>
                        <div class="form-group" style="width: 130px;">
                            <label class="form-label">Sexe :</label>
                            <select name="sexe" class="form-select">
                                <option value="">—</option>
                                <option value="Masculin">Masculin</option>
                                <option value="Féminin">Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date de naissance :</label>
                            <input type="date" name="date_naissance" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu de naissance :</label>
                            <input type="text" name="lieu_naissance" class="form-control" placeholder="Ex: Maternité de Tattaguine">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date de déclaration :</label>
                            <input type="date" name="date_declaration" class="form-control">
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms du père :</label>
                            <input type="text" name="prenoms_pere" class="form-control" placeholder="Prénoms du père...">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de la mère :</label>
                            <input type="text" name="nom_mere" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de la mère :</label>
                            <input type="text" name="prenoms_mere" class="form-control">
                        </div>
                    </div>
                </div>

                <div id="creer_show_bloc_MARIAGE" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $acte->type === App\Enums\TypeActe::MARIAGE ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de mariage :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date du mariage :</label>
                            <input type="date" name="date_mariage" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu du mariage :</label>
                            <input type="text" name="lieu_mariage" class="form-control" placeholder="Ex: Tattaguine">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Régime matrimonial :</label>
                            <input type="text" name="regime_matrimonial" class="form-control" placeholder="Ex: Monogamie">
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'époux :</label>
                            <input type="text" name="nom_epoux" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'époux :</label>
                            <input type="text" name="prenoms_epoux" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'épouse :</label>
                            <input type="text" name="nom_epouse" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'épouse :</label>
                            <input type="text" name="prenoms_epouse" class="form-control">
                        </div>
                    </div>
                </div>

                <div id="creer_show_bloc_DECES" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $acte->type === App\Enums\TypeActe::DECES ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de décès :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom du défunt :</label>
                            <input type="text" name="nom_defunt" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms du défunt :</label>
                            <input type="text" name="prenoms_defunt" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date du décès :</label>
                            <input type="date" name="date_deces" class="form-control">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu du décès :</label>
                            <input type="text" name="lieu_deces" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="creer_obs_acte" class="form-label">Observations archivistiques :</label>
                    <textarea name="observations" id="creer_obs_acte" class="form-textarea" placeholder="Mentions marginales, particularités de transcription..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-creer-acte">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enregistrer l'acte
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- MODALE POP-UP : Associer un fichier numérisé (Section 8) --}}
    @if(auth()->check() && auth()->user()->aDroit('fichiers.associer'))
        <x-modal id="modal-associer-fichier" titre="Associer une copie numérique à l'acte {{ $acte->identifiant_acte }}">
            <form action="{{ route('fichiers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id_acte" value="{{ $acte->id_acte }}">

                <div class="form-group">
                    <label for="assoc_fichier" class="form-label">Document numérisé (TIFF, PDF ou JPEG) : *</label>
                    <input type="file" name="fichier" id="assoc_fichier" class="form-control" accept=".tif,.tiff,.pdf,.jpg,.jpeg" required>
                    <div style="font-size: 12px; margin-top: 4px; color: var(--noir);">
                        Formats acceptés : TIFF (images maîtresses), PDF et JPEG (consultation). Max 25 Mo.
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="assoc_date_num" class="form-label">Date de numérisation :</label>
                        <input type="date" name="date_numerisation" id="assoc_date_num" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="form-group" style="width: 120px;">
                        <label for="assoc_page" class="form-label">N° de page :</label>
                        <input type="number" name="numero_page" id="assoc_page" class="form-control" value="1" min="1">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-associer-fichier">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Associer et sceller le SHA-256
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- MODALE POP-UP : Remplacer la copie numérique (Section 8 — justification obligatoire) --}}
    @if($acte->fichierActif && auth()->check() && auth()->user()->aDroit('fichiers.remplacer'))
        <x-modal id="modal-remplacer-fichier" titre="Remplacement de copie numérique — Justification requise">
            <form action="{{ route('fichiers.remplacer', $acte->fichierActif->id_fichier) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div style="border: 1px solid var(--noir); padding: 12px; margin-bottom: 16px; background-color: var(--blanc);">
                    <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Copie actuelle qui sera archivée :</div>
                    <div style="font-weight: 800; font-size: 13px; margin-top: 2px;">{{ $acte->fichierActif->nom_fichier }}</div>
                </div>

                <div class="form-group">
                    <label for="remp_fichier" class="form-label">Nouvelle reproduction numérique (TIFF, PDF ou JPEG) : *</label>
                    <input type="file" name="fichier" id="remp_fichier" class="form-control" accept=".tif,.tiff,.pdf,.jpg,.jpeg" required>
                </div>

                <div class="form-group">
                    <label for="remp_justification" class="form-label">Motif archivistique et justification obligatoire : *</label>
                    <textarea name="justification_remplacement" id="remp_justification" class="form-textarea" rows="3" placeholder="Ex: Résolution initiale insuffisante (150 DPI). Nouveau scan 400 DPI pleine page..." required minlength="10"></textarea>
                    <div style="font-size: 12px; margin-top: 4px; color: var(--noir);">
                        Conformément à la section 8 du cahier des charges, l'ancienne version reste conservée inerte dans l'historique d'archive et l'opération est inscrite au journal.
                    </div>
                </div>

                <div class="form-group">
                    <label for="remp_date_num" class="form-label">Date du nouveau scan :</label>
                    <input type="date" name="date_numerisation" id="remp_date_num" class="form-control" value="{{ date('Y-m-d') }}">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-remplacer-fichier">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Valider le remplacement
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- MODALE POP-UP : Enregistrer une demande de communication pour cet acte (Section 9.6) --}}
    @if(auth()->check() && auth()->user()->aDroit('demandes.saisir'))
        <x-modal id="modal-demande-communication" titre="Enregistrer une demande de communication — {{ $acte->identifiant_acte }}">
            <form action="{{ route('demandes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_modal_source" value="modal-demande-communication">
                <input type="hidden" name="actes[]" value="{{ $acte->id_acte }}">

                <div style="border: 1px solid var(--noir); padding: 10px 14px; margin-bottom: 16px; background-color: var(--blanc); font-size: 13px;">
                    <strong>Acte concerné :</strong> Acte de {{ $acte->type->libelle() }} n° {{ $acte->numero_acte }} ({{ $acte->annee }}) — Registre {{ $acte->registre->cote }}
                </div>

                <div class="form-group">
                    <label for="demande_acte_service" class="form-label">Service demandeur ou Requérant : *</label>
                    <input type="text" name="service_demandeur" id="demande_acte_service" class="form-control" value="{{ old('service_demandeur') }}" placeholder="Ex: Tribunal d'Instance de Fatick, Brigade de Gendarmerie, Usager..." required maxlength="150">
                </div>

                <div class="form-group">
                    <label for="demande_acte_objet" class="form-label">Objet officiel de la communication : *</label>
                    <textarea name="objet" id="demande_acte_objet" class="form-textarea" rows="3" placeholder="Ex: Réquisition n° 42/2026 ou demande de copie intégrale..." required maxlength="255">{{ old('objet') }}</textarea>
                </div>

                <div class="form-group">
                    <label for="demande_acte_date" class="form-label">Date de la demande :</label>
                    <input type="date" name="date_demande" id="demande_acte_date" class="form-control" value="{{ old('date_demande', date('Y-m-d')) }}">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-demande-communication">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enregistrer la demande
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
