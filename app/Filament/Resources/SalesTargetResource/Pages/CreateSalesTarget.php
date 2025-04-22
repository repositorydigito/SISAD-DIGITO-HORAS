<?php

namespace App\Filament\Resources\SalesTargetResource\Pages;

use App\Filament\Resources\SalesTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\SalesTargetVersion;
use App\Models\businessLine;
use App\Models\SalesTarget;

class CreateSalesTarget extends CreateRecord
{
    protected static string $resource = SalesTargetResource::class;
    
    protected function afterCreate(): void
    {
        $version = $this->record;
        $selectedLines = $this->data['selected_lines'] ?? [];
        
        $businessLines = BusinessLine::whereIn('id', $selectedLines)->get();
        
        foreach ($businessLines as $line) {
            // Asegurar que todos los valores sean numéricos
            $monthlyAmounts = [
                'january_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['january_amount']),
                'february_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['february_amount']),
                'march_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['march_amount']),
                'april_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['april_amount']),
                'may_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['may_amount']),
                'june_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['june_amount']),
                'july_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['july_amount']),
                'august_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['august_amount']),
                'september_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['september_amount']),
                'october_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['october_amount']),
                'november_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['november_amount']),
                'december_amount' => $this->getNumericValue($this->data['salesTargets'][$line->id]['december_amount']),
            ];

            $version->salesTargets()->create(array_merge([
                'business_line_id' => $line->id,
                'created_by' => auth()->id()
            ], $monthlyAmounts));
        }
    }

    /**
     * Convierte cualquier valor de entrada en un número decimal válido
     */
    protected function getNumericValue($value): float
    {
        // Si el valor está vacío o no es numérico, devolver 0
        if (empty($value) || !is_numeric($value)) {
            return 0.00;
        }
        
        // Limpiar el valor y convertirlo a decimal
        $cleanValue = str_replace([',', ' '], ['.', ''], $value);
        return round((float) $cleanValue, 2);
    }

}
