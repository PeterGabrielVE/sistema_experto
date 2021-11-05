<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $table='precommendations';

    protected $fillable = ['description'];
}
