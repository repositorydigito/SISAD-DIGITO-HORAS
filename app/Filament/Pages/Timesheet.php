<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Log;


class Timesheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Hojas de Tiempo';
    protected static ?string $title = 'Hojas de Tiempo';

    public $selectedCell = null;
    protected static string $view = 'filament.pages.timesheet';

    public $currentDate;
    public $selectedProject;
    public $selectedDay;
    public $entries;
    public $phaseHours = [];

    public $phases = [
        'inicio' => [
            'name' => 'Inicio',
            'color' => 'bg-green-400'
        ],
        'planificacion' => [
            'name' => 'Planificación',
            'color' => 'bg-green-200'
        ],
        'ejecucion' => [
            'name' => 'Ejecución',
            'color' => 'bg-yellow-200'
        ],
        'control' => [
            'name' => 'Control',
            'color' => 'bg-yellow-400'
        ],
        'cierre' => [
            'name' => 'Cierre',
            'color' => 'bg-green-100'
        ]
    ];

    public function mount(): void
    {
        $this->currentDate = now();
        $this->loadEntries();
    }

    protected function getFormSchema(): array
    {
        return [
            Grid::make()
                ->schema(
                    collect($this->phases)->map(
                        fn($phase, $key) =>
                        TextInput::make("phaseHours.{$key}")
                            ->label($phase['name'])
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(24)
                            ->step(0.5)
                            ->suffix('horas')
                    )->toArray()
                )
        ];
    }

    public function selectCell($projectId, $day): void
    {
        Log::info('SelectCell llamado con:', [
            'projectId' => $projectId,
            'day' => $day
        ]);

        $this->selectedProject = $projectId;
        $this->selectedDay = $day;
        $this->selectedCell = [
            'project_id' => $projectId,
            'day' => $day,
        ];

        Log::info('Estado después de selectCell:', [
            'selectedProject' => $this->selectedProject,
            'selectedDay' => $this->selectedDay,
            'selectedCell' => $this->selectedCell
        ]);
    }


    public function getActions(): array
    {
        return [
            Action::make('timeEntry')
                ->label('Gestionar Horas')
                ->form([
                    Grid::make(3)
                        ->schema([
                            TextInput::make('phaseHours.inicio')
                                ->label('Inicio')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(24)
                                ->step(0.5)
                                ->suffix('horas'),
                            TextInput::make('phaseHours.planificacion')
                                ->label('Planificación')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(24)
                                ->step(0.5)
                                ->suffix('horas'),
                            TextInput::make('phaseHours.ejecucion')
                                ->label('Ejecución')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(24)
                                ->step(0.5)
                                ->suffix('horas'),
                            TextInput::make('phaseHours.control')
                                ->label('Control')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(24)
                                ->step(0.5)
                                ->suffix('horas'),
                            TextInput::make('phaseHours.cierre')
                                ->label('Cierre')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(24)
                                ->step(0.5)
                                ->suffix('horas')
                        ])
                ])
                ->modalWidth(MaxWidth::Medium)
                ->modalHeading(function () {
                    $hasHours = $this->selectedCell && $this->getCellEntries($this->selectedCell['project_id'], $this->selectedCell['day']) > 0;
                    return $hasHours ? 'Actualizar Horas' : 'Registrar Horas';
                })
                ->modalDescription(fn() => "Proyecto: " . Project::find($this->selectedProject)?->name)
                ->fillForm(function () {
                    if (!$this->selectedProject || !$this->selectedDay) {
                        return [];
                    }

                    $date = Carbon::parse($this->currentDate)->setDay($this->selectedDay);

                    $hours = [];
                    foreach ($this->phases as $phaseKey => $phase) {
                        $entry = TimeEntry::where('user_id', auth()->id())
                            ->where('project_id', $this->selectedProject)
                            ->where('phase', $phaseKey)
                            ->whereDate('date', $date)
                            ->first();

                        $hours['phaseHours'][$phaseKey] = $entry ? (float) $entry->hours : 0;
                    }

                    return $hours;
                })
                ->action(function (array $data): void {

                    $date = Carbon::parse($this->currentDate)->setDay($this->selectedDay);

                    TimeEntry::where('user_id', auth()->id())
                        ->where('project_id', $this->selectedProject)
                        ->whereDate('date', $date)
                        ->delete();

                    Log::info('Datos a crear:', $data);

                    foreach ($data['phaseHours'] as $phase => $hours) {
                        $hours = floatval($hours);

                        if ($hours > 0) {
                            TimeEntry::create([
                                'user_id' => auth()->id(),
                                'project_id' => $this->selectedProject,
                                'date' => $date,
                                'phase' => $phase,
                                'hours' => $hours
                            ]);
                        }
                    }

                    $this->loadEntries();
                    $this->selectedCell = null;

                    Notification::make()
                        ->title('Horas guardadas correctamente')
                        ->success()
                        ->send();
                })
        ];
    }


    public function openModal($projectId, $day): void
    {
        $this->selectCell($projectId, $day);
        $this->mountAction('timeEntry');
    }

    public function getCellEntries($projectId, $day)
    {
        $date = Carbon::parse($this->currentDate)->setDay($day)->toDateString();

        $filteredEntries = collect($this->entries)
            ->filter(function ($entry) use ($projectId, $date) {
                return $entry['project_id'] == $projectId
                    && Carbon::parse($entry['date'])->toDateString() === $date;
            });

        return $filteredEntries->sum('hours');
    }

    public function loadEntries(): void
    {
        $startOfMonth = Carbon::parse($this->currentDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->currentDate)->endOfMonth();

        $this->entries = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'project_id' => $entry->project_id,
                    'date' => $entry->date,
                    'hours' => (float) $entry->hours,
                    'phase' => $entry->phase,
                ];
            })
            ->toArray();
    }

    public function nextMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonth();
        $this->loadEntries();
    }

    public function previousMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subMonth();
        $this->loadEntries();
    }

    public function getDayTotal($day)
    {
        $date = Carbon::parse($this->currentDate)->setDay($day)->toDateString();

        return collect($this->entries)
            ->filter(fn($entry) => Carbon::parse($entry['date'])->toDateString() === $date)
            ->sum('hours');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
