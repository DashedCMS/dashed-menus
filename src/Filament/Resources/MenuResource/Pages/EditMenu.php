<?php

namespace Dashed\DashedMenus\Filament\Resources\MenuResource\Pages;

use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedMenus\Filament\Resources\MenuResource;

class EditMenu extends EditRecord
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('orderMenuTree')
                ->label('Menu structuur ordenen')
                ->icon('heroicon-o-bars-arrow-down')
                ->color('gray')
                ->modalHeading('Menu structuur')
                ->modalDescription('Sleep items om de volgorde te wijzigen, of versleep ze onder elkaar om sub-items te maken. Wijzigingen worden direct opgeslagen.')
                ->modalWidth('4xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Sluiten')
                ->record(fn () => $this->record)
                ->schema([
                    MenuResource::adjacencyListField(),
                ])
                ->mountUsing(function (Schema $schema, ?Model $record): void {
                    // Bind het Menu-record en het modelClass expliciet aan de
                    // modal-schema; zonder deze twee setters valt
                    // getModelInstance() in saade's HasRelationship terug op
                    // app(Model::class) en zijn de bestaande menu-items leeg.
                    if ($record) {
                        $schema->model($record::class);
                        $schema->record($record);
                    }

                    $schema->fill();
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = Str::slug($data['name']);

        return $data;
    }
}
