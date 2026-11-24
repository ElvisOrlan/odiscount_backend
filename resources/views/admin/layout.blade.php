<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') – odiscount administration</title>
    <link rel="icon" href="{{ asset('images/icone.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/admin.css') }}">
</head>
<body>
<aside class="side">
    <div class="brand">o<b>discount</b><small>Espace administration</small></div>
    <a class="nav" href="{{ route('admin.dashboard') }}" @if($page === 'dashboard') aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        Tableau de bord
    </a>
    <a class="nav" href="{{ route('admin.categories') }}" @if($page === 'categories') aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
        Catégories
    </a>
    <a class="nav" href="{{ route('admin.partners') }}" @if($page === 'partners') aria-current="page" @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l1-5h16l1 5M4 9v11h16V9M9 20v-6h6v6"/></svg>
        Partenaires
    </a>
    @role('Master')
        <a class="nav" href="{{ route('admin.users') }}" @if(in_array($page, ['users', 'roles'], true)) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="4"/><path d="M2 21v-2a7 7 0 0 1 14 0v2M17 4a4 4 0 0 1 0 8M18 14a6 6 0 0 1 4 5v2"/></svg>
            Utilisateurs et rôles
        </a>
    @endrole
    <div class="foot">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button class="nav logout" type="submit">Se déconnecter</button>
        </form>
    </div>
</aside>

<main>
    <div class="top">
        <div>
            <h1>@yield('heading')</h1>
            <p>@yield('subtitle')</p>
        </div>
        @hasSection('add-label')
            <a class="btn pri" href="@yield('add-url')">+ @yield('add-label')</a>
        @endif
    </div>
    @yield('content')
</main>

<div id="toast" role="status" aria-live="polite"></div>
<script src="{{ asset('assets/admin/js/admin.js') }}" defer></script>
</body>
</html>
