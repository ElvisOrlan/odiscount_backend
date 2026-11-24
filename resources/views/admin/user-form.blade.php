@extends('admin.layout')

@php($isEditing = isset($user))

@section('title', $isEditing ? 'Modifier un utilisateur' : 'Créer un utilisateur')
@section('heading', $isEditing ? 'Modifier un utilisateur' : 'Créer un utilisateur')
@section('subtitle', $isEditing ? 'Mettez à jour le compte et ses rôles.' : 'Créez un compte et attribuez-lui un ou plusieurs rôles.')

@section('content')
    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            <p>Le formulaire contient des erreurs. Vérifiez les champs signalés.</p>
        </div>
    @endif

    <nav class="section-tabs" aria-label="Gestion des accès">
        <a class="section-tab is-current" href="{{ route('admin.users') }}">Utilisateurs</a>
        <a class="section-tab" href="{{ route('admin.roles') }}">Rôles</a>
    </nav>

    <form class="card partner-form" method="POST" action="{{ $isEditing ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="form-grid">
            <div class="form-field">
                <label for="name">Nom complet <span aria-hidden="true">*</span></label>
                <input id="name" name="name" type="text" maxlength="255" value="{{ old('name', $user->name ?? '') }}" required>
                @error('name') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="form-field">
                <label for="email">Adresse e-mail <span aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" maxlength="255" value="{{ old('email', $user->email ?? '') }}" required>
                @error('email') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="form-field">
                <label for="password">{{ $isEditing ? 'Nouveau mot de passe' : 'Mot de passe' }} @unless($isEditing)<span aria-hidden="true">*</span>@endunless</label>
                <input id="password" name="password" type="password" autocomplete="new-password" @unless($isEditing) required @endunless>
                <small>@if($isEditing) Laissez vide pour conserver le mot de passe actuel. @else 8 caractères minimum. @endif</small>
                @error('password') <small class="field-error">{{ $message }}</small> @enderror
            </div>
            <div class="form-field">
                <label for="password_confirmation">{{ $isEditing ? 'Confirmer le nouveau mot de passe' : 'Confirmer le mot de passe' }} @unless($isEditing)<span aria-hidden="true">*</span>@endunless</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @unless($isEditing) required @endunless>
            </div>
            <fieldset class="role-options form-field-wide">
                <legend>Rôles <span aria-hidden="true">*</span></legend>
                <small>Un compte doit avoir au moins un rôle. Le rôle Master donne accès à la gestion des comptes et des rôles.</small>
                <div class="role-checkboxes">
                    @foreach ($roles as $role)
                        <label class="form-checkbox">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, array_map('strval', old('roles', isset($user) ? $user->roles->modelKeys() : [])), true))>
                            <span>{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('roles') <small class="field-error">{{ $message }}</small> @enderror
                @error('roles.*') <small class="field-error">{{ $message }}</small> @enderror
            </fieldset>
        </div>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.users') }}">Annuler</a>
            <button class="btn pri" type="submit">{{ $isEditing ? 'Enregistrer les modifications' : 'Créer le compte' }}</button>
        </div>
    </form>
@endsection
