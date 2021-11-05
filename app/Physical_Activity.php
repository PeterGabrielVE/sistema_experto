<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Physical_Activity extends Model
{
    protected $table='physical_activity';

    protected $fillable = [
        'name'
    ];
}
