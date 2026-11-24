<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partenaire extends Model
{
    protected $table = 'partenaires';

    protected $fillable = [
        'categorie_id',
        'image_partenaire',
        'is_active',
        'nom_partenaire',
        'taux_reduction',
        'info_reduction',
        'telephone',
        'ville_id',
        'commune_id',
        'localisation',
        'nom_prenom_gerant',
        'mail_gerant',
        'phone_gerant',
        'activite_principale',
        'boutique_physique',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'boutique_physique' => 'boolean',
        ];
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categorie_id');
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_partenaire
            ? asset('images/partenaires/'.$this->image_partenaire)
            : null;
    }
}
