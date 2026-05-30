<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Force testing environment BEFORE the app boots so the .env
        // production value cannot trigger migrate:fresh confirmation prompts.
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';

        $app = require dirname(__DIR__, 1) . '/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
