<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Food_Group extends Model
{

    protected $table='food_group';

    protected $fillable = [
        'name'
    ];
}
