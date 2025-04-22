<?php

namespace App\Filament\Resources\TimeEntryUserBusinessLineReportResource\Pages;

use App\Filament\Resources\TimeEntryUserBusinessLineReportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTimeEntryUserBusinessLineReports extends ListRecords
{
    protected static string $resource = TimeEntryUserBusinessLineReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
