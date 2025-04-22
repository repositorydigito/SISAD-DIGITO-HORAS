<?php

namespace App\Filament\Resources\TimeEntryReportResource\Pages;

use App\Filament\Resources\TimeEntryReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeEntryReport extends EditRecord
{
    protected static string $resource = TimeEntryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
