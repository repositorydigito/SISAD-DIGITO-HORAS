<?php

namespace App\Filament\Resources\SalesTargetResource\Pages;

use App\Filament\Resources\SalesTargetResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\SalesTarget;
use Illuminate\Support\Facades\DB;

class EditSalesTarget extends EditRecord
{
    protected static string $resource = SalesTargetResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $salesTargets = SalesTarget::where('version_id', $this->record->id)
            ->get()
            ->groupBy('business_line_id');

        $data['salesTargets'] = $salesTargets->map(function ($targets) {
            $target = $targets->first();
            $amounts = [];
            foreach (SalesTargetResource::$months as $key => $_) {
                $value = $target->{$key . '_amount'};
                $amounts[$key . '_amount'] = $value ? floatval($value) : 0;
            }
            return $amounts;
        })->toArray();

        $data['selected_lines'] = $salesTargets->keys()->toArray();

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        try {
            DB::beginTransaction();

            // Eliminar metas anteriores
            SalesTarget::where('version_id', $this->record->id)->delete();

            // Crear nuevas metas para cada línea de negocio seleccionada
            foreach ($data['salesTargets'] as $lineId => $amounts) {
                if (in_array($lineId, $data['selected_lines'])) {
                    SalesTarget::create([
                        'version_id' => $this->record->id,
                        'business_line_id' => $lineId,
                        'january_amount' => $amounts['january_amount'] ?? 0,
                        'february_amount' => $amounts['february_amount'] ?? 0,
                        'march_amount' => $amounts['march_amount'] ?? 0,
                        'april_amount' => $amounts['april_amount'] ?? 0,
                        'may_amount' => $amounts['may_amount'] ?? 0,
                        'june_amount' => $amounts['june_amount'] ?? 0,
                        'july_amount' => $amounts['july_amount'] ?? 0,
                        'august_amount' => $amounts['august_amount'] ?? 0,
                        'september_amount' => $amounts['september_amount'] ?? 0,
                        'october_amount' => $amounts['october_amount'] ?? 0,
                        'november_amount' => $amounts['november_amount'] ?? 0,
                        'december_amount' => $amounts['december_amount'] ?? 0,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
