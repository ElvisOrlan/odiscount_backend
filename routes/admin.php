<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\Category;
use App\Models\Commune;
use App\Models\Partenaire;
use App\Models\User;
use App\Models\Ville;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

Route::prefix('office')->name('admin.')->group(function () {
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

        Route::middleware('role:Master')->group(function () {
            Route::get('/users', static function (Request $request) {
                $filters = $request->validate([
                    'q' => ['nullable', 'string', 'max:100'],
                    'role' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
                    'status' => ['nullable', Rule::in(['0', '1'])],
                ]);
                $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
                $users = User::query()
                    ->with('roles')
                    ->when($filters['q'] ?? null, static function ($query, $search): void {
                        $search = '%'.$search.'%';
                        $query->where(static function ($query) use ($search): void {
                            $query->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search)
                                ->orWhereHas('roles', static fn ($roleQuery) => $roleQuery->where('name', 'like', $search));
                        });
                    })
                    ->when($filters['role'] ?? null, static function ($query, $roleId): void {
                        $query->whereHas('roles', static fn ($roleQuery) => $roleQuery->whereKey($roleId));
                    })
                    ->when(isset($filters['status']), static function ($query) use ($filters): void {
                        $query->where('is_active', $filters['status'] === '1');
                    })
                    ->orderBy('name')
                    ->orderBy('id')
                    ->paginate(15)
                    ->appends($request->query());

                return view('admin.users', [
                    'page' => 'users',
                    'users' => $users,
                    'filters' => $filters,
                    'roles' => $roles,
                ]);
            })->name('users');

            Route::get('/users/create', static function () {
                return view('admin.user-form', [
                    'page' => 'users',
                    'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
                ]);
            })->name('users.create');

            Route::get('/users/{user}', static function (User $user) {
                return view('admin.user-show', [
                    'page' => 'users',
                    'user' => $user->load('roles'),
                ]);
            })->name('users.show');

            Route::post('/users', static function (Request $request) {
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:8', 'confirmed'],
                    'roles' => ['required', 'array', 'min:1'],
                    'roles.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
                ]);

                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'is_active' => true,
                ]);
                $user->syncRoles($validated['roles']);

                return redirect()
                    ->route('admin.users')
                    ->with('status', 'Le compte « '.$user->name.' » a été créé.');
            })->name('users.store');

            Route::get('/users/{user}/edit', static function (User $user) {
                return view('admin.user-form', [
                    'page' => 'users',
                    'user' => $user->load('roles'),
                    'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
                ]);
            })->name('users.edit');

            Route::put('/users/{user}', static function (Request $request, User $user) {
                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                    'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                    'roles' => ['required', 'array', 'min:1'],
                    'roles.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
                ]);
                $selectedRoles = Role::query()
                    ->where('guard_name', 'web')
                    ->whereKey($validated['roles'])
                    ->get();
                $retainsMaster = $selectedRoles->contains('name', 'Master');
                $isActiveMaster = $user->is_active && $user->hasRole('Master');

                if ($user->is(Auth::user()) && $user->hasRole('Master') && ! $retainsMaster) {
                    throw ValidationException::withMessages([
                        'roles' => 'Vous ne pouvez pas retirer votre propre rôle Master.',
                    ]);
                }

                return DB::transaction(static function () use ($validated, $selectedRoles, $user, $isActiveMaster, $retainsMaster) {
                    if ($isActiveMaster && ! $retainsMaster) {
                        $activeMasterCount = User::query()
                            ->where('is_active', true)
                            ->role('Master')
                            ->lockForUpdate()
                            ->get(['users.id'])
                            ->count();

                        if ($activeMasterCount <= 1) {
                            throw ValidationException::withMessages([
                                'roles' => 'Le dernier compte Master actif ne peut pas perdre ce rôle.',
                            ]);
                        }
                    }

                    $user->name = $validated['name'];
                    $user->email = $validated['email'];
                    if (! empty($validated['password'])) {
                        $user->password = Hash::make($validated['password']);
                    }
                    $user->save();
                    $user->syncRoles($selectedRoles);

                    return redirect()
                        ->route('admin.users')
                        ->with('status', 'Le compte « '.$user->name.' » a été modifié.');
                });
            })->name('users.update');

            Route::delete('/users/{user}', static function (User $user) {
                if ($user->is(Auth::user())) {
                        throw ValidationException::withMessages([
                            'user' => 'Vous ne pouvez pas supprimer votre propre compte.',
                        ]);
                }

                return DB::transaction(static function () use ($user) {
                        if ($user->is_active && $user->hasRole('Master')) {
                            $activeMasterCount = User::query()
                                ->where('is_active', true)
                                ->role('Master')
                                ->lockForUpdate()
                                ->get(['users.id'])
                                ->count();

                            if ($activeMasterCount <= 1) {
                                throw ValidationException::withMessages([
                                    'user' => 'Le dernier compte Master actif ne peut pas être supprimé.',
                                ]);
                            }
                        }

                        $name = $user->name;
                        $user->syncRoles([]);
                        $user->syncPermissions([]);
                        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                        $user->delete();

                        return redirect()
                            ->route('admin.users')
                            ->with('status', 'Le compte « '.$name.' » a été supprimé.');
                });
            })->name('users.destroy');

            Route::patch('/users/{user}/status', static function (Request $request, User $user) {
                $validated = $request->validate([
                        'active' => ['required', 'boolean'],
                ]);

                if (! $validated['active'] && $user->is(Auth::user())) {
                    throw ValidationException::withMessages([
                        'active' => 'Vous ne pouvez pas désactiver votre propre compte.',
                    ]);
                }

                return DB::transaction(static function () use ($validated, $user) {
                    if (! $validated['active'] && $user->is_active && $user->hasRole('Master')) {
                        $activeMasterCount = User::query()
                            ->where('is_active', true)
                            ->role('Master')
                            ->lockForUpdate()
                            ->get(['users.id'])
                            ->count();

                        if ($activeMasterCount <= 1) {
                            throw ValidationException::withMessages([
                                'active' => 'Le dernier compte Master actif ne peut pas être désactivé.',
                            ]);
                        }
                    }

                    $user->is_active = $validated['active'];
                    $user->save();
                    $status = $user->is_active ? 'activé' : 'désactivé';

                    return back()->with('status', 'Le compte « '.$user->name.' » a été '.$status.'.');
                });
            })->name('users.status');

            Route::get('/roles', static function () {
                $roles = Role::query()
                    ->where('guard_name', 'web')
                    ->withCount('users')
                    ->orderBy('name')
                    ->get();

                return view('admin.roles', [
                    'page' => 'roles',
                    'roles' => $roles,
                    'systemRoles' => ['Admin', 'User', 'Master'],
                ]);
            })->name('roles');

            Route::post('/roles', static function (Request $request) {
                $request->merge(['name' => trim((string) $request->input('name'))]);
                $validated = $request->validate([
                    'name' => [
                        'required',
                        'string',
                        'max:100',
                        'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                        Rule::unique('roles', 'name')->where('guard_name', 'web'),
                    ],
                ]);

                Role::create([
                    'name' => trim($validated['name']),
                    'guard_name' => 'web',
                ]);

                return redirect()->route('admin.roles')->with('status', 'Le rôle a été créé.');
            })->name('roles.store');

            Route::put('/roles/{role}', static function (Request $request, Role $role) {
                abort_unless($role->guard_name === 'web', 404);
                if (in_array($role->name, ['Admin', 'User', 'Master'], true)) {
                    throw ValidationException::withMessages([
                        'name' => 'Les rôles système ne peuvent pas être renommés.',
                    ]);
                }

                $request->merge(['name' => trim((string) $request->input('name'))]);
                $validated = $request->validate([
                    'name' => [
                        'required',
                        'string',
                        'max:100',
                        'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                        Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id),
                    ],
                ]);

                $role->name = trim($validated['name']);
                $role->save();

                return redirect()->route('admin.roles')->with('status', 'Le rôle a été renommé.');
            })->name('roles.update');

            Route::delete('/roles/{role}', static function (Role $role) {
                abort_unless($role->guard_name === 'web', 404);
                if (in_array($role->name, ['Admin', 'User', 'Master'], true)) {
                    throw ValidationException::withMessages([
                        'role' => 'Les rôles système ne peuvent pas être supprimés.',
                    ]);
                }
                if ($role->users()->exists()) {
                    throw ValidationException::withMessages([
                        'role' => 'Ce rôle est attribué à des comptes. Retirez-le d’abord de ces comptes.',
                    ]);
                }

                $role->delete();

                return redirect()->route('admin.roles')->with('status', 'Le rôle a été supprimé.');
            })->name('roles.destroy');
        });

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
                'villes' => Ville::query()->orderBy('ville')->get(['id', 'ville']),
                'communes' => Commune::query()->with('ville:id,ville')->orderBy('commune')->get(['id', 'ville_id', 'commune']),
            ]);
        })->middleware('role:Admin|Master')->name('partners.create');

        Route::get('/partners/{partner}/edit', static function (Partenaire $partner) {
            return view('admin.partners-create', [
                'page' => 'partners',
                'partner' => $partner,
                'categories' => Category::query()->orderBy('categorie')->get(['id', 'categorie', 'status']),
                'villes' => Ville::query()->orderBy('ville')->get(['id', 'ville']),
                'communes' => Commune::query()->with('ville:id,ville')->orderBy('commune')->get(['id', 'ville_id', 'commune']),
            ]);
        })->middleware('role:Admin|Master')->name('partners.edit');

        Route::post('/partners', static function (Request $request) {
            $validated = $request->validate([
                'categorie_id' => ['required', 'integer', Rule::exists('categories', 'id')],
                'nom_partenaire' => ['required', 'string', 'max:255'],
                'taux_reduction' => ['required', 'numeric', 'min:0', 'max:100'],
                'info_reduction' => ['nullable', 'string'],
                'telephone' => ['nullable', 'string', 'max:30'],
                'image_partenaire' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'localisation' => ['nullable', 'string', 'max:255'],
                'nom_prenom_gerant' => ['nullable', 'string', 'max:255'],
                'mail_gerant' => ['nullable', 'email', 'max:255'],
                'phone_gerant' => ['nullable', 'string', 'max:30'],
                'activite_principale' => ['nullable', 'string', 'max:255'],
                'boutique_physique' => ['sometimes', 'boolean'],
            ]);

            $partner = new Partenaire;
            $partner->categorie_id = $validated['categorie_id'];
            if ($request->hasFile('image_partenaire')) {
                $image = $request->file('image_partenaire');
                $imageName = $image->hashName();
                $image->move(public_path('images/partenaires'), $imageName);
                $partner->image_partenaire = $imageName;
            }
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

        Route::put('/partners/{partner}', static function (Request $request, Partenaire $partner) {
            $validated = $request->validate([
                'categorie_id' => ['required', 'integer', Rule::exists('categories', 'id')],
                'nom_partenaire' => ['required', 'string', 'max:255'],
                'taux_reduction' => ['required', 'numeric', 'min:0', 'max:100'],
                'info_reduction' => ['nullable', 'string'],
                'telephone' => ['nullable', 'string', 'max:30'],
                'image_partenaire' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'ville_id' => ['nullable', 'integer', Rule::exists('villes', 'id')],
                'commune_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('communes', 'id')->where(
                        static fn ($query) => $query->where('ville_id', $request->input('ville_id'))
                    ),
                ],
                'localisation' => ['nullable', 'string', 'max:255'],
                'nom_prenom_gerant' => ['nullable', 'string', 'max:255'],
                'mail_gerant' => ['nullable', 'email', 'max:255'],
                'phone_gerant' => ['nullable', 'string', 'max:30'],
                'activite_principale' => ['nullable', 'string', 'max:255'],
                'boutique_physique' => ['sometimes', 'boolean'],
            ]);

            if ($request->hasFile('image_partenaire')) {
                $image = $request->file('image_partenaire');
                $imageName = $image->hashName();
                $image->move(public_path('images/partenaires'), $imageName);
                $partner->image_partenaire = $imageName;
            }

            $partner->fill([
                'categorie_id' => $validated['categorie_id'],
                'nom_partenaire' => $validated['nom_partenaire'],
                'taux_reduction' => $validated['taux_reduction'],
                'info_reduction' => $validated['info_reduction'] ?? null,
                'telephone' => $validated['telephone'] ?? null,
                'ville_id' => $validated['ville_id'] ?? null,
                'commune_id' => $validated['commune_id'] ?? null,
                'localisation' => $validated['localisation'] ?? null,
                'nom_prenom_gerant' => $validated['nom_prenom_gerant'] ?? null,
                'mail_gerant' => $validated['mail_gerant'] ?? null,
                'phone_gerant' => $validated['phone_gerant'] ?? null,
                'activite_principale' => $validated['activite_principale'] ?? null,
                'boutique_physique' => $validated['boutique_physique'] ?? false,
            ]);
            $partner->save();

            return redirect()
                ->route('admin.partners')
                ->with('status', 'Le partenaire « '.$partner->nom_partenaire.' » a été modifié.');
        })->middleware('role:Admin|Master')->name('partners.update');

        Route::patch('/partners/{partner}/status', static function (Request $request, Partenaire $partner) {
            $validated = $request->validate([
                'active' => ['required', 'boolean'],
            ]);
            $partner->is_active = $validated['active'];
            $partner->save();

            $message = $partner->is_active ? 'réactivé' : 'désactivé';

            return back()->with('status', 'Le partenaire « '.$partner->nom_partenaire.' » a été '.$message.'.');
        })->middleware('role:Admin|Master')->name('partners.status');

        Route::get('/', static function () {
            $categories = Category::query()
                ->withCount(['partenaires' => static fn ($query) => $query->where('is_active', true)])
                ->orderBy('id')
                ->get();
            $activeCategories = $categories->where('status', 'active');
            $activePartners = Partenaire::query()
                ->where('is_active', true)
                ->whereHas('categorie', static fn ($query) => $query->where('status', 'active'));

            return view('admin.dashboard', [
                'page' => 'dashboard',
                'categories' => $categories,
                'activeCategoryCount' => $activeCategories->count(),
                'visiblePartnerCount' => (clone $activePartners)->count(),
                'averageDiscount' => (int) round((float) (clone $activePartners)->avg('taux_reduction')),
                'emptyActiveCategoryCount' => $activeCategories->where('partenaires_count', 0)->count(),
            ]);
        })->middleware('role:Admin|Master')->name('dashboard');
        Route::get('/categories', static function () {
            $categories = Category::query()
                ->withCount(['partenaires' => static fn ($query) => $query->where('is_active', true)])
                ->orderBy('id')
                ->get();

            return view('admin.categories', [
                'page' => 'categories',
                'categories' => $categories,
            ]);
        })->middleware('role:Admin|Master')->name('categories');
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
        })->middleware('role:Admin|Master')->name('partners');
    });
});
