<?php

namespace App\Exports;

use App\Models\TimeEntryUserBusinessLineReport;
use App\Models\BusinessLine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeEntryUserBusinessLineReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $from;
    protected $until;
    protected $columnCount;

    // Obtenemos todas las líneas de negocio
    protected function getAllBusinessLines(): array
    {
        return BusinessLine::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function __construct($from, $until)
    {
        $this->from = $from;
        $this->until = $until;
        $this->columnCount = count($this->getAllBusinessLines()) + 2; // Usuario + Total
    }

    public function query()
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        // Creamos las columnas para cada línea de negocio
        $businessLineColumns = collect($this->getAllBusinessLines())->map(function ($name, $id) {
            return "SUM(CASE WHEN projects.business_line_id = {$id} THEN time_entries.hours ELSE 0 END) as business_line_{$id}";
        })->implode(', ');

        return \App\Models\User::query()
            ->select([
                'users.id',
                'users.name as user_name',
                DB::raw($businessLineColumns),
                DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
            ])
            ->leftJoin('time_entries', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'time_entries.user_id')
                    ->whereBetween('time_entries.date', [$startDate, $endDate]);
            })
            ->leftJoin('projects', 'projects.id', '=', 'time_entries.project_id')
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name');
    }

    public function collection()
    {
        $query = $this->query();
        return $query->get();
    }

    public function headings(): array
    {
        $headings = ['Usuario'];

        // Agregamos las columnas de líneas de negocio
        foreach ($this->getAllBusinessLines() as $businessLineId => $businessLineName) {
            $headings[] = $businessLineName;
        }

        // Agregamos la columna de total
        $headings[] = 'Total';

        return $headings;
    }

    public function map($row): array
    {
        $data = [$row->user_name];

        // Agregamos los valores de cada línea de negocio
        foreach ($this->getAllBusinessLines() as $businessLineId => $businessLineName) {
            $data[] = $row->{"business_line_{$businessLineId}"} ?? 0;
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
        $businessLineColumns = count($this->getAllBusinessLines());
        $sheet->getStyle("B2:{$lastColumn}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function getLastColumn(): string
    {
        return chr(65 + $this->columnCount - 1); // 65 es el código ASCII para 'A'
    }
}
