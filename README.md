# PatriGest

> Plateforme de gestion du patrimoine public — République du Togo.
> Suivi des biens immobiliers, mobiliers et véhicules : enregistrement,
> affectation, transfert, validation hiérarchique, maintenance, incidents,
> inventaires, traçabilité et reporting.

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Fonctionnalités](#fonctionnalités)
4. [Rôles et permissions](#rôles-et-permissions)
5. [Parcours utilisateur](#parcours-utilisateur)
6. [Installation locale](#installation-locale)
7. [Déploiement Render](#déploiement-render)
8. [Tests](#tests)
9. [Structure du dépôt](#structure-du-dépôt)
10. [Conventions et règles métier](#conventions-et-règles-métier)

---

## Vue d'ensemble

PatriGest est une application **Laravel 12 / PHP 8.3 / PostgreSQL 16** organisée
en **deux services** communiquant par API REST authentifiée (Laravel Sanctum) :

| Service | Responsabilité |
|---|---|
| `auth-service` | Identités, mots de passe, rôles & permissions (Spatie), structures administratives (directions/services), journal d'audit, journaux de connexion, **interface web Blade** |
| `asset-service` | Patrimoine : biens, localisations, documents, affectations, transferts, soumissions, maintenances, incidents, inventaires, rapports, notifications |

Nginx fait office de reverse proxy unique : il sert le front web et route
`/api/v1/*` vers le bon service. Les deux applications sont packagées dans une
seule image Docker pour le déploiement.

---

## Architecture

```
Navigateur
    │  HTTP (session + CSRF)
    ▼
┌─────────── nginx :8080 (ou :10000 sur Render) ───────────┐
│  / , /login, /dashboard, /patrimoine…  → auth-service    │
│  /api/v1/auth/*                          → auth-service  │
│  /api/v1/assets|assignments|transfers|…  → asset-service │
└──────────────────────────────────────────────────────────┘
        │                              │
        ▼                              ▼
  auth-service                   asset-service
  (users, roles, structures,     (assets, workflows,
   audit, sessions)               notifications…)
        │                              │
        └──────────► PostgreSQL ◄───────┘
              auth_service + asset_db
```

### Flux d'authentification inter-services

1. L'utilisateur se connecte sur le **front web** (session + cookie CSRF).
2. À la connexion, l'auth-service crée un **token Sanctum** stocké en session.
3. Chaque appel `AssetApiClient` de l'auth-service transmet ce token en
   `Authorization: Bearer` vers l'asset-service.
4. Le middleware `auth.patri` de l'asset-service **revalide le token auprès de
   l'auth-service** (`GET /api/v1/auth/me`) — le résultat est **mis en cache
   5 minutes** pour limiter la latence.
5. Les permissions (ex. `approve-transfer`) sont vérifiées côté asset-service
   via les données renvoyées par `/auth/me`.

Ce découpage garantit qu'**aucune base n'est partagée** : l'asset-service ne
voit jamais la table `users`, seulement des IDs et des libellés résolus côté
web (`App\Support\Labels`).

---

## Fonctionnalités

### Patrimoine (cœur métier)

- **Trois types de biens** : `immobilier`, `mobilier`, `vehicule` — chacun avec
  ses propres champs techniques (surface/occupants pour l'immobilier,
  marque/série/état pour le mobilier, immatriculation/marque pour le véhicule).
- **Références métier séquentielles** générées de façon atomique :
  `PG-IMM-0001`, `PG-MOB-0001`, `PG-VEH-0001`.
- **Cycle de vie** : `brouillon → en_validation → actif`, puis
  `maintenance`, `hors_service`, `reforme`.
- **Localisation géographique** : région, préfecture, commune, adresse, site,
  **coordonnées GPS**, historique des localisations avec dates d'effet.
- **QR code SVG** par bien (token unique `qr_token`) affiché sur la fiche et
  la carte **Leaflet/OpenStreetMap** quand des coordonnées existent.
- Soft delete : aucun bien n'est jamais supprimé physiquement.

### Workflows de validation

| Workflow | Règle |
|---|---|
| **Soumission** (création d'un bien) | `brouillon → en_validation → actif` ou rejet motivé → retour `brouillon` |
| **Affectation** | Demande d'affectation d'un bien à une structure/dépositaire, approuvée ou rejetée |
| **Transfert** | Changement de direction/service/dépositaire, workflow identique |

Règles communes : **motif obligatoire** pour tout rejet, **anti
auto-approbation** (l'auteur d'une demande ne peut pas la décider),
décision unique (pas de double validation), annulation possible par le
demandeur tant que la demande est en attente.

### Opérations courantes

- **Maintenances** : cycle `planifiée → en_cours → terminée/annulée`,
  synchronisation automatique du statut du bien, coût et prestataire.
- **Incidents** : `ouvert → en_cours → résolu → clos`, gravité
  (mineure/majeure/critique), coût estimé.
- **Documents** : upload/téléchargement/suppression par bien (facture,
  contrat, garantie…), proxy de téléchargement authentifié.
- **Inventaires** : campagnes avec lignes par bien, contrôle
  `trouvé / absent / non_concerne`, clôture conditionnelle, suivi des
  biens absents.
- **Rapports** : synthèse consolidée (totaux, répartitions par type/statut,
  workflows en attente, incidents, maintenance, inventaires) + exports
  **CSV** (compatibles Excel : BOM UTF-8, séparateur `;`) et **PDF**
  (synthèse A4 et liste des biens A4 paysage, via dompdf).

### Interface transverse

- **Notifications persistées** : chaque décision (soumission, affectation,
  transfert approuvés/rejetés) notifie le demandeur ; page `/notifications`
  + badge de non-lues dans la topbar + marquage lu unitaire/global.
- **Recherche globale** : le champ de la topbar recherche dans les biens
  (référence, désignation, description).
- **Noms lisibles** : utilisateurs et structures affichés par leur nom
  (jamais « Utilisateur #2 »), résolus depuis l'auth-service avec cache.
- **Administration** : gestion des utilisateurs, rôles, permissions et
  structures ; journal d'audit et journaux de connexion.

---

## Rôles et permissions

L'application utilise **Spatie Laravel Permission**. Les rôles sont des
regroupements de permissions ; les permissions sont vérifiées côté serveur
(et les boutons masqués côté UI quand la permission manque).

| Rôle | Permissions principales |
|---|---|
| **Responsable patrimoine** | Créer/éditer/soumettre des biens, affectations, transferts, documents, rapports |
| **Décideur** | Approuver/rejeter soumissions, affectations, transferts (sans auto-approbation) |
| **Agent inventaire** | Campagnes d'inventaire, incidents |
| **Agent bâtiment** | Incidents, maintenance |
| **Admin technique** | Utilisateurs, rôles, structures, audit — **pas** de validation métier (séparation des pouvoirs) |

---

## Parcours utilisateur

### 1. Créer un bien (responsable patrimoine)

`Patrimoine` → **+ Enregistrer un bien** → wizard en 5 étapes :

1. **Général** : type, désignation, description, acquisition, origine
2. **Administratif** : état, direction, service, dépositaire + champs du type
3. **Localisation** : région → commune, adresse, **coordonnées GPS**
4. **Documents** : jusqu'à 5 fichiers joints
5. **Vérification** : récapitulatif → **Brouillon** ou **Soumettre**

### 2. Soumettre et faire valider

- Sur la fiche du bien (brouillon) : **Soumettre pour validation**.
- Le décideur ouvre **Validations** → file d'attente → **Approuver** ou
  **Rejeter** (motif obligatoire).
- Le demandeur est **notifié** du résultat ; le bien passe `actif` ou
  retourne `brouillon`.

### 3. Affecter / transférer

- **Affectations** / **Transferts** → formulaire (bien, cible, motif) →
  workflow de validation identique.
- ⚠️ **Auto-approbation interdite** : il faut un second compte pour décider.

### 4. Exploiter

- **Maintenance** : planifier → démarrer → terminer (coût, prestataire).
- **Incidents** : déclarer → résoudre → clore.
- **Inventaires** : créer une campagne → ajouter des biens → vérifier
  chacun → clôturer.
- **Rapports** : consulter la synthèse, exporter CSV ou PDF.

### 5. Administrer

- **Administration** : utilisateurs (création, rôle, activation), rôles et
  permissions, structures.
- **Journal d'audit** : traçabilité des actions sensibles.
- **Mon profil** : changement de mot de passe.

---

## Installation locale

Prérequis : **Docker Desktop** + Docker Compose, Node.js/npm (build Vite).

```powershell
cd C:\PatriGest

# 1. Fichiers d'environnement (première fois uniquement)
Copy-Item services\auth-service\.env.example services\auth-service\.env
Copy-Item services\asset-service\.env.example services\asset-service\.env

# 2. Build des assets front (public\build n'est pas versionné)
Push-Location services\auth-service
npm.cmd install
npm.cmd run build
Pop-Location

# 3. Démarrer + initialiser
 docker compose up --build -d
docker compose exec auth-service php artisan key:generate
docker compose exec asset-service php artisan key:generate
docker compose exec auth-service php artisan migrate --seed
docker compose exec asset-service php artisan migrate
```

- **Front** : http://localhost:8080
- **PostgreSQL** : `localhost:5432` (`patri_user` / `patri_dev_password`)
- **Bases** : `auth_service`, `asset_db` (créée par `asset-db-init`)

> ⚠️ Le projet **doit rester hors d'OneDrive** (`C:\PatriGest`) : le code
> est bind-monté et OneDrive rendait chaque requête inutilisable (5–80 s).
> Les anciennes copies (`C:\dev\PatriGest`, `OneDrive\Desktop\PatriGesti`)
> sont abandonnées — ne pas y lancer `docker compose` (conflit de port 8080).

### OPcache et rechargement

`opcache.validate_timestamps=0` est actif : **après chaque modification PHP**,
recharger php-fpm :

```bash
docker exec patrigesti-local-auth-service-1 sh -c "kill -USR2 1"
docker exec patrigesti-local-asset-service-1 sh -c "kill -USR2 1"
```

Après `composer require` ou ajout de package : même reload obligatoire
(l'autoload est figé dans OPcache).

Si les conteneurs PHP sont recréés, redémarrer nginx (noms résolus au boot) :

```bash
docker restart patrigesti-local-nginx-1
```

---

## Déploiement Render

Image Docker unique : **nginx + 2× PHP-FPM** dans un conteneur.
`infrastructure/docker/entrypoint.sh` s'exécute à chaque démarrage :

1. Génère les `.env` des deux services depuis les variables d'environnement ;
2. Attend PostgreSQL puis **crée `auth_service` et `asset_db` si absentes** ;
3. `migrate --force` sur les deux services + `db:seed --force` sur
   l'auth-service (**idempotent** → les comptes de démo sont recréés à
   chaque déploiement) ;
4. `config:cache` + `view:cache` (performance) ;
5. Démarre nginx + php-fpm via supervisord.

### Variables d'environnement

| Variable | Exemple | Rôle |
|---|---|---|
| `APP_URL` | `https://patrigest.onrender.com` | URL publique |
| `DB_HOST` / `DB_PORT` | `dpg-xxxx` / `5432` | PostgreSQL Render (interne) |
| `DB_USERNAME` / `DB_PASSWORD` | — | Credentials PostgreSQL |
| `APP_KEY` | `base64:…` | Clé de chiffrement (à fixer ! sinon régénérée à chaque boot) |
| `APP_KEY_AUTH` / `APP_KEY_ASSET` | `base64:…` | Clés séparées (optionnel) |
| `ASSET_SERVICE_URL` | `http://localhost:10000` | asset-service via nginx interne |
| `AUTH_SERVICE_URL` | `http://localhost:10000` | auth-service via nginx interne |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | Optionnel |

### Points d'attention production

- **Sessions** : driver `database`, cookie `Secure` (HTTPS), durée 120 min.
- **OPcache prod** : `opcache-prod.ini` (256 M, `validate_timestamps=0`,
  `opcache.file_cache` désactivé) — l'image est figée, inutile de revalider.
- **Proxy** : `trustProxies` ne fait **pas** confiance à
  `X-Forwarded-Host` (envoyé vide par Render/Cloudflare → URLs cassées
  `https:/https:/login`). Le `Host` réel de nginx fait foi ;
  `URL::forceRootUrl()` applique `APP_URL` en HTTPS.
- **419** : une session expirée sur une page web redirige proprement vers
  `/login` (au lieu de la page « Page Expired ») ; la page login est servie
  avec `Cache-Control: no-store`.
- **Latence** : le plan gratuit Render endort le service après ~15 min
  d'inactivité → cold start 30–60 s à la première requête. Options :
  ping externe (UptimeRobot) ou instance payante toujours active.

---

## Tests

```powershell
docker compose exec auth-service php artisan test    # 31 tests
docker compose exec asset-service php artisan test   # 91 tests
```

Couverture : authentification web (login/logout/419/proxy HTTPS), CRUD et
workflows métier (soumissions, affectations, transferts, anti auto-approbation,
rejet motivé), notifications, inventaires, incidents, maintenance,
documents, rapports, middlewares d'autorisation.

---

## Structure du dépôt

```
PatriGest/
├── docker-compose.yml              # nginx + 2 services PHP + PostgreSQL
├── Dockerfile                      # image unique (nginx + php-fpm ×2)
├── infrastructure/
│   ├── nginx/                      # default.conf (local) + render.conf
│   ├── php/                        # opcache-dev.ini / opcache-prod.ini
│   ├── docker/entrypoint.sh        # boot Render : .env, migrations, seed, caches
│   └── php-fpm/, supervisor/       # pools et services
├── services/
│   ├── auth-service/               # Laravel — identités, rôles, structures, UI
│   │   ├── app/Http/Controllers/Web/   # contrôleurs Blade (dashboard, patrimoine…)
│   │   ├── app/Support/                # AssetApiClient, Labels, AuditLogger…
│   │   └── resources/views/            # maquettes Figma (Tailwind)
│   └── asset-service/              # Laravel — API métier
│       ├── app/Http/Controllers/Api/    # assets, assignments, transfers…
│       ├── app/Support/Notifier.php     # notifications de workflow
│       └── database/migrations/         # schéma asset_db
├── docs/
│   ├── architecture.md             # architecture détaillée
│   ├── GUIDE_UTILISATION.md        # manuel utilisateur complet
│   └── HYPOTHESES.md               # hypothèses non validées
└── AGENTS.md                       # notes de développement (commandes, pièges)
```

---

## Conventions et règles métier

- **Langue** : interface et documents en **français**.
- **Montants** : FCFA (`value` + `value_type` par bien).
- **Fuseau horaire** : `Africa/Lome`.
- **Références** : `PG-{IMM|MOB|VEH}-NNNN`, atomiques via `reference_counters`.
- **Suppressions** : soft delete partout (traçabilité).
- **Rejets** : motif obligatoire ; **auto-approbation interdite**.
- **Hypothèses** non validées avec le métier → `docs/HYPOTHESES.md`.
- **Pas de secrets** dans le dépôt ; `.env` locaux uniquement.
