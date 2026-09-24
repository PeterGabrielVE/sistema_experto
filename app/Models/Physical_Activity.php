<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Physical_Activity extends Model
{
    protected $table='physical_activity';

    protected $fillable = [
        'name'
    ];
}
