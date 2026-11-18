# PatriGest — Guide d'utilisation

> Manuel utilisateur de la plateforme de gestion du patrimoine public.
> Interface en français — montants en FCFA — fuseau `Africa/Lome`.

---

## Table des matières

1. [Connexion et déconnexion](#1-connexion-et-déconnexion)
2. [Presentation generale de l'interface](#2-presentation-generale-de-linterface)
3. [Tableau de bord](#3-tableau-de-bord)
4. [Patrimoine — les biens](#4-patrimoine--les-biens)
5. [Fiche d'un bien](#5-fiche-dun-bien)
6. [Créer un bien (assistant en 5 étapes)](#6-créer-un-bien-assistant-en-5-étapes)
7. [Modifier un bien](#7-modifier-un-bien)
8. [Soumettre un bien pour validation](#8-soumettre-un-bien-pour-validation)
9. [Affectations](#9-affectations)
10. [Transferts](#10-transferts)
11. [Centre de validation](#11-centre-de-validation)
12. [Notifications](#12-notifications)
13. [Inventaires](#13-inventaires)
14. [Incidents](#14-incidents)
15. [Maintenance](#15-maintenance)
16. [Documents](#16-documents)
17. [Rapports et exports](#17-rapports-et-exports)
18. [Administration](#18-administration)
19. [Mon profil](#19-mon-profil)
20. [Recherche globale](#20-recherche-globale)
21. [Dépannage (FAQ)](#21-dépannage-faq)

---

## 1. Connexion et déconnexion

### Se connecter

1. Ouvrez l'adresse de l'application (locale : `http://localhost:8080`,
   hébergée : l'URL Render communiquée par l'administrateur).
2. Page **Connexion** : saisissez votre **identifiant** (adresse e-mail ou
   nom d'utilisateur) et votre **mot de passe**.
3. Cliquez sur **Se connecter** → vous arrivez sur le tableau de bord.

> Vos identifiants sont fournis par l'administrateur de la plateforme.
> En environnement de démonstration, des comptes de test sont créés
> automatiquement (voir `DemoDataSeeder`).

### Problèmes fréquents à la connexion

| Symptôme | Cause probable | Solution |
|---|---|---|
| « Page expirée » (419) | Session ancienne ou page restée ouverte longtemps | Actualisez la page de connexion (F5) puis reconnectez-vous |
| « Identifiants incorrects » | Faille de saisie ou compte inexistant | Vérifiez l'orthographe ; le login accepte e-mail **ou** nom d'utilisateur |
| « Ce compte est désactivé » | Compte désactivé par l'admin | Contactez l'administrateur |
| Lent au premier chargement | Service endormi (hébergement gratuit) | Patientez ~30–60 s à la première requête, puis c'est fluide |

### Se déconnecter

Cliquez sur l'**icône de déconnexion** en haut à droite (à côté de votre
nom). Vous revenez à la page de connexion — indispensable pour changer
de compte.

---

## 2. Presentation generale de l'interface

L'interface est organisée en **trois zones** :

```
┌────────────┬──────────────────────────────────────────────┐
│            │  TOPBAR : recherche globale • notifications │
│  SIDEBAR   │           • nom utilisateur • déconnexion   │
│  (menu)    ├──────────────────────────────────────────────┤
│            │                                              │
│  Tableau   │              CONTENU DE LA PAGE              │
│  de bord   │                                              │
│  Patrimoine│                                              │
│  Affectat. │                                              │
│  Transferts│                                              │
│  Validat.  │                                              │
│  Invent.   │                                              │
│  Incident. │                                              │
│  Mainten.  │                                              │
│  Docum.    │                                              │
│  Rapports  │                                              │
│  Admin     │                                              │
└────────────┴──────────────────────────────────────────────┘
```

- **Sidebar** : navigation entre les modules (visible selon vos rôles).
- **Topbar** : recherche globale de biens, cloche de notifications
  (badge = nombre de non-lues), votre nom, déconnexion.
- **Contenu** : tableaux, formulaires et fiches propres à chaque module.

---

## 3. Tableau de bord

Le **Tableau de bord** (`/dashboard`) offre une vue synthétique :

- **Indicateurs clés** : nombre total de biens, valeur totale du
  patrimoine (FCFA), répartitions par type et par statut.
- **Workflows en attente** : soumissions, affectations et transferts
  en attente de décision.
- **Accès rapide** aux modules opérationnels.

> Si le service patrimoine est indisponible, la page l'indique
> explicitement plutôt que d'afficher des zéros trompeurs.

---

## 4. Patrimoine — les biens

Le menu **Patrimoine** (`/patrimoine`) liste tous les biens enregistrés.

### Colonnes du tableau

| Colonne | Contenu |
|---|---|
| Référence | Identifiant métier unique (`PG-MOB-0001`…) |
| Désignation | Nom du bien |
| Type | Immobilier / Mobilier / Véhicule |
| Statut | Brouillon, En validation, Actif, Maintenance, Hors service, Réformé |
| État | État physique constaté |
| Valeur | Montant + nature (ex. valeur marchande, cadastrale) |
| Localisation | Commune/site actuel |
| Actions | **Consulter** (fiche), Modifier, Soumettre… |

### Filtres et recherche

- **Recherche** : tapez un mot-clé (référence, désignation, description)
  dans le champ ou via la topbar.
- **Filtres** : type, statut — combinables, avec pagination (15/page).

### Bouton « + Enregistrer un bien »

Visible uniquement si vous avez la permission de création. Ouvre
l'assistant de création (voir §6).

---

## 5. Fiche d'un bien

Cliquez **Consulter** sur une ligne → fiche complète (`/patrimoine/{id}`).

### Contenu de la fiche

- **En-tête** : référence, désignation, statut (badge coloré), boutons
  d'action contextuels (Modifier, Soumettre, Affecter, Transférer…).
- **Informations générales** : tous les champs du bien + détails
  spécifiques au type (surface pour un immobilier, plaque pour un
  véhicule…).
- **Localisation** : adresse actuelle + **carte GPS** (Leaflet /
  OpenStreetMap) quand des coordonnées ont été saisies + historique
  des localisations avec dates d'effet.
- **Affectation** : direction, service et dépositaire actuels (noms
  lisibles, pas des numéros).
- **QR code** : code SVG unique du bien + son token — permet
  l'identification physique du bien.
- **Documents** : liste des fichiers joints avec téléchargement.
- **Incidents & maintenances** : historique des événements du bien.
- **Historique** : traçabilité des changements.

### Actions selon le statut

| Statut | Actions disponibles |
|---|---|
| Brouillon | Modifier, Soumettre pour validation, Supprimer (archive) |
| En validation | (attente de décision — aucune action) |
| Actif | Modifier, Affectation, Transfert, Maintenance, Incident, Documents |
| Maintenance / Hors service / Réformé | Consultation + opérations autorisées |

---

## 6. Créer un bien (assistant en 5 étapes)

`Patrimoine` → **+ Enregistrer un bien**. L'assistant guide en 5 étapes :

### Étape 1 — Général

- **Type** : Immobilier, Mobilier ou Véhicule (conditionne la suite).
- Désignation, description, date et valeur d'acquisition, origine,
  nature de la valeur.

### Étape 2 — Administratif

- État du bien, direction, service, dépositaire.
- **Champs spécifiques au type** :
  - *Immobilier* : surface, occupants, titre foncier…
  - *Mobilier* : marque, série, état, quantité…
  - *Véhicule* : immatriculation, marque, modèle, année, kilométrage…

### Étape 3 — Localisation

- Région, préfecture, commune, adresse, site/bâtiment.
- **Coordonnées GPS** (latitude/longitude) — optionnelles mais
  recommandées : elles activent la **carte** sur la fiche.

### Étape 4 — Documents

- Jusqu'à **5 fichiers** joints dès la création (facture, contrat,
  garantie, photo…) avec type de document.

### Étape 5 — Vérification

- Récapitulatif complet de la saisie.
- Choix final :
  - **Enregistrer en brouillon** → le bien reste modifiable.
  - **Soumettre pour validation** → le bien passe `en_validation`
    et attend la décision d'un valideur.

> La **référence** (`PG-XXX-NNNN`) est générée automatiquement —
> pas besoin de la choisir.

---

## 7. Modifier un bien

1. Ouvrez la fiche du bien → bouton **Modifier**.
2. Le formulaire est pré-rempli ; modifiez les champs utiles.
3. **Enregistrer** → retour à la fiche mise à jour.

> Un bien `en_validation` ou `actif` peut être modifié selon vos
> permissions ; les champs bloqués le sont pour préserver la
> traçabilité.

---

## 8. Soumettre un bien pour validation

1. Sur la fiche d'un bien en **brouillon** : bouton
   **Soumettre pour validation**.
2. Le statut passe à `en_validation` ; la demande apparaît dans le
   **Centre de validation** des décideurs.
3. Vous êtes **notifié** de la décision (voir §12).

- **Approbation** → statut `actif`.
- **Rejet** (motif obligatoire) → retour `brouillon`, corrigez puis
  resoumettez.

---

## 9. Affectations

Une **affectation** rattache un bien actif à une structure
(direction/service) et/ou un dépositaire.

### Créer une affectation

`Affectations` → **+ Nouvelle affectation** :

1. Choisissez le **bien** (liste des biens actifs).
2. Renseignez la **cible** : direction, service et/ou dépositaire.
3. Précisez le **motif** et la date d'effet.
4. **Soumettre** → la demande passe `en_attente`.

### Cycle de vie

`en_attente` → **Approuvée** (le bien est affecté) ou **Rejetée**
(motif obligatoire, retour possible). Le demandeur peut **annuler**
sa demande tant qu'elle est en attente.

> ⚠️ Vous ne pouvez pas approuver **votre propre** demande —
> un second valideur est requis (règle anti-fraude).

---

## 10. Transferts

Un **transfert** déplace un bien d'une affectation vers une autre
(nouvelle direction, service ou dépositaire).

### Créer un transfert

`Transferts` → **+ Nouveau transfert** :

1. Bien concerné.
2. **Destination** : nouvelle direction / service / dépositaire.
3. Motif et date d'effet.
4. **Soumettre** → même workflow de validation que les affectations.

La liste affiche la destination avec les **noms** des structures et
personnes (pas des numéros internes).

---

## 11. Centre de validation

`Validations` centralise **toutes les demandes en attente** :
soumissions de biens, affectations, transferts.

### Pour chaque demande

- **Contexte** : type de demande, bien concerné (référence +
  désignation), demandeur (nom), date, motif.
- **Décision** :
  - **Approuver** → la demande est acceptée, l'opération s'applique,
    le demandeur est notifié.
  - **Rejeter** → un **motif est obligatoire** ; le demandeur est
    notifié et peut corriger/resoumettre.

### Règles affichées

- Les boutons n'apparaissent que si vous avez la **permission**
  de décision pour ce type de demande.
- Vos **propres demandes** affichent « Auto-approbation interdite »
  au lieu d'un bouton (vous devez laisser un collègue décider).

---

## 12. Notifications

La **cloche** en haut à droite affiche le nombre de notifications
non lues (badge rouge).

### Page Notifications (`/notifications`)

- Liste des événements : décisions sur vos demandes, avec date,
  type et résumé.
- Cliquez une notification → elle est marquée lue et vous êtes
  redirigé vers la ressource concernée.
- **Tout marquer comme lu** pour vider le badge.

### Événements notifiés

| Événement | Notifié |
|---|---|
| Soumission approuvée / rejetée | Le créateur du bien |
| Affectation approuvée / rejetée | Le demandeur |
| Transfert approuvé / rejeté | Le demandeur |

---

## 13. Inventaires

Les **inventaires** permettent des campagnes de vérification physique
des biens.

### Créer une campagne

`Inventaires` → **+ Nouvelle campagne** : nom, description, et
sélection des biens à inventorier.

### Déroulement

1. Ouvrez la campagne → ajoutez des biens si besoin.
2. Pour chaque bien, enregistrez le constat :
   - **Trouvé** : le bien est physiquement présent.
   - **Absent** : le bien est manquant (signalé dans les rapports).
   - **Non concerné** : hors périmètre de la campagne.
3. **Clôturer** la campagne — possible quand tous les biens ont
   été vérifiés.

La liste distingue campagnes **en cours** et **clôturées**.

---
## 14. Incidents

Les **incidents** enregistrent les événements touchant un bien
(dommage, perte, accident, vol).

### Déclarer un incident

`Incidents` → **+ Déclarer un incident** :

1. Bien concerné.
2. **Type** : dommage, perte, accident ou vol.
3. Description détaillée, date de survenue.
4. **Gravité** : mineure, majeure ou critique.
5. Coût estimé (optionnel).

### Cycle de vie

`ouvert` → `en_cours` → `résolu` (notes de résolution) → `clos`.
Le statut du bien peut être impacté selon la gravité.

---

## 15. Maintenance

Le module **Maintenance** gère l'entretien des biens.

### Planifier

`Maintenance` → **+ Planifier** : bien, type (préventive/corrective),
description, date prévue, coût, prestataire, notes.

### Cycle de vie

`planifiée` → **Démarrer** (`en_cours`) → **Terminer**
(date réelle, coût final) ou **Annuler**.

> La maintenance synchronise le **statut du bien** : un bien en
> maintenance apparaît comme tel dans le patrimoine.

---

## 16. Documents

Les **documents** sont les fichiers attachés aux biens (factures,
contrats, garanties, photos,PV…).

- Dépôt possible **à la création** du bien (étape 4 du wizard) ou
  depuis la **fiche du bien**.
- Types : facture, contrat, garantie, autre.
- Téléchargement authentifié via la page Documents ou la fiche.
- Suppression possible selon vos permissions.

---

## 17. Rapports et exports

`Rapports` présente la **synthèse consolidée** :

- Indicateurs clés : total des biens, valeur totale (FCFA).
- Répartitions par type et par statut.
- Workflows en attente, incidents ouverts, maintenance en cours.
- Inventaires : campagnes, biens absents constatés.

### Exports

| Bouton | Format | Contenu |
|---|---|---|
| Synthèse CSV | `.csv` (Excel-ready : BOM UTF-8, `;`) | Indicateurs clé/valeur |
| Biens CSV | `.csv` | Liste complète des biens |
| Synthèse PDF | `.pdf` A4 | Rapport de synthèse mis en page |
| Biens PDF | `.pdf` A4 paysage | Tableau exhaustif des biens |

> Les exports respectent la permission `view-reports`.

---

## 18. Administration

Réservé aux comptes avec permissions d'administration.

### Utilisateurs

- Liste paginée des comptes (nom, e-mail, rôles, statut).
- **Créer** un utilisateur : identité, rôle, structure.
- **Modifier** : rôle, structure, activation/désactivation.

### Rôles & permissions

- Rôles existants avec nombre d'utilisateurs.
- Permissions par rôle (contrôle d'accès granulaire).

### Structures

- Directions et services de l'organisation (référencés par les
  affectations et les biens).

### Journal d'audit

- Traçabilité des actions sensibles : connexions, créations,
  validations, suppressions — avec auteur, date et détail.

---

## 19. Mon profil

`Profil` permet à chaque utilisateur de :

- Consulter ses informations (nom, e-mail, rôle, structure).
- **Changer son mot de passe** : mot de passe actuel + nouveau
  (8 caractères minimum, confirmation).

---

## 20. Recherche globale

Le champ de recherche de la **topbar** (icône loupe) recherche dans
tous les biens : référence, désignation, description.

- Tapez ≥ 2 caractères → suggestions instantanées.
- Entrée → page Patrimoine filtrée sur le terme.
- Fonctionne depuis n'importe quelle page.

---

## 21. Dépannage (FAQ)

### « Page expirée » / erreur 419

Votre session a expiré (inactivité > 2 h ou page restée ouverte).
→ Retournez à `/login` et reconnectez-vous. La page vous redirige
automatiquement avec un message explicite.

### Les boutons d'action n'apparaissent pas

Les actions sont **contextuelles et permissionnées** :
- Vous n'avez peut-être pas le rôle requis.
- Le statut du bien ne permet pas l'action (ex. pas de modification
  sur un bien en validation).
→ Vérifiez votre rôle auprès de l'administrateur.

### « Auto-approbation interdite »

Vous essayez de valider **votre propre** demande — règle
anti-fraude. → Un collègue doit décider à votre place.

### Un rejet échoue sans motif

Le motif de rejet est **obligatoire** — renseignez-le toujours.

### La carte GPS n'apparaît pas sur la fiche

La carte nécessite des **coordonnées saisies** (latitude/longitude
à l'étape Localisation). Sans elles, seule l'adresse textuelle
s'affiche.

### Les données semblent périmées

Actualisez la page (F5). Si le problème persiste, déconnectez-vous
et reconnectez-vous pour renouveler le token de session.

### Lent après une période d'inactivité (hébergement)

Le service gratuit s'endort après ~15 min → le premier chargement
peut prendre 30–60 s (cold start). Les requêtes suivantes sont
rapides. Un ping externe peut maintenir le service éveillé.

---

## Aide et support

- **Problème technique** : contactez l'administrateur système avec
  la page concernée, l'heure et le message d'erreur.
- **Droit d'accès manquant** : demandez à l'administrateur d'ajuster
  votre rôle ou vos permissions.
- **Données erronées** : signalez à l'administrateur — les suppressions
  sont tracées (soft delete) pour garantir la traçabilité.
