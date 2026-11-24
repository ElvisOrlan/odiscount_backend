<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    public function partenaires(): HasMany
    {
        return $this->hasMany(Partenaire::class, 'categorie_id');
    }
}
