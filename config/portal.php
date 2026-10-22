<?php

// Patient portal (US-6.3). Access rules, not clinical values.
return [

    // Terms of use and of personal data processing (Ley 19.628) the patient accepts (D-5). The clinic
    // writes the text with legal advice; it is not in the repository. Without it no one is invited.
    'terms_path' => env('PATIENT_TERMS_PATH', storage_path('app/legal/terminos-portal-pacientes.md')),

    // Days the invitation link is valid (D-2).
    'invitation_days' => 7,

];
