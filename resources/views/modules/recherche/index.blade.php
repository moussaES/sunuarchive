@extends('layouts.app')

@section('titre', 'Recherche d\'actes d\'état civil')

@section('content')
    {{-- En-tête de la page --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="titre-page" style="margin-bottom: 4px;">Recherche d'actes d'état civil</h1>
            <p style="margin: 0; font-size: 14px; font-weight: 600;">
                Consultation et repérage des actes répertoriés dans les registres de la Mairie de Tattaguine.
            </p>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="button" class="btn btn-secondary" data-modal-open="modal-recherche-avancee">
                ⚙ Critères avancés...
            </button>
            @if(auth()->check() && auth()->user()->aDroit('actes.creer'))
                <button type="button" class="btn btn-primary" data-modal-open="modal-creer-acte-rapide">
                    + Nouvel acte
                </button>
            @endif
        </div>
    </div>

    {{-- 1. Barre de recherche simple (Section 9.4) --}}
    <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 16px 20px; margin-bottom: 24px;">
        <form action="{{ route('actes.recherche') }}" method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            @if(!empty($criteres['id_registre']))
                <input type="hidden" name="id_registre" value="{{ $criteres['id_registre'] }}">
            @endif
            <div style="flex: 1; min-width: 260px;">
                <label for="input-recherche-q" class="sr-only" style="display: none;">Recherche rapide :</label>
                <input
                    type="text"
                    name="q"
                    id="input-recherche-q"
                    class="form-control"
                    placeholder="Rechercher par numéro d'acte, année, cote, ou nom..."
                    value="{{ $criteres['q'] ?? '' }}"
                    style="font-size: 15px; padding: 10px 14px;"
                >
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">
                    Rechercher
                </button>

                @if(!empty(array_filter($criteres)))
                    <a href="{{ route('actes.recherche') }}" class="btn btn-secondary" style="text-decoration: none;">
                        ✕ Effacer
                    </a>
                @endif
            </div>
        </form>

        {{-- Badges des critères actifs --}}
        @php
            $filtresActifs = array_filter($criteres, fn($v) => $v !== null && $v !== '');
            $registreFiltre = !empty($criteres['id_registre']) ? $registres->firstWhere('id_registre', (int) $criteres['id_registre']) : null;
        @endphp
        @if(!empty($filtresActifs))
            <div style="display: flex; gap: 8px; align-items: center; margin-top: 12px; flex-wrap: wrap; font-size: 12px;">
                <span style="font-weight: 700; text-transform: uppercase;">Filtres actifs :</span>
                @foreach($filtresActifs as $cle => $valeur)
                    @if($cle !== 'page')
                        <span style="border: 1px solid var(--noir); padding: 2px 8px; background-color: var(--menthe); color: var(--noir); font-weight: 700;">
                            @if($cle === 'id_registre' && $registreFiltre)
                                REGISTRE : {{ $registreFiltre->cote }} ({{ $registreFiltre->type->libelle() }})
                            @else
                                {{ strtoupper($cle) }} : {{ $valeur }}
                            @endif
                        </span>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- 2. Compteur et En-tête des résultats (Section 9.4) --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 15px; font-weight: 800;">
            @if($resultats->total() === 0)
                Aucun résultat
            @elseif($resultats->total() === 1)
                1 acte répertorié trouvé
            @else
                {{ $resultats->total() }} actes répertoriés trouvés
            @endif
        </div>

        <div style="font-size: 12px; font-weight: 600;">
            Page {{ $resultats->currentPage() }} sur {{ max(1, $resultats->lastPage()) }}
        </div>
    </div>

    {{-- 3. Tableau des résultats ou Message d'orientation si aucun résultat --}}
    @if($resultats->isNotEmpty())
        <div class="table-responsive" style="border: 2px solid var(--noir); background-color: var(--blanc); margin-bottom: 24px;">
            <table class="table-sigaec">
                <thead>
                    <tr>
                        <th style="width: 25%;">Personne(s) mentionnée(s)</th>
                        <th style="width: 12%;">Type d'acte</th>
                        <th style="width: 15%;">Numéro officiel · Année</th>
                        <th style="width: 25%;">Registre & Emplacement physique</th>
                        <th style="width: 13%;">Copie numérique</th>
                        <th style="width: 10%; text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resultats as $acte)
                        <tr>
                            <td>
                                <strong>{{ $rechercheService->resoudrePersonnePrincipale($acte, auth()->user()) }}</strong>
                                <div style="font-size: 11px; font-weight: 700; margin-top: 2px;">
                                    {{ $acte->identifiant_acte }}
                                </div>
                            </td>
                            <td>
                                <span style="border: 1px solid var(--noir); padding: 2px 6px; font-size: 11px; font-weight: 800; background-color: var(--blanc);">
                                    {{ $acte->type->libelle() }}
                                </span>
                            </td>
                            <td>
                                <strong>Acte n° {{ $acte->numero_acte }}</strong>
                                <div style="font-size: 12px;">Année {{ $acte->annee }}</div>
                                @if($acte->possedeJugement())
                                    <div style="font-size: 11px; font-weight: 800; margin-top: 2px;">
                                        ⚖ Jugement {{ $acte->numero_jugement ? 'n° ' . $acte->numero_jugement : '' }} {{ $acte->annee_jugement ? '(' . $acte->annee_jugement . ')' : '' }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <a href="{{ route('registres.index', ['detail' => $acte->id_registre]) }}" style="font-weight: 800;">
                                        {{ $acte->registre->cote }}
                                    </a>
                                </div>
                                <div style="font-size: 12px; margin-top: 2px;">
                                    📍 {{ $acte->registre->localisation ?? 'Magasin central' }}
                                </div>
                            </td>
                            <td>
                                @if($acte->fichierActif)
                                    <x-statut type="positif" texte="Numérisé" />
                                    <div style="font-size: 11px; font-weight: 700; margin-top: 2px;">
                                        {{ strtoupper($acte->fichierActif->format) }} ({{ round(($acte->fichierActif->taille_octets ?? 0) / 1024, 0) }} Ko)
                                    </div>
                                @else
                                    <x-statut type="attente" texte="Non numérisé" />
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('actes.show', $acte->id_acte) }}" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px; text-decoration: none; display: inline-block;">
                                    Ouvrir →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination conforme --}}
        <div style="margin-bottom: 32px;">
            {{ $resultats->links() }}
        </div>
    @else
        {{-- Message clair et structuré si aucun résultat (Section 9.4) --}}
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 32px 24px; margin-bottom: 24px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="font-size: 20px; font-weight: 800; margin-bottom: 8px;">
                    Aucun acte ne correspond à vos critères de recherche
                </div>
                <p style="font-size: 14px; margin: 0; max-width: 600px; margin-left: auto; margin-right: auto;">
                    Le système n'a trouvé aucune transcription d'acte correspondant exactement aux critères renseignés.
                </p>
            </div>

            <div style="border-top: 2px solid var(--noir); padding-top: 20px; max-width: 680px; margin: 0 auto;">
                <div style="font-weight: 800; font-size: 14px; text-transform: uppercase; margin-bottom: 12px;">
                    Conseils d'orientation archivistique (Mairie de Tattaguine) :
                </div>

                <ul style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8;">
                    <li>
                        <strong>Vérifiez l'orthographe :</strong> Les transcriptions anciennes de noms sérères ou wolofs comportent fréquemment des variantes phonétiques (ex: <em>Sarr / Sar</em>, <em>Ndiaye / N'diaye</em>, <em>Faye / Faï</em>).
                    </li>
                    <li>
                        <strong>Contrôlez le numéro officiel :</strong> Assurez-vous que le numéro correspond bien au numéro de déclaration porté en marge du feuillet et non au numéro de volume.
                    </li>
                    <li>
                        <strong>Élargissez la période :</strong> Enregistrez une recherche sur une plage d'années plus large (± 2 à 3 ans autour de la date présumée du fait d'état civil).
                    </li>
                    <li>
                        <strong>Vérifiez l'inventaire physique :</strong> Le registre recherché est peut-être classé comme « En cours d'inventaire » ou « À restaurer ». Consultez le module <a href="{{ route('registres.index') }}"><strong>Inventaire des registres</strong></a>.
                    </li>
                </ul>

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ route('actes.recherche') }}" class="btn btn-secondary">
                        Réinitialiser la recherche
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- MODALE POP-UP : Recherche avancée multicritères (Section 9.4) --}}
    <x-modal id="modal-recherche-avancee" titre="Recherche multicritères avancée d'actes">
        <form action="{{ route('actes.recherche') }}" method="GET">
            <div style="display: flex; gap: 16px;">
                <div class="form-group" style="flex: 1;">
                    <label for="filtre_type" class="form-label">Type d'acte :</label>
                    <select name="type" id="filtre_type" class="form-select">
                        <option value="">Tous les types d'actes</option>
                        @foreach(App\Enums\TypeActe::cases() as $t)
                            <option value="{{ $t->value }}" {{ ($criteres['type'] ?? '') === $t->value ? 'selected' : '' }}>
                                {{ $t->libelle() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="filtre_num" class="form-label">Numéro d'acte officiel :</label>
                    <input type="text" name="numero_acte" id="filtre_num" class="form-control" value="{{ $criteres['numero_acte'] ?? '' }}" placeholder="Ex: 0042">
                </div>
            </div>

            <div style="display: flex; gap: 16px;">
                <div class="form-group" style="flex: 1;">
                    <label for="filtre_annee_debut" class="form-label">Année de début :</label>
                    <input type="number" name="annee_debut" id="filtre_annee_debut" class="form-control" value="{{ $criteres['annee_debut'] ?? '' }}" placeholder="Ex: 2010">
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="filtre_annee_fin" class="form-label">Année de fin :</label>
                    <input type="number" name="annee_fin" id="filtre_annee_fin" class="form-control" value="{{ $criteres['annee_fin'] ?? '' }}" placeholder="Ex: 2024">
                </div>
            </div>

            <div class="form-group">
                <label for="filtre_registre" class="form-label">Registre d'origine :</label>
                <select name="id_registre" id="filtre_registre" class="form-select">
                    <option value="">Tous les registres conservés</option>
                    @foreach($registres as $reg)
                        <option value="{{ $reg->id_registre }}" {{ ($criteres['id_registre'] ?? '') == $reg->id_registre ? 'selected' : '' }}>
                            {{ $reg->cote }} ({{ $reg->type->libelle() }} · {{ $reg->annee_debut }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Bloc de recherche nominative selon habilitation --}}
            <div style="border: 1px solid var(--noir); padding: 12px 16px; margin-bottom: 16px; background-color: var(--blanc);">
                <div style="font-size: 13px; font-weight: 800; margin-bottom: 8px; text-transform: uppercase;">
                    Recherche nominative (selon droits) :
                </div>

                @if(auth()->check() && auth()->user()->aDroit('actes.recherche_nominative'))
                    <div style="display: flex; gap: 16px;">
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="filtre_nom" class="form-label">Nom patronymique :</label>
                            <input type="text" name="nom" id="filtre_nom" class="form-control" value="{{ $criteres['nom'] ?? '' }}" placeholder="Ex: SARR, DIOP...">
                        </div>

                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label for="filtre_prenoms" class="form-label">Prénoms :</label>
                            <input type="text" name="prenoms" id="filtre_prenoms" class="form-control" value="{{ $criteres['prenoms'] ?? '' }}" placeholder="Ex: Babacar, Aminata...">
                        </div>
                    </div>
                @else
                    <div style="font-size: 13px; color: var(--noir);">
                        <em>Votre profil d'habilitation ne permet pas la recherche directe par données nominatives.</em>
                    </div>
                @endif
            </div>

            <div class="form-group">
                <label for="filtre_copie" class="form-label">Présence d'une copie numérique :</label>
                <select name="avec_copie" id="filtre_copie" class="form-select">
                    <option value="">Tous (numérisés ou non)</option>
                    <option value="1" {{ ($criteres['avec_copie'] ?? '') === '1' ? 'selected' : '' }}>Uniquement avec copie numérique</option>
                    <option value="0" {{ ($criteres['avec_copie'] ?? '') === '0' ? 'selected' : '' }}>Uniquement sans copie numérique (à numériser)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <a href="{{ route('actes.recherche') }}" class="btn btn-secondary" style="text-decoration: none;">
                    Réinitialiser
                </a>
                <button type="submit" class="btn btn-primary">
                    Appliquer les critères avancés
                </button>
            </div>
        </form>
    </x-modal>

    {{-- MODALE POP-UP : Nouvel acte rapide --}}
    @if(auth()->check() && auth()->user()->aDroit('actes.creer'))
        <x-modal id="modal-creer-acte-rapide" titre="Indexation d'un nouvel acte d'état civil">
            <form action="{{ route('actes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_modal_source" value="modal-creer-acte-rapide">

                <div class="form-group">
                    <label for="creer_id_reg" class="form-label">Registre de rattachement : *</label>
                    <select name="id_registre" id="creer_id_reg" class="form-select" required>
                        @foreach($registres as $reg)
                            <option value="{{ $reg->id_registre }}" {{ old('id_registre', $criteres['id_registre'] ?? '') == $reg->id_registre ? 'selected' : '' }}>
                                {{ $reg->cote }} ({{ $reg->type->libelle() }} · {{ $reg->annee_debut }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @php
                    $typePreselectionne = old('type', ($registreFiltre ? $registreFiltre->type->value : 'NAISSANCE'));
                @endphp

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_t" class="form-label">Type d'acte : *</label>
                        <select name="type" id="creer_t" class="form-select" required onchange="basculerChampsTypeActe(this.value, 'creer_rapide')">
                            @foreach(App\Enums\TypeActe::cases() as $t)
                                <option value="{{ $t->value }}" {{ $typePreselectionne === $t->value ? 'selected' : '' }}>{{ $t->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_n" class="form-label">Numéro officiel : *</label>
                        <input type="text" name="numero_acte" id="creer_n" class="form-control" value="{{ old('numero_acte') }}" placeholder="Ex: 0045" required>
                    </div>

                    <div class="form-group" style="width: 130px;">
                        <label for="creer_a" class="form-label">Année : *</label>
                        <input type="number" name="annee" id="creer_a" class="form-control" value="{{ old('annee', $registreFiltre ? $registreFiltre->annee_debut : date('Y')) }}" required>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_de" class="form-label">Date d'enregistrement :</label>
                        <input type="date" name="date_enregistrement" id="creer_de" class="form-control" value="{{ old('date_enregistrement', date('Y-m-d')) }}">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_pf" class="form-label">Folio / Page :</label>
                        <input type="text" name="page_folio" id="creer_pf" class="form-control" value="{{ old('page_folio') }}" placeholder="Ex: Page 12">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_ev" class="form-label">État de vérification : *</label>
                        <select name="etat_verification" id="creer_ev" class="form-select" required>
                            @foreach(App\Enums\EtatVerification::cases() as $ev)
                                <option value="{{ $ev->value }}" {{ old('etat_verification') === $ev->value ? 'selected' : '' }}>{{ $ev->libelle() }}</option>
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
                            <input type="text" name="numero_jugement" class="form-control" value="{{ old('numero_jugement') }}" placeholder="Ex: 0114" inputmode="numeric" pattern="[0-9]*">
                        </div>
                        <div class="form-group" style="flex: 1; margin-bottom: 0;">
                            <label class="form-label">Date du jugement :</label>
                            <input type="date" name="date_jugement" class="form-control" value="{{ old('date_jugement') }}">
                        </div>
                        <div class="form-group" style="width: 130px; margin-bottom: 0;">
                            <label class="form-label">Année jugement :</label>
                            <input type="number" name="annee_jugement" class="form-control" value="{{ old('annee_jugement') }}" placeholder="Ex: 2020">
                        </div>
                    </div>
                </div>

                {{-- Champs de transcription selon le type d'acte --}}
                <div id="creer_rapide_bloc_NAISSANCE" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $typePreselectionne === 'NAISSANCE' ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de naissance :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'enfant :</label>
                            <input type="text" name="nom_enfant" class="form-control" value="{{ old('nom_enfant') }}" placeholder="Ex: FAYE">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'enfant :</label>
                            <input type="text" name="prenoms_enfant" class="form-control" value="{{ old('prenoms_enfant') }}" placeholder="Ex: Moussa">
                        </div>
                        <div class="form-group" style="width: 130px;">
                            <label class="form-label">Sexe :</label>
                            <select name="sexe" class="form-select">
                                <option value="">—</option>
                                <option value="Masculin" {{ old('sexe') === 'Masculin' ? 'selected' : '' }}>Masculin</option>
                                <option value="Féminin" {{ old('sexe') === 'Féminin' ? 'selected' : '' }}>Féminin</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date de naissance :</label>
                            <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu de naissance :</label>
                            <input type="text" name="lieu_naissance" class="form-control" value="{{ old('lieu_naissance') }}" placeholder="Ex: Maternité de Tattaguine">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date de déclaration :</label>
                            <input type="date" name="date_declaration" class="form-control" value="{{ old('date_declaration') }}">
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms du père :</label>
                            <input type="text" name="prenoms_pere" class="form-control" value="{{ old('prenoms_pere') }}" placeholder="Prénoms du père...">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de la mère :</label>
                            <input type="text" name="nom_mere" class="form-control" value="{{ old('nom_mere') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de la mère :</label>
                            <input type="text" name="prenoms_mere" class="form-control" value="{{ old('prenoms_mere') }}">
                        </div>
                    </div>
                </div>

                <div id="creer_rapide_bloc_MARIAGE" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $typePreselectionne === 'MARIAGE' ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de mariage :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date du mariage :</label>
                            <input type="date" name="date_mariage" class="form-control" value="{{ old('date_mariage') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu du mariage :</label>
                            <input type="text" name="lieu_mariage" class="form-control" value="{{ old('lieu_mariage') }}" placeholder="Ex: Tattaguine">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Régime matrimonial :</label>
                            <input type="text" name="regime_matrimonial" class="form-control" value="{{ old('regime_matrimonial') }}" placeholder="Ex: Monogamie">
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'époux :</label>
                            <input type="text" name="nom_epoux" class="form-control" value="{{ old('nom_epoux') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'époux :</label>
                            <input type="text" name="prenoms_epoux" class="form-control" value="{{ old('prenoms_epoux') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom de l'épouse :</label>
                            <input type="text" name="nom_epouse" class="form-control" value="{{ old('nom_epouse') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms de l'épouse :</label>
                            <input type="text" name="prenoms_epouse" class="form-control" value="{{ old('prenoms_epouse') }}">
                        </div>
                    </div>
                </div>

                <div id="creer_rapide_bloc_DECES" style="border-top: 1px solid var(--noir); padding-top: 12px; margin-top: 8px; display: {{ $typePreselectionne === 'DECES' ? 'block' : 'none' }};">
                    <div style="font-weight: 800; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;">Transcription des mentions de décès :</div>
                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Nom du défunt :</label>
                            <input type="text" name="nom_defunt" class="form-control" value="{{ old('nom_defunt') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Prénoms du défunt :</label>
                            <input type="text" name="prenoms_defunt" class="form-control" value="{{ old('prenoms_defunt') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Date du décès :</label>
                            <input type="date" name="date_deces" class="form-control" value="{{ old('date_deces') }}">
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Lieu du décès :</label>
                            <input type="text" name="lieu_deces" class="form-control" value="{{ old('lieu_deces') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="creer_obs_rapide" class="form-label">Observations archivistiques :</label>
                    <textarea name="observations" id="creer_obs_rapide" class="form-textarea" placeholder="Mentions marginales, particularités de transcription...">{{ old('observations') }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-creer-acte-rapide">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Créer l'acte
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
