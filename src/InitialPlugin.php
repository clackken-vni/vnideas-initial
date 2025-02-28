<?php

namespace Vnideas\Initial;

use Biostate\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Vnideas\Initial\Filament\Resources\PluginManagerResource;

class InitialPlugin implements Plugin
{
    public function getId(): string
    {
        return 'initial';
    }

    public function register(Panel $panel): void
    {
        //
        $panel
            ->resources([
                PluginManagerResource::class
            ])->plugins([
//                FilamentMenuBuilderPlugin::make()
//                RolesManagementPlugin::make(),
//                LogsManagementPlugin::make(),
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
