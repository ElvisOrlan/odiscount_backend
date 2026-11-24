<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion – odiscount administration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login">
<main class="login-card">
    <div class="brand" style="color:var(--ink);padding:0 0 18px">o<b>discount</b><small>Espace administration</small></div>
    <h1>Connexion administrateur</h1>
    <p>Connectez-vous pour gérer les catégories et les partenaires.</p>

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
        @error('email')<div class="login-error" role="alert">{{ $message }}</div>@enderror

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        @error('password')<div class="login-error" role="alert">{{ $message }}</div>@enderror

        <button class="btn pri" type="submit">Se connecter</button>
    </form>
</main>
</body>
</html>
