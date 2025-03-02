<?php

namespace Vnideas\Initial\Filament\Resources\PluginManagerResource;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Vnideas\Initial\Filament\Resources\PluginManagerResource;

class EditPlugin extends EditRecord
{
    protected static string $resource = PluginManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn($record) => !in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management'])),
        ];
    }
}
