@extends('admin.layout')

@section('title', 'Détail utilisateur')
@section('heading', $user->name)
@section('subtitle', 'Consultez les informations du compte et ses rôles.')
@section('add-label', 'Modifier')
@section('add-url', route('admin.users.edit', $user))

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
        <a class="section-tab is-current" href="{{ route('admin.users') }}">Utilisateurs</a>
        <a class="section-tab" href="{{ route('admin.roles') }}">Rôles</a>
    </nav>

    <section class="card partner-form" aria-label="Informations du compte">
        <div class="form-grid">
            <div class="form-field">
                <span>Nom complet</span>
                <strong>{{ $user->name }}</strong>
            </div>
            <div class="form-field">
                <span>Adresse e-mail</span>
                <strong>{{ $user->email }}</strong>
            </div>
            <div class="form-field">
                <span>Statut</span>
                <strong class="status {{ $user->is_active ? 'status-on' : 'status-off' }}">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</strong>
            </div>
            <div class="form-field">
                <span>Rôles</span>
                <strong>{{ $user->getRoleNames()->join(', ') ?: 'Aucun rôle' }}</strong>
            </div>
            <div class="form-field">
                <span>Créé le</span>
                <strong>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</strong>
            </div>
            <div class="form-field">
                <span>Dernière modification</span>
                <strong>{{ $user->updated_at?->format('d/m/Y H:i') ?? '—' }}</strong>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.users') }}">Retour à la liste</a>
            @if (auth()->id() !== $user->id)
                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Confirmer le changement de statut de ce compte ?')">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="active" value="{{ $user->is_active ? '0' : '1' }}">
                    <button class="btn {{ $user->is_active ? 'dng' : '' }}" type="submit">{{ $user->is_active ? 'Désactiver' : 'Activer' }}</button>
                </form>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Confirmer la suppression définitive de ce compte ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn dng" type="submit">Supprimer</button>
                </form>
            @else
                <span class="muted">Votre compte ne peut pas être désactivé ni supprimé ici.</span>
            @endif
        </div>
    </section>
@endsection
