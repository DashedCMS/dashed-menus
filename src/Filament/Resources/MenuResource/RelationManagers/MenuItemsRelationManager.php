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
use Filament\Infolists\Components\TextEntry;
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
                self::copyAction(),
            ]);
    }

    public function copyAction(): Action
    {
        return Action::make('copy')
            ->icon('heroicon-o-document-duplicate')
            ->label('Kopiëren')
            ->visible(count(Locales::getLocalesArray()) > 1)
            ->schema([
                TextEntry::make('description')
                    ->state('Hiermee kopieer je alle inhoudt naar andere talen. Dit kan even duren.'),
                Select::make('to_locales')
                    ->options(Locales::getLocalesArray())
                    ->preload()
                    ->searchable()
                    ->default(collect(Locales::getLocalesArrayWithoutCurrent())->keys()->toArray())
                    ->required()
                    ->label('Naar talen')
                    ->multiple(),
            ])
            ->action(function (array $data) {

                foreach ($this->ownerRecord->menuItems as $menuItem) {
                    foreach ($menuItem->translatable as $column) {
                        $textToTranslate = $menuItem->getTranslation($column, $this->activeLocale);
                        foreach ($data['to_locales'] as $locale) {
                            $menuItem->setTranslation($column, $locale, $textToTranslate);
                        }
                    }

                    $menuItem->save();

                    if ($menuItem->customBlocks) {
                        $translatableCustomBlockColumns = [
                            'blocks',
                        ];

                        foreach ($translatableCustomBlockColumns as $column) {
                            $textToTranslate = $menuItem->customBlocks->getTranslation($column, $this->activeLocale);
                            foreach ($data['to_locales'] as $locale) {
                                $menuItem->customBlocks->setTranslation($column, $locale, $textToTranslate);
                            }
                        }

                        $menuItem->customBlocks->save();
                    }
                }

                Notification::make()
                    ->title('Item is gekopieerd naar andere talen')
                    ->success()
                    ->send();

                return redirect()->to(request()->header('Referer'));
            });
    }
}
