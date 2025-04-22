<?php

namespace App\Filament\Resources\ExpenseBudgetResource\Pages;

use App\Filament\Resources\ExpenseBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseBudgets extends ListRecords
{
    protected static string $resource = ExpenseBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
