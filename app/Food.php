<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Foods extends Model
{
    protected $table='foods';

    protected $fillable = [
        'id_group', 'item', 'portion', 'kcal', 'protein', 'lipid', 'cho', 'clna_mg', 'k_mg', 'p_mg', 'ca_mg'
    ];
}
