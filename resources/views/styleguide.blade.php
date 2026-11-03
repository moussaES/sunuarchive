@extends('layouts.app')

@section('titre', 'Guide de styles UI')

@section('content')
    <h1 class="titre-page">Guide de styles UI & Composants</h1>
    <p style="margin-bottom: 24px; font-weight: 600;">
        Démonstration de la charte graphique officielle de SIGAEC (Mairie de Tattaguine).
        Couleur unique : Vert menthe (<code>#3EB489</code>), fond blanc (<code>#FFFFFF</code>), texte et bordures noirs (<code>#111111</code>).
    </p>

    {{-- Section 1 : Palette monochrome stricte --}}
    <x-carte titre="1. Palette autorisée (Section 3 du cahier des charges)">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <div style="border: 1px solid var(--noir); padding: 16px; background-color: var(--menthe); color: var(--noir); width: 220px; text-align: center;">
                <div style="font-weight: 800; font-size: 18px;">--menthe</div>
                <div style="font-weight: 700;">#3EB489</div>
                <div style="font-size: 12px; margin-top: 4px;">Couleur unique du logiciel</div>
            </div>
            <div style="border: 1px solid var(--noir); padding: 16px; background-color: var(--blanc); color: var(--noir); width: 220px; text-align: center;">
                <div style="font-weight: 800; font-size: 18px;">--blanc</div>
                <div style="font-weight: 700;">#FFFFFF</div>
                <div style="font-size: 12px; margin-top: 4px;">Fond de page et cartes</div>
            </div>
            <div style="border: 1px solid var(--noir); padding: 16px; background-color: var(--noir); color: var(--blanc); width: 220px; text-align: center;">
                <div style="font-weight: 800; font-size: 18px;">--noir</div>
                <div style="font-weight: 700;">#111111</div>
                <div style="font-size: 12px; margin-top: 4px;">Texte, bordures, icônes</div>
            </div>
        </div>
    </x-carte>

    {{-- Section 2 : Statuts typographiques sans couleurs secondaires --}}
    <x-carte titre="2. Statuts sans couleur chromatique additionnelle (Section 3.4)">
        <p style="margin-bottom: 16px;">
            La hiérarchie des statuts est transmise par le préfixe typographique, la bordure et le libellé textuel intégral :
        </p>
        <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <x-statut type="positif" texte="Complet / Vérifié" />
            <x-statut type="attente" texte="À numériser / En attente" />
            <x-statut type="attention" texte="À vérifier / Dégradé" />
            <x-statut type="critique" texte="Très dégradé / Manquant" />
        </div>
    </x-carte>

    {{-- Section 3 : Boutons --}}
    <x-carte titre="3. Boutons d'action (Cibles ≥ 44px)">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
            <x-bouton variant="primary">Bouton principal</x-bouton>
            <x-bouton variant="secondary">Bouton secondaire</x-bouton>
            <x-bouton variant="danger">Action critique</x-bouton>
            <button type="button" class="btn btn-primary" data-modal-open="modal-demo">
                ■ Ouvrir le formulaire en pop-up (Modal)
            </button>
        </div>
    </x-carte>

    {{-- Section 4 : Indicateurs chiffrés et barres de progression --}}
    <x-carte titre="4. Indicateurs du tableau de bord & Progression">
        <div class="grille-indicateurs">
            <div class="indicateur">
                <div class="indicateur__chiffre">14 / 18</div>
                <div class="indicateur__libelle">Registres inventoriés</div>
            </div>
            <div class="indicateur">
                <div class="indicateur__chiffre">1 240</div>
                <div class="indicateur__libelle">Actes indexés</div>
            </div>
            <div class="indicateur">
                <div class="indicateur__chiffre">1 180</div>
                <div class="indicateur__libelle">Fichiers numériques</div>
            </div>
            <div class="indicateur">
                <div class="indicateur__chiffre">12</div>
                <div class="indicateur__libelle">Fiches à vérifier</div>
            </div>
        </div>

        <h4 style="margin-bottom: 8px; font-weight: 700;">Progression globale de la numérisation (78 %)</h4>
        <div class="barre-progression" aria-label="Progression de la numérisation">
            <div class="barre-progression__remplissage" style="width: 78%;"></div>
        </div>
    </x-carte>

    {{-- Section 5 : Tableau avec survol --}}
    <x-carte titre="5. Tableau de consultation d'archives">
        <div class="table-responsive">
            <table class="table-sigaec">
                <thead>
                    <tr>
                        <th>Cote</th>
                        <th>Type d'acte</th>
                        <th>Années couvertes</th>
                        <th>Emplacement physique</th>
                        <th>État matériel</th>
                        <th>Statut inventaire</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>EC-NAI-2020-REG001</strong></td>
                        <td>Naissance</td>
                        <td>2020 - 2020</td>
                        <td>Salle d'archives / Armoire 1 / Étagère 2</td>
                        <td><x-statut type="positif" texte="Bon" /></td>
                        <td><x-statut type="positif" texte="Inventorié" /></td>
                    </tr>
                    <tr>
                        <td><strong>EC-MAR-2019-REG002</strong></td>
                        <td>Mariage</td>
                        <td>2019 - 2019</td>
                        <td>Salle d'archives / Armoire 1 / Étagère 3</td>
                        <td><x-statut type="attention" texte="Dégradé" /></td>
                        <td><x-statut type="attente" texte="En cours" /></td>
                    </tr>
                    <tr>
                        <td><strong>EC-DEC-2018-REG003</strong></td>
                        <td>Décès</td>
                        <td>2018 - 2018</td>
                        <td>Salle d'archives / Armoire 2 / Étagère 1</td>
                        <td><x-statut type="critique" texte="Très dégradé" /></td>
                        <td><x-statut type="attente" texte="À traiter" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-carte>

    {{-- Section 6 : Fenêtre modale / Pop-up pour formulaires --}}
    <x-modal id="modal-demo" titre="Exemple de formulaire en Pop-up (Modal)">
        <form id="form-styleguide-demo" onsubmit="event.preventDefault(); alert('Formulaire validé avec succès.'); document.getElementById('modal-demo').close();">
            <div class="form-group">
                <label for="demo_nom" class="form-label">Nom complet de l'acte ou référence :</label>
                <input type="text" id="demo_nom" class="form-control" placeholder="Ex: TAT-EC-NAI-2020-REG001" required>
                <div class="form-hint">Respecter la convention de cotation archivistique.</div>
            </div>

            <div class="form-group">
                <label for="demo_type" class="form-label">Type de registre :</label>
                <select id="demo_type" class="form-select" required>
                    <option value="">Sélectionnez un type d'acte...</option>
                    <option value="NAISSANCE">Naissance</option>
                    <option value="MARIAGE">Mariage</option>
                    <option value="DECES">Décès</option>
                </select>
            </div>

            <div class="form-group">
                <label for="demo_observations" class="form-label">Observations archivistiques :</label>
                <textarea id="demo_observations" class="form-textarea" placeholder="Indiquer ici les lacunes, pages froissées ou particularités..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary" data-modal-close="modal-demo">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    Enregistrer dans l'inventaire
                </button>
            </div>
        </form>
    </x-modal>
@endsection
