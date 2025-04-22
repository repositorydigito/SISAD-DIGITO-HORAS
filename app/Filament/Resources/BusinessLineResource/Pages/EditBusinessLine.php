<?php

namespace App\Filament\Resources\BusinessLineResource\Pages;

use App\Filament\Resources\BusinessLineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessLine extends EditRecord
{
    protected static string $resource = BusinessLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
