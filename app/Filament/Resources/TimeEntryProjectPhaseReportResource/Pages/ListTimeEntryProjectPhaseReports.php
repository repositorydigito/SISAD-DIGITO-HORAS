<?php

namespace App\Filament\Resources\TimeEntryProjectPhaseReportResource\Pages;

use App\Filament\Resources\TimeEntryProjectPhaseReportResource;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntryProjectPhaseReports extends ListRecords
{
    protected static string $resource = TimeEntryProjectPhaseReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
