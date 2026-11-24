<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    protected $connection = 'odiscount';

    protected $table = 'villes';
}
