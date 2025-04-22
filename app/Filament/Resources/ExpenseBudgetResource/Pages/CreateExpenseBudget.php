<?php

namespace App\Filament\Resources\ExpenseBudgetResource\Pages;

use App\Filament\Resources\ExpenseBudgetResource;
use App\Models\CostCenter;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseBudget extends CreateRecord
{
    protected static string $resource = ExpenseBudgetResource::class;

    protected function afterCreate(): void
    {
        $version = $this->record;
        $selectedCenters = $this->data['selected_centers'] ?? [];

        $costCenters = CostCenter::with('categories')->whereIn('id', $selectedCenters)->get();

        foreach ($costCenters as $center) {
            foreach ($center->categories as $category) {
                $monthlyAmounts = [
                    'january_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['january_amount']),
                    'february_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['february_amount']),
                    'march_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['march_amount']),
                    'april_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['april_amount']),
                    'may_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['may_amount']),
                    'june_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['june_amount']),
                    'july_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['july_amount']),
                    'august_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['august_amount']),
                    'september_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['september_amount']),
                    'october_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['october_amount']),
                    'november_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['november_amount']),
                    'december_amount' => $this->getNumericValue($this->data['expenseBudgets'][$center->id][$category->id]['december_amount']),
                ];

                $version->expenseBudgets()->create(array_merge([
                    'cost_center_id' => $center->id,
                    'category_id' => $category->id,
                    'created_by' => auth()->id()
                ], $monthlyAmounts));
            }
        }
    }

    protected function getNumericValue($value): float
    {
        if (empty($value) || !is_numeric($value)) {
            return 0.00;
        }

        $cleanValue = str_replace([',', ' '], ['.', ''], $value);
        return round((float) $cleanValue, 2);
    }
}
