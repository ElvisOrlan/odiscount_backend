@extends('front.layouts.app')

@section('title', $category->categorie . ' - odiscount')

@section('content')
  <main class="category-page">
    <div class="category-page__heading">
      <h1 class="category-page__title">{{ $category->categorie }}</h1>
      <a class="category-page__back" href="{{ route('front.home') }}">Retour à l’accueil</a>
    </div>

    <div class="listing-grid">
      @forelse ($partenaires as $partenaire)
        @php
          $localisation = $partenaire->localisation
            ?: ($partenaire->commune?->commune ?? $partenaire->ville?->ville ?? 'Localisation non renseignée');
          $reduction = rtrim(rtrim(number_format((float) $partenaire->taux_reduction, 2, ',', ' '), '0'), ',');
        @endphp
        <article class="listing-card">
          <div class="listing-card__image" role="img" aria-label="{{ $partenaire->nom_partenaire }}">
            @if ($partenaire->image_url)
              <img src="{{ $partenaire->image_url }}"
                   alt="{{ $partenaire->nom_partenaire }}" loading="lazy">
            @else
              <span class="listing-card__placeholder-label">Image à venir</span>
            @endif
            <span class="listing-card__discount">-{{ $reduction }}%</span>
          </div>
          <div class="listing-card__body">
            <h2 class="listing-card__name">{{ $partenaire->nom_partenaire }}</h2>
            <p class="listing-card__location">{{ $localisation }}</p>
            @if ($partenaire->info_reduction)
              <p class="listing-card__offer">{{ $partenaire->info_reduction }}</p>
            @endif
            <dl class="listing-card__details">
              @if ($partenaire->telephone)
                <div><dt>Contacts :</dt><dd>{{ $partenaire->telephone }}</dd></div>
              @endif
              <div><dt>Localisation :</dt><dd>{{ $localisation }}</dd></div>
              @if ($partenaire->nom_prenom_gerant)
                <div><dt>Gérant :</dt><dd>{{ $partenaire->nom_prenom_gerant }}</dd></div>
              @endif
              @if ($partenaire->phone_gerant)
                <div><dt>Contact gérant :</dt><dd>{{ $partenaire->phone_gerant }}</dd></div>
              @endif
            </dl>
          </div>
        </article>
      @empty
        <p class="listing-grid__empty">Aucun partenaire n’est actuellement disponible dans cette catégorie.</p>
      @endforelse
    </div>
  </main>
@endsection
