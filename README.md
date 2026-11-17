# PatriGest

PatriGest est une application Laravel en microservices : `auth-service` gère les
comptes, rôles, structures et sessions web ; `asset-service` gère les biens.
Nginx expose l'application sur le port `8080`, et PostgreSQL héberge les deux
bases (`auth_service` et `asset_db`).

## Démarrage local avec Docker Desktop

Prérequis : Docker Desktop avec Docker Compose, Node.js et npm.

1. Créez les fichiers d'environnement uniquement s'ils n'existent pas déjà :

   ```powershell
   Copy-Item services\auth-service\.env.example services\auth-service\.env
   Copy-Item services\asset-service\.env.example services\asset-service\.env
   ```

   Ces fichiers d'exemple contiennent des identifiants réservés au
   développement local. Ne les utilisez pas en production.

2. Construisez les ressources Vite utilisées par la page de connexion et le
   dashboard. Ce build est nécessaire avant le premier démarrage Docker, car
   `public\build` est généré localement et n'est pas versionné :

   ```powershell
   Push-Location services\auth-service
   npm.cmd install
   npm.cmd run build
   Pop-Location
   ```

   Après une modification CSS ou JavaScript, relancez `npm.cmd run build`.

3. Démarrez les conteneurs et initialisez les clés, schémas et données de
   démonstration :

   ```powershell
   docker compose up --build -d
   docker compose exec auth-service php artisan key:generate
   docker compose exec asset-service php artisan key:generate
   docker compose exec auth-service php artisan migrate --seed
   docker compose exec asset-service php artisan migrate
   ```

   Le service `asset-db-init` crée `asset_db` de façon idempotente, y compris
   lorsque le volume PostgreSQL existe déjà. Les volumes `auth_vendor` et
   `asset_vendor` conservent les dépendances Composer installées dans les
   images malgré les montages des sources.

4. Ouvrez <http://localhost:8080/login>. Les comptes fictifs sont créés par
   `DemoDataSeeder`; leur mot de passe commun est `PatriGest@2026`. Ces comptes
   sont réservés au développement et ne doivent jamais être activés en
   production.

## Vérifications

```powershell
docker compose exec auth-service php artisan test
docker compose exec asset-service php artisan test
```

Le dashboard affiche les totaux de biens, leurs répartitions par type et
statut, et la somme des valeurs renseignées. Il dépend de l'Asset Service;
si celui-ci est indisponible, la page signale explicitement l'erreur.
Tous les modules métier sont implémentés : affectations, transferts,
validations, inventaires, incidents, maintenance, documents et rapports.

Le projet Docker utilise le nom Compose `patrigesti-local` afin d'isoler ses
conteneurs et ses volumes de tout ancien checkout `PatriGesti`. Les données
existantes d'un autre projet Compose ne sont ni modifiées ni supprimées.
Sauvegardez les volumes avant toute opération de maintenance et ne lancez pas
`docker compose down -v` si vous souhaitez conserver les données locales.

Le dépôt se trouve dans `C:\PatriGest`, hors de OneDrive. L'ancienne copie
sous `OneDrive\Desktop\PatriGesti` est abandonnée : y lancer `docker
compose` recréerait un second projet sur le port 8080 et casserait
l'application.

## Déploiement Render

L'image Docker unique contient nginx + les deux PHP-FPM. Au démarrage,
`infrastructure/docker/entrypoint.sh` :

1. génère les `.env` des deux services depuis les variables d'environnement ;
2. attend PostgreSQL puis **crée `auth_service` et `asset_db` si absentes** ;
3. exécute `migrate --force` sur les deux services et `db:seed --force`
   sur l'auth-service (idempotent — les comptes de démo sont recréés à
   chaque déploiement, mot de passe `PatriGest@2026`) ;
4. démarre nginx + php-fpm via supervisord.

### Variables d'environnement à définir sur Render

| Variable | Exemple | Rôle |
|---|---|---|
| `APP_URL` | `https://patrigest.onrender.com` | URL publique (redirections, URLs générées) |
| `DB_HOST` | `dpg-xxxx-a` | Hôte PostgreSQL Render (interne) |
| `DB_PORT` | `5432` | Port PostgreSQL |
| `DB_USERNAME` | `patri_user` | Utilisateur PostgreSQL |
| `DB_PASSWORD` | `•••` | Mot de passe PostgreSQL |
| `APP_KEY` | `base64:…` | Clé de chiffrement (partagée par défaut) |
| `APP_KEY_AUTH` / `APP_KEY_ASSET` | `base64:…` | Clés séparées (optionnel, prioritaire sur `APP_KEY`) |
| `ASSET_SERVICE_URL` | `http://localhost:10000` | URL interne asset-service (via nginx) |
| `AUTH_SERVICE_URL` | `http://localhost:10000` | URL interne auth-service (via nginx) |
| `APP_ENV` | `production` | Optionnel |
| `APP_DEBUG` | `false` | Optionnel |

Si `APP_KEY` est absente, une clé est générée à chaque démarrage (les
sessions sont alors invalidées à chaque redéploiement — définissez-la
pour la stabilité). Les sessions utilisent le driver `database` : la
table `sessions` est créée par les migrations.

### ⚠️ Piège proxy résolu

`trustProxies` ne fait pas confiance à `X-Forwarded-Host` (envoyé vide
par Render/Cloudflare, ce qui produisait des URLs `https:/https:/login`).
Le `Host` réel transmis par nginx fait foi. Si `APP_URL` est en HTTPS,
`URL::forceRootUrl()` l'utilise en priorité pour les URLs absolues.
