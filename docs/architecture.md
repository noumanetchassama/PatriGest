# Architecture PatriGest (état implémenté)

## Vue d'ensemble

Architecture microservices. Chaque service = un conteneur Laravel 12 (PHP 8.3-FPM)
+ sa propre base PostgreSQL 16 + un volume dédié. Nginx fait office de reverse
proxy unique pour l'instant (un seul service exposé).

```
Navigateur ──► nginx (:8080) ──► auth-service (php-fpm:9000) ──► postgres (auth_service)
                                      └──► asset-service (php-fpm:9000) ──► postgres (asset_db)
```

## Services prévus

| Service | Responsabilité | État |
|---|---|---|
| auth-service | utilisateurs, auth, rôles, permissions, structures, audit | **Fonctionnel** (API v1 + web login + seeders + tests) |
| asset-service | biens immobiliers/mobiliers/véhicules | **En cours** (API v1 + migrations + tests ; frontend à venir) |
| assignment-service | affectations, transferts, validations | À créer (Phase 4) |
| maintenance-service | inventaires, incidents, maintenance | À créer (Phase 6) |
| document-service | documents, versions, confidentialité | À créer (Phase 5) |
| report-service | dashboards, rapports, exports | À créer (Phase 7) |

## Auth Service — implémentation

- **API** : `routes/api.php`, préfixe `/api/v1`, authentification Bearer Sanctum.
- **Web** : `routes/web.php` — login/logout par session (`web` guard),
  pages Blade, throttle 5/min sur le login. Le dashboard
  utilise un jeton Sanctum conservé en session serveur pour charger les
  statistiques de biens ; le jeton est révoqué à la déconnexion.
  Les deux canaux partagent `login_logs` et `audit_logs`.
- **Frontend** (depuis les maquettes SVG) : layout `layouts/app` avec
  sidebar marine 230 px + topbar (recherche, notifications, utilisateur),
  tokens du design system (`resources/css/app.css`), composant
  `x-status-badge`, partial `partials/pagination`. Pages :
  `/dashboard`, `/patrimoine` (+ fiche `/patrimoine/{id}`),
  `/affectations`, `/transferts`, `/validations` (approbation/rejet
  relayés à l'asset-service), `/inventaires`, `/incidents`,
  `/maintenance`, `/documents`, `/rapports`, `/notifications`,
  `/administration`, `/parametres`.
- **`App\Support\AssetApiClient`** : proxy HTTP vers l'asset-service
  avec le token de session ; `poolGet()` exécute les appels en
  parallèle (dashboard, centre de validation, fiche bien).
- **Permissions** : Spatie Laravel Permission, guard `web`, middlewares
  `permission:` / `role:` aliasés dans `bootstrap/app.php`.
- **Structures** : table `structures` auto-référencée
  (`administration` → `direction` → `service`), table pivot
  `user_structure` (avec `is_primary`).
- **Audit** : `audit_logs` (morph cible, old/new values JSON, IP) alimenté par
  `App\Support\AuditLogger::log()`. `login_logs` pour les tentatives de connexion.
- **Seeders** : `RolesAndPermissionsSeeder` (permissions + matrice des 9 rôles),
  `DemoDataSeeder` (arborescence MFB fictive + 1 utilisateur par rôle,
  mot de passe `PatriGest@2026`).

## Décisions techniques

- Recherche insensible à la casse via `LOWER(col) LIKE ?` (compatible
  PostgreSQL et SQLite pour les tests).
- `json` plutôt que `jsonb` dans `audit_logs` pour la portabilité SQLite.
- Suppression logique (`softDeletes`) sur `structures` ; les users sont
  supprimés physiquement sauf garde-fou anti auto-suppression.
- OPcache activé dans le Dockerfile. Le dépôt doit idéalement être déplacé hors
  de OneDrive pour éviter les ralentissements I/O.
- Nginx : `fastcgi_read_timeout 300` pour absorber les ralentissements locaux.
- Docker Compose utilise le projet `patrigesti-local` et des volumes dédiés afin
  de ne pas réutiliser les conteneurs ou données d'un autre checkout.
- **Volumes `*_storage_*`** (sessions, cache, vues compilées, logs) : les
  écritures runtime sont hors du bind mount OneDrive — sans eux chaque
  requête mettait 30–300 s (sync OneDrive sur chaque écriture). Après
  création d'un volume, penser à `chmod -R 777` sur les dossiers si les
  permissions copiées sont en lecture seule (`dr-xr-xr-x`).
- La base `asset_db` est créée au démarrage par `asset-db-init`, également pour
  les volumes PostgreSQL existants.

## Asset Service — implémentation

- **Base dédiée** : `asset_db` sur le même PostgreSQL (conteneur `patri-postgres`).
- **Tables** : `assets` (champs communs + `reference` unique `PG-XXX-NNNN`,
  `qr_token` UUID, soft deletes), `real_estate_details`, `movable_details`,
  `vehicle_details` (détails exclusifs par type, `asset_id` unique),
  `asset_locations` (historique avec `is_current`/`effective_from/to`),
  `reference_counters` (compteurs IMM/MOB/VEH, `lockForUpdate` dans une
  transaction → génération atomique sans doublon).
- **Auth inter-services** : middleware `auth.patri` qui appelle
  `GET {AUTH_SERVICE_URL}/api/v1/auth/me` (via nginx, `http://nginx`)
  avec le Bearer token, mis en cache 300 s. Retourne 401/403.
  ⚠️ Solution transitoire : à remplacer par des jetons signés
  (JWT/HMAC) ou un service d'autorisation dédié quand le volume le justifiera.
- **Autorisation** : permissions `view-assets`, `create-assets`,
  `edit-assets` vérifiées côté serveur via les permissions retournées
  par l'Auth Service.
- **Références croisées** : `direction_id`, `service_id`,
  `custodian_user_id` stockés comme IDs simples (pas de FK inter-bases).
- **Nginx** : location regex `^/api/v1/(health|assets|asset-categories|
  asset-locations|asset-history|asset-submissions|assignments|transfers|maintenances|incidents|documents|inventories|inventory-items|reports)` → `asset-service:9000`
  avec `SCRIPT_FILENAME` fixe (pas de redirection interne : préserve le
  corps des requêtes et évite le piège du fallback `/index.php` qui
  retombait sur l'auth-service).

## Endpoints implémentés (Asset Service)

| Méthode | Route | Permission |
|---|---|---|
| GET | `/api/v1/assets` | `view-assets` (filtres, recherche, tri, pagination) |
| GET | `/api/v1/assets/stats` | `view-assets` (totaux par type et statut, valeur totale) |
| POST | `/api/v1/assets` | `create-assets` |
| GET | `/api/v1/assets/{id}` | `view-assets` |
| PUT | `/api/v1/assets/{id}` | `edit-assets` |
| DELETE | `/api/v1/assets/{id}` | `edit-assets` (soft delete) |
| GET | `/api/v1/assets/{id}/locations` | `view-assets` |
| POST | `/api/v1/assets/{id}/locations` | `edit-assets` (clôture l'ancienne) |
| GET | `/api/v1/asset-categories` | `view-assets` |
| GET | `/api/v1/assignments` | `view-assignments` (filtres, pagination) |
| POST | `/api/v1/assignments` | `create-assignments` |
| GET | `/api/v1/assignments/{id}` | `view-assignments` |
| POST | `/api/v1/assignments/{id}/approve` | `approve-assignment` — auto-approbation interdite |
| POST | `/api/v1/assignments/{id}/reject` | `approve-assignment` — motif obligatoire |
| POST | `/api/v1/assignments/{id}/cancel` | `view-assignments` — demandeur uniquement |
| GET | `/api/v1/transfers` | `view-assignments` (filtres, pagination) |
| POST | `/api/v1/transfers` | `create-transfer` |
| GET | `/api/v1/transfers/{id}` | `view-assignments` |
| POST | `/api/v1/transfers/{id}/approve` | `approve-transfer` — auto-approbation interdite |
| POST | `/api/v1/transfers/{id}/reject` | `approve-transfer` — motif obligatoire |
| POST | `/api/v1/transfers/{id}/cancel` | `view-assignments` — demandeur uniquement |
| POST | `/api/v1/assets/{id}/submit` | `submit-assets` — brouillon → `en_validation` |
| GET | `/api/v1/asset-submissions` | `view-assets` (filtres, pagination) |
| GET | `/api/v1/asset-submissions/{id}` | `view-assets` |
| POST | `/api/v1/asset-submissions/{id}/approve` | `approve-asset-creation` — auto-approbation interdite |
| POST | `/api/v1/asset-submissions/{id}/reject` | `approve-asset-creation` — motif obligatoire |
| GET | `/api/v1/maintenances` | `view-assets` (filtres, pagination) |
| GET | `/api/v1/assets/{id}/maintenances` | `view-assets` |
| POST | `/api/v1/assets/{id}/maintenances` | `manage-maintenance` |
| GET | `/api/v1/maintenances/{id}` | `view-assets` |
| PUT | `/api/v1/maintenances/{id}` | `manage-maintenance` (uniquement si `planifiee`) |
| POST | `/api/v1/maintenances/{id}/start` | `manage-maintenance` — bien → `maintenance` |
| POST | `/api/v1/maintenances/{id}/complete` | `manage-maintenance` — bien → statut antérieur |
| POST | `/api/v1/maintenances/{id}/cancel` | `manage-maintenance` |
| GET | `/api/v1/incidents` | `view-assets` (filtres, pagination) |
| POST | `/api/v1/assets/{id}/incidents` | `manage-incidents` |
| GET | `/api/v1/incidents/{id}` | `view-assets` |
| POST | `/api/v1/incidents/{id}/resolve` | `manage-incidents` — `resolved_at` + notes |
| POST | `/api/v1/incidents/{id}/close` | `manage-incidents` — uniquement si `resolu` |
| GET | `/api/v1/documents` | `view-documents` (filtres, pagination) |
| GET | `/api/v1/assets/{id}/documents` | `view-documents` |
| POST | `/api/v1/assets/{id}/documents` | `manage-documents` (multipart, 20 Mo max) |
| GET | `/api/v1/documents/{id}` | `view-documents` |
| GET | `/api/v1/documents/{id}/download` | `view-documents` (téléchargement) |
| DELETE | `/api/v1/documents/{id}` | `manage-documents` (supprime fichier + enregistrement) |
| GET | `/api/v1/inventories` | `view-assets` (filtres, pagination) |
| POST | `/api/v1/inventories` | `manage-inventory` (création + `asset_ids` optionnels) |
| GET | `/api/v1/inventories/{id}` | `view-assets` |
| GET | `/api/v1/inventories/{id}/items` | `view-assets` (filtre `status`) |
| POST | `/api/v1/inventories/{id}/items` | `manage-inventory` (anti-doublon) |
| POST | `/api/v1/inventory-items/{id}/check` | `manage-inventory` — `trouve`/`absent`/`non_concerne` |
| POST | `/api/v1/inventories/{id}/close` | `manage-inventory` — exige tous les biens vérifiés |
| GET | `/api/v1/reports/overview` | `view-reports` (agrégation tous domaines) |

## Rapports

- `GET /api/v1/reports/overview` retourne une synthèse JSON unique :
  biens (total, valeur cumulée, répartitions type/statut), workflows en
  attente (affectations, transferts, soumissions), maintenances
  (en cours / planifiées / coût des terminées), incidents ouverts par
  sévérité, inventaires (en cours / clos / biens absents), nombre de
  documents.
- Volontairement en lecture seule et sans pagination — pensé pour le
  dashboard. Des exports CSV/PDF et des filtres temporels
  (`?from`/`?to`) pourront être ajoutés (H26).

## Inventaires physiques

- Tables `asset_inventories` (campagne : `en_cours` → `clos`,
  `started_by`/`closed_by`/`closed_at`) et `asset_inventory_items`
  (ligne par bien : `en_attente` → `trouve`/`absent`/`non_concerne`,
  `checked_by`/`checked_at`/`notes`). Contrainte unique
  `(inventory, asset)` contre les doublons.
- La clôture est refusée tant qu'il reste des lignes `en_attente`
  (422 avec le nombre restant). Une ligne vérifiée ne peut plus l'être.
- Pas de rapprochement automatique avec l'emplacement déclaré : les
  écarts (`absent`, notes) sont constatés mais aucune action
  automatique sur le bien — à valider métier (H25).

## Documents des biens

- Table `asset_documents` : `asset_id`, `type` (`facture` / `contrat` /
  `garantie` / `carte_grise` / `autre`), `name`, `file_path`,
  `mime_type`, `size`, `description`, `uploaded_by`.
- Fichiers stockés sur le disque `local` (`storage/app/private/documents/`)
  — accès uniquement via l'endpoint `download` authentifié, pas d'URL
  publique. À migrer vers un stockage objet (S3-compatible) en production.

## Maintenances

- Table `asset_maintenances` : `asset_id`, `type` (`preventive` /
  `corrective`), `description`, `scheduled_date`, `realized_date`,
  `cost`, `status` (`planifiee` → `en_cours` → `terminee` / `annulee`),
  `provider`, `notes`, `previous_asset_status`, `created_by`.
- `start` passe le bien au statut `maintenance` en mémorisant son
  statut antérieur ; `complete`/`cancel` (si en cours) restaurent ce
  statut. Une maintenance commencée/terminée n'est plus modifiable.

## Incidents

- Table `asset_incidents` : `asset_id`, `type` (`dommage` / `perte` /
  `accident` / `vol`), `description`, `occurred_at`, `severity`
  (`mineure` / `majeure` / `critique`), `status` (`ouvert` → `en_cours`
  → `resolu` → `clos`), `estimated_cost`, `resolution_notes`,
  `resolved_at`, `reported_by`.
- Cycle strict : `resolve` marque `resolu` + `resolved_at` (refusé si
  déjà résolu/clos) ; `close` exige le statut `resolu`.
- Les incidents ne modifient pas automatiquement le statut du bien
  (décision métier à valider — H20).

## Soumissions de biens — workflow de validation

- Table `asset_submissions` : `asset_id`, `requested_by`, `reviewed_by`,
  `status` (`en_attente` → `approuvee` / `rejetee`), `motif`,
  `decision_motif`, `decided_at`.
- Cycle : `brouillon` → `en_validation` (soumission) → `actif`
  (approbation) ou retour à `brouillon` (rejet, motif obligatoire).
- `en_validation` ne peut pas être positionné via l'API
  (`MANUAL_STATUSES` exclut ce statut des requêtes create/update).
- Règles : une seule soumission `en_attente` par bien ; pas
  d'auto-approbation ; le statut du bien ne change qu'après décision ;
  historique complet des soumissions conservé.

## Transferts — workflow de validation

- Table `asset_transfers` : `asset_id`, cibles `to_direction_id` /
  `to_service_id` / `to_custodian_user_id` (au moins une requise),
  `requested_by`, `reviewed_by`, `status` (même cycle que les
  affectations), `motif`, `decision_motif`, `decided_at`.
- **Règles** : une seule mutation `en_attente` par bien ; pas
  d'auto-approbation ; motif de rejet obligatoire ; les champs du bien
  (`direction_id`, `service_id`, `custodian_user_id`) ne sont modifiés
  qu'à l'approbation, uniquement pour les cibles fournies ; historique
  préservé.

## Affectations — workflow de validation

- Table `asset_assignments` : `asset_id`, `custodian_user_id`,
  `requested_by`, `reviewed_by`, `status` (`en_attente` → `approuvee` /
  `rejetee` / `annulee`), `motif`, `decision_motif`, `decided_at`.
- **Règles** : une seule demande `en_attente` par bien ; le demandeur ne
  peut pas approuver sa propre demande (403) ; le rejet exige un motif
  (422 sinon) ; `asset.custodian_user_id` n'est modifié qu'à
  l'approbation ; une demande traitée ne peut plus l'être (historique
  préservé) ; annulation réservée au demandeur tant que `en_attente`.
- Implémenté dans l'asset-service (extractible en assignment-service
  dédié si l'architecture microservices l'exige — H19).

## Endpoints implémentés (Auth Service)

| Méthode | Route | Permission |
|---|---|---|
| POST | `/api/v1/auth/login` | publique (throttle 5/min) |
| POST | `/api/v1/auth/logout` | authentifié |
| GET | `/api/v1/auth/me` | authentifié |
| GET/POST | `/api/v1/users` | `manage-users` |
| GET/PUT/DELETE | `/api/v1/users/{user}` | `manage-users` |
| GET/POST | `/api/v1/roles` | `manage-roles` |
| PUT/DELETE | `/api/v1/roles/{role}` | `manage-roles` |
| GET | `/api/v1/permissions` | `manage-roles` |
| GET/POST | `/api/v1/structures` | `manage-structures` |
| GET/PUT/DELETE | `/api/v1/structures/{structure}` | `manage-structures` |
| GET | `/api/v1/structures-tree` | authentifié |
| GET | `/api/v1/audit-logs` | `view-audit-logs` |
