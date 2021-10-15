<?php

namespace App;

use App\Patient;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'address', 'city', 'country', 'comment', 'birthdate', 'image','gender'
    ];
}
