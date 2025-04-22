<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use App\Models\TimeEntryUserProjectReport;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class TimeEntryUserProjectReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $from;
    protected $until;
    protected $headers;
    protected $columnCount;
    protected $projects;

    public function __construct($from, $until)
    {
        $this->from = Carbon::parse($from)->startOfDay();
        $this->until = Carbon::parse($until)->endOfDay();
        $this->projects = Project::orderBy('name')->get();
        $this->headers = $this->getHeaders();
        $this->columnCount = count($this->headers);
    }

    public function collection()
    {
        $query = $this->query();
        return $query->get();
    }

    public function headings(): array
    {
        return $this->headers;
    }

    protected function getHeaders(): array
    {
        $headers = ['Usuario'];

        foreach ($this->projects as $project) {
            $headers[] = $project->name;
        }

        $headers[] = 'Total';

        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastColumn();
        $lastRow = $sheet->getHighestRow();

        // Estilo para el encabezado
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Ajustar el ancho de las columnas
        $sheet->getColumnDimension('A')->setWidth(30);

        // Generar columnas desde B hasta la última
        $currentColumn = 'B';
        while ($currentColumn <= $lastColumn) {
            $sheet->getColumnDimension($currentColumn)->setWidth(15);
            $currentColumn = $this->getNextColumn($currentColumn);
        }

        // Alinear las columnas numéricas a la derecha
        $currentColumn = 'B';
        while ($currentColumn <= $lastColumn) {
            $sheet->getStyle($currentColumn . '2:' . $currentColumn . $lastRow)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $currentColumn = $this->getNextColumn($currentColumn);
        }

        // Agregar bordes a toda la tabla
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Formato para los números
        $currentColumn = 'B';
        while ($currentColumn <= $lastColumn) {
            $sheet->getStyle($currentColumn . '2:' . $currentColumn . $lastRow)
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
            $currentColumn = $this->getNextColumn($currentColumn);
        }
    }

    protected function getLastColumn(): string
    {
        $column = '';
        $number = $this->columnCount;

        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)) . $column;
            $number = floor($number / 26);
        }

        return $column;
    }

    protected function getNextColumn(string $column): string
    {
        $length = strlen($column);
        $carry = true;
        $result = '';

        for ($i = $length - 1; $i >= 0; $i--) {
            $char = ord($column[$i]);
            if ($carry) {
                $char++;
                if ($char > 90) { // 'Z'
                    $char = 65; // 'A'
                } else {
                    $carry = false;
                }
            }
            $result = chr($char) . $result;
        }

        if ($carry) {
            $result = 'A' . $result;
        }

        return $result;
    }

    public function query()
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        // Obtenemos todos los proyectos
        $projects = Project::orderBy('name')->get();

        // Creamos las columnas para cada proyecto
        $projectColumns = $projects->map(function ($project) {
            return "SUM(CASE WHEN time_entries.project_id = {$project->id} THEN time_entries.hours ELSE 0 END) as project_{$project->id}";
        })->implode(', ');

        // Modificamos la consulta para usar LEFT JOIN y mostrar todos los usuarios
        return \App\Models\User::query()
            ->select([
                'users.name as user_name',
                DB::raw($projectColumns),
                DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
            ])
            ->leftJoin('time_entries', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'time_entries.user_id')
                    ->whereBetween('time_entries.date', [$startDate, $endDate]);
            })
            ->groupBy('users.name')
            ->orderBy('users.name');
    }
}
