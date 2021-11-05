<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Time_Of_Day extends Model
{
    protected $table='time_of_day';

    protected $fillable = [
        'name'
    ];
}
