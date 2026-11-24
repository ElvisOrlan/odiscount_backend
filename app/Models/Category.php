<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'categorie',
        'slug',
        'image_categorie',
        'status',
    ];

    public function partenaires(): HasMany
    {
        return $this->hasMany(Partenaire::class, 'categorie_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_categorie
            ? asset('images/categories/'.$this->image_categorie)
            : null;
    }
}
