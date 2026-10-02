<?php

// Clinical cut-off points, shared with the Python services: edit shared/clinical_thresholds.json.
return json_decode(
    file_get_contents(base_path('shared/clinical_thresholds.json')),
    true,
    flags: JSON_THROW_ON_ERROR
);
