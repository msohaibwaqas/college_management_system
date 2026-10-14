<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | Set some default values. It is possible to add all defines that can be set
    | in dompdf_config.inc.php. You can also override the entire config file.
    |
    */
    'show_warnings' => false,   // Throw an Exception on warnings from dompdf

    /*
    |--------------------------------------------------------------------------
    | Public Path Resolution
    |--------------------------------------------------------------------------
    | Ensures public path resolves cleanly on shared hosts (e.g. public_html)
    | or custom production directory structures to prevent 'Cannot resolve public path'.
    */
    'public_path' => (file_exists(public_path()) && realpath(public_path())) 
        ? public_path() 
        : ((file_exists(base_path('public_html')) && realpath(base_path('public_html'))) 
            ? base_path('public_html') 
            : base_path()),

    /*
     * Dejavu Sans font is missing glyphs for converted entities, turn it off if you need to show € and £.
     */
    'convert_entities' => true,

    'options' => [
        /**
         * The location of the DOMPDF font directory
         */
        'font_dir' => storage_path('fonts'),

        /**
         * The location of the DOMPDF font cache directory
         */
        'font_cache' => storage_path('fonts'),

        /**
         * The location of a temporary directory.
         */
        'temp_dir' => sys_get_temp_dir(),

        /**
         * dompdf's "chroot"
         */
        'chroot' => realpath(base_path()) ?: base_path(),

        /**
         * Protocol whitelist
         */
        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,

        'log_output_file' => null,

        'enable_font_subsetting' => false,

        'pdf_backend' => 'CPDF',

        'default_media_type' => 'screen',

        'default_paper_size' => 'a4',

        'default_paper_orientation' => 'portrait',

        'default_font' => 'serif',

        'dpi' => 96,

        'enable_php' => false,

        'enable_javascript' => true,

        'enable_remote' => true,

        'allowed_remote_hosts' => null,

        'font_height_ratio' => 1.1,

        'enable_html5_parser' => true,
    ],

];
