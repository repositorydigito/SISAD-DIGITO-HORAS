<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Exports\UserHoursReportExport;
use Maatwebsite\Excel\Facades\Excel;

class UserHoursReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Reporte de Horas por Usuario';
    protected static ?string $title = 'Reporte de Horas por Usuario';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $slug = 'reportes-horas-usuario';

    protected static string $view = 'filament.pages.user-hours-report';

    public $startDate;
    public $endDate;
    public $users = [];
    public $dateRange = [];
    public $userHours = [];
    public $selectedUserDate = null;
    public $showDetailModal = false;
    public $isLoading = false;

    public function mount(): void
    {
        // Por defecto, mostrar el mes actual
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Fecha Inicio')
                            ->required()
                            ->default(now()->startOfMonth()),
                        DatePicker::make('endDate')
                            ->label('Fecha Fin')
                            ->required()
                            ->default(now()->endOfMonth()),
                    ])
            ]);
    }

    public function updated($property)
    {
        if (in_array($property, ['startDate', 'endDate'])) {
            $this->loadData();
        }
    }

    public function applyFilters()
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        if (!$this->startDate || !$this->endDate) {
            return;
        }

        $this->isLoading = true;

        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        // Generar array de fechas en el rango
        $this->dateRange = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $this->dateRange[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        // Obtener todos los usuarios
        $this->users = User::orderBy('name')->get();

        // Obtener todas las entradas de tiempo en el rango
        $timeEntries = TimeEntry::with(['user', 'project'])
            ->whereBetween('date', [$startDate, $endDate])
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

        $this->isLoading = false;
    }

    public function getDayTotal($date): float
    {
        $total = 0;
        foreach ($this->userHours as $userData) {
            $total += $userData['dates'][$date]['total'] ?? 0;
        }
        return $total;
    }

    public function getUserTotal($userId): float
    {
        $total = 0;
        if (isset($this->userHours[$userId])) {
            foreach ($this->userHours[$userId]['dates'] as $dateData) {
                $total += $dateData['total'];
            }
        }
        return $total;
    }

    public function setCurrentMonth()
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function setPreviousMonth()
    {
        $previousMonth = now()->subMonth();
        $this->startDate = $previousMonth->startOfMonth()->toDateString();
        $this->endDate = $previousMonth->endOfMonth()->toDateString();
        $this->loadData();
    }

    public function setCurrentWeek()
    {
        $this->startDate = now()->startOfWeek()->toDateString();
        $this->endDate = now()->endOfWeek()->toDateString();
        $this->loadData();
    }

    public function setLast30Days()
    {
        $this->startDate = now()->subDays(30)->toDateString();
        $this->endDate = now()->toDateString();
        $this->loadData();
    }

    public function showUserDateDetail($userId, $date)
    {
        $user = $this->users->find($userId);
        if (!$user) {
            return;
        }

        $this->selectedUserDate = [
            'user_id' => $userId,
            'date' => $date,
            'user_name' => $user->name,
            'entries' => $this->userHours[$userId]['dates'][$date]['entries'] ?? []
        ];
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedUserDate = null;
    }



    public function exportData()
    {
        if (!$this->startDate || !$this->endDate) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Debe seleccionar un rango de fechas antes de exportar')
                ->danger()
                ->send();
            return;
        }

        try {
            $fileName = 'reporte_horas_usuario_' . 
                       Carbon::parse($this->startDate)->format('Y-m-d') . '_' . 
                       Carbon::parse($this->endDate)->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new UserHoursReportExport($this->startDate, $this->endDate),
                $fileName
            );
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al exportar')
                ->body('Ocurrió un error al generar el archivo: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_UserHoursReport') ?? false;
    }

    protected function getActions(): array
    {
        return [];
    }
}