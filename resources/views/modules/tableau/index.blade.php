@extends('layouts.app')

@section('titre', 'Tableau de bord — Mairie de Tattaguine')

@section('content')
    {{-- En-tête du tableau de bord --}}
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 class="titre-page" style="margin-bottom: 4px;">Tableau de bord de conservation</h1>
            <p style="margin: 0; font-size: 14px; font-weight: 600;">
                Supervision générale des archives de l'état civil de la Mairie de Tattaguine.
            </p>
        </div>

        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            @if(!auth()->check() || auth()->user()->aDroit('actes.consulter'))
                <a href="{{ route('actes.recherche') }}" class="btn btn-primary" style="text-decoration: none;">
                    🔍 Rechercher un acte
                </a>
            @endif

            @if(auth()->check() && auth()->user()->aDroit('rapports.exporter'))
                <a href="{{ route('rapports.pdf') }}" class="btn btn-secondary" style="text-decoration: none;">
                    📄 Rapport PDF officiel
                </a>
                <a href="{{ route('rapports.csv') }}" class="btn btn-secondary" style="text-decoration: none;">
                    📊 Export CSV d'inventaire
                </a>
            @endif
        </div>
    </div>

    {{-- Bandeau de contrôle du système et de sécurité (Section 9.2) --}}
    <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 16px 20px; margin-bottom: 24px;">
        <div style="font-size: 13px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
            État du système · Sécurité et intégrité des archives municipales
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; font-size: 13px;">
            {{-- Dernière sauvegarde --}}
            <div style="border: 1px solid var(--noir); padding: 10px 14px; background-color: var(--blanc);">
                <div style="font-weight: 700; font-size: 11px; text-transform: uppercase;">Dernière sauvegarde automatique</div>
                <div style="font-size: 15px; font-weight: 800; margin-top: 4px;">
                    @if($etat_systeme['derniere_sauvegarde'])
                        {{ $etat_systeme['derniere_sauvegarde']->date_sauvegarde->format('d/m/Y à H:i') }}
                    @else
                        Aucune sauvegarde
                    @endif
                </div>
                <div style="margin-top: 4px;">
                    @if($etat_systeme['derniere_sauvegarde'] && $etat_systeme['derniere_sauvegarde']->resultat === 'REUSSIE')
                        <x-statut type="positif" texte="Sauvegarde intègre" />
                    @else
                        <x-statut type="critique" texte="À planifier" />
                    @endif
                </div>
            </div>

            {{-- Dernier test de restauration --}}
            <div style="border: 1px solid var(--noir); padding: 10px 14px; background-color: var(--blanc);">
                <div style="font-weight: 700; font-size: 11px; text-transform: uppercase;">Dernier test de restauration</div>
                <div style="font-size: 15px; font-weight: 800; margin-top: 4px;">
                    @if($etat_systeme['dernier_test_restauration'])
                        {{ $etat_systeme['dernier_test_restauration']->date_sauvegarde->format('d/m/Y') }}
                        <span style="font-size: 12px; font-weight: normal;">({{ $etat_systeme['jours_depuis_test'] }} j)</span>
                    @else
                        Non réalisé
                    @endif
                </div>
                <div style="margin-top: 4px;">
                    @if($etat_systeme['rappel_trimestriel'])
                        <x-statut type="attention" texte="Test trimestriel à planifier" />
                    @else
                        <x-statut type="positif" texte="Conforme (< 90 jours)" />
                    @endif
                </div>
            </div>

            {{-- Intégrité des copies numériques --}}
            <div style="border: 1px solid var(--noir); padding: 10px 14px; background-color: var(--blanc);">
                <div style="font-weight: 700; font-size: 11px; text-transform: uppercase;">Intégrité cryptographique SHA-256</div>
                <div style="font-size: 15px; font-weight: 800; margin-top: 4px;">
                    {{ $etat_systeme['fichiers_valides'] }} / {{ $etat_systeme['fichiers_total'] }} fichiers intègres
                </div>
                <div style="margin-top: 4px;">
                    @if($etat_systeme['anomalies_fichiers'] === 0)
                        <x-statut type="positif" texte="0 altération ni disparition" />
                    @else
                        <x-statut type="critique" texte="{{ $etat_systeme['anomalies_fichiers'] }} anomalie(s) détectée(s)" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 1. Les 4 indicateurs de synthèse (Section 9.2) --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- Indicateur 1 : Registres inventoriés --}}
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 18px 20px;">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">1. Registres inventoriés</div>
            <div style="font-size: 28px; font-weight: 900; margin: 8px 0;">
                {{ $indicateurs['registres_inventories'] }} <span style="font-size: 16px; font-weight: 600;">/ {{ $indicateurs['total_registres'] }}</span>
            </div>
            <div style="font-size: 13px; font-weight: 700;">
                {{ $indicateurs['pourcentage_inventaire'] }} % des volumes traités
            </div>
        </div>

        {{-- Indicateur 2 : Actes indexés --}}
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 18px 20px;">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">2. Actes indexés en base</div>
            <div style="font-size: 28px; font-weight: 900; margin: 8px 0;">
                {{ $indicateurs['total_actes'] }}
            </div>
            <div style="font-size: 13px; font-weight: 700;">
                Transcriptions consultables
            </div>
        </div>

        {{-- Indicateur 3 : Fichiers numériques associés --}}
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 18px 20px;">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">3. Fichiers numérisés</div>
            <div style="font-size: 28px; font-weight: 900; margin: 8px 0;">
                {{ $indicateurs['total_fichiers_actifs'] }}
            </div>
            <div style="font-size: 13px; font-weight: 700;">
                Reproductions sous SHA-256
            </div>
        </div>

        {{-- Indicateur 4 : Fiches à vérifier --}}
        <div style="border: 2px solid var(--noir); background-color: var(--blanc); padding: 18px 20px;">
            <div style="font-size: 12px; font-weight: 700; text-transform: uppercase;">4. Fiches d'actes à vérifier</div>
            <div style="font-size: 28px; font-weight: 900; margin: 8px 0;">
                {{ $indicateurs['fiches_a_verifier'] }}
            </div>
            <div style="font-size: 13px; font-weight: 700;">
                À vérifier ou illisibles
            </div>
        </div>
    </div>

    {{-- Disposition 2 colonnes : Progression de numérisation à gauche, Registres à surveiller à droite --}}
    <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 32px;">
        {{-- Colonne gauche : Progression de numérisation par type d'acte --}}
        <div style="flex: 1; min-width: 320px;">
            <x-carte titre="Progression de numérisation par type d'acte">
                <p style="font-size: 13px; margin-top: 0; margin-bottom: 16px;">
                    Proportion d'actes d'état civil rattachés à une copie numérique de consultation.
                </p>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    @foreach($progression as $cle => $item)
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 800; margin-bottom: 6px;">
                                <span>Acte de {{ $item['libelle'] }}</span>
                                <span>{{ $item['pourcentage'] }} % ({{ $item['numerises'] }} / {{ $item['total'] }})</span>
                            </div>

                            {{-- Jauge de progression stricte noir / vert menthe (Section 3 & 9.2) --}}
                            <div style="border: 2px solid var(--noir); background-color: var(--blanc); height: 16px; width: 100%; box-sizing: border-box;">
                                <div style="background-color: var(--menthe); height: 100%; width: {{ $item['pourcentage'] }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(!auth()->check() || auth()->user()->aDroit('registres.consulter'))
                    <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--noir); font-size: 12px;">
                        <a href="{{ route('registres.index') }}" style="font-weight: 800;">
                            Consulter l'inventaire physique des registres →
                        </a>
                    </div>
                @endif
            </x-carte>
        </div>

        {{-- Colonne droite : Liste des Registres à surveiller --}}
        <div style="flex: 1.4; min-width: 340px;">
            <x-carte titre="Registres physiques à surveiller">
                <p style="font-size: 13px; margin-top: 0; margin-bottom: 12px;">
                    Volumes présentant un état matériel dégradé ou une présence physique nécessitant contrôle.
                </p>

                @if($registres_a_surveiller->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table-sigaec">
                            <thead>
                                <tr>
                                    <th>Cote</th>
                                    <th>Nature & Années</th>
                                    <th>Emplacement</th>
                                    <th>État matériel</th>
                                    <th>Présence</th>
                                    <th style="text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($registres_a_surveiller as $reg)
                                    <tr>
                                        <td>
                                            @if(!auth()->check() || auth()->user()->aDroit('registres.consulter'))
                                                <a href="{{ route('registres.index', ['detail' => $reg->id_registre]) }}" style="font-weight: 800;">
                                                    {{ $reg->cote }}
                                                </a>
                                            @else
                                                <strong style="font-weight: 800;">{{ $reg->cote }}</strong>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $reg->type->libelle() }}</strong>
                                            <div style="font-size: 11px;">{{ $reg->annee_debut }} - {{ $reg->annee_fin }}</div>
                                        </td>
                                        <td style="font-size: 12px;">{{ $reg->localisation ?? 'Magasin' }}</td>
                                        <td>
                                            <x-statut :type="$reg->etat_materiel->classeStatut()" :texte="$reg->etat_materiel->libelle()" />
                                        </td>
                                        <td>
                                            <x-statut :type="$reg->presence->classeStatut()" :texte="$reg->presence->libelle()" />
                                        </td>
                                        <td style="text-align: center;">
                                            @if(!auth()->check() || auth()->user()->aDroit('registres.consulter'))
                                                <a href="{{ route('registres.index', ['detail' => $reg->id_registre]) }}" class="btn btn-secondary" style="font-size: 11px; padding: 3px 8px; text-decoration: none;">
                                                    Détail →
                                                </a>
                                            @else
                                                <span style="font-size: 12px; color: var(--noir);">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 16px; border: 1px dashed var(--noir); text-align: center; font-size: 13px;">
                        ✓ Aucun registre ne présente d'anomalie physique actuellement.
                    </div>
                @endif
            </x-carte>
        </div>
    </div>
@endsection
