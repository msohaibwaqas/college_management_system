<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;

class ModelResolver
{
    /**
     * Get fully qualified class names of all models in App\Models.
     */
    public static function all(): array
    {
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        $classes = [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $fqcn = "App\\Models\\$name";

            if (class_exists($fqcn)) {
                $classes[] = $fqcn;
            }
        }

        return $classes;
    }


    /**
     * Instantiate all models.
     */
    public static function instances(): array
    {
        return array_map(fn($class) => new $class, self::all());
    }
}
