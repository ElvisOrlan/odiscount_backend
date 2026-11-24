<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Category;
use App\Models\Partenaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::name('front.')->group(function (): void {
    Route::get('/', static function () {
        $categories = Category::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        return view('front.index', compact('categories'));
    })->name('home');

    Route::get('/categories/{slug}', static function (string $slug) {
        $category = Category::query()
            ->where('status', 'active')
            ->where('slug', $slug)
            ->firstOrFail();

        $partenaires = $category->partenaires()
            ->with(['commune', 'ville'])
            ->orderBy('id')
            ->get();

        return view('front.categories.show', compact('category', 'partenaires'));
    })->name('categories.show');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', static function (Request $request) {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    })->name('login');

    Route::post('/login', static function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            ...$credentials,
            'is_active' => true,
        ])) {
            return back()->withErrors([
                'email' => 'Adresse e-mail ou mot de passe incorrect, ou compte désactivé.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    })->middleware('throttle:5,1')->name('login.submit');

    Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
        Route::post('/logout', static function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login');
        })->name('logout');

        Route::patch('/categories/{category}/status', static function (Request $request, Category $category) {
            $validated = $request->validate([
                'active' => ['required', 'boolean'],
            ]);

            $category->status = $validated['active'] ? 'active' : 'inactive';
            $category->save();

            return response()->json(['status' => $category->status]);
        })->middleware('role:Admin|Master')->name('categories.status');

        Route::get('/partners/create', static function () {
            return view('admin.partners-create', [
                'page' => 'partners',
                'categories' => Category::query()->orderBy('categorie')->get(['id', 'categorie', 'status']),
            ]);
        })->middleware('role:Admin|Master')->name('partners.create');

        Route::post('/partners', static function (Request $request) {
            $validated = $request->validate([
                'categorie_id' => ['required', 'integer', Rule::exists('categories', 'id')],
                'nom_partenaire' => ['required', 'string', 'max:255'],
                'taux_reduction' => ['required', 'numeric', 'min:0', 'max:100'],
                'info_reduction' => ['nullable', 'string'],
                'telephone' => ['nullable', 'string', 'max:30'],
                'localisation' => ['nullable', 'string', 'max:255'],
                'nom_prenom_gerant' => ['nullable', 'string', 'max:255'],
                'mail_gerant' => ['nullable', 'email', 'max:255'],
                'phone_gerant' => ['nullable', 'string', 'max:30'],
                'activite_principale' => ['nullable', 'string', 'max:255'],
                'boutique_physique' => ['sometimes', 'boolean'],
            ]);

            $partner = new Partenaire;
            $partner->categorie_id = $validated['categorie_id'];
            $partner->nom_partenaire = $validated['nom_partenaire'];
            $partner->taux_reduction = $validated['taux_reduction'];
            $partner->info_reduction = $validated['info_reduction'] ?? null;
            $partner->telephone = $validated['telephone'] ?? null;
            $partner->localisation = $validated['localisation'] ?? null;
            $partner->nom_prenom_gerant = $validated['nom_prenom_gerant'] ?? null;
            $partner->mail_gerant = $validated['mail_gerant'] ?? null;
            $partner->phone_gerant = $validated['phone_gerant'] ?? null;
            $partner->activite_principale = $validated['activite_principale'] ?? null;
            $partner->boutique_physique = $validated['boutique_physique'] ?? false;
            $partner->save();

            return redirect()
                ->route('admin.partners')
                ->with('status', 'Le partenaire « '.$partner->nom_partenaire.' » a été créé.');
        })->middleware('role:Admin|Master')->name('partners.store');

        Route::get('/', static function () {
            $categories = Category::query()
                ->withCount('partenaires')
                ->orderBy('id')
                ->get();
            $activeCategories = $categories->where('status', 'active');
            $activePartners = Partenaire::query()
                ->whereHas('categorie', static fn ($query) => $query->where('status', 'active'));

            return view('admin.dashboard', [
                'page' => 'dashboard',
                'categories' => $categories,
                'activeCategoryCount' => $activeCategories->count(),
                'visiblePartnerCount' => (clone $activePartners)->count(),
                'averageDiscount' => (int) round((float) (clone $activePartners)->avg('taux_reduction')),
                'emptyActiveCategoryCount' => $activeCategories->where('partenaires_count', 0)->count(),
            ]);
        })->name('dashboard');
        Route::get('/categories', static function () {
            $categories = Category::query()
                ->withCount('partenaires')
                ->orderBy('id')
                ->get();

            return view('admin.categories', [
                'page' => 'categories',
                'categories' => $categories,
            ]);
        })->name('categories');
        Route::get('/partners', static function (Request $request) {
            $filters = $request->validate([
                'q' => ['nullable', 'string', 'max:100'],
                'category' => ['nullable', 'integer', 'min:1'],
            ]);
            $categories = Category::query()
                ->orderBy('id')
                ->get(['id', 'categorie', 'status']);

            $partnerQuery = Partenaire::query()
                ->with([
                    'categorie:id,categorie,status',
                    'ville:id,ville',
                    'commune:id,commune',
                ])
                ->when(
                    $filters['category'] ?? null,
                    static fn ($query, $categoryId) => $query->where('categorie_id', $categoryId)
                )
                ->when($filters['q'] ?? null, static function ($query, $search) {
                    $search = '%'.$search.'%';

                    $query->where(static function ($query) use ($search) {
                        $query->where('nom_partenaire', 'like', $search)
                            ->orWhere('activite_principale', 'like', $search)
                            ->orWhere('info_reduction', 'like', $search)
                            ->orWhere('localisation', 'like', $search)
                            ->orWhere('nom_prenom_gerant', 'like', $search)
                            ->orWhere('telephone', 'like', $search)
                            ->orWhere('phone_gerant', 'like', $search)
                            ->orWhereHas('categorie', static fn ($categoryQuery) => $categoryQuery->where('categorie', 'like', $search))
                            ->orWhereHas('ville', static fn ($cityQuery) => $cityQuery->where('ville', 'like', $search))
                            ->orWhereHas('commune', static fn ($communeQuery) => $communeQuery->where('commune', 'like', $search));
                    });
                });

            $partners = $partnerQuery
                ->orderBy('nom_partenaire')
                ->orderBy('id')
                ->paginate(15)
                ->appends($request->query());

            if ($partners->total() > 0 && $partners->currentPage() > $partners->lastPage()) {
                return redirect()->to($partners->url($partners->lastPage()));
            }

            return view('admin.partners', [
                'page' => 'partners',
                'categories' => $categories,
                'partners' => $partners,
                'filters' => $filters,
            ]);
        })->name('partners');
    });
});
