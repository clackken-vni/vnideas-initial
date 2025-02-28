<?php

namespace Vnideas\Initial\Filament\Resources\PluginManagerResource;

use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\TextInput;
use Vnideas\Initial\Filament\Resources\PluginManagerResource;
use Livewire\Attributes\On;

class ListPlugin extends ListRecords
{
    protected static string $resource = PluginManagerResource::class;

    protected array $packageInfo = []; // Lưu trữ dữ liệu package tạm thời

    #[On('open-install-modal')]
    public function openInstallModal(array $packageInfo): void
    {
        $this->mountAction('install_package', ['package_info' => $packageInfo]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('install_package')
                ->label('Install Package')
                ->form([
                    TextInput::make('name')
                        ->default(fn ($arguments) => $arguments['package_info']['name'] ?? '')
                        ->readOnly()
                        ->required(),
                    TextInput::make('description')
                        ->default(fn ($arguments) => $arguments['package_info']['description'] ?? '')
                        ->readOnly()
                        ->required(),
                    TextInput::make('version')
                        ->default(fn ($arguments) => $arguments['package_info']['version'] ?? '')
                        ->readOnly()
                        ->required(),
                ])
                ->mountUsing(function (Form $form, array $arguments) {
                    $form->fill([
                        'name' => $arguments['package_info']['name'] ?? '',
                        'description' => $arguments['package_info']['description'] ?? '',
                        'version' => $arguments['package_info']['version'] ?? '',
                    ]);
                })
                ->modal()
                ->modalSubmitActionLabel('Install')
                ->action(function (array $data) {
                    $packageInfo = [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'version' => $data['version'],
                    ];
                    PluginManagerResource::installPackage($packageInfo['name'], $packageInfo['version']);
                    PluginManagerResource::addPackageRecord($packageInfo);
                })
                ->modalWidth('lg')->extraAttributes(['style' => 'display:none'])
        ];
    }
}