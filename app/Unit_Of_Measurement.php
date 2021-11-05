<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Unit_Of_Measurement extends Model
{
    protected $table='unit_of_measurement';

    protected $fillable = [
        'name'
    ];
}
