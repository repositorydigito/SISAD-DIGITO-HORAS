<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import')
                ->label('IMPORTAR DESDE CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->size('lg')
                ->extraAttributes([
                    'class' => 'animate-bounce',
                    'style' => 'font-weight: bold; border: 2px solid red; padding: 10px;',
                ])
                ->url(fn () => TimeEntryResource::getUrl('import')),
        ];
    }
}
