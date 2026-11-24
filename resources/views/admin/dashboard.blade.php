@extends('admin.layout')

@section('title', 'Tableau de bord')
@section('heading', 'Tableau de bord')
@section('subtitle', 'Vue d’ensemble du catalogue de partenaires.')

@section('content')
    @php($maxPartners = max(1, (int) $categories->max('partenaires_count')))

    <section class="stats">
        <div class="stat"><b>{{ $visiblePartnerCount }}</b><span>partenaires visibles</span></div>
        <div class="stat"><b>{{ $activeCategoryCount }}</b><span>catégories visibles</span></div>
        <div class="stat"><b>{{ $averageDiscount }} %</b><span>réduction moyenne</span></div>
        <div class="stat"><b>{{ $emptyActiveCategoryCount }}</b><span>catégories sans partenaire</span></div>
    </section>

    <section class="card">
        <h2>Partenaires par catégorie</h2>
        @foreach ($categories as $category)
            <div class="bar">
                <span>{{ $category->categorie }}</span>
                <i style="width: {{ $category->partenaires_count / $maxPartners * 100 }}%"></i>
                <em>{{ $category->partenaires_count }}</em>
            </div>
        @endforeach
    </section>
@endsection
