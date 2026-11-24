<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Authentification et rôles

L'authentification web utilise les comptes de la table `users`. L'API utilise Laravel Sanctum avec des jetons Bearer. Les migrations ajoutent `is_active` à `users`, la table des jetons personnels et les tables de Spatie Permission.

Après configuration de la base du backend, appliquer les migrations puis créer les rôles sans leur attribuer de permissions :

```sh
php artisan migrate
php artisan db:seed --class=RoleSeeder
```

Les rôles disponibles sont `Admin`, `User` et `Master`. Le seeder crée également les comptes de test suivants, tous actifs et avec le mot de passe `password` :

| E-mail | Rôle |
|---|---|
| `admin@example.com` | `Admin` |
| `user@example.com` | `User` |
| `master@example.com` | `Master` |

Ces comptes sont réservés au développement et ne doivent pas être conservés en production. Pour attribuer un rôle à un compte existant, utiliser le code applicatif ou Artisan Tinker :

```php
App\Models\User::where('email', 'admin@example.com')->firstOrFail()->assignRole('Master');
```

La connexion de l'interface se fait sur `/admin/login`. Pour l'API, envoyer `POST /api/login` avec `email`, `password` et éventuellement `device_name`, puis transmettre le jeton retourné dans l'en-tête `Authorization: Bearer …`. `GET /api/user` retourne le compte authentifié et `POST /api/logout` révoque le jeton courant. `PATCH /api/users/{id}/status` permet aux rôles `Admin` et `Master` de modifier `is_active`; la désactivation bloque la connexion et révoque les jetons du compte. Les permissions Spatie restent à définir.

La page `/admin/partners` lit les partenaires et leurs relations catégorie, ville et commune depuis la connexion `odiscount`. Elle liste aussi les partenaires rattachés à une catégorie inactive et permet un filtrage par catégorie ou par texte.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
