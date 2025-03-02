<?php

namespace Vnideas\Initial\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Vnideas\Initial\Filament\Resources\PluginManagerResource\CreatePlugin;
use Vnideas\Initial\Filament\Resources\PluginManagerResource\EditPlugin;
use Vnideas\Initial\Filament\Resources\PluginManagerResource\ListPlugin;
use Vnideas\Initial\Models\VniPackages;
use Vnideas\Initial\Traits\initAuthTrait;

class PluginManagerResource extends Resource
{
    use initAuthTrait;

    protected static ?string $model = VniPackages::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                         ->label(__('initial::i.general.name'))
                         ->required()
                         ->maxLength(255)
                         ->disabled(function ($record) {
                             return $record && in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management']);
                         }),
                TextInput::make('description')
                         ->label(__('initial::i.general.description'))
                         ->maxLength(255)
                         ->nullable()
                         ->disabled(function ($record) {
                             return $record && in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management']);
                         }),
                TextInput::make('install_name')
                         ->label(__('initial::i.plugin.install_name'))
                         ->required()
                         ->maxLength(255)
                         ->disabled(function ($record) {
                             return $record && in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management']);
                         }),
                TextInput::make('version')
                         ->label(__('initial::i.plugin.version'))
                         ->required()
                         ->maxLength(255)
                         ->disabled(function ($record) {
                             return $record && in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management']);
                         }),
                Toggle::make('activated')
                      ->label(__('initial::i.plugin.activated'))
                      ->default(false)
                      ->disabled(fn($record) => $record && !$record->installed),
                Toggle::make('installed')
                      ->label(__('initial::i.plugin.installed'))
                      ->default(false)
                      ->disabled(),
                KeyValue::make('install_command')
                        ->label('Install Command') // Không có key trong file, giữ nguyên hoặc thêm sau
                        ->keyLabel('Parameter')
                        ->valueLabel('Value')
                        ->nullable()
                        ->helperText('Optional: Add custom Composer install command parameters (e.g., "--no-dev").'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                          ->label(__('initial::i.general.id')),
                TextColumn::make('name')
                          ->label(__('initial::i.general.name')),
                TextColumn::make('install_name')
                          ->label(__('initial::i.plugin.install_name')),
                TextColumn::make('version')
                          ->label(__('initial::i.plugin.version')),
                TextColumn::make('activated')
                          ->label(__('initial::i.plugin.activated'))
                          ->formatStateUsing(fn($state) => $state ? __('initial::i.general.yes') : __('initial::i.general.no')),
                TextColumn::make('installed')
                          ->label(__('initial::i.plugin.installed'))
                          ->formatStateUsing(fn($state) => $state ? __('initial::i.general.yes') : __('initial::i.general.no')),
            ])
            ->headerActions([
                Action::make('add_package')
                      ->label(__('initial::i.plugin.add_package'))
                      ->icon('heroicon-o-plus')
                      ->form([
                          TextInput::make('package_name')
                                   ->label(__('initial::i.plugin.package_name'))
                                   ->required()
                                   ->placeholder('e.g., spatie/laravel-permission')
                                   ->rules(['string', 'max:255', 'regex:/^[a-zA-Z0-9\/-]+$/']),
                      ])
                      ->modalSubmitActionLabel(__('initial::i.plugin.search_button'))
                      ->modalWidth('sm')
                      ->action(function (array $data, $livewire) {
                          $packageName = $data['package_name'];
                          try {
                              $response = Http::timeout(10)->get("https://repo.packagist.org/p2/{$packageName}.json");
                              if ($response->successful()) {
                                  $packageData = $response->json()['packages'][$packageName][0] ?? null;
                                  if ($packageData) {
                                      $info = [
                                          'name' => $packageData['name'],
                                          'description' => $packageData['description'] ?? 'No description',
                                          'version' => $packageData['version'] ?? 'Latest',
                                      ];
                                      $livewire->dispatch('open-install-modal', packageInfo: $info);
                                  } else {
                                      Notification::make()
                                                  ->title(__('initial::i.notifications.package_not_found'))
                                                  ->danger()
                                                  ->send();
                                  }
                              } else {
                                  Log::warning('Failed to fetch package: ' . $packageName);
                                  Notification::make()
                                              ->title(__('initial::i.notifications.fetch_error'))
                                              ->danger()
                                              ->send();
                              }
                          } catch (\Exception $e) {
                              Log::error('Error fetching package: ' . $e->getMessage());
                              Notification::make()
                                          ->title(__('initial::i.notifications.generic_error'))
                                          ->danger()
                                          ->send();
                          }
                      }),
            ])
            ->actions([
                DeleteAction::make()
                            ->label(__('initial::i.plugin.remove_package'))
                            ->before(function (Model $record) {
                                if (!self::canDelete($record)) {
                                    Notification::make()
                                                ->title(__('initial::i.notifications.permission_denied'))
                                                ->danger()
                                                ->send();
                                    return;
                                }
                                self::removePackage($record->name);
                            })
                            ->visible(fn(Model $record) => $record->installed && !in_array($record->install_name, ['vnideas/roles-management', 'vnideas/logs-management'])),
                Action::make('install_package')
                      ->label(__('initial::i.plugin.install_package'))
                      ->icon('heroicon-o-sparkles')
                      ->visible(fn(Model $record) => !$record->installed)
                      ->action(function (Model $record) {
                          if (!self::canCreate()) {
                              Notification::make()
                                          ->title(__('initial::i.notifications.permission_denied'))
                                          ->danger()
                                          ->send();
                              return;
                          }
                          self::installPackage($record->install_name, $record->version);
                      }),
                Action::make('activate_package')
                      ->label(__('initial::i.plugin.enable_package'))
                      ->icon('heroicon-o-check-badge')
                      ->visible(fn(Model $record) => $record->installed && !$record->activated)
                      ->action(function (Model $record) {
                          if (!self::canEdit($record)) {
                              Notification::make()
                                          ->title(__('initial::i.notifications.permission_denied'))
                                          ->danger()
                                          ->send();
                              return;
                          }
                          $record->activated = true;
                          $record->save();
                          Notification::make()
                                      ->title(__('initial::i.general.package_activated', ['package' => $record->name]))
                                      ->success()
                                      ->send();
                      }),
                Action::make('deactivate')
                      ->label(__('initial::i.plugin.disable_package'))
                      ->icon('heroicon-o-trash')
                      ->visible(fn(Model $record) => $record->activated)
                      ->action(function (Model $record) {
                          if (!self::canEdit($record)) {
                              Notification::make()
                                          ->title(__('initial::i.notifications.permission_denied'))
                                          ->danger()
                                          ->send();
                              return;
                          }
                          $record->activated = false;
                          $record->save();
                          Notification::make()
                                      ->title(__('initial::i.general.package_deactivated', ['package' => $record->name]))
                                      ->success()
                                      ->send();
                      }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlugin::route('/'),
            'create' => CreatePlugin::route('/create'),
            'edit' => EditPlugin::route('/{record}/edit'),
        ];
    }

    public static function installPackage(string $packageName, string $version): bool
    {
        try {
            if (!preg_match('/^[a-zA-Z0-9\/-]+$/', $packageName) || !preg_match('/^[a-zA-Z0-9\.\-]+$/', $version)) {
                Notification::make()
                            ->title(__('initial::i.notifications.invalid_input'))
                            ->danger()
                            ->send();
                return false;
            }

            exec('which composer', $composerOutput);
            $composerPath = $composerOutput[0] ?? '/usr/bin/composer';
            $composerHome = '/tmp/composer';

            if (!file_exists($composerHome)) {
                if (!mkdir($composerHome, 0755, true) && !is_dir($composerHome)) {
                    Log::error('Failed to create COMPOSER_HOME directory: ' . $composerHome);
                    Notification::make()
                                ->title(__('initial::i.notifications.install_failed'))
                                ->danger()
                                ->send();
                    return false;
                }
            }

            $command = $version === '*'
                ? "COMPOSER_HOME={$composerHome} {$composerPath} require " . escapeshellarg($packageName) . " --no-interaction 2>&1"
                : "COMPOSER_HOME={$composerHome} {$composerPath} require " . escapeshellarg("{$packageName}:{$version}") . " --no-interaction 2>&1";

            $projectDir = base_path();
            exec("cd {$projectDir} && {$command}", $output, $returnVar);

            if ($returnVar === 0) {
                Notification::make()
                            ->title(__('initial::i.notifications.install_success', ['package' => $packageName, 'version' => $version]))
                            ->success()
                            ->send();
                return true;
            } else {
                Log::error('Composer install failed: ' . implode("\n", $output));
                Notification::make()
                            ->title(__('initial::i.notifications.install_failed'))
                            ->danger()
                            ->send();
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error installing package: ' . $e->getMessage());
            Notification::make()
                        ->title(__('initial::i.notifications.generic_error'))
                        ->danger()
                        ->send();
            return false;
        }
    }

    public static function removePackage(string $packageName): bool
    {
        try {
            if (!preg_match('/^[a-zA-Z0-9\/-]+$/', $packageName)) {
                Notification::make()
                            ->title(__('initial::i.notifications.invalid_input'))
                            ->danger()
                            ->send();
                return false;
            }

            exec('which composer', $composerOutput);
            $composerPath = $composerOutput[0] ?? '/usr/bin/composer';
            $composerHome = '/tmp/composer';

            if (!file_exists($composerHome)) {
                if (!mkdir($composerHome, 0755, true) && !is_dir($composerHome)) {
                    Log::error('Failed to create COMPOSER_HOME directory: ' . $composerHome);
                    Notification::make()
                                ->title(__('initial::i.notifications.remove_failed'))
                                ->danger()
                                ->send();
                    return false;
                }
            }

            $command = "COMPOSER_HOME={$composerHome} {$composerPath} remove " . escapeshellarg($packageName) . " --no-interaction 2>&1";
            $projectDir = base_path();
            exec("cd {$projectDir} && {$command}", $output, $returnVar);

            if ($returnVar === 0) {
                Notification::make()
                            ->title(__('initial::i.notifications.remove_success', ['package' => $packageName]))
                            ->success()
                            ->send();
                return true;
            } else {
                Log::error('Composer remove failed: ' . implode("\n", $output));
                Notification::make()
                            ->title(__('initial::i.notifications.remove_failed'))
                            ->danger()
                            ->send();
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error removing package: ' . $e->getMessage());
            Notification::make()
                        ->title(__('initial::i.notifications.generic_error'))
                        ->danger()
                        ->send();
            return false;
        }
    }

    public static function addPackageRecord(array $info): void
    {
        try {
            VniPackages::create([
                'name' => $info['name'],
                'description' => $info['description'],
                'install_name' => $info['name'],
                'version' => $info['version'],
                'activated' => false,
                'installed' => true,
            ]);
            Notification::make()
                        ->title(__('initial::i.notifications.record_added'))
                        ->success()
                        ->send();
        } catch (\Exception $e) {
            Log::error('Error adding package record: ' . $e->getMessage());
            Notification::make()
                        ->title(__('initial::i.notifications.record_failed'))
                        ->danger()
                        ->send();
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('initial::i.plugin.plugin_label');
    }

    public static function getModelLabel(): string
    {
        return __('initial::i.plugin.plugin_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('initial::i.plugin.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('initial::i.general.setting_text');
    }

    public static function canCreate(): bool
    {
        return self::checkCreate(static::getModel());
    }

    public static function canDelete(Model $record): bool
    {
        return self::checkDelete(static::getModel());
    }

    public static function canEdit(Model $record): bool
    {
        return self::checkEdit(static::getModel());
    }

    public static function canView(Model $record): bool
    {
        return self::checkView(static::getModel());
    }

    public static function canViewAny(): bool
    {
        return self::checkViewAny(static::getModel());
    }
}