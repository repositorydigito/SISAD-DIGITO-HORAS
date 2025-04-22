<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use App\Models\TimeEntryReport;
use Illuminate\Support\Facades\DB;

class TimeEntryReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $from;
    protected $until;
    protected $headers;
    protected $columnCount;

    public function __construct($from, $until)
    {
        $this->from = Carbon::parse($from)->startOfDay();
        $this->until = Carbon::parse($until)->endOfDay();
        $this->headers = $this->getHeaders();
        $this->columnCount = count($this->headers);
    }

    public function collection()
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        // Generamos las columnas dinámicas para cada día
        $dates = collect($startDate->copy()->daysUntil($endDate->copy()));
        $dateColumns = $dates->map(function ($date) {
            $dateStr = $date->toDateString();
            return "SUM(CASE WHEN DATE(time_entries.date) = '{$dateStr}' THEN time_entries.hours ELSE 0 END) as day_{$date->format('Y_m_d')}";
        })->implode(', ');

        return \App\Models\User::query()
            ->select([
                'users.name as user_name',
                DB::raw($dateColumns),
                DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
            ])
            ->leftJoin('time_entries', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'time_entries.user_id')
                    ->whereBetween('time_entries.date', [$startDate, $endDate]);
            })
            ->groupBy('users.name')
            ->orderBy('users.name')
            ->get()
            ->map(function ($record) use ($dates) {
                $data = [
                    'Usuario' => $record->user_name,
                ];

                // Agregamos las columnas de días
                foreach ($dates as $date) {
                    $columnName = "day_{$date->format('Y_m_d')}";
                    $data[$date->format('d/m')] = $record->$columnName ?? 0;
                }

                // Agregamos el total
                $data['Total'] = $record->total_hours;

                return $data;
            });
    }

    public function headings(): array
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        $headings = ['Usuario'];

        // Agregamos los encabezados de días
        $dates = collect($startDate->copy()->daysUntil($endDate->copy()));
        foreach ($dates as $date) {
            $headings[] = $date->format('d/m');
        }

        // Agregamos el encabezado de total
        $headings[] = 'Total';

        return $headings;
    }

    protected function getHeaders(): array
    {
        $headers = ['Recurso'];

        $dates = collect($this->from->copy()->daysUntil($this->until->copy()));
        foreach ($dates as $date) {
            $headers[] = $date->format('d/m');
        }

        $headers[] = 'Total';

        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();
        $dates = collect($startDate->copy()->daysUntil($endDate->copy()));
        $lastColumn = $dates->count() + 2; // +2 por la columna de usuario y total

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
            'A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow() => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ],
            'A1:' . $sheet->getHighestColumn() . '1' => [
                'borders' => [
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '64748B'],
                    ],
                ],
            ],
            'A1:A' . $sheet->getHighestRow() => [
                'borders' => [
                    'right' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '64748B'],
                    ],
                ],
            ],
            $sheet->getHighestColumn() . '1:' . $sheet->getHighestColumn() . $sheet->getHighestRow() => [
                'borders' => [
                    'left' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '64748B'],
                    ],
                ],
            ],
            'A' . $sheet->getHighestRow() . ':' . $sheet->getHighestColumn() . $sheet->getHighestRow() => [
                'borders' => [
                    'top' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '64748B'],
                    ],
                ],
            ],
        ];
    }
}
