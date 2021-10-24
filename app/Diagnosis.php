<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $fillable = [
        'id_patient', 'carbohydrate', 'isocaloric', 'lipid', 'protein', 'imc_desired', 'result_harray', 'result_pulgar','insulin_index',
        'size','weight','physical_activity','schedule_meal','workday','schedule_activity','age','caloric_ingestion',
        'imc'
    ];
}
