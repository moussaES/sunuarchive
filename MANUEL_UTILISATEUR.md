# Manuel d'Utilisation — SIGAEC

**Système Informatisé de Gestion des Archives de l'État Civil**  
**Commune de Tattaguine — Département de Fatick, Sénégal**

---

## 1. Introduction & Principes Directeurs

Le **SIGAEC** est l'outil quotidien des agents communaux et de l'officier de l'état civil de la Mairie de Tattaguine. Il a pour vocation de :
1. **Préserver les registres physiques** contre les manipulations excessives et l'usure du temps.
2. **Accélérer les recherches d'actes** pour délivrer les extraits aux usagers en un temps record.
3. **Sécuriser la valeur probante des archives** grâce au scellement cryptographique et à la traçabilité intégrale.

### La Charte Visuelle Monochrome
L'interface de SIGAEC utilise rigoureusement **trois teintes** :
- **Vert Menthe (`#3EB489`)** : Action principale, bandeaux officiels, confirmation de réussite.
- **Blanc (`#FFFFFF`)** : Fond de lecture reposant pour la saisie et les fiches d'actes.
- **Noir (`#111111`)** : Textes, bordures nettes et contrastes d'accessibilité.

### Système Universel de Pop-ups Modales
Pour garantir une navigation rapide sans rechargement lourd et sans perte de contexte, **tous les formulaires de saisie, de modification et de décision s'ouvrent dans des fenêtres pop-ups modales**.  
Pour fermer une pop-up :
- Cliquez sur le bouton de fermeture **✕** en haut à droite.
- Ou cliquez sur le bouton **Annuler**.
- Ou cliquez simplement à l'extérieur de la boîte de dialogue sur le fond noir.

---

## 2. Accès & Sécurité du Poste de Travail

### Connexion
1. Cliquez sur le bouton **Connexion agent** dans la barre latérale gauche.
2. Renseignez votre identifiant de compte et votre mot de passe.
3. Cliquez sur **Se connecter**.

> [!CAUTION]
> **Règle des 5 tentatives** : Après 5 échecs consécutifs de mot de passe, votre compte est automatiquement verrouillé pour des raisons de sécurité. Vous devrez vous rapprocher de l'Administrateur technique pour le débloquer.

### Déconnexion automatique après inactivité
Si vous quittez votre poste ou ne touchez plus à l'écran pendant **15 minutes consécutives**, le système vous déconnecte automatiquement pour empêcher toute consultation indiscrète.

---

## 3. Le Tableau de Bord de Conservation

Accessible dès l'écran d'accueil (`/`), il fournit une vision en temps réel de l'état des archives :

1. **Les 4 Indicateurs Consolidés** :
   - *1. Registres inventoriés* : Nombre total de registres physiques inscrits et pourcentage inventorié.
   - *2. Actes indexés en base* : Nombre total d'actes transcrits (naissances, mariages, décès).
   - *3. Fichiers numérisés* : Nombre de reproductions numériques scellées sous SHA-256.
   - *4. Fiches à vérifier* : Nombre d'actes incomplets ou en attente de vérification par l'archiviste.

2. **Progression de Numérisation par Nature d'Acte** :
   - Barres de progression noir et vert menthe pour les Naissances, Mariages et Décès.

3. **Bandeau de Sécurité & État du Système** :
   - Date et résultat de la dernière sauvegarde automatique.
   - Date du dernier test de restauration (avec rappel visuel si le test date de plus de 90 jours).
   - Taux d'intégrité cryptographique des fichiers scellés (100 % conforme).

4. **Registres physiques à surveiller** :
   - Alerte immédiate sur les registres signalés en état matériel *Dégradé* ou avec une présence physique *À vérifier*.

---

## 4. Module Registres — Inventaire Physique

Le module **Registres** liste l'ensemble des volumes physiques conservés dans la salle d'archives de la mairie.

### Recherche & Filtres
- **Filtrer par type d'acte** : Tous, Naissance, Mariage, Décès.
- **Filtrer par état matériel** : Bon, Moyen, Dégradé.
- **Filtrer par présence physique** : Présent, Déplacé, Manquant.

### Consulter le volet de détail
Cliquez sur le lien **Détail →** d'une ligne de registre pour déployer le panneau latéral complet :
- Cote archivistique officielle (ex: `EC-NAI-2015-REG001`).
- Localisation physique précise (Salle, Armoire, Rayonnage).
- Nombre de feuillets et plage des numéros d'actes.
- Bouton direct **« Voir les actes de ce registre »**.

### Enregistrer un nouveau registre (Archiviste)
1. Cliquez sur le bouton **+ Nouveau registre**.
2. Remplissez le formulaire modale : type, cote, année de début, année de fin, nombre de pages, localisation, état matériel et présence.
3. Validez en cliquant sur **Enregistrer le registre**.

### Exporter l'inventaire en CSV
Cliquez sur le bouton **📊 Export CSV** en haut de page. Le fichier généré intègre l'encodage UTF-8 avec signature BOM pour s'ouvrir parfaitement sous Microsoft Excel sans déformation des caractères sénégalais.

---

## 5. Module Actes — Fiches d'État Civil & Indexation

### Typologie des Actes
Le SIGAEC gère les trois actes d'état civil avec leurs spécificités réglementaires :
- **Naissance** : Date et heure de naissance, lieu, sexe, mentions marginales, père, mère et déclarant.
- **Mariage** : Date et lieu de célébration, régime matrimonial (monogamie / polygamie), identité des deux époux, témoins et célébrant.
- **Décès** : Date et heure du décès, lieu, cause, défunt, dernier conjoint, déclarant.

### Le Bandeau Légal d'Avertissement
Toute consultation de fiche d'acte affiche en tête la mention légale obligatoire :  
> **« Copie de consultation, le registre papier reste le document de référence »**  
Cette mention rappelle que seule la version papier du registre communal fait foi devant la justice.

### Création d'un Acte
1. Ouvrez la modale **+ Nouvel acte** depuis la liste ou la recherche.
2. Sélectionnez le type d'acte et renseignez le numéro d'acte et l'année.
3. Remplissez les données de l'événement et l'identité des personnes indexées.
4. Cliquez sur **Enregistrer l'acte**.

> [!NOTE]
> **Contrôle d'unicité composite** : Deux actes ne peuvent pas posséder le même numéro dans le même registre et la même année. En revanche, le numéro 1 peut légitimement exister dans le registre de Naissance 2015 et dans le registre de Naissance 2016.

### Correction d'une Transcription (Archiviste)
Si une erreur matérielle a été commise lors de la saisie initiale :
1. Sur la fiche de l'acte, cliquez sur **Corriger la transcription**.
2. La fenêtre modale s'ouvre avec les données actuelles.
3. Modifiez les champs erronés et validez.
4. L'opération est immédiatement consignée dans le journal d'activité d'audit.

---

## 6. Gestion des Copies Numériques (Scans d'Actes)

### Consultation en direct
Sur la fiche de l'acte, la reproduction scannée s'affiche dans le visualiseur sécurisé :
- Prise en charge des formats **PDF**, **TIFF** et **JPEG/PNG**.
- Bouton de zoom, plein écran et navigation page par page.

### Association d'une nouvelle copie numérique
1. Sur la fiche d'acte, cliquez sur **Ajouter une copie numérique**.
2. Sélectionnez le fichier scanné sur votre poste.
3. Le système applique automatiquement la nomenclature officielle de nommage :  
   `TAT-EC-[TYPE]-[ANNEE]-[REGISTRE]-[ACTE]-[PAGE].[EXT]`
4. L'empreinte cryptographique SHA-256 est calculée et scellée immédiatement.

### Remplacement tracé d'un scan (Archiviste)
Si un acte a été numérisé avec une page tronquée ou une résolution insuffisante :
1. Cliquez sur **Remplacer cette copie**.
2. Sélectionnez le nouveau fichier haute définition.
3. **Renseignez obligatoirement la justification motivée** (au moins 10 caractères, ex: *« Scan 300 DPI pour rendre lisible la mention de mariage en marge »*).
4. Cliquez sur **Valider le remplacement**.
5. L'ancien fichier n'est pas détruit : il est horodaté et conservé inerte dans l'historique d'audit.

---

## 7. Moteur de Recherche Simple & Avancé

Accessible via le menu **Recherche d'actes** :

### Recherche Simple
Saisissez simplement un mot-clé dans le champ principal :
- Numéro d'acte (ex: `42`).
- Année (ex: `2015`).
- Cote de registre (ex: `REG001`).
- Nom d'une personne (si vous disposez de l'habilitation nominative).

### Recherche Avancée (Pop-up modale)
Cliquez sur **⚙ Critères avancés...** pour combiner plusieurs filtres :
- Type d'acte exact.
- Plage chronologique (Année de début et Année de fin).
- Registre spécifique.
- Présence obligatoire d'un scan numérique (*Oui / Non*).
- Nom et prénom de la personne recherchée.

### Aide en cas d'absence de résultat (4 points d'orientation)
Si aucun acte ne correspond à votre requête, SIGAEC affiche automatiquement un encadré d'orientation archivistique en 4 points :
1. **Contrôle de l'orthographe du patronyme** (variations fréquentes : Ba / Bah, Ly / Li, Diallo / Diao...).
2. **Vérification du numéro de déclaration** sur le jugement supplétif ou le volet d'origine.
3. **Élargissement chronologique** (rechercher à $\pm 2$ ans de l'année supposée).
4. **Vérification du statut du registre** dans le module Registres (le registre n'est peut-être pas encore entièrement indexé).

---

## 8. Demandes de Communication & Réquisitions

Conformément à la réglementation sénégalaise, la délivrance de pièces à des autorités extérieures (Tribunal de Grande Instance, Gendarmerie, Police) ou à des usagers fait l'objet d'un circuit formalisé :

1. **Saisie de la demande** : L'archiviste enregistre la réquisition via la modale **+ Nouvelle demande** avec son objet et les actes concernés. Le statut initial est **En attente**.
2. **Décision de l'Officier** : Le Responsable habilité examine la demande dans son onglet d'administration et statue formellement :
   - **Accordée** : La communication ou l'extrait est autorisé.
   - **Refusée** : Motif légal opposé (défaut d'habilitation ou secret de filiation).
3. La décision est horodatée et signée électroniquement dans le journal d'activité.

---

## 9. Guide des Badges et Niveaux d'Alerte

| Badge Visuel | Style Typographique | Signification Métier |
| :--- | :--- | :--- |
| **Succès / Accordé** | Fond menthe, texte noir, bordure noire | Opération réussie, acte vérifié, sauvegarde intègre. |
| **En attente** | Fond blanc, texte noir, bordure fine | Acte à vérifier, demande d'extrait en cours d'instruction. |
| **Attention** | Fond blanc, texte noir, bordure pointillée | Registre déplacé, test de restauration trimestriel dépassé. |
| **Critique / Échec** | Fond noir, texte blanc plein | Erreur mot de passe, compte verrouillé, registre dégradé. |
