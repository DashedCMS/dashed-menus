<?php

namespace Dashed\DashedMenus\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Dashed\DashedMenus\Models\Menu;
use Dashed\DashedMenus\Models\MenuItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Saade\FilamentAdjacencyList\Forms\Components\AdjacencyList;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Dashed\DashedCore\Classes\Actions\ActionGroups\ToolbarActions;
use Dashed\DashedMenus\Filament\Resources\MenuResource\Pages\EditMenu;
use Dashed\DashedMenus\Filament\Resources\MenuResource\Pages\ListMenu;
use Dashed\DashedMenus\Filament\Resources\MenuResource\Pages\CreateMenu;
use Dashed\DashedMenus\Filament\Resources\MenuResource\RelationManagers\MenuItemsRelationManager;

class MenuResource extends Resource
{
    use Translatable;

    protected static ?string $model = Menu::class;
    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';
    protected static string | UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Menu\'s';
    protected static ?string $label = 'Menu';
    protected static ?string $pluralLabel = 'Menu\'s';

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Menu')->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->unique('dashed__menus', 'name', fn ($record) => $record)
                            ->reactive()
                            ->lazy()
                            ->afterStateUpdated(function (Set $set, $state, $livewire) {
                                $set('name', Str::slug($state));
                            }),
                    ]),
                Section::make('Sorteren')
                    ->description('Klik om uit te klappen. Sleep items om de volgorde te wijzigen, of versleep ze onder elkaar om sub-items te maken.')
                    ->icon('heroicon-o-bars-arrow-down')
                    ->columnSpanFull()
                    ->visibleOn('edit')
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed()
                    ->schema([
                        self::adjacencyListField(),
                    ]),
            ]);
    }

    /**
     * Centrale definitie van het tree-builder schema. Wordt op de EditMenu-page
     * door een header-action getoond in een modal zodat de form-pagina zelf
     * compact blijft en de visuele menu-bouwer alleen ruimte inneemt wanneer
     * de admin er expliciet voor kiest.
     */
    public static function adjacencyListField(): AdjacencyList
    {
        return AdjacencyList::make('parentMenuItems')
            ->label('')
            ->relationship('parentMenuItems')
            ->labelKey('name')
            ->childrenKey('childMenuItems')
            ->orderColumn('order')
            ->maxDepth(-1)
            ->modal(false)
            ->itemLabel(function (array $item): string {
                $id = $item['id'] ?? null;
                if ($id) {
                    $menuItem = MenuItem::find($id);
                    if ($menuItem) {
                        return (string) ($menuItem->name(false) ?: '#'.$id);
                    }
                }

                return (string) ($item['name'] ?? 'Nieuw item');
            })
            ->form([
                TextInput::make('name')
                    ->label('Naam')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount_of_menu_items')
                    ->label('Aantal menu items')
                    ->getStateUsing(fn ($record) => $record->menuItems->count()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions(ToolbarActions::getActions());
    }

    public static function getRelations(): array
    {
        return [
            MenuItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenu::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
