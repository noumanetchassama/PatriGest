# PatriGest — Notes de développement

## Emplacement et démarrage

Copie canonique : `C:\PatriGest` (hors OneDrive — requis pour la
performance ; le code bind-mounté sur OneDrive rendait l'app inutilisable).
Projet Compose `patrigesti-local`. Les copies `C:\dev\PatriGesti` et
`C:\Users\LENOVO\OneDrive\Desktop\PatriGesti` sont abandonnées — ne plus
les utiliser ni lancer `docker compose` depuis ces dossiers (deux projets
sur le même port 8080 entreront en conflit).

```bash
cd "C:\PatriGest"
docker compose up -d        # build si besoin : --build
```

Les `vendor/` des deux services sont des **volumes nommés**
(`auth_vendor`, `asset_vendor`) : les dossiers `vendor/` locaux doivent
simplement exister (points de montage), leur contenu est ignoré.

- Front : http://localhost:8080 — comptes de démo créés par `DemoDataSeeder` (identifiants dans le seeder, réservés au dev local)
- PostgreSQL : localhost:5432 (`patri_user` / `patri_dev_password`)
- Bases : `auth_service`, `asset_db` (créée par `asset-db-init`)

## Performance

Les volumes nommés `auth_vendor` / `asset_vendor` masquent `vendor/` (le
gros des fichiers PHP) dans des volumes Docker natifs. Depuis
`C:\PatriGest` (disque local), les requêtes warmed sont ~1–3 s ; le
premier chargement après démarrage reste plus lent (OPcache froid).

`infrastructure/php/opcache-dev.ini` monte `opcache.validate_timestamps=0`
dans les deux services PHP : **les modifs de code ne sont pas prises en
compte automatiquement**. Après chaque édition PHP :

```bash
docker exec patrigesti-local-auth-service-1 kill -USR2 1
docker exec patrigesti-local-asset-service-1 kill -USR2 1
```

Si `docker compose up -d` recrée les conteneurs PHP (nouvelles IP),
redémarrer nginx qui a résolu les noms au démarrage :

```bash
docker restart patrigesti-local-nginx-1
```

Sinon toutes les requêtes `.php` tombent sur le mauvais service
(`/` renvoie du JSON asset-service, `/login` → 404).

## Tests

```bash
docker exec patrigesti-local-auth-service-1 php artisan test
docker exec patrigesti-local-asset-service-1 php artisan test
```

## Règles projet

- Ne pas committer/pousser sans demande explicite.
- Interface en français, montants en FCFA, timezone `Africa/Lome`.
- Hypothèses non validées → `docs/HYPOTHESES.md`.

## Performance / volumes

- `storage/` est monté en volumes nommés (`*_storage_*`) pour sortir les
  écritures runtime de OneDrive. Si un volume est recréé avec des
  permissions `dr-xr-xr-x`, lancer dans chaque conteneur :
  `chmod -R 777 /var/www/storage/framework/{sessions,cache,views} /var/www/storage/logs`
- Après modification de code PHP : `docker exec <service> sh -c "kill -USR2 1"`
  (OPcache `validate_timestamps=0`).
