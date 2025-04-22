<?php

namespace App\Filament\Resources\TimeEntryUserProjectReportResource\Pages;

use App\Filament\Resources\TimeEntryUserProjectReportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTimeEntryUserProjectReports extends ListRecords
{
    protected static string $resource = TimeEntryUserProjectReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
