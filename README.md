# Odiscount

Application unifiée Laravel 12, hébergée dans `odiscount_backend`, avec un site public et un espace d’administration. Le dossier `odiscount_frontend` est resté séparé et n’a pas été modifié.

## Prérequis

- PHP 8.2 ou supérieur avec PDO MySQL
- Composer
- MySQL 8+ ou MariaDB compatible
- Node.js/npm uniquement si les ressources Vite de développement sont nécessaires

## Interfaces

### Site public

- `/` : accueil et catégories actives
- `/categories/{slug}` : partenaires, réductions et localisations d’une catégorie active
- Vues Blade : `resources/views/front/`
- Routes nommées : `front.home`, `front.categories.show`
- CSS et JavaScript : `public/assets/front/css/style.css`, `public/assets/front/js/script.js`

Le site public lit directement les modèles Eloquent et la base MySQL par défaut. Il n’utilise pas d’API HTTP entre les deux interfaces.

### Administration

- `/admin/login` : connexion par session
- `/admin` : tableau de bord
- `/admin/categories` : catégories et visibilité
- `/admin/partners` : recherche, filtre et pagination des partenaires
- `/admin/partners/create` : création d’un partenaire
- Vues Blade : `resources/views/admin/`
- Routes : `routes/admin.php`, chargées avec le middleware web
- CSS et JavaScript : `public/assets/admin/css/admin.css`, `public/assets/admin/js/admin.js`

Les contrôles de session, les rôles Spatie (`Admin`, `User`, `Master`) et le middleware `EnsureUserIsActive` sont conservés. Les catégories et partenaires sont administrés dans le même schéma MySQL que celui lu par le site public. Une création de partenaire dans l’administration est immédiatement disponible sur la page publique de sa catégorie.

## Base de données

Configurer `.env` avec les paramètres utilisés localement :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=odiscount_new
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Les modèles métier utilisent uniquement la connexion par défaut. Les tables métier sont `categories`, `partenaires`, `villes` et `communes`. Les migrations de création correspondantes vérifient `Schema::hasTable` avant toute création et ne suppriment pas ces tables lors d’un rollback. Les migrations Laravel, utilisateurs, sessions, cache, queue et rôles sont également dans `database/migrations`.

**La base existante fait foi.** Vérifier l’état du schéma et des migrations avant d’exécuter une migration sur une base partagée. Ne jamais utiliser `migrate:fresh`, `migrate:refresh` ou `db:wipe` sur `odiscount_new`.

## Images et assets

Le dossier d’images commun est `public/images/` :

- `public/images/categories/{nom_fichier}` pour les images de catégorie
- `public/images/partenaires/{nom_fichier}` pour les images de partenaire

Les valeurs d’image conservées en base sont des noms de fichiers ; les modèles `Category` et `Partenaire` exposent l’accesseur `image_url` qui construit leurs URL publiques. Le formulaire actuel de création de partenaire ne téléverse pas d’image : il faut que les fichiers et leurs noms soient présents dans le dossier partagé et la base.

Les styles et scripts des deux interfaces restent séparés sous `public/assets/front/` et `public/assets/admin/`. Ces pages utilisent directement ces fichiers statiques ; elles ne dépendent pas du build Vite/Tailwind.

## Installation et lancement

Depuis `odiscount_backend` :

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Renseigner ensuite la base dans `.env` et vérifier préalablement que le schéma `odiscount_new` et les migrations correspondent aux données existantes. Pour le développement :

```sh
php artisan serve
```

Les comptes et mots de passe créés par `RoleSeeder` (`admin@example.com`, `user@example.com`, `master@example.com`, mot de passe `password`) sont des identifiants de développement uniquement ; ne pas les conserver en production.

## Vérification

```sh
php artisan optimize:clear
php artisan route:list
php artisan test
```

Les tests PHPUnit isolent leur base avec SQLite en mémoire ; cette configuration est réservée aux tests et ne change pas la connexion MySQL de l’application.

## Déploiement

- Configurer le document root du serveur web sur `odiscount_backend/public/`.
- Installer les dépendances de production avec `composer install --no-dev --optimize-autoloader`.
- Vérifier les variables `.env`, puis exécuter `php artisan config:cache`.
- Ne lancer aucune commande destructive sur la base de production.

L’API a été retirée : aucune route `/api/*` n’est exposée et Sanctum n’est plus utilisé. La migration historique de `personal_access_tokens` est conservée ; aucune table existante n’a été supprimée.
