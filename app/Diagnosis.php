<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $fillable = [
        'id_patient', 'carbohydrate', 'isocaloric_carbohydrate', 'lipido','isocaloric_lipido', 'protein','isocaloric_protein', 'imc_desired','result_pulgar','insulin_index',
        'size','weight','physical_activity','age','imc'
    ];
}
