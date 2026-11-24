<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'odiscount - Les petites annonces les plus proches de chez vous')</title>
  <meta name="description" content="@yield('meta_description', 'Les petites annonces les plus proches de chez vous : resto, auto, spa-fitness, éducation, hôtels, santé, modes, optique et plus encore.')">
  <link rel="stylesheet" href="{{ asset('assets/front/css/style.css') }}">
  <link rel="icon" href="{{ asset('images/icone.png') }}" type="image/png">
</head>
<body>
  <header class="topbar">
    <div class="topbar__inner">
      <a href="{{ url('/') }}" class="logo" aria-label="odiscount - accueil">
        <img src="{{ asset('images/logos.png') }}" alt="odiscount" class="logo__image">
      </a>
      <nav class="topbar__links" aria-label="Liens rapides">
        <a href="{{ url('/') }}">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10" cy="10" r="6.6" fill="none" stroke="currentColor" stroke-width="2.6"/>
            <path d="m15.2 15.2 5.6 5.6" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
          </svg>
          <span>Recherche</span>
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="12" r="11" fill="currentColor"/>
            <path d="M9 9.4a3.1 3.1 0 1 1 4.5 2.8c-1 .5-1.5 1.1-1.5 2.2" fill="none" stroke="#fff" stroke-width="2.3" stroke-linecap="round"/>
            <circle cx="12" cy="17.7" r="1.4" fill="#fff"/>
          </svg>
          <span>Aide</span>
        </a>
      </nav>
    </div>
  </header>

  <nav class="mainnav" aria-label="Catégories">
    <div class="mainnav__inner">
      <a href="{{ url('/') }}" class="mainnav__home" aria-label="Accueil">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
          <path d="M12 3 2 12h3v8h5v-5h4v5h5v-8h3L12 3z"/>
        </svg>
      </a>
      <ul class="mainnav__list">
        @foreach ($menuCategories as $menuCategory)
          <li>
            <a href="{{ route('front.categories.show', $menuCategory->slug) }}"
               class="mainnav__link{{ request()->routeIs('front.categories.show') && request()->route('slug') === $menuCategory->slug ? ' mainnav__link--active' : '' }}"
               @if (request()->routeIs('front.categories.show') && request()->route('slug') === $menuCategory->slug) aria-current="page" @endif>
              {{ $menuCategory->categorie }}
            </a>
          </li>
        @endforeach
      </ul>
      <a href="#" class="mainnav__market">Super marché</a>
    </div>
  </nav>

  @yield('content')

  <footer class="footer">
    <div class="footer__inner">
      <div class="footer__cols">
        <div class="footer__col">
          <h4 class="footer__title">Nous connaître</h4>
          <ul>
            <li><a href="#">Qui sommes-nous?</a></li>
            <li><a href="#">Politique d'utilisation</a></li>
            <li><a href="#" class="footer__link--gold">Condition Générale d'utilisation</a></li>
          </ul>
        </div>
        <div class="footer__col">
          <h4 class="footer__title">Liens utiles</h4>
          <ul>
            <li><a href="#">Contactez-nous</a></li>
            <li><a href="#">Aide</a></li>
            <li><a href="#">Vos suggestions</a></li>
          </ul>
        </div>
        <div class="footer__col footer__col--social">
          <ul class="social">
            <li><a href="#" aria-label="X"><span>X</span></a></li>
            <li><a href="#" aria-label="LinkedIn"><span>in</span></a></li>
            <li><a href="#" aria-label="Facebook"><span>f</span></a></li>
            <li>
              <a href="#" aria-label="Instagram">
                <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                  <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/>
                </svg>
              </a>
            </li>
            <li><a href="#" aria-label="Pinterest"><span>p</span></a></li>
          </ul>
        </div>
      </div>

      <div class="footer__bottom">
        <a href="#" class="footer__post">Laisser une annonce</a>
        <p class="footer__copy">
          © <span id="year">2026</span> odiscount.ci | Tous droits réservés<br>
          © Conçu par | MOOBICOM
        </p>
      </div>
    </div>
  </footer>

  <script src="{{ asset('assets/front/js/script.js') }}"></script>
</body>
</html>
