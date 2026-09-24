<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    protected $table='rules';

    protected $fillable = ['name','min','max'];
}
