@extends('admin.layout')

@section('title', 'Catégories')
@section('heading', 'Catégories')
@section('subtitle', 'Les rubriques du menu et de la page d’accueil.')

@section('content')
    <div class="wrap">
        <table class="tbl">
            <thead>
            <tr><th>Catégorie</th><th>Adresse publique</th><th>Partenaires</th><th>Statut</th><th>Visible</th></tr>
            </thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td><div class="who"><div class="av">{{ mb_strtoupper(mb_substr($category->categorie, 0, 1)) }}</div><b>{{ $category->categorie }}</b></div></td>
                    <td><code>/categories/{{ $category->slug }}</code></td>
                    <td>{{ $category->partenaires_count }}</td>
                    <td data-category-status>{{ $category->status === 'active' ? 'Active' : 'Masquée' }}</td>
                    <td>
                        <button
                            class="sw"
                            type="button"
                            role="switch"
                            aria-checked="{{ $category->status === 'active' ? 'true' : 'false' }}"
                            aria-label="{{ $category->status === 'active' ? 'Désactiver' : 'Activer' }} la catégorie {{ $category->categorie }}"
                            data-category-toggle
                            data-active="{{ $category->status === 'active' ? '1' : '0' }}"
                            data-status-url="{{ route('admin.categories.status', $category) }}"
                        ></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Aucune catégorie n’est enregistrée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
