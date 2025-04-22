<?php

namespace App\Filament\Resources\EquipmentLogResource\Pages;

use App\Filament\Resources\EquipmentLogResource;
use App\Models\EquipmentLog;
use App\Models\Project;
use App\Models\Equipment;
use Carbon\Carbon;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ListEquipmentLogs extends ListRecords
{
    protected static string $resource = EquipmentLogResource::class;

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

                            // Procesar proyecto (obligatorio)
                            $projectName = $row['proyecto'] ?? null;
                            $project = $projectName ? Project::where('name', 'LIKE', "%{$projectName}%")->first() : null;
                            if (!$project) {
                                throw new \Exception("Proyecto no encontrado: {$projectName}");
                            }
                            $row['project_id'] = $project->id;

                            // Procesar equipo (obligatorio)
                            $equipmentName = $row['equipo'] ?? null;
                            $equipment = $equipmentName ? Equipment::where('name', 'LIKE', "%{$equipmentName}%")->first() : null;
                            if (!$equipment) {
                                throw new \Exception("Equipo no encontrado: {$equipmentName}");
                            }
                            $row['equipment_id'] = $equipment->id;

                            // Procesar fecha (obligatorio)
                            $dateValue = $row['fecha'] ?? null;
                            if (is_numeric($dateValue)) {
                                $date = ExcelDate::excelToDateTimeObject($dateValue);
                                $row['date'] = $date->format('Y-m-d');
                            } else {
                                $row['date'] = $dateValue ? Carbon::createFromFormat('d/m/Y', $dateValue)->format('Y-m-d') : null;
                            }

                            // Campos numéricos opcionales
                            $row['start_time'] = isset($row['horometro_inicial']) && is_numeric($row['horometro_inicial'])
                                ? floatval($row['horometro_inicial'])
                                : null;

                            $row['end_time'] = isset($row['horometro_final']) && is_numeric($row['horometro_final'])
                                ? floatval($row['horometro_final'])
                                : null;

                            $row['engine_hours'] = 0; // Valor por defecto

                            $row['delay_hours'] = isset($row['horas_de_demora']) && is_numeric($row['horas_de_demora'])
                                ? floatval($row['horas_de_demora'])
                                : 0;

                            $row['initial_mileage'] = isset($row['kilometraje_inicial']) && is_numeric($row['kilometraje_inicial'])
                                ? floatval($row['kilometraje_inicial'])
                                : null;

                            $row['final_mileage'] = isset($row['kilometraje_final']) && is_numeric($row['kilometraje_final'])
                                ? floatval($row['kilometraje_final'])
                                : null;

                            $row['tons'] = isset($row['toneladas']) && is_numeric($row['toneladas'])
                                ? floatval($row['toneladas'])
                                : null;

                            $row['diesel_gal'] = isset($row['diesel_gal']) && is_numeric($row['diesel_gal'])
                                ? floatval($row['diesel_gal'])
                                : null;

                            // Procesar actividad de demora
                            if (isset($row['actividades_de_demora']) && !empty($row['actividades_de_demora'])) {
                                $row['delay_activity'] = strtoupper(str_replace(' ', '_', $row['actividades_de_demora']));
                            } else {
                                $row['delay_activity'] = null;
                            }

                            // Validación de campos según la estructura de la base de datos
                            $validator = Validator::make($row, [
                                'project_id' => 'required|exists:projects,id',
                                'equipment_id' => 'required|exists:equipments,id',
                                'date' => 'required|date',
                                'start_time' => 'required|numeric',
                                'end_time' => 'nullable|numeric',
                                'engine_hours' => 'nullable|numeric',
                                'delay_hours' => 'nullable|numeric',
                                'delay_activity' => 'nullable|in:CALENTAMIENTO,TRASLADO_EQUIPO,MANTENIMIENTO_PREVIO,MANTENIMIENTO_PROGRAMADO,HORAS_MOTOR_MANTENIMIENTO,HORAS_MOTOR_MANTENIMIENTO_NO_PROGRAMADO',
                                'initial_mileage' => 'nullable|numeric',
                                'final_mileage' => 'nullable|numeric',
                                'tons' => 'nullable|numeric',
                                'diesel_gal' => 'nullable|numeric',
                            ]);

                            if ($validator->fails()) {
                                $errors = $validator->errors()->toArray();
                                foreach ($errors as $field => $errorMessages) {
                                    Log::error("Validation failed for field '{$field}' with errors: " . implode(', ', $errorMessages));
                                }
                                throw new \Exception('Validation failed for row. See logs for details.');
                            }

                            // Registro antes de la inserción
                            Log::info('Inserting row: ' . json_encode($row));

                            // Inserción usando el modelo EquipmentLog
                            EquipmentLog::create($row);

                            // Registro después de la inserción
                            Log::info('Row inserted successfully');

                            return $row;
                        } catch (\Exception $e) {
                            // Registra el error en los logs para depurar
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
