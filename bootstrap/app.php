<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        
        // Register System Health Monitoring (inside web stack for session support)
        $middleware->web(append: [
            \Sohaib\CmsCore\SystemHealthMiddleware::class,
            \App\Http\Middleware\EnforceMaxSessionDuration::class,
            \App\Http\Middleware\EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            $maxPost = ini_get('post_max_size');
            $maxUpload = ini_get('upload_max_filesize');
            return back()->with('error', "The uploaded backup file exceeds your server's maximum POST limit (upload_max_filesize: {$maxUpload}, post_max_size: {$maxPost}). Please compress the SQL file to .gz or increase post_max_size in php.ini.");
        });
    })->create();
