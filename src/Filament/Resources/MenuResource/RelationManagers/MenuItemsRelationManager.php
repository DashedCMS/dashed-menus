<?php

namespace Dashed\DashedMenus\Filament\Resources\MenuResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Actions\DeleteBulkAction;
use Dashed\DashedMenus\Models\MenuItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Filament\Resources\RelationManagers\RelationManager;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;
use LaraZeus\SpatieTranslatable\Resources\RelationManagers\Concerns\Translatable;

class MenuItemsRelationManager extends RelationManager
{
    use Translatable;

    protected static string $relationship = 'menuItems';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {

        $menuItems = MenuItem::all();

        foreach ($menuItems as $menuItem) {
            if ($menuItem->parent_menu_item_id) {
                $parent = $menuItems->where('id', $menuItem->parent_menu_item_id)->first();
                if (! $parent) {
                    $menuItem->parent_menu_item_id = null;
                    $menuItem->save();
                }
            }
        }

        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naam')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->name(false))
                    ->searchable(),
//                TextColumn::make('parentMenuItem.name')
//                    ->label('Bovenliggende item')
//                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->getStateUsing(fn ($record) => str_replace(url('/'), '', $record->getUrl())),
                TextColumn::make('site_ids')
                    ->label('Sites')
                    ->getStateUsing(fn ($record) => implode(' | ', $record->site_ids))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->reorderable('order')
            ->recordActions([
                Action::make('edit')
                    ->label('Bewerken')
                    ->icon('heroicon-o-pencil-square')
                    ->button()
                    ->url(fn (MenuItem $record) => route('filament.dashed.resources.menu-items.edit', [$record])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Menu item aanmaken')
                    ->button()
                    ->url(fn () => route('filament.dashed.resources.menu-items.create') . '?menuId=' . $this->ownerRecord->id),
                LocaleSwitcher::make(),
                Action::make('translate')
                    ->icon('heroicon-m-language')
                    ->label('Vertaal menu')
                    ->visible(AutomatedTranslation::automatedTranslationsEnabled())
                    ->schema([
                        Select::make('from_locale')
                            ->options(Locales::getLocalesArray())
                            ->preload()
                            ->searchable()
                            ->required()
                            ->label('Vanaf taal'),
                        Select::make('to_locales')
                            ->options(Locales::getLocalesArray())
                            ->preload()
                            ->searchable()
                            ->required()
                            ->label('Naar talen')
                            ->multiple(),
                    ])
                    ->action(function (array $data) {
                        foreach ($this->ownerRecord->menuItems as $menuItem) {
                            AutomatedTranslation::translateModel($menuItem, $data['from_locale'], $data['to_locales'], ['name']);
                        }

                        Notification::make()
                            ->title("Menu wordt vertaald")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
