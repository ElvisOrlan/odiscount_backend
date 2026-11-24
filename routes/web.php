<?php

use App\Models\Category;
use Illuminate\Support\Facades\Route;

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
            ->where('is_active', true)
            ->with(['commune', 'ville'])
            ->orderBy('id')
            ->get();

        return view('front.categories.show', compact('category', 'partenaires'));
    })->name('categories.show');
});
