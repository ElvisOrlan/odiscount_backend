# Notes de fusion frontend / backend

## Branche et sauvegarde locale

- Branche de travail : `fusion-front-back`.
- Le frontend `odiscount_frontend` est uniquement une source de lecture et ne doit pas être modifié ni supprimé.
- Le backend contient déjà un répertoire non suivi `public/images/`. Il est préexistant, conservé tel quel et ne doit pas être inclus dans les commits de cette fusion.

## Étape 1 — Audit

- Les deux projets sont des applications Laravel 12 en PHP 8.2+, avec des modèles métier de mêmes noms (`Category`, `Partenaire`, `Ville`, `Commune`) et des vues distinctes. Le backend garde ses vues sous `resources/views/admin`; les vues du site seront déplacées logiquement sous `resources/views/front`.
- Le backend a une connexion métier secondaire `odiscount`, tandis que ses comptes utilisent la connexion par défaut. Le frontend utilise la connexion par défaut, configurée SQLite par défaut. La fusion fera utiliser MySQL `odiscount_new` comme connexion par défaut unique.
- Les configurations des deux projets utilisent déjà des pilotes `database` pour sessions, cache et queues ; ils suivront la connexion MySQL unique, sans migration ni vidage de ces tables.
- Le formulaire d’ajout de partenaire et son traitement backend ne contiennent pas de champ de téléversement et ne sauvegardent pas de nom d’image. Les rendus actuels consomment des noms de fichiers stockés en base depuis `public/images/categories/` et `public/images/partenaires/`. Les 65 fichiers d’image présents dans le frontend sont déjà présents à l’identique dans le `public/images/` local du backend ; aucune valeur en base et aucun fichier image ne seront remplacés.
- L’interface d’administration inspectée n’appelait pas les routes `/api/*` et ne contenait pas de gestion d’état de compte. L’API et Sanctum ont donc été supprimés ; la migration historique de `personal_access_tokens` est conservée et aucune table n’est supprimée.
- Le frontend charge son CSS et JavaScript directement depuis `public/css/style.css` et `public/js/script.js`. Le backend charge ses assets admin depuis `public/css/admin.css` et `public/js/admin.js`. Les entrées Vite/Tailwind ne servent pas à ces interfaces et ne seront pas modifiées.

## Décisions d’implémentation

- Les routes publiques seront nommées `front.home` et `front.categories.show`; les noms de routes admin resteront préfixés par `admin.`.
- Le schéma métier existant fait foi. Les migrations frontend `categories`, `partenaires`, `villes` et `communes` ne seront pas ajoutées au backend et les migrations existantes ne seront pas lancées sur la base de production.
- La construction des URLs d’image sera centralisée sur les modèles et utilisée dans les vues publiques ; les chemins de stockage et les valeurs existantes en base resteront inchangés.
- Le formulaire de recherche factice et les autres liens visuels non implémentés resteront inchangés, conformément au périmètre.

## Journal des étapes

- [x] Étape 1 : audit rapide ; branche créée et décisions consignées.
- [x] Étape 2 : connexion MySQL unique, `.env` et `.env.example` alignés sur `odiscount_new` ; sessions, cache et queues conservent leurs pilotes `database` et utilisent la connexion par défaut.
- [x] Étape 3 : les dépendances Composer et npm utiles au frontend étaient déjà présentes dans le backend, sans version supplémentaire à fusionner ; `composer install` et `composer dump-autoload` ont été exécutés. Les scripts Composer ont été désactivés pour contourner leur blocage dans cet environnement.
- [x] Étape 4 : modèles métier fusionnés (champs assignables, relations et URLs d’image), tests admin alignés sur la connexion par défaut ; aucune migration métier frontend n’a été copiée.
- [x] Étape 5 : pages publiques intégrées sous `resources/views/front/`, routes publiques nommées `front.*` ajoutées et catégories du menu partagées via le fournisseur de vues.
- [x] Étape 6 : routes admin séparées dans `routes/admin.php`, chargées avec le middleware `web`; sessions, rôles Spatie et `EnsureUserIsActive` conservés. Les noms restent `admin.*`; la route API de statut de compte n’a pas d’équivalent web car aucune interface admin ne l’utilisait.
- [x] Étape 7 : assets publics copiés dans `public/assets/front/css|js`, assets admin déplacés dans `public/assets/admin/css|js` ; les deux interfaces chargent leurs chemins distincts. Vite/Tailwind ne servent pas ces rendus statiques et la configuration Vite est inchangée.
- [x] Étape 8 : dossiers d’images communs conservés sous `public/images/`, URLs centralisées dans les accesseurs `image_url` et utilisées dans les vues publiques et admin. L’admin ne téléverse pas encore d’images ; cette fonctionnalité n’a pas été ajoutée afin de ne pas modifier le formulaire/HTML au-delà du périmètre visuel.
- [x] Étape 9 : routes API retirées de l’application et de `bootstrap/app.php`, tests API supprimés, trait et dépendance Sanctum retirés. La migration de `personal_access_tokens` est conservée et aucune table n’est supprimée.
- [ ] Étape 10 : tests, routes et vérifications HTTP non destructives.
- [ ] Étape 11 : documentation finale.

## Points à surveiller en production

- Configurer le document root du serveur web sur `odiscount_backend/public/`.
- Déployer les dépendances de production avec `composer install --no-dev`.
- Vérifier les variables d’environnement et exécuter `php artisan config:cache` après configuration.
- Ne jamais exécuter `migrate:fresh`, `migrate:refresh`, `db:wipe` ni aucune commande destructive sur `odiscount_new`.
