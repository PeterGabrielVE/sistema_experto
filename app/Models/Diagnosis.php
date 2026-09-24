<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    protected $fillable = [
        'id_patient', 'carbohydrate', 'isocaloric_carbohydrate', 'lipido','isocaloric_lipido', 'protein','isocaloric_protein', 'imc_desired','result_pulgar','insulin_index',
        'size','weight','physical_activity','age','imc','created_by',
        'id_rule','inference_source','inference_confidence','model_version'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User','created_by','id');
    }
}
