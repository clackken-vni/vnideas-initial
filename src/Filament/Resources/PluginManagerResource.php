<?php

namespace Vnideas\Initial\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
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
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('initial::i.general.id')),
                TextColumn::make('name')->label(__('initial::i.general.name')),
                TextColumn::make('description')->label(__('initial::i.general.description')),
                TextColumn::make('install_name')->label(__('initial::i.plugin.install_name')),
                TextColumn::make('version')->label(__('initial::i.plugin.version')),
                TextColumn::make('activated')->label(__('initial::i.plugin.activated'))
                    ->formatStateUsing(fn($state) => $state ? __('initial::i.general.yes') : __('initial::i.general.no')),
                TextColumn::make('installed')->label(__('initial::i.plugin.installed'))
                    ->formatStateUsing(fn($state) => $state ? __('initial::i.general.yes') : __('initial::i.general.no')),
            ])
            ->headerActions([
                Action::make('add_package')
                    ->label('Add Package')
                    ->icon('heroicon-o-plus')
                    ->form([
                        TextInput::make('package_name')
                            ->label('Package Name')
                            ->required()
                            ->placeholder('e.g., spatie/laravel-permission'),
                    ])
                    ->modalSubmitActionLabel('Search')
                    ->modalWidth('sm')
                    ->action(function (array $data, $livewire) {
                        $packageName = $data['package_name'];
                        $response = Http::get("https://repo.packagist.org/p2/{$packageName}.json");

                        if ($response->successful()) {
                            $packageData = $response->json()['packages'][$packageName][0] ?? null;
                            if ($packageData) {
                                $info = [
                                    'name' => $packageData['name'],
                                    'description' => $packageData['description'] ?? 'No description',
                                    'version' => $packageData['version'] ?? 'Latest',
                                ];

                                // Dispatch sự kiện để ListPlugin mở modal
                                $livewire->dispatch('open-install-modal', packageInfo: $info);
                            } else {
                                Notification::make()->title('Package not found')->danger()->send();
                            }
                        } else {
                            Notification::make()->title('Error fetching package')->danger()->send();
                        }
                    }),
            ])->actions([
                DeleteAction::make()->label('Remove Package')
                    ->before(function (Model $record) {
                        // Gỡ package trước khi xóa record
                        self::removePackage($record->name);
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

    /**
     * @throws \JsonException
     */
    public static function installPackage(string $packageName, string $version): bool
    {
        exec('which composer', $composerOutput);
        $composerPath = $composerOutput[0] ?? '/usr/bin/composer'; // Lấy đường dẫn hoặc mặc định nếu không tìm thấy

        $composerHome = '/tmp/composer';
        if (!file_exists($composerHome)) {
            if (!mkdir($composerHome, 0755, true) && !is_dir($composerHome)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $composerHome));
            }
        }

        $command = "COMPOSER_HOME={$composerHome} {$composerPath} require {$packageName}:{$version} --no-interaction 2>&1";
        $projectDir = base_path();
        exec("cd {$projectDir} && {$command}", $output, $returnVar);

        if ($returnVar === 0) {
            Notification::make()->title("Installed $packageName version $version successfully")->success()->send();
            return true;
        } else {
            Notification::make()->title("Error installing $packageName")->danger()->body(implode("\n", $output))->send();
            return false;
        }
    }

    public static function removePackage(string $packageName): bool
    {
        exec('which composer', $composerOutput);
        $composerPath = $composerOutput[0] ?? '/usr/bin/composer';
        $composerHome = '/tmp/composer';
        if (!file_exists($composerHome)) {
            if (!mkdir($composerHome, 0755, true) && !is_dir($composerHome)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $composerHome));
            }
        }

        $command = "COMPOSER_HOME={$composerHome} {$composerPath} remove {$packageName} --no-interaction 2>&1";
        $projectDir = base_path();
        exec("cd {$projectDir} && {$command}", $output, $returnVar);

        if ($returnVar === 0) {
            Notification::make()->title("Removed $packageName successfully")->success()->send();
            return true;
        }

        Notification::make()->title("Error removing $packageName")->danger()->body(implode("\n", $output))->send();
        return false;
    }

    public static function addPackageRecord(array $info): void
    {
        VniPackages::create([
            'name' => $info['name'],
            'description' => $info['description'],
            'install_name' => $info['name'],
            'version' => $info['version'],
            'activated' => false,
            'installed' => true,
        ]);

        Notification::make()->title('Package record added')->success()->send();
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