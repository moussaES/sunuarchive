@extends('layouts.app')

@section('titre', 'Inventaire des Registres')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="titre-page" style="margin-bottom: 4px;">Inventaire des Registres</h1>
            <div style="font-size: 14px; font-weight: 600;">
                Gestion, conservation et localisation des registres physiques de l'état civil.
            </div>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            @if(auth()->check() && auth()->user()->aDroit('rapports.exporter'))
                <a href="{{ route('registres.export.csv', request()->all()) }}" class="btn btn-secondary">
                    ■ Exporter la liste à contrôler (CSV)
                </a>
            @endif

            @if(auth()->check() && auth()->user()->aDroit('registres.creer'))
                <button type="button" class="btn btn-primary" data-modal-open="modal-creer-registre">
                    + Nouveau registre
                </button>
            @endif
        </div>
    </div>

    {{-- Barre de filtres de recherche (Section 9.3) --}}
    <x-carte style="margin-bottom: 20px; padding: 18px 24px;">
        <form method="GET" action="{{ route('registres.index') }}" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label for="filtre_recherche" class="form-label">Recherche libre :</label>
                <input
                    type="text"
                    id="filtre_recherche"
                    name="recherche"
                    class="form-control"
                    placeholder="Cote, localisation, mot-clé..."
                    value="{{ $filtres['recherche'] ?? '' }}"
                >
            </div>

            <div style="width: 180px;">
                <label for="filtre_type" class="form-label">Type d'acte :</label>
                <select id="filtre_type" name="type" class="form-select">
                    <option value="">Tous les types</option>
                    @foreach(App\Enums\TypeActe::cases() as $type)
                        <option value="{{ $type->value }}" {{ ($filtres['type'] ?? '') === $type->value ? 'selected' : '' }}>
                            {{ $type->libelle() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 180px;">
                <label for="filtre_etat" class="form-label">État matériel :</label>
                <select id="filtre_etat" name="etat_materiel" class="form-select">
                    <option value="">Tous les états</option>
                    @foreach(App\Enums\EtatMateriel::cases() as $etat)
                        <option value="{{ $etat->value }}" {{ ($filtres['etat_materiel'] ?? '') === $etat->value ? 'selected' : '' }}>
                            {{ $etat->libelle() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="width: 180px;">
                <label for="filtre_presence" class="form-label">Présence physique :</label>
                <select id="filtre_presence" name="presence" class="form-select">
                    <option value="">Toutes présences</option>
                    @foreach(App\Enums\PresencePhysique::cases() as $pres)
                        <option value="{{ $pres->value }}" {{ ($filtres['presence'] ?? '') === $pres->value ? 'selected' : '' }}>
                            {{ $pres->libelle() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="min-height: 44px;">
                    Filtrer
                </button>
                @if(!empty(array_filter($filtres)))
                    <a href="{{ route('registres.index') }}" class="btn btn-secondary" style="min-height: 44px;">
                        Réinitialiser
                    </a>
                @endif
            </div>
        </form>
    </x-carte>

    {{-- Disposition 2 colonnes : Tableau d'inventaire à gauche + Volet de détail à droite --}}
    <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
        {{-- Tableau des registres --}}
        <div style="flex: 2; min-width: 320px;">
            <div class="table-responsive">
                <table class="table-sigaec" id="table-registres">
                    <thead>
                        <tr>
                            <th>Cote archivistique</th>
                            <th>Type</th>
                            <th>Années</th>
                            <th>État matériel</th>
                            <th>Présence</th>
                            <th>Inventaire</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registres as $reg)
                            @php
                                $estSelectionne = ($registreSelectionne && $registreSelectionne->id_registre === $reg->id_registre);
                            @endphp
                            <tr
                                style="{{ $estSelectionne ? 'border-left: 4px solid var(--noir); background-color: var(--blanc);' : '' }} cursor: pointer;"
                                onclick="window.location.href='{{ route('registres.index', array_merge(request()->all(), ['detail' => $reg->id_registre])) }}'"
                            >
                                <td>
                                    <strong>{{ $reg->cote }}</strong>
                                    <div style="font-size: 12px; margin-top: 2px;">
                                        {{ $reg->actes_count }} acte(s) indexé(s)
                                    </div>
                                </td>
                                <td>{{ $reg->type->libelle() }}</td>
                                <td>{{ $reg->annee_debut }} — {{ $reg->annee_fin }}</td>
                                <td>
                                    <x-statut :type="$reg->etat_materiel->classeStatut()" :texte="$reg->etat_materiel->libelle()" />
                                </td>
                                <td>
                                    <x-statut :type="$reg->presence->classeStatut()" :texte="$reg->presence->libelle()" />
                                </td>
                                <td>
                                    @if($reg->estInventorie())
                                        <x-statut type="positif" texte="Complet" />
                                    @else
                                        <x-statut type="attente" texte="À traiter" />
                                    @endif
                                </td>
                                <td>
                                    <a
                                        href="{{ route('registres.index', array_merge(request()->all(), ['detail' => $reg->id_registre])) }}"
                                        class="btn btn-secondary"
                                        style="min-height: 32px; padding: 4px 10px; font-size: 13px;"
                                        onclick="event.stopPropagation();"
                                    >
                                        Consulter
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 32px;">
                                    <strong>Aucun registre ne correspond aux critères de sélection.</strong>
                                    <div style="margin-top: 8px; font-size: 13px;">
                                        Vérifiez l'orthographe de la cote ou réinitialisez les filtres.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                <div style="font-size: 13px; font-weight: 700;">
                    Total : {{ method_exists($registres, 'total') ? $registres->total() : $registres->count() }} registre(s) répertorié(s)
                </div>

                @if(method_exists($registres, 'links'))
                    <div>
                        {{ $registres->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Volet de détail du registre sélectionné (Section 9.3) --}}
        <div style="flex: 1; min-width: 300px;">
            @if($registreSelectionne)
                <x-carte titre="Volet de détail du registre">
                    <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--noir);">
                        <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">Cote de classement</div>
                        <div style="font-size: 20px; font-weight: 900;">{{ $registreSelectionne->cote }}</div>
                        <div style="font-size: 14px; font-weight: 600; margin-top: 2px;">
                            {{ $registreSelectionne->type->libelle() }} · Années {{ $registreSelectionne->annee_debut }} - {{ $registreSelectionne->annee_fin }}
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 14px; margin-bottom: 20px;">
                        <div>
                            <strong>Volume / Feuillets :</strong>
                            Volume {{ $registreSelectionne->numero_volume ?? '1' }} · {{ $registreSelectionne->nombre_pages ?? '?' }} pages
                        </div>

                        <div>
                            <strong>Actes répertoriés / indexés :</strong>
                            {{ $registreSelectionne->nombre_actes ?? 'Non renseigné' }} répertorié(s) ·
                            <strong>{{ $registreSelectionne->actes_count }}</strong> indexé(s) dans SIGAEC
                        </div>

                        <div>
                            <strong>Localisation physique :</strong>
                            <div style="margin-top: 2px; padding: 6px 10px; border: 1px solid var(--noir); background-color: var(--blanc); font-weight: 600;">
                                {{ $registreSelectionne->localisation ?? 'Non précisée' }}
                            </div>
                        </div>

                        <div>
                            <strong>État de conservation :</strong>
                            <div style="margin-top: 4px;">
                                <x-statut :type="$registreSelectionne->etat_materiel->classeStatut()" :texte="$registreSelectionne->etat_materiel->libelle()" />
                            </div>
                        </div>

                        <div>
                            <strong>Présence en magasin :</strong>
                            <div style="margin-top: 4px;">
                                <x-statut :type="$registreSelectionne->presence->classeStatut()" :texte="$registreSelectionne->presence->libelle()" />
                            </div>
                        </div>

                        <div>
                            <strong>Dernier contrôle d'inventaire :</strong>
                            {{ $registreSelectionne->date_inventaire ? $registreSelectionne->date_inventaire->format('d/m/Y') : 'Aucun contrôle enregistré' }}
                        </div>

                        @if($registreSelectionne->observations)
                            <div>
                                <strong>Observations archivistiques :</strong>
                                <div style="margin-top: 4px; padding: 8px 10px; border: 1px solid var(--noir); background-color: var(--blanc); font-size: 13px;">
                                    {{ $registreSelectionne->observations }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Boutons d'action du volet de détail (Section 9.3) --}}
                    <div style="display: flex; flex-direction: column; gap: 8px; border-top: 1px solid var(--noir); padding-top: 16px;">
                        @if(!auth()->check() || auth()->user()->aDroit('actes.consulter'))
                            <a href="{{ route('actes.recherche', ['id_registre' => $registreSelectionne->id_registre]) }}" class="btn btn-primary" style="width: 100%;">
                                ■ Voir les actes de ce registre
                            </a>
                        @endif

                        @if(auth()->check() && auth()->user()->aDroit('registres.modifier'))
                            <button
                                type="button"
                                class="btn btn-secondary"
                                data-modal-open="modal-modifier-registre-{{ $registreSelectionne->id_registre }}"
                                style="width: 100%;"
                            >
                                Modifier la fiche registre
                            </button>
                        @endif
                    </div>
                </x-carte>
            @else
                <x-carte titre="Volet de détail">
                    <p style="font-size: 14px; text-align: center; padding: 24px;">
                        Sélectionnez une ligne dans l'inventaire pour afficher le détail archivistique du registre.
                    </p>
                </x-carte>
            @endif
        </div>
    </div>

    {{-- Fenêtre modale de création d'un nouveau registre (Section 9.3 & POP-UP demandé) --}}
    @if(auth()->check() && auth()->user()->aDroit('registres.creer'))
        <x-modal id="modal-creer-registre" titre="Nouveau registre d'état civil">
            <form action="{{ route('registres.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="creer_cote" class="form-label">Cote archivistique officielle : *</label>
                    <input
                        type="text"
                        name="cote"
                        id="creer_cote"
                        class="form-control"
                        placeholder="Ex : EC-NAI-2024-REG008"
                        required
                    >
                    <div class="form-hint">Format normalisé : EC-[TYPE]-[ANNEE]-[REGnnn]</div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_type" class="form-label">Type de registre : *</label>
                        <select name="type" id="creer_type" class="form-select" required>
                            @foreach(App\Enums\TypeActe::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="width: 120px;">
                        <label for="creer_annee_debut" class="form-label">Année début : *</label>
                        <input type="number" name="annee_debut" id="creer_annee_debut" class="form-control" min="1850" max="2100" value="{{ date('Y') }}" required>
                    </div>

                    <div class="form-group" style="width: 120px;">
                        <label for="creer_annee_fin" class="form-label">Année fin : *</label>
                        <input type="number" name="annee_fin" id="creer_annee_fin" class="form-control" min="1850" max="2100" value="{{ date('Y') }}" required>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_volume" class="form-label">Numéro de volume :</label>
                        <input type="text" name="numero_volume" id="creer_volume" class="form-control" placeholder="1" value="1">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_pages" class="form-label">Nombre de pages :</label>
                        <input type="number" name="nombre_pages" id="creer_pages" class="form-control" min="1" placeholder="Ex : 100">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_actes" class="form-label">Nombre d'actes :</label>
                        <input type="number" name="nombre_actes" id="creer_actes" class="form-control" min="0" placeholder="Si vérifié">
                    </div>
                </div>

                <div class="form-group">
                    <label for="creer_localisation" class="form-label">Localisation physique dans les magasins :</label>
                    <input
                        type="text"
                        name="localisation"
                        id="creer_localisation"
                        class="form-control"
                        placeholder="Ex : Salle d'archives / Armoire A / Étagère 1 / Boîte B3"
                    >
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="creer_etat" class="form-label">État matériel : *</label>
                        <select name="etat_materiel" id="creer_etat" class="form-select" required>
                            @foreach(App\Enums\EtatMateriel::cases() as $etat)
                                <option value="{{ $etat->value }}">{{ $etat->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="creer_presence" class="form-label">Présence physique : *</label>
                        <select name="presence" id="creer_presence" class="form-select" required>
                            @foreach(App\Enums\PresencePhysique::cases() as $pres)
                                <option value="{{ $pres->value }}">{{ $pres->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label for="creer_date_inventaire" class="form-label">Date du contrôle d'inventaire :</label>
                        <input type="date" name="date_inventaire" id="creer_date_inventaire" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>

                    <div style="flex: 1; padding-top: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700;">
                            <input type="checkbox" name="inventaire_complet" value="1" style="width: 18px; height: 18px;">
                            <span>Registre entièrement inventorié</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="creer_observations" class="form-label">Observations archivistiques :</label>
                    <textarea name="observations" id="creer_observations" class="form-textarea" placeholder="Feuillets manquants, dégradations, particularités de reliure..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-creer-registre">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Enregistrer le registre
                    </button>
                </div>
            </form>
        </x-modal>
    @endif

    {{-- Fenêtre modale de modification du registre sélectionné --}}
    @if($registreSelectionne && auth()->check() && auth()->user()->aDroit('registres.modifier'))
        <x-modal id="modal-modifier-registre-{{ $registreSelectionne->id_registre }}" titre="Modifier la fiche du registre {{ $registreSelectionne->cote }}">
            <form action="{{ route('registres.update', $registreSelectionne->id_registre) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="mod_cote" class="form-label">Cote archivistique : *</label>
                    <input
                        type="text"
                        name="cote"
                        id="mod_cote"
                        class="form-control"
                        value="{{ $registreSelectionne->cote }}"
                        required
                    >
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="mod_type" class="form-label">Type d'acte : *</label>
                        <select name="type" id="mod_type" class="form-select" required>
                            @foreach(App\Enums\TypeActe::cases() as $type)
                                <option value="{{ $type->value }}" {{ $registreSelectionne->type === $type ? 'selected' : '' }}>
                                    {{ $type->libelle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="width: 120px;">
                        <label for="mod_annee_debut" class="form-label">Année début : *</label>
                        <input type="number" name="annee_debut" id="mod_annee_debut" class="form-control" value="{{ $registreSelectionne->annee_debut }}" required>
                    </div>

                    <div class="form-group" style="width: 120px;">
                        <label for="mod_annee_fin" class="form-label">Année fin : *</label>
                        <input type="number" name="annee_fin" id="mod_annee_fin" class="form-control" value="{{ $registreSelectionne->annee_fin }}" required>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="mod_volume" class="form-label">Numéro de volume :</label>
                        <input type="text" name="numero_volume" id="mod_volume" class="form-control" value="{{ $registreSelectionne->numero_volume }}">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="mod_pages" class="form-label">Nombre de pages :</label>
                        <input type="number" name="nombre_pages" id="mod_pages" class="form-control" value="{{ $registreSelectionne->nombre_pages }}">
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="mod_actes" class="form-label">Nombre d'actes :</label>
                        <input type="number" name="nombre_actes" id="mod_actes" class="form-control" value="{{ $registreSelectionne->nombre_actes }}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="mod_localisation" class="form-label">Localisation physique :</label>
                    <input
                        type="text"
                        name="localisation"
                        id="mod_localisation"
                        class="form-control"
                        value="{{ $registreSelectionne->localisation }}"
                    >
                </div>

                <div style="display: flex; gap: 16px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="mod_etat" class="form-label">État matériel : *</label>
                        <select name="etat_materiel" id="mod_etat" class="form-select" required>
                            @foreach(App\Enums\EtatMateriel::cases() as $etat)
                                <option value="{{ $etat->value }}" {{ $registreSelectionne->etat_materiel === $etat ? 'selected' : '' }}>
                                    {{ $etat->libelle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="mod_presence" class="form-label">Présence physique : *</label>
                        <select name="presence" id="mod_presence" class="form-select" required>
                            @foreach(App\Enums\PresencePhysique::cases() as $pres)
                                <option value="{{ $pres->value }}" {{ $registreSelectionne->presence === $pres ? 'selected' : '' }}>
                                    {{ $pres->libelle() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label for="mod_date_inventaire" class="form-label">Date du dernier contrôle :</label>
                        <input type="date" name="date_inventaire" id="mod_date_inventaire" class="form-control" value="{{ $registreSelectionne->date_inventaire ? $registreSelectionne->date_inventaire->format('Y-m-d') : '' }}">
                    </div>

                    <div style="flex: 1; padding-top: 24px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700;">
                            <input type="checkbox" name="inventaire_complet" value="1" {{ $registreSelectionne->inventaire_complet ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            <span>Registre entièrement inventorié</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mod_observations" class="form-label">Observations archivistiques :</label>
                    <textarea name="observations" id="mod_observations" class="form-textarea">{{ $registreSelectionne->observations }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" data-modal-close="modal-modifier-registre-{{ $registreSelectionne->id_registre }}">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Mettre à jour la fiche
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
@endsection
