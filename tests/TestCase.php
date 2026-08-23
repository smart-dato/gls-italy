<?php

namespace SmartDato\GlsItaly\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SmartDato\GlsItaly\GlsItalyServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            GlsItalyServiceProvider::class,
            LaravelDataServiceProvider::class,
        ];
    }
}
