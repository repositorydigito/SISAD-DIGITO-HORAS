<?php

namespace App\Exports;

use App\Models\TimeEntryProjectPhaseReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeEntryProjectPhaseReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $from;
    protected $until;
    protected $columnCount;

    // Definimos todas las fases posibles
    protected static array $allPhases = [
        'inicio' => 'Inicio',
        'planificacion' => 'Planificación',
        'ejecucion' => 'Ejecución',
        'control' => 'Control',
        'cierre' => 'Cierre'
    ];

    public function __construct($from, $until)
    {
        $this->from = $from;
        $this->until = $until;
        $this->columnCount = count(self::$allPhases) + 3; // Proyecto + Línea de Negocio + Total
    }

    public function query()
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        // Usamos las fases predefinidas
        $phases = collect(self::$allPhases)->keys();

        // Creamos las columnas para cada fase
        $phaseColumns = $phases->map(function ($phase) {
            return "SUM(CASE WHEN time_entries.phase = '{$phase}' THEN time_entries.hours ELSE 0 END) as phase_{$phase}";
        })->implode(', ');

        return TimeEntryProjectPhaseReport::query()
            ->join('projects', 'projects.id', '=', 'time_entries.project_id')
            ->join('business_lines', 'business_lines.id', '=', 'projects.business_line_id')
            ->select([
                'projects.name as project_name',
                'business_lines.name as business_line_name',
                DB::raw($phaseColumns),
                DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
            ])
            ->whereBetween('time_entries.date', [$startDate, $endDate])
            ->groupBy('projects.name', 'business_lines.name')
            ->orderBy('business_lines.name')
            ->orderBy('projects.name');
    }

    public function collection()
    {
        $query = $this->query();
        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'Proyecto',
            'Línea de Negocio',
        ];

        // Agregamos las columnas de fases
        foreach (self::$allPhases as $phase => $label) {
            $headings[] = $label;
        }

        // Agregamos la columna de total
        $headings[] = 'Total';

        return $headings;
    }

    public function map($row): array
    {
        $data = [
            $row->project_name,
            $row->business_line_name,
        ];

        // Agregamos los valores de cada fase
        foreach (self::$allPhases as $phase => $label) {
            $data[] = $row->{"phase_{$phase}"} ?? 0;
        }

        // Agregamos el total
        $data[] = $row->total_hours;

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastColumn();
        $lastRow = $sheet->getHighestRow();

        // Estilo para los encabezados
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2E8F0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Estilo para las celdas de datos
        $sheet->getStyle("A2:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Ajustar el ancho de las columnas
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Formato numérico para las columnas de horas
        $phaseColumns = count(self::$allPhases);
        $sheet->getStyle("C2:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function getLastColumn(): string
    {
        return chr(65 + $this->columnCount - 1); // 65 es el código ASCII para 'A'
    }
}
