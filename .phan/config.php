<?php

use Phan\Config;

return [
    "allow_missing_properties" => false,
    "null_casts_as_any_type" => false,
    'backward_compatibility_checks' => false,
    "quick_mode" => false,
    "minimum_severity" => 6,
    'directory_list' => [
        '.',
    ],

    "exclude_analysis_directory_list" => [
         'vendor',
         'Skel/plugin',
         'Skel/project',
         'Angular/templates',
         
    ],

    'analyzed_file_extensions' => ['php'],
];
