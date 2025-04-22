<?php

namespace App\Exports;

use App\Models\TimeEntryProjectReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeEntryProjectReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $from;
    protected $until;

    public function __construct($from, $until)
    {
        $this->from = Carbon::parse($from)->startOfDay();
        $this->until = Carbon::parse($until)->endOfDay();
    }

    public function collection()
    {
        $startDate = Carbon::parse($this->from)->startOfDay();
        $endDate = Carbon::parse($this->until)->endOfDay();

        // Obtenemos todos los usuarios
        $users = \App\Models\User::query()
            ->select('users.id', 'users.name')
            ->orderBy('users.name')
            ->get();

        // Creamos las columnas para cada usuario
        $userColumns = $users->map(function ($user) {
            return "SUM(CASE WHEN time_entries.user_id = {$user->id} THEN time_entries.hours ELSE 0 END) as user_{$user->id}";
        })->implode(', ');

        return \App\Models\Project::query()
            ->join('business_lines', 'business_lines.id', '=', 'projects.business_line_id')
            ->select([
                'projects.id',
                'projects.name as project_name',
                'business_lines.name as business_line_name',
                DB::raw($userColumns),
                DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
            ])
            ->leftJoin('time_entries', function ($join) use ($startDate, $endDate) {
                $join->on('projects.id', '=', 'time_entries.project_id')
                    ->whereBetween('time_entries.date', [$startDate, $endDate]);
            })
            ->groupBy('projects.id', 'projects.name', 'business_lines.name')
            ->orderBy('business_lines.name')
            ->orderBy('projects.name')
            ->get()
            ->map(function ($record) use ($users) {
                $data = [
                    'Proyecto' => $record->project_name,
                    'Línea de Negocio' => $record->business_line_name,
                ];

                // Agregamos las columnas de usuarios
                foreach ($users as $user) {
                    $columnName = "user_{$user->id}";
                    $data[$user->name] = $record->$columnName ?? 0;
                }

                // Agregamos el total
                $data['Total'] = $record->total_hours;

                return $data;
            });
    }

    public function headings(): array
    {
        $headings = ['Proyecto', 'Línea de Negocio'];

        // Agregamos los encabezados de usuarios
        $users = \App\Models\User::query()
            ->select('users.name')
            ->orderBy('users.name')
            ->get();

        foreach ($users as $user) {
            $headings[] = $user->name;
        }

        // Agregamos el encabezado de total
        $headings[] = 'Total';

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        $users = \App\Models\User::query()->count();
        $lastColumn = $users + 3; // +3 por las columnas de proyecto, línea de negocio y total

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
