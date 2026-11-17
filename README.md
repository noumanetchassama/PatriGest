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

Pour conserver les connexions après un redémarrage ou entre plusieurs
instances, configurez le service web avec `SESSION_DRIVER=database` et
connectez-le à une base PostgreSQL persistante. Exécutez les migrations
Laravel au déploiement afin de créer notamment la table `sessions`.
Définissez également une `APP_KEY` stable (ne la régénérez pas à chaque
démarrage), `APP_ENV=production` et `SESSION_SECURE_COOKIE=true`. Utilisez
l'URL HTTPS publique comme `APP_URL` et laissez `SESSION_DOMAIN` vide sauf
si l'application doit partager ses cookies entre plusieurs sous-domaines.
La configuration Nginx de Render transmet les en-têtes `X-Forwarded-*` à
Laravel : ils sont nécessaires pour que les redirections et les cookies de
session reconnaissent correctement le HTTPS terminé par Render. Redéployez
l'image après toute modification de cette configuration.
