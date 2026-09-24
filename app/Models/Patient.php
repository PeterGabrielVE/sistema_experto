<?php

namespace App\Models;

use App\Models\Patient;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table='patients';

    protected $fillable = [
        'first_name', 'last_name','rut', 'address','comment', 'birthdate', 'image','gender','created_by'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User','created_by','id');
    }
}
