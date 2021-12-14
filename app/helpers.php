<?php

function regla_tres($iso, $cho, $gr){
    $valor = 0;
    $gr_total = 0;
    $valor = $iso*80/100;

    $gr_total = $valor*100/$gr;
    return $gr_total;
}