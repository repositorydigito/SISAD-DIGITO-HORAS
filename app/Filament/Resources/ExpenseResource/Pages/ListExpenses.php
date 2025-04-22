<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Category;
use App\Models\Project;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getTitle(): string
    {
        $segment = request()->segment(3);

        return match ($segment) {
            'billing' => 'Por pagar',
            'receivable' => 'Por reembolsar',
            default => 'Listado de Gastos',
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->label('Carga Masiva')
                ->color('primary')
                ->processCollectionUsing(function (string $modelClass, \Illuminate\Support\Collection $collection) {
                    $collection->map(function ($row) {
                        try {
                            $row = is_array($row) ? $row : $row->toArray();

                            // Validación preliminar de claves requeridas antes del mapeo
                            $requiredKeys = ['tipo_documento', 'moneda', 'estado'];
                            foreach ($requiredKeys as $key) {
                                if (!isset($row[$key])) {
                                    Log::error("Key '{$key}' is missing or null in the row: " . json_encode($row));
                                    throw new \Exception("Key '{$key}' is missing or null.");
                                }
                            }

                            // Mapear los campos
                            $row['document_type'] = $row['tipo_documento'] ?? null;
                            $row['currency'] = $row['moneda'] ?? null;
                            $row['status'] = $row['estado'] ?? null;
                            $row['document_number'] = isset($row['numero_documento']) ? (string) $row['numero_documento'] : null;

                            Log::info("document_type: {$row['document_type']}, currency: {$row['currency']}, status: {$row['status']}");

                            // Mapear la entidad
                            $entity = isset($row['entidad']) 
                                ? Entity::where('business_name', 'LIKE', "%{$row['entidad']}%")->first() 
                                : null;
                            $row['entity_id'] = $entity?->id;

                            // Mapear el centro de costo
                            $costCenter = isset($row['centro_de_costo']) 
                                ? CostCenter::where('center_name', 'LIKE', "%{$row['centro_de_costo']}%")->first() 
                                : null;
                            $row['cost_center_id'] = $costCenter?->id;

                            // Mapear la categoría
                            $category = isset($row['categoria']) 
                                ? Category::where('category_name', 'LIKE', "%{$row['categoria']}%")->first() 
                                : null;
                            $row['category_id'] = $category?->id;

                            // Mapear el proyecto
                            $project = isset($row['proyecto']) 
                                ? Project::where('name', 'LIKE', "%{$row['proyecto']}%")->first() 
                                : null;
                            $row['project_id'] = $project?->id;

                            // Manejo de fechas
                            $row['document_date'] = $this->convertExcelDate($row['fecha_documento']);
                            $row['planned_payment_date'] = $this->convertExcelDate($row['fecha_plan_pago'] ?? null);
                            $row['actual_payment_date'] = $this->convertExcelDate($row['fecha_pago_real'] ?? null);

                            // Convertir montos
                            $row['amount_usd'] = isset($row['monto_usd']) ? floatval($row['monto_usd']) : null;
                            $row['amount_pen'] = isset($row['monto_pen']) ? floatval($row['monto_pen']) : null;

                            // Validación de datos
                            $validator = Validator::make($row, [
                                'entity_id' => 'required|integer|exists:entities,id',
                                'cost_center_id' => 'nullable|integer|exists:cost_centers,id',
                                'category_id' => 'nullable|integer|exists:categories,id',
                                'project_id' => 'nullable|integer|exists:projects,id',
                                'document_type' => 'required|in:Recibo por Honorarios,Recibo de Compra,Nota de crédito,Boleta de pago,Nota de Pago,Sin Documento,Ticket',
                                'document_number' => 'nullable|string|max:50',
                                'document_date' => 'required|date',
                                'currency' => 'required|in:Soles,Dolares',
                                'status' => 'required|in:por revisar,por pagar,por pagar detraccion,por reembolsar,pagado',
                            ]);

                            if ($validator->fails()) {
                                foreach ($validator->errors()->toArray() as $field => $errorMessages) {
                                    Log::error("Validation failed for field '{$field}' with errors: " . implode(', ', $errorMessages));
                                }
                                throw new \Exception('Validation failed for row. See logs for details.');
                            }

                            // Crear registro en la base de datos
                            Expense::create($row);

                            Log::info('Row inserted successfully');
                            return $row;
                        } catch (\Exception $e) {
                            Log::error('Error processing row: ' . json_encode($row) . '. Error: ' . $e->getMessage());
                            throw $e;
                        }
                    });

                    return $collection;
                })
                ->slideOver(),
            Actions\CreateAction::make(),
        ];
    }

    private function convertExcelDate($value)
    {
        if (isset($value) && is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        if (isset($value)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error("Date conversion error for value '{$value}': " . $e->getMessage());
            }
        }

        return null;
    }
}
