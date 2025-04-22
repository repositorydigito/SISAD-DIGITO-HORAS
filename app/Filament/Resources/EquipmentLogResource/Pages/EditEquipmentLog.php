<?php

namespace App\Filament\Resources\EquipmentLogResource\Pages;

use App\Filament\Resources\EquipmentLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentLog extends EditRecord
{
    protected static string $resource = EquipmentLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
