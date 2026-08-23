<?php

namespace SmartDato\GlsItaly;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GlsItalyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('gls-italy')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GlsItaly::class);
    }
}
