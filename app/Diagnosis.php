<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $fillable = [
        'id_patient', 'carbohydrate', 'isocaloric', 'lipido', 'protein', 'imc_desired', 'result_harris', 'result_pulgar','insulin_index',
        'size','weight','physical_activity','schedule_meal','workday','schedule_activity','age','imc'
    ];
}
