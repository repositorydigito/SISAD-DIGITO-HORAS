<?php

namespace App\Filament\Resources\TimeEntryResource\Pages;

use App\Filament\Resources\TimeEntryResource;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImportTimeEntries extends Page
{
    protected static string $resource = TimeEntryResource::class;

    protected static string $view = 'filament.resources.time-entry-resource.pages.import-time-entries';

    public $file = null;

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $this->file;

        if (!$file || !file_exists($file->getPathname())) {
            Notification::make()
                ->title('Error: No se ha seleccionado ningún archivo')
                ->danger()
                ->send();
            return;
        }

        // Obtener proyectos y usuarios para validación
        $projects = Project::pluck('id')->toArray();
        $users = User::pluck('id')->toArray();
        $validPhases = array_keys(TimeEntry::PHASES);

        // Procesar el archivo
        $handle = fopen($file->getPathname(), 'r');

        // Leer la primera línea y limpiar posibles BOM o espacios
        $firstLine = fgets($handle);
        rewind($handle); // Volver al inicio del archivo

        // Detectar y eliminar BOM si existe
        $bom = pack('H*', 'EFBBBF');
        $firstLine = preg_replace("/^$bom/", '', $firstLine);

        // Detectar el separador (coma o punto y coma)
        $separator = ',';
        if (strpos($firstLine, ';') !== false) {
            $separator = ';';
        }

        // Leer encabezados con el separador detectado
        if ($separator === ',') {
            $headers = fgetcsv($handle);
        } else {
            $headers = fgetcsv($handle, 0, $separator);
        }

        // Limpiar encabezados
        $headers = array_map(function($header) {
            return trim(strtolower(str_replace(['"', "'"], '', $header)));
        }, $headers);

        // Verificar encabezados
        $requiredHeaders = ['project_id', 'user_id', 'date', 'hours', 'phase', 'description'];
        $missingHeaders = array_diff($requiredHeaders, $headers);

        if (!empty($missingHeaders)) {
            // Mostrar encabezados encontrados vs requeridos para ayudar al usuario
            $message = 'Faltan encabezados requeridos: ' . implode(', ', $missingHeaders) . "\n";
            $message .= 'Encabezados encontrados: ' . implode(', ', $headers);

            Notification::make()
                ->title('Error en los encabezados del archivo')
                ->body($message)
                ->danger()
                ->persistent()
                ->actions([
                    NotificationAction::make('download_template')
                        ->label('Descargar plantilla')
                        ->url(route('time-entries.template'))
                        ->openUrlInNewTab()
                ])
                ->send();
            fclose($handle);
            return;
        }

        // Mapear índices de columnas
        $columnMap = array_flip($headers);

        // Preparar para procesar
        $totalRows = 0;
        $successRows = 0;
        $errorRows = 0;

        // Procesar cada línea
        DB::beginTransaction();
        try {
            while (($row = ($separator === ',' ? fgetcsv($handle) : fgetcsv($handle, 0, $separator))) !== false) {
                $totalRows++;

                // Extraer datos
                $data = [
                    'project_id' => $row[$columnMap['project_id']] ?? null,
                    'user_id' => $row[$columnMap['user_id']] ?? null,
                    'date' => $row[$columnMap['date']] ?? null,
                    'hours' => $row[$columnMap['hours']] ?? null,
                    'phase' => $row[$columnMap['phase']] ?? null,
                    'description' => $row[$columnMap['description']] ?? null,
                ];

                // Validar datos básicos
                $isValid = true;
                $rowErrors = [];

                // Validar que los campos requeridos no estén vacíos
                if (empty($data['project_id'])) {
                    $isValid = false;
                    $rowErrors[] = "ID de proyecto vacío";
                }

                if (empty($data['user_id'])) {
                    $isValid = false;
                    $rowErrors[] = "ID de usuario vacío";
                }

                if (empty($data['date'])) {
                    $isValid = false;
                    $rowErrors[] = "Fecha vacía";
                }

                if (empty($data['hours'])) {
                    $isValid = false;
                    $rowErrors[] = "Horas vacías";
                }

                if (empty($data['phase'])) {
                    $isValid = false;
                    $rowErrors[] = "Fase vacía";
                }

                // Validar que el proyecto exista
                if (!in_array($data['project_id'], $projects)) {
                    $isValid = false;
                    $rowErrors[] = "El proyecto con ID {$data['project_id']} no existe";
                }

                // Validar que el usuario exista
                if (!in_array($data['user_id'], $users)) {
                    $isValid = false;
                    $rowErrors[] = "El usuario con ID {$data['user_id']} no existe";
                }

                // Convertir y validar la fecha
                if (!empty($data['date'])) {
                    // Verificar si la fecha está en formato dd/mm/yyyy
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data['date'], $matches)) {
                        // Convertir al formato yyyy-mm-dd
                        $data['date'] = $matches[3] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                    }

                    // Validar que la fecha sea válida
                    if (!strtotime($data['date'])) {
                        $isValid = false;
                        $rowErrors[] = "Formato de fecha inválido: {$data['date']}";
                    }
                }

                // Validar horas
                if (!is_numeric($data['hours']) || $data['hours'] < 0.5 || $data['hours'] > 24) {
                    $isValid = false;
                    $rowErrors[] = "Horas inválidas (debe ser entre 0.5 y 24): {$data['hours']}";
                }

                // Validar fase
                if (!in_array($data['phase'], $validPhases)) {
                    $isValid = false;
                    $rowErrors[] = "Fase inválida (debe ser: " . implode(', ', $validPhases) . "): {$data['phase']}";
                }

                if (!$isValid) {
                    $errorRows++;
                    $errors[] = "Fila {$totalRows}: " . implode(', ', $rowErrors);
                    continue;
                }

                // Crear entrada de tiempo
                TimeEntry::create($data);
                $successRows++;
            }

            DB::commit();

            // Preparar mensaje de resultado
            if ($errorRows > 0) {
                // Limitar la cantidad de errores mostrados para evitar mensajes demasiado largos
                $errorMessages = count($errors) > 10
                    ? array_slice($errors, 0, 10) + ['...y ' . (count($errors) - 10) . ' más']
                    : $errors;

                $errorList = '<ul class="list-disc pl-5 mt-2">';
                foreach ($errorMessages as $error) {
                    $errorList .= '<li>' . $error . '</li>';
                }
                $errorList .= '</ul>';

                Notification::make()
                    ->title("Importación completada con advertencias")
                    ->body("Total: {$totalRows}, Exitosas: {$successRows}, Errores: {$errorRows}" . $errorList)
                    ->warning()
                    ->persistent()
                    ->send();
            } else {
                Notification::make()
                    ->title("Importación completada con éxito")
                    ->body("Se importaron {$successRows} registros correctamente")
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Error en la importación')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            fclose($handle);
        }

        // Limpiar el formulario
        $this->reset('file');
    }
}
