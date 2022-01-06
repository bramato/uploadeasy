<?php

namespace Bramato\Uploadeasy;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Bramato\Uploadeasy\Commands\UploadeasyCommand;

class UploadeasyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('uploadeasy')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_uploadeasy_table')
            ->hasCommand(UploadeasyCommand::class);
    }
}
