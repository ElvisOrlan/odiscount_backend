@extends('admin.layout')

@section('title', 'Utilisateurs')
@section('heading', 'Utilisateurs')
@section('subtitle', 'Gérez les comptes et les rôles attribués.')
@section('add-label', 'Créer un utilisateur')
@section('add-url', route('admin.users.create'))

@section('content')
    @if (session('status'))
        <p class="notice notice-success" role="status">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    <nav class="section-tabs" aria-label="Gestion des accès">
        <a class="section-tab is-current" href="{{ route('admin.users') }}" aria-current="page">Utilisateurs</a>
        <a class="section-tab" href="{{ route('admin.roles') }}">Rôles</a>
    </nav>

    <form class="tools" method="GET" action="{{ route('admin.users') }}">
        <input type="search" name="q" placeholder="Rechercher par nom, e-mail ou rôle" aria-label="Rechercher un utilisateur" value="{{ $filters['q'] ?? '' }}">
        <select name="role" aria-label="Filtrer par rôle">
            <option value="">Tous les rôles</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) ($filters['role'] ?? '') === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Filtrer par statut">
            <option value="">Tous les statuts</option>
            <option value="1" @selected(($filters['status'] ?? '') === '1')>Actifs</option>
            <option value="0" @selected(($filters['status'] ?? '') === '0')>Désactivés</option>
        </select>
        <button class="btn pri" type="submit">Filtrer</button>
        @if (!empty($filters['q'] ?? null) || !empty($filters['role'] ?? null) || ($filters['status'] ?? null) !== null)
            <a class="btn" href="{{ route('admin.users') }}">Effacer</a>
        @endif
    </form>

    <p class="pagination-summary">
        @if ($users->total())
            Affichage de {{ $users->firstItem() }} à {{ $users->lastItem() }} sur {{ $users->total() }} utilisateurs
        @else
            Aucun utilisateur trouvé
        @endif
    </p>

    <div class="wrap">
        <table class="tbl">
            <thead>
            <tr><th>Utilisateur</th><th>Rôles</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="who">
                            <div class="av">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                            <div><b>{{ $user->name }}</b><small>{{ $user->email }}</small></div>
                        </div>
                    </td>
                    <td>{{ $user->getRoleNames()->join(', ') ?: 'Aucun rôle' }}</td>
                    <td><span class="status {{ $user->is_active ? 'status-on' : 'status-off' }}">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                    <td>
                        <div class="act">
                            <a class="btn sm" href="{{ route('admin.users.show', $user) }}">Consulter</a>
                            <a class="btn sm" href="{{ route('admin.users.edit', $user) }}">Modifier</a>
                            @if (auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Confirmer le changement de statut de ce compte ?')">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="active" value="{{ $user->is_active ? '0' : '1' }}">
                                    <button class="btn sm {{ $user->is_active ? 'dng' : '' }}" type="submit">{{ $user->is_active ? 'Désactiver' : 'Activer' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Confirmer la suppression définitive de ce compte ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn sm dng" type="submit">Supprimer</button>
                                </form>
                            @else
                                <span class="muted">Votre compte</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">Aucun utilisateur ne correspond à la recherche.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <nav class="pagination" aria-label="Pagination des utilisateurs">
            @if ($users->onFirstPage())
                <span class="pagination-link is-disabled" aria-disabled="true">Précédent</span>
            @else
                <a class="pagination-link" href="{{ $users->previousPageUrl() }}" rel="prev">Précédent</a>
            @endif
            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $pageNumber => $url)
                @if ($pageNumber === $users->currentPage())
                    <span class="pagination-link is-current" aria-current="page">{{ $pageNumber }}</span>
                @else
                    <a class="pagination-link" href="{{ $url }}">{{ $pageNumber }}</a>
                @endif
            @endforeach
            @if ($users->hasMorePages())
                <a class="pagination-link" href="{{ $users->nextPageUrl() }}" rel="next">Suivant</a>
            @else
                <span class="pagination-link is-disabled" aria-disabled="true">Suivant</span>
            @endif
        </nav>
    @endif
@endsection
