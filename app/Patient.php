<?php

namespace App;

use App\Patient;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table='patients';

    protected $fillable = [
        'first_name', 'last_name','rut', 'address','comment', 'birthdate', 'image','gender'
    ];
}
