@extends('admin.layout')

@section('title', 'Rôles')
@section('heading', 'Rôles')
@section('subtitle', 'Gérez les rôles et leur attribution aux comptes.')

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
        <a class="section-tab" href="{{ route('admin.users') }}">Utilisateurs</a>
        <a class="section-tab is-current" href="{{ route('admin.roles') }}" aria-current="page">Rôles</a>
    </nav>

    <form class="card role-create-form" method="POST" action="{{ route('admin.roles.store') }}">
        @csrf
        <h2>Créer un rôle</h2>
        <div class="role-create-fields">
            <div class="form-field">
                <label for="name">Nom du rôle</label>
                <input id="name" name="name" type="text" maxlength="100" value="{{ old('name') }}" pattern="[A-Za-z][A-Za-z0-9 _-]*" required>
                <small>Lettres, chiffres, espaces, tirets et tirets bas. Les rôles n’accordent pas de permissions fines par eux-mêmes.</small>
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <button class="btn pri" type="submit">Créer le rôle</button>
        </div>
    </form>

    <h2 class="section-heading">Rôles disponibles</h2>
    <div class="wrap">
        <table class="tbl">
            <thead><tr><th>Rôle</th><th>Comptes associés</th><th>Gestion</th></tr></thead>
            <tbody>
            @foreach ($roles as $role)
                @php($isSystemRole = in_array($role->name, $systemRoles, true))
                <tr>
                    <td>
                        @if ($isSystemRole)
                            <b>{{ $role->name }}</b> <small>Rôle système</small>
                        @else
                            <form class="role-rename-form" method="POST" action="{{ route('admin.roles.update', $role) }}">
                                @csrf
                                @method('PUT')
                                <label class="visually-hidden" for="role-name-{{ $role->id }}">Nom du rôle {{ $role->name }}</label>
                                <input id="role-name-{{ $role->id }}" name="name" type="text" maxlength="100" value="{{ $role->name }}" pattern="[A-Za-z][A-Za-z0-9 _-]*" required>
                                <button class="btn sm" type="submit">Renommer</button>
                            </form>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @if ($isSystemRole)
                            <span class="muted">Protégé</span>
                        @elseif ($role->users_count === 0)
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Supprimer ce rôle ?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn sm dng" type="submit">Supprimer</button>
                            </form>
                        @else
                            <span class="muted">Retirez-le d’abord des utilisateurs</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
