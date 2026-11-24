@extends('front.layouts.app')

@section('content')
  <!-- ============ HERO / RECHERCHE ============ -->
  <section class="hero">
    <div class="hero__content">
      <h1 class="hero__title">Les petites annonces les plus proches de chez vous</h1>
      <form class="search" id="search-form" action="#" method="get" role="search">
        <label class="sr-only" for="search-category">Catégorie</label>
        <select class="search__select" id="search-category" name="categorie">
          <option value="">Choisissez une catégorie</option>
          @foreach ($categories as $category)
            <option value="{{ $category->slug }}">{{ $category->categorie }}</option>
          @endforeach
        </select>
        <label class="sr-only" for="search-place">Lieu</label>
        <input class="search__input" id="search-place" type="text" name="lieu" placeholder="Où ?" autocomplete="off">
        <button class="search__btn" type="submit">Rechercher</button>
      </form>
    </div>
  </section>

  <!-- ============ CONTENU PRINCIPAL ============ -->
  <main class="main">
    <div class="main__inner">

      <!-- Colonne gauche -->
      <aside class="sidebar">
        <a href="#" class="promo" aria-label="Affiche Job Center">
          <img class="promo__img"
               src="{{ asset('images/jobcenter.jpg') }}"
               alt="Affiche Job Center" loading="lazy">
        </a>

        <div class="perks">
          <article class="perk">
            <h3 class="perk__title">Gratuit, et la modification aussi !</h3>
            <p class="perk__text">Chez nous, la gratuité c'est un état d'esprit. En plus d'économiser de l'argent vous gagnez du temps !</p>
          </article>
          <article class="perk">
            <h3 class="perk__title">Une fidélité récompensée</h3>
            <p class="perk__text">À chaque dépôt d'annonce, vous gagnez des points bonus. Remontée en tête de liste, ajout de photos… offrez un maximum de visibilité à vos annonces pour vendre rapidement.</p>
          </article>
          <article class="perk">
            <h3 class="perk__title">Un profil unique, comme vous</h3>
            <p class="perk__text">Un avatar qui vous représente, des badges certifiants… Votre débarras, vos préférences.</p>
          </article>
        </div>
      </aside>

      <!-- Colonne droite : catégories -->
      <section class="categories">
        <h2 class="categories__title">Nos catégories</h2>

        @foreach ($categories->chunk(6) as $categoryGroup)
          <div class="cat-grid{{ $loop->first ? '' : ' cat-grid--second' }}">
            @foreach ($categoryGroup as $category)
              <a href="{{ route('front.categories.show', $category->slug) }}" class="cat{{ $category->image_categorie ? '' : ' cat--empty' }}">
                @if ($category->image_url)
                  <img src="{{ $category->image_url }}"
                       alt="{{ $category->categorie }}" loading="lazy">
                @endif
                <span class="cat__label">{{ $category->categorie }}</span>
              </a>
            @endforeach
          </div>
        @endforeach
      </section>

    </div>
  </main>

  <!-- ============ BANNIÈRE PUBLICITAIRE ============ -->
  <section class="banner" aria-label="Bannière">
    <img class="banner__img"
         src="{{ asset('images/banniere/banniere-index-footer.png') }}"
         alt="Bannière publicitaire" loading="lazy">
    <div class="banner__bar"></div>
  </section>

@endsection
