<?php

// Identity of the clinic on the documents handed to the patient (US-5.2, D-5). Contact data
// left empty is not shown.
return [

    'name' => env('CLINIC_NAME', 'El Sauce'),
    'address' => env('CLINIC_ADDRESS'),
    'phone' => env('CLINIC_PHONE'),
    'email' => env('CLINIC_EMAIL'),

    // PNG or JPEG embedded in the PDF; without it, the name takes its place.
    'logo_path' => env('CLINIC_LOGO_PATH', public_path('assets/img/sauce-logo.png')),

];
