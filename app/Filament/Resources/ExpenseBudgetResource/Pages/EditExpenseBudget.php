<?php

namespace App\Filament\Resources\ExpenseBudgetResource\Pages;

use App\Filament\Resources\ExpenseBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseBudget extends EditRecord
{
    protected static string $resource = ExpenseBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
