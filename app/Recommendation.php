<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $table='recommendations';

    protected $fillable = ['id_rule','description'];
}
