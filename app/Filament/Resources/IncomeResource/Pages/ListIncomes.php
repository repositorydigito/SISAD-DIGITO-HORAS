<?php

namespace App\Filament\Resources\IncomeResource\Pages;

use App\Filament\Resources\IncomeResource;
use App\Models\Income;
use App\Models\Entity;
use App\Models\Project;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ListIncomes extends ListRecords
{
    protected static string $resource = IncomeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->label('Carga Masiva')
                ->color('primary')
                ->processCollectionUsing(function (string $modelClass, \Illuminate\Support\Collection $collection) {
                  
                    // Inspección de la colección completa para depurar si es necesario
                   // dd($collection->toArray()); // Puedes comentar esta línea una vez que hayas depurado los datos
                    
                    $collection->map(function ($row) {
                        try {
                            // Transformar el array si es necesario
                            $row = is_array($row) ? $row : $row->toArray();

                            // Buscar y mapear la entidad
                            $entity = isset($row['entidad']) 
                                ? Entity::where('business_name', 'LIKE', "%{$row['entidad']}%")->first() 
                                : null;
                            $row['entity_id'] = $entity ? $entity->id : null;

                            // Buscar y mapear el proyecto
                            $project = isset($row['proyecto']) 
                                ? Project::where('name', 'LIKE', "%{$row['proyecto']}%")->first() 
                                : null;
                            $row['project_id'] = $project ? $project->id : null;

                            // Manejo de la fecha del documento
                            $row['document_date'] = isset($row['fecha_documento']) && is_numeric($row['fecha_documento']) 
                                ? ExcelDate::excelToDateTimeObject($row['fecha_documento'])->format('Y-m-d') 
                                : Carbon::createFromFormat('d/m/Y', $row['fecha_documento'])->format('Y-m-d');

                            // Manejo de las otras fechas (opcional)
                            $row['payment_plan_date'] = isset($row['fecha_plan_pago']) && is_numeric($row['fecha_plan_pago']) 
                                ? ExcelDate::excelToDateTimeObject($row['fecha_plan_pago'])->format('Y-m-d') 
                                : $row['fecha_plan_pago'];

                            $row['real_payment_date'] = isset($row['fecha_pago_real']) && is_numeric($row['fecha_pago_real']) 
                                ? ExcelDate::excelToDateTimeObject($row['fecha_pago_real'])->format('Y-m-d') 
                                : $row['fecha_pago_real'];

                            // Convertir montos
                            $row['amount_usd'] = isset($row['monto_usd']) ? floatval($row['monto_usd']) : null;
                            $row['amount_pen'] = isset($row['monto_pen']) ? floatval($row['monto_pen']) : null;

                            // Mapeo adicional (ajustar según tus necesidades)
                            $row['document_type'] = $row['tipo_documento'] ?? null;
                            $row['document_number'] = $row['numero_documento'] ?? null;
                            $row['currency'] = $row['moneda'] ?? null;
                            $row['status'] = $row['estado'] ?? null;

                            // Validación de datos
                            $validator = Validator::make($row, [
                                'entity_id' => 'required|integer',
                                'project_id' => 'required|integer',
                                'document_type' => 'required|in:Boleta de venta,Factura,Nota de abono,Nota de débito,Valor residual',
                                'document_number' => 'required|string|max:50',
                                'document_date' => 'required|date',
                                'currency' => 'required|in:Soles,Dólares',
                                'status' => 'required|in:Por Revisar,Por Facturar,Por Cobrar,Cobrado,Suspendido,Provisionado',
                            ]);

                            if ($validator->fails()) {
                                $errors = $validator->errors()->toArray();
                                foreach ($errors as $field => $errorMessages) {
                                    Log::error("Validation failed for field '{$field}' with errors: " . implode(', ', $errorMessages));
                                }
                                throw new \Exception('Validation failed for row. See logs for details.');
                            }

                            // Crear registro en la base de datos
                            Income::create($row);

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
}
