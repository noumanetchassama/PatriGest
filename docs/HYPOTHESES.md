# Hypothèses PatriGest

Hypothèses et décisions non encore confirmées par le propriétaire du projet.
Chaque hypothèse validée ou infirmée doit être mise à jour ici.

## Auth Service

| # | Hypothèse | Statut |
|---|-----------|--------|
| H1 | L'authentification API utilise Laravel Sanctum (tokens Bearer) ; le frontend Blade utilisera les sessions web. Un même endpoint `/auth/login` JSON servira les deux via Sanctum stateful si nécessaire. | En attente de validation |
| H2 | Les rôles sont stockés avec le guard `web` (guard par défaut du modèle User). Sanctum authentifie l'utilisateur sans changer de guard. | Implémenté |
| H3 | Les 9 rôles fonctionnels sont des **rôles** Spatie, pas des profils rigides : les droits réels viennent des permissions. Un utilisateur peut cumuler plusieurs rôles. | Implémenté |
| H4 | La matrice rôles × permissions du seeder `RolesAndPermissionsSeeder` est une proposition initiale. Les permissions `manage-inventory`, `manage-incidents`, `manage-maintenance`, `manage-documents`, `view-documents`, `view-reports` sont pré-créées pour les phases futures. | En attente de validation métier |
| H5 | L'administrateur technique reçoit les permissions d'administration (`manage-*`) mais **aucune** permission de validation métier (`approve-*`). | Conforme au cahier des charges |
| H6 | Le nom d'utilisateur (identifiant de connexion) accepte les points (`kossi.mensah`) en plus des tirets et underscores. | Implémenté |
| H7 | Un utilisateur peut appartenir à plusieurs structures administratives via `user_structure`, avec une structure `is_primary`. | Implémenté |
| H8 | Les structures forment un arbre `parent_id` auto-référencé (administration → direction → service), avec suppression logique (`softDeletes`). | Implémenté |
| H9 | La hiérarchie stricte administration→direction→service n'est pas encore forcée en base (le champ `type` est libre parmi les 3 valeurs). À durcir si besoin. | À valider |
| H10 | Le mot de passe de démonstration `PatriGest@2026` est réservé au seed de développement. | À changer en production |
| H11 | Les journaux `login_logs` et `audit_logs` sont conservés sans purge pour l'instant. Une politique de rétention sera à définir. | À valider |
| H12 | Le « journal d'audit » enregistre pour l'instant les actions Auth Service (login, CRUD users/rôles/structures). Les autres services devront émettre leurs propres événements d'audit ou appeler une API commune — à concevoir en Phase 3. | À concevoir |
| H13 | Auth inter-services : l'asset-service vérifie les tokens en appelant `/api/v1/auth/me` de l'auth-service via nginx (`http://nginx`), résultat mis en cache 300 s. **Transitoire** — à remplacer par des jetons signés ou un introspection endpoint dédié. | Implémenté (provisoire) |
| H14 | `value_type` des biens : valeurs `acquisition` / `actuelle` / `estimee` (nature de la valorisation). `value` est en FCFA. | À valider |
| H15 | Les biens sont créés avec `status=brouillon` ; la soumission (`submit-assets`) passe à `en_validation`, l'approbation (`approve-asset-creation`) à `actif`, le rejet ramène à `brouillon`. Implémenté. | Implémenté |
| H16 | Champs des tables de détails (surfaces, nb_pieces, immatriculation, etc.) sont une proposition initiale — à ajuster selon les maquettes « Enregistrer un bien » et le PDF d'analyse. | À valider |
| H17 | `direction_id` / `service_id` / `custodian_user_id` sont des IDs de l'Auth Service stockés sans FK (pas de jointure inter-bases). L'API ne renvoie que les IDs ; l'enrichissement (noms) sera fait côté frontend via l'API auth. | Implémenté |
| H18 | Le dashboard web charge les statistiques des biens via `GET /api/v1/assets/stats`. Un jeton Sanctum dédié est conservé dans la session serveur et révoqué à la déconnexion ; les chiffres respectent actuellement la même permission `view-assets` et le même périmètre global que l'API des biens. | Implémenté |
| H19 | Les affectations sont implémentées dans l'asset-service (`asset_assignments`) plutôt que dans un assignment-service dédié — plus simple tant que le volume est faible. À extraire si nécessaire. | À valider |
| H20 | Cycle de vie d'une affectation : `en_attente` → `approuvee`/`rejetee`/`annulee`. Pas de date d'effet ni de durée pour l'instant — à ajuster selon les maquettes «Affectation». | À valider |
| H21 | Transfert = mutation d'un bien (direction/service/dépositaire) avec le même workflow que l'affectation. Distinction affectation/transfert : l'affectation change le dépositaire, le transfert peut aussi changer la structure — à valider métier. | À valider |
| H22 | Maintenances : `start` fait passer le bien à `maintenance` et mémorise son statut antérieur (`previous_asset_status`), restauré à `complete`/`cancel`. Cycle `planifiee → en_cours → terminee/annulee`. | Implémenté |
| H23 | Incidents : cycle `ouvert → en_cours → resolu → clos`, `resolved_at` posé à la résolution, `close` exige `resolu`. Les incidents ne changent pas automatiquement le statut du bien — à valider (un `vol`/`perte` devrait peut-être passer le bien à `hors_service`/`reforme`). | À valider |
| H24 | Documents : stockés sur disque `local` privé, servis via `GET /documents/{id}/download` authentifié (pas d'URL signée pour l'instant). Types proposés : facture/contrat/garantie/carte_grise/autre — liste à valider avec les maquettes. | Implémenté (stockage provisoire) |
| H25 | Inventaires : une campagne liste des biens (`asset_ids`) ; chaque ligne passe `en_attente → trouve/absent/non_concerne` une seule fois ; clôture impossible si des lignes restent en attente. Aucun effet automatique sur le bien en cas d'écart — à valider (faut-il proposer une affectation/transfert ou un incident depuis un écart ?). | À valider |
| H26 | Rapports : `GET /reports/overview` = agrégation temps réel sans filtres ni exports. Les besoins exacts (période, structure, format PDF/Excel) restent à valider avec les maquettes « Rapports ». | À valider |
| H27 | Frontend : pages construites d'après les maquettes SVG. Le wizard de création de bien (5 étapes : général, administratif, localisation, documents, vérification) est implémenté — un seul POST final vers `/api/v1/assets` avec les sous-tableaux `real_estate`/`movable`/`vehicle` et `location`, puis uploads de documents et soumission optionnelle. Reste à faire : exports PDF/Excel (le CSV est implémenté), cartes GPS, notifications persistées, écrans par rôle et versions mobiles. Le QR code du bien est généré en SVG (bacon/bacon-qr-code) sur la fiche. Les IDs de structures/users sont affichés en attendant l'endpoint d'enrichissement (noms). | En cours |

## Incident résolu

- **Tests contre la mauvaise base** : le bloc `environment:` de
  `docker-compose.yml` injectait `APP_ENV=local` et `DB_*` en variables
  d'environnement réelles, ce qui écrasait `phpunit.xml` (`$_SERVER`
  prioritaire) → les tests tournaient sur PostgreSQL et `RefreshDatabase`
  a vidé la base de dev une fois. Correction : bloc `environment:` retiré
  (le `.env` monté en volume fait foi), `force="true"` ajouté dans
  `phpunit.xml`, base re-seedée. Les tests utilisent désormais SQLite
  `:memory:` de façon fiable.

## Points bloquants connus

- **Performance — RÉSOLU** : le dépôt a été déplacé de OneDrive vers `C:\PatriGest` (disque local). Pages à chaud ~1–3 s. Ne plus lancer Compose depuis l'ancien chemin OneDrive.
- **Maquettes Figma** : présentes en PNG dans `Maquettes/` mais non inspectables par l'agent dans cet environnement. Les écrans devront être validés un par un avec le propriétaire ou fournis en SVG/export Dev Mode.
- **PDF** : `Analyse_besoins_complete_PatriGest.pdf` et la fiche technique ne sont pas extractibles ici ; les décisions clés doivent être confirmées oralement ou en texte.
