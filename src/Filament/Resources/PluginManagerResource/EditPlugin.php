<?php

namespace Vnideas\Initial\Filament\Resources\PluginManagerResource;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Vnideas\Initial\Filament\Resources\PluginManagerResource;

class EditPlugin extends EditRecord
{
    protected static string $resource = PluginManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
