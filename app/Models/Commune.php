<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commune extends Model
{
    protected $table = 'communes';

    protected $fillable = ['ville_id', 'commune'];

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class);
    }
}
