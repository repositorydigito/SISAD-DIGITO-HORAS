<?php

namespace App\Exports;

use App\Models\User;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserHoursReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $startDate;
    protected $endDate;
    protected $dateRange;
    protected $users;
    protected $userHours;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        $this->loadData();
    }

    protected function loadData()
    {
        // Generar array de fechas en el rango
        $this->dateRange = [];
        $currentDate = $this->startDate->copy();
        while ($currentDate <= $this->endDate) {
            $this->dateRange[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        // Obtener todos los usuarios
        $this->users = User::orderBy('name')->get();

        // Obtener todas las entradas de tiempo en el rango
        $timeEntries = TimeEntry::with(['user', 'project'])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        // Organizar datos por usuario y fecha
        $this->userHours = [];
        foreach ($this->users as $user) {
            $this->userHours[$user->id] = [
                'user' => $user,
                'dates' => []
            ];

            $userEntries = $timeEntries->where('user_id', $user->id);
            
            foreach ($this->dateRange as $date) {
                $dayEntries = $userEntries->filter(function ($entry) use ($date) {
                    return $entry->date->toDateString() === $date;
                });
                
                $totalHours = $dayEntries->sum('hours');
                
                $this->userHours[$user->id]['dates'][$date] = [
                    'total' => $totalHours,
                    'entries' => $dayEntries->groupBy('project.name')->map(function ($entries) {
                        return $entries->sum('hours');
                    })->toArray()
                ];
            }
        }
    }

    public function headings(): array
    {
        $headings = ['Usuario'];
        
        // Agregar encabezados de fechas
        foreach ($this->dateRange as $date) {
            $carbonDate = Carbon::parse($date);
            $headings[] = $carbonDate->format('d/m') . "\n" . $carbonDate->format('D');
        }
        
        $headings[] = 'Total';
        
        return $headings;
    }

    public function array(): array
    {
        $data = [];
        
        // Agregar fila con información del período
        $periodRow = ['Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y')];
        for ($i = 0; $i < count($this->dateRange); $i++) {
            $periodRow[] = '';
        }
        $periodRow[] = '';
        $data[] = $periodRow;
        
        // Agregar fila vacía
        $emptyRow = array_fill(0, count($this->dateRange) + 2, '');
        $data[] = $emptyRow;
        
        // Agregar datos de usuarios
        foreach ($this->users as $user) {
            $row = [$user->name];
            
            $userTotal = 0;
            foreach ($this->dateRange as $date) {
                $hours = $this->userHours[$user->id]['dates'][$date]['total'] ?? 0;
                $row[] = $hours > 0 ? number_format($hours, 1) : '';
                $userTotal += $hours;
            }
            
            $row[] = number_format($userTotal, 1);
            $data[] = $row;
        }
        
        // Agregar fila de totales por día
        $totalRow = ['TOTAL POR DÍA'];
        $grandTotal = 0;
        
        foreach ($this->dateRange as $date) {
            $dayTotal = 0;
            foreach ($this->userHours as $userData) {
                $dayTotal += $userData['dates'][$date]['total'] ?? 0;
            }
            $totalRow[] = number_format($dayTotal, 1);
            $grandTotal += $dayTotal;
        }
        
        $totalRow[] = number_format($grandTotal, 1);
        $data[] = $totalRow;
        
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getColumnLetter(count($this->dateRange) + 2);
        $lastRow = count($this->users) + 4; // +4 por las filas adicionales
        
        return [
            // Estilo para la fila del período
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E3F2FD']],
            ],
            
            // Estilo para los encabezados (fila 3)
            3 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'BBDEFB']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true
                ],
            ],
            
            // Estilo para la fila de totales
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF3E0']],
            ],
            
            // Bordes para toda la tabla
            "A3:{$lastColumn}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            
            // Alineación para las columnas de horas
            "B3:{$lastColumn}{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 25]; // Columna de usuarios más ancha
        
        // Columnas de fechas
        $column = 'B';
        foreach ($this->dateRange as $date) {
            $widths[$column] = 10;
            $column++;
        }
        
        // Columna de total
        $widths[$column] = 12;
        
        return $widths;
    }

    public function title(): string
    {
        return 'Reporte Horas Usuario';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Ajustar altura de la fila de encabezados
                $event->sheet->getDelegate()->getRowDimension(3)->setRowHeight(30);
                
                // Ajustar altura de la fila del período
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(20);
            },
        ];
    }

    private function getColumnLetter($columnIndex)
    {
        $letter = '';
        while ($columnIndex > 0) {
            $columnIndex--;
            $letter = chr(65 + ($columnIndex % 26)) . $letter;
            $columnIndex = intval($columnIndex / 26);
        }
        return $letter;
    }
}