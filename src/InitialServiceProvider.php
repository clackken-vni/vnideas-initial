<?php

namespace Vnideas\Initial;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vnideas\Initial\Commands\InitialCommand;
use Vnideas\Initial\Testing\TestsInitial;
use Illuminate\Support\Facades\Artisan;

class InitialServiceProvider extends PackageServiceProvider
{
    public static string $name = 'initial';
    public static string $viewNamespace = 'initial';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(function (InstallCommand $command) {
                        if ($command->confirm('Do you want to run the migrations and seeders now?', true)) {
                            Artisan::call('migrate', ['--path' => __DIR__ . '/../database/migrations']);
                            Artisan::call('db:seed', ['--class' => 'Vnideas\\Initial\\Database\\Seeders\\PackageSeeder']);
                            $command->info('Migrations and seeders executed successfully.');
                        }
                    })
                    ->askToStarRepoOnGitHub('vnideas/initial');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->name('initial')->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }

        if (file_exists($package->basePath('/../src/Helpers/helpers.php'))) {
            $this->registerHelpers();
        }
    }

    public function packageRegistered(): void
    {
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        FilamentIcon::register($this->getIcons());

        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/initial/{$file->getFilename()}"),
                ], 'initial-stubs');
            }

            $this->publishes([
                __DIR__ . '/../database/seeders/PackageSeeder.php' => database_path('seeders/PackageSeeder.php'),
            ], 'initial-seeders');
        }

        Testable::mixin(new TestsInitial);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'vnideas/initial';
    }

    protected function getAssets(): array
    {
        return [];
    }

    protected function getCommands(): array
    {
        return [
            InitialCommand::class,
        ];
    }

    protected function getIcons(): array
    {
        return [];
    }

    protected function getRoutes(): array
    {
        return [];
    }

    protected function getScriptData(): array
    {
        return [];
    }

    protected function getMigrations(): array
    {
        return [
            'create_vni_packages_table',
        ];
    }

    public function registerHelpers(): void
    {
        $helperFile = __DIR__ . '/../src/Helpers/helpers.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
    }
}