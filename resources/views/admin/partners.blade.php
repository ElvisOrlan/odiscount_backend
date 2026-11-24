@extends('admin.layout')

@section('title', 'Partenaires')
@section('heading', 'Partenaires')
@section('subtitle', 'Les partenaires enregistrés et leurs informations.')
@section('add-label', 'Créer un partenaire')
@section('add-url', route('admin.partners.create'))

@section('content')
    @if (session('status'))
        <p class="notice notice-success" role="status">{{ session('status') }}</p>
    @endif

    <form class="tools" id="partner-filters" method="GET" action="{{ route('admin.partners') }}">
        <input
            type="search"
            name="q"
            id="partner-search"
            placeholder="Rechercher un partenaire, un quartier, un gérant"
            aria-label="Rechercher"
            value="{{ $filters['q'] ?? '' }}"
        >
        <select name="category" id="category-filter" aria-label="Filtrer par catégorie">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->categorie }}</option>
            @endforeach
        </select>
        <button class="btn pri" type="submit">Rechercher</button>
        @if (!empty($filters['q']) || !empty($filters['category']))
            <a class="btn" href="{{ route('admin.partners') }}">Effacer les filtres</a>
        @endif
    </form>

    <p class="pagination-summary">
        @if ($partners->total())
            Affichage de {{ $partners->firstItem() }} à {{ $partners->lastItem() }} sur {{ $partners->total() }} partenaires
        @else
            Aucun partenaire trouvé
        @endif
    </p>

    <div class="wrap">
        <table class="tbl">
            <thead>
            <tr><th>Partenaire</th><th>Catégorie</th><th>Réduction</th><th>Offre</th><th>Localisation</th><th>Gérant</th><th>Contact</th></tr>
            </thead>
            <tbody id="partner-rows">
            @forelse ($partners as $partner)
                @php
                    $location = $partner->localisation
                        ?: ($partner->commune?->commune ?? $partner->ville?->ville ?? 'Non renseignée');
                    $categoryName = $partner->categorie?->categorie ?? 'Catégorie inconnue';
                @endphp
                <tr data-category="{{ $partner->categorie_id }}">
                    <td>
                        <div class="who">
                            <div class="av">{{ mb_strtoupper(mb_substr($partner->nom_partenaire, 0, 1)) }}</div>
                            <div>
                                <b>{{ $partner->nom_partenaire }}</b>
                                @if ($partner->activite_principale)
                                    <small>{{ $partner->activite_principale }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td>
                        {{ $categoryName }}
                        @if ($partner->categorie)
                            <small class="status {{ $partner->categorie->status === 'active' ? 'status-on' : 'status-off' }}">
                                {{ $partner->categorie->status === 'active' ? 'Catégorie active' : 'Catégorie masquée' }}
                            </small>
                        @endif
                    </td>
                    <td><span class="disc">-{{ rtrim(rtrim(number_format((float) $partner->taux_reduction, 2, ',', ' '), '0'), ',') }}%</span></td>
                    <td>{{ $partner->info_reduction ?: '–' }}</td>
                    <td>{{ $location }}</td>
                    <td>{{ $partner->nom_prenom_gerant ?: '–' }}</td>
                    <td>{{ $partner->telephone ?: '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Aucun partenaire ne correspond aux critères sélectionnés.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($partners->hasPages())
        <nav class="pagination" aria-label="Pagination des partenaires">
            @if ($partners->onFirstPage())
                <span class="pagination-link is-disabled" aria-disabled="true">Précédent</span>
            @else
                <a class="pagination-link" href="{{ $partners->previousPageUrl() }}" rel="prev">Précédent</a>
            @endif

            @foreach ($partners->getUrlRange(max(1, $partners->currentPage() - 2), min($partners->lastPage(), $partners->currentPage() + 2)) as $page => $url)
                @if ($page === $partners->currentPage())
                    <span class="pagination-link is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($partners->hasMorePages())
                <a class="pagination-link" href="{{ $partners->nextPageUrl() }}" rel="next">Suivant</a>
            @else
                <span class="pagination-link is-disabled" aria-disabled="true">Suivant</span>
            @endif
        </nav>
    @endif
@endsection
