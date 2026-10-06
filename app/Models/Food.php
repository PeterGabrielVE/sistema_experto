<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table='foods';

    protected $fillable = [
        'id_group', 'name', 'item', 'portion', 'gr', 'kcal', 'protein', 'lipid', 'saturated_fat', 'cho', 'glycemic_index', 'clna_mg', 'k_mg', 'p_mg', 'ca_mg'
    ];
}
