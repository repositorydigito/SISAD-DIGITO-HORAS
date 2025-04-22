<?php

namespace App\Filament\Resources\SalesTargetResource\Pages;

use App\Filament\Resources\SalesTargetResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewSalesTarget extends ViewRecord
{
    protected static string $resource = SalesTargetResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('year')
                            ->label('Año'),
                        TextEntry::make('version_number')
                            ->label('Versión'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'approved' => 'success',
                            }),
                    ])
                    ->columns(3),
                    
                Section::make('Detalle de Montos')
                    ->view('filament.tables.sales-target-matrix', [
                        'businessLines' => \App\Models\BusinessLine::all(),
                        'months' => SalesTargetResource::$months,
                        'salesTargets' => $this->record->salesTargets->groupBy('business_line_id')
                    ])
            ]);
    }
}
