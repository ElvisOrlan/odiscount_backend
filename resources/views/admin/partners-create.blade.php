@extends('admin.layout')

@section('title', 'Créer un partenaire')
@section('heading', 'Créer un partenaire')
@section('subtitle', 'Renseignez les informations du nouveau partenaire.')

@section('content')
    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            <p>Le formulaire contient des erreurs. Vérifiez les champs signalés.</p>
        </div>
    @endif

    <form class="card partner-form" method="POST" action="{{ route('admin.partners.store') }}">
        @csrf

        <div class="form-grid">
            <div class="form-field">
                <label for="nom_partenaire">Nom du partenaire <span aria-hidden="true">*</span></label>
                <input id="nom_partenaire" name="nom_partenaire" type="text" maxlength="255" value="{{ old('nom_partenaire') }}" required>
                @error('nom_partenaire') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="categorie_id">Catégorie <span aria-hidden="true">*</span></label>
                <select id="categorie_id" name="categorie_id" required>
                    <option value="">Choisir une catégorie</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('categorie_id') == $category->id)>
                            {{ $category->categorie }}{{ $category->status === 'active' ? '' : ' (masquée)' }}
                        </option>
                    @endforeach
                </select>
                @error('categorie_id') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="taux_reduction">Taux de réduction (%) <span aria-hidden="true">*</span></label>
                <input id="taux_reduction" name="taux_reduction" type="number" min="0" max="100" step="0.01" value="{{ old('taux_reduction') }}" required>
                @error('taux_reduction') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="activite_principale">Activité principale</label>
                <input id="activite_principale" name="activite_principale" type="text" maxlength="255" value="{{ old('activite_principale') }}">
                @error('activite_principale') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field form-field-wide">
                <label for="info_reduction">Description de l’offre</label>
                <textarea id="info_reduction" name="info_reduction" rows="3">{{ old('info_reduction') }}</textarea>
                @error('info_reduction') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="telephone">Téléphone du partenaire</label>
                <input id="telephone" name="telephone" type="tel" maxlength="30" value="{{ old('telephone') }}">
                @error('telephone') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="localisation">Localisation</label>
                <input id="localisation" name="localisation" type="text" maxlength="255" value="{{ old('localisation') }}">
                @error('localisation') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="nom_prenom_gerant">Nom du gérant</label>
                <input id="nom_prenom_gerant" name="nom_prenom_gerant" type="text" maxlength="255" value="{{ old('nom_prenom_gerant') }}">
                @error('nom_prenom_gerant') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="mail_gerant">E-mail du gérant</label>
                <input id="mail_gerant" name="mail_gerant" type="email" maxlength="255" value="{{ old('mail_gerant') }}">
                @error('mail_gerant') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <div class="form-field">
                <label for="phone_gerant">Téléphone du gérant</label>
                <input id="phone_gerant" name="phone_gerant" type="tel" maxlength="30" value="{{ old('phone_gerant') }}">
                @error('phone_gerant') <small class="field-error">{{ $message }}</small> @enderror
            </div>

            <label class="form-checkbox">
                <input name="boutique_physique" type="checkbox" value="1" @checked(old('boutique_physique'))>
                <span>Le partenaire dispose d’une boutique physique</span>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="{{ route('admin.partners') }}">Annuler</a>
            <button class="btn pri" type="submit">Créer le partenaire</button>
        </div>
    </form>
@endsection
