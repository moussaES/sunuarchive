<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>SIGAEC — Rapport officiel d'état des archives</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #111111;
            background-color: #FFFFFF;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #111111;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 14px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            font-size: 12px;
            margin: 4px 0 0 0;
            font-weight: normal;
        }
        .header .sub {
            font-size: 10px;
            margin-top: 4px;
            font-style: italic;
        }
        .document-title {
            text-align: center;
            background-color: #3EB489;
            color: #111111;
            font-weight: bold;
            font-size: 13px;
            padding: 8px;
            border: 2px solid #111111;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .meta-box {
            border: 1px solid #111111;
            padding: 8px 12px;
            margin-bottom: 20px;
            background-color: #FFFFFF;
            font-size: 10px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #111111;
            padding-bottom: 4px;
            margin-top: 16px;
            margin-bottom: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #111111;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #3EB489;
            color: #111111;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge {
            display: inline-block;
            padding: 2px 4px;
            border: 1px solid #111111;
            font-weight: bold;
            font-size: 9px;
            background-color: #FFFFFF;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
        }
        .signatures td {
            border: none;
            padding: 0;
            vertical-align: top;
            width: 50%;
        }
        .sign-box {
            border: 1px dashed #111111;
            height: 80px;
            margin-top: 8px;
            margin-right: 15px;
        }
    </style>
</head>
<body>

    {{-- En-tête officiel Mairie de Tattaguine --}}
    <div class="header">
        <h1>RÉPUBLIQUE DU SÉNÉGAL</h1>
        <h2>Un Peuple — Un But — Une Foi</h2>
        <div class="sub">
            Région de Fatick · Département de Fatick · Commune de Tattaguine<br>
            Bureau de l'État Civil et des Archives Communales
        </div>
    </div>

    {{-- Titre officiel du rapport --}}
    <div class="document-title">
        SIGAEC — Rapport officiel de situation des archives municipales
    </div>

    {{-- Métadonnées de l'édition --}}
    <div class="meta-box">
        <strong>Date d'édition :</strong> {{ now()->format('d/m/Y à H:i') }} &nbsp;|&nbsp;
        <strong>Édité par :</strong> {{ $demandeur->nom_complet }} ({{ $demandeur->profil->libelle() }}) &nbsp;|&nbsp;
        <strong>Périmètre :</strong> Conservation physique, intégrité numérique et sauvegardes
    </div>

    {{-- 1. Indicateurs clés de conservation --}}
    <div class="section-title">1. Indicateurs consolidés de l'état civil</div>
    <table>
        <thead>
            <tr>
                <th>Indicateur</th>
                <th class="text-center">Valeur actuelle</th>
                <th>Diagnostic archivistique</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Registres inventoriés</strong></td>
                <td class="text-center">{{ $donnees['indicateurs']['registres_inventories'] }} / {{ $donnees['indicateurs']['total_registres'] }}</td>
                <td>{{ $donnees['indicateurs']['pourcentage_inventaire'] }} % des registres entièrement contrôlés et cotés</td>
            </tr>
            <tr>
                <td><strong>Actes d'état civil indexés</strong></td>
                <td class="text-center">{{ $donnees['indicateurs']['total_actes'] }}</td>
                <td>Transcriptions informatisées conformes aux registres papier</td>
            </tr>
            <tr>
                <td><strong>Copies numériques associées</strong></td>
                <td class="text-center">{{ $donnees['indicateurs']['total_fichiers_actifs'] }}</td>
                <td>Reproductions numériques (TIFF/PDF) scellées sous empreinte SHA-256</td>
            </tr>
            <tr>
                <td><strong>Fiches d'actes à vérifier</strong></td>
                <td class="text-center">{{ $donnees['indicateurs']['fiches_a_verifier'] }}</td>
                <td>Actes nécessitant un contrôle de transcription ou mentionnés illisibles</td>
            </tr>
        </tbody>
    </table>

    {{-- 2. Avancement de la numérisation par type --}}
    <div class="section-title">2. Taux de numérisation par nature d'acte</div>
    <table>
        <thead>
            <tr>
                <th>Nature de l'acte</th>
                <th class="text-center">Actes répertoriés</th>
                <th class="text-center">Numérisés</th>
                <th class="text-center">Taux d'avancement</th>
            </tr>
        </thead>
        <tbody>
            @foreach($donnees['progression'] as $p)
                <tr>
                    <td><strong>{{ $p['libelle'] }}</strong></td>
                    <td class="text-center">{{ $p['total'] }}</td>
                    <td class="text-center">{{ $p['numerises'] }}</td>
                    <td class="text-center"><strong>{{ $p['pourcentage'] }} %</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 3. Registres physiques à surveiller --}}
    <div class="section-title">3. Registres physiques à surveiller prioritairement</div>
    @if($donnees['registres_a_surveiller']->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Cote</th>
                    <th>Type</th>
                    <th>Années</th>
                    <th>Localisation physique</th>
                    <th>État matériel</th>
                    <th>Présence</th>
                </tr>
            </thead>
            <tbody>
                @foreach($donnees['registres_a_surveiller'] as $reg)
                    <tr>
                        <td><strong>{{ $reg->cote }}</strong></td>
                        <td>{{ $reg->type->libelle() }}</td>
                        <td>{{ $reg->annee_debut }} - {{ $reg->annee_fin }}</td>
                        <td>{{ $reg->localisation ?? 'Magasin central' }}</td>
                        <td><span class="badge">{{ $reg->etat_materiel->libelle() }}</span></td>
                        <td><span class="badge">{{ $reg->presence->libelle() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p><em>Aucun registre ne nécessite de surveillance particulière actuellement. Tous les volumes sont en bon état et présents.</em></p>
    @endif

    {{-- 4. Sécurité, intégrité et sauvegardes --}}
    <div class="section-title">4. Contrôle d'intégrité cryptographique et sauvegardes</div>
    <table>
        <thead>
            <tr>
                <th>Point de contrôle</th>
                <th>Dernier état constaté</th>
                <th>Conformité</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Dernière sauvegarde des données</strong></td>
                <td>{{ $donnees['etat_systeme']['derniere_sauvegarde'] ? $donnees['etat_systeme']['derniere_sauvegarde']->date_sauvegarde->format('d/m/Y à H:i') : 'Aucune sauvegarde' }}</td>
                <td>{{ $donnees['etat_systeme']['derniere_sauvegarde'] ? strtoupper($donnees['etat_systeme']['derniere_sauvegarde']->resultat) : 'À EXÉCUTER' }}</td>
            </tr>
            <tr>
                <td><strong>Dernier test de restauration</strong></td>
                <td>{{ $donnees['etat_systeme']['dernier_test_restauration'] ? $donnees['etat_systeme']['dernier_test_restauration']->date_sauvegarde->format('d/m/Y à H:i') : 'Non réalisé' }}</td>
                <td>{{ $donnees['etat_systeme']['rappel_trimestriel'] ? 'TEST TRIMESTRIEL REQUIS' : 'CONFORME (< 90 JOURS)' }}</td>
            </tr>
            <tr>
                <td><strong>Intégrité SHA-256 du parc de fichiers</strong></td>
                <td>{{ $donnees['etat_systeme']['fichiers_valides'] }} / {{ $donnees['etat_systeme']['fichiers_total'] }} fichiers intègres</td>
                <td>{{ $donnees['etat_systeme']['anomalies_fichiers'] === 0 ? 'INTÉGRITÉ CONFORME' : 'ALERTE : ' . $donnees['etat_systeme']['anomalies_fichiers'] . ' ANOMALIE(S)' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Visa et signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <strong>L'Archiviste communal :</strong><br>
                <div class="sign-box"></div>
            </td>
            <td>
                <strong>L'Officier de l'État Civil / Maire :</strong><br>
                <div class="sign-box"></div>
            </td>
        </tr>
    </table>

</body>
</html>
