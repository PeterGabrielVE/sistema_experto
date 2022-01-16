<?php

function regla_tres($iso, $cho, $gr){
    $valor = 0;
    $gr_total = 0;
    $valor = $iso*80/100;

    $gr_total = $valor*$gr/$cho;
    return $gr_total;
}

function regla_tres_acompañante($iso, $cho, $gr){
    $valor = 0;
    $gr_total = 0;
    $valor = $iso*20/100;

    $gr_total = $valor*100/$gr;
    return $gr_total;
}


function regla_tres_prot($id,$iso, $pro, $gr){

    $gr_total = 0;
    if($id == 11){
        $gr_total = $iso/$pro;
    }else{
        $gr_total = $gr*$iso/$pro;
    }
    return $gr_total;
}

function regla_tres_lip($iso, $lip, $gr){
    $gr_total = 0;
    $gr_total = $gr*$lip/$iso;
    return $gr_total;
}

function regla_tres_lip_80($iso, $lip, $gr){
    $iso = $iso * 0.80;
    $gr_total = 0;
    $gr_total = $gr*$iso/$lip;
    return $gr_total;
}

function regla_tres_lip_20($iso, $lip, $gr){
    $iso = $iso * 0.20;
    $gr_total = 0;
    $gr_total = $gr*$iso/$lip;
    return $gr_total;
}