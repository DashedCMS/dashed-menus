<?php

namespace Dashed\DashedMenus\Filament\Resources;

use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Classes\Sites;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Dashed\DashedMenus\Models\MenuItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Dashed\DashedCore\Classes\QueryHelpers\SearchQuery;
use Dashed\DashedCore\Filament\Concerns\HasCustomBlocksTab;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Dashed\DashedMenus\Filament\Resources\MenuItemResource\Pages\EditMenuItem;
use Dashed\DashedMenus\Filament\Resources\MenuItemResource\Pages\ListMenuItems;
use Dashed\DashedMenus\Filament\Resources\MenuItemResource\Pages\CreateMenuItem;

class MenuItemResource extends Resource
{
    use Translatable;
    use HasCustomBlocksTab;

    protected static ?string $model = MenuItem::class;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getRecordTitle($record): ?string
    {
        return $record->name();
    }

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $label = 'Menu item';
    protected static ?string $pluralLabel = 'Menu items';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $menuItemId = request()->get('menuId', null);

        $routeModels = [];
        $routeModelInputs = [];
        foreach (cms()->builder('routeModels') as $key => $routeModel) {
            $routeModels[$key] = $routeModel['name'];

            $routeModelInputs[] =
                Select::make("{$key}_id")
                    ->label("Kies een " . strtolower($routeModel['name']))
                    ->required()
                    ->options(function () use ($routeModel) {
                        $options = [];
                        $results = $routeModel['class']::get();

                        foreach ($results as $result) {
                            $options[$result->id] = $result->nameWithParents;
                        }

                        return $options;
                    })
                    ->searchable()
                    ->hidden(fn ($get) => ! in_array($get('type'), [$key]))
                    ->afterStateHydrated(function (Select $component, Set $set, $state) {
                        $set($component, fn ($record) => $record->model_id ?? '');
                    });
        }

        $newSchema = array_merge([
            Select::make('menu_id')
                ->label('Kies een menu')
                ->searchable()
                ->preload()
                ->relationship('menu', 'name')
                ->default($menuItemId)
                ->afterStateUpdated(function (Get $get, Set $set) {
                    $set('order', (MenuItem::where('menu_id', $get('menu_id'))->orderBy('order', 'desc')->first()->order ?? 0) + 1);
                })
                ->reactive()
                ->required(),
            Select::make('parent_menu_item_id')
                ->label('Kies een bovenliggend menu item')
                ->relationship('parentMenuItem', 'name')
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name()),
            Select::make('type')
                ->label('Kies een type')
                ->searchable()
                ->preload()
                ->options(array_merge([
                    'normal' => 'Normaal',
                    'externalUrl' => 'Externe URL',
                ], $routeModels))
                ->required()
                ->reactive(),
            Select::make('site_ids')
                ->multiple()
                ->searchable()
                ->preload()
                ->label('Actief op sites')
                ->options(collect(Sites::getSites())->pluck('name', 'id')->toArray())
                ->hidden(function () {
                    return ! (Sites::getAmountOfSites() > 1);
                })
                ->required(),
            TextInput::make('order')
                ->label('Volgorde')
                ->required()
                ->default(1)
                ->numeric()
                ->reactive()
                ->maxValue(10000),
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255)
                ->reactive(),
            TextInput::make('url')
                ->label('URL')
                ->required()
                ->maxLength(1000)
                ->reactive()
                ->hidden(fn ($get) => ! in_array($get('type'), ['normal', 'externalUrl'])),
        ], $routeModelInputs);

        cms()->activateBuilderBlockClasses();

        return $schema
            ->schema([
                Section::make('Menu')
                    ->columnSpanFull()
                    ->schema(array_merge($newSchema, static::customBlocksTab('menuItemBlocks')))
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->name())
                    ->searchable(query: SearchQuery::make()),
                TextColumn::make('url')
                    ->label('URL')
                    ->getStateUsing(fn ($record) => str_replace(url('/'), '', $record->getUrl())),
                TextColumn::make('site_ids')
                    ->label('Sites')
                    ->getStateUsing(fn ($record) => implode(' | ', $record->site_ids)),
            ])
            ->recordActions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuItems::route('/'),
            'create' => CreateMenuItem::route('/create'),
            'edit' => EditMenuItem::route('/{record}/edit'),
        ];
    }
}
