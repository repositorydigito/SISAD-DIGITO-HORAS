<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryUserProjectReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Project;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\MaxWidth;
use App\Exports\TimeEntryUserProjectReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class TimeEntryUserProjectReportResource extends Resource
{
    protected static ?string $model = TimeEntryUserProjectReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte de Horas Usuario-Proyecto';
    protected static ?string $modelLabel = 'reporte de horas usuario-proyecto';
    protected static ?string $pluralModelLabel = 'reportes de horas usuario-proyecto';
    protected static ?string $slug = 'reportes-horas-usuario-proyecto';

    public static function table(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $filters = $livewire->tableFilters['date_range'] ?? [];
        $currentPage = $livewire->tableFilters['project_page'] ?? 0;

        // Columnas base que siempre estarán presentes
        $columns = [
            Tables\Columns\TextColumn::make('user_name')
                ->label('Usuario')
                ->sortable()
                ->searchable(['users.name']) // Agregamos el campo 'name' de la tabla 'users' al campo de busqueda
                ->icon('heroicon-o-user'),
        ];

        // Obtenemos todos los proyectos activos
        $projects = Project::orderBy('name')->get();

        // Calculamos el total de páginas de proyectos
        $totalPages = ceil($projects->count() / 5);
        $currentPage = min($currentPage, $totalPages - 1);

        // Obtenemos los proyectos para la página actual
        $currentProjects = $projects->slice($currentPage * 5, 5);

        // Agregamos una columna por cada proyecto de la página actual
        foreach ($currentProjects as $project) {
            $columns[] = Tables\Columns\TextColumn::make("project_{$project->id}")
                ->label($project->name)
                ->numeric()
                ->alignEnd()
                ->summarize([
                    Tables\Columns\Summarizers\Sum::make()
                        ->label('Total')
                        ->numeric(decimalPlaces: 2)
                        ->suffix(' hrs')
                ]);
        }

        // Agregamos la columna de total
        $columns[] = Tables\Columns\TextColumn::make('total_hours')
            ->label('Total')
            ->numeric()
            ->alignEnd()
            ->summarize([
                Tables\Columns\Summarizers\Sum::make()
                    ->label('Total General')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' hrs')
            ]);

        return $table
            ->deferLoading()
            ->columns($columns)
            ->query(function (Builder $query) use ($filters, $currentProjects): Builder {
                // Si no hay filtros, retornamos una query vacía
                if (!isset($filters['from']) || !isset($filters['until'])) {
                    return TimeEntryUserProjectReport::query()->whereRaw('1 = 0');
                }

                $startDate = Carbon::parse($filters['from'])->startOfDay();
                $endDate = Carbon::parse($filters['until'])->endOfDay();

                // Creamos las columnas para cada proyecto de la página actual
                $projectColumns = $currentProjects->map(function ($project) {
                    return "SUM(CASE WHEN time_entries.project_id = {$project->id} THEN time_entries.hours ELSE 0 END) as project_{$project->id}";
                })->implode(', ');

                // Modificamos la consulta para usar LEFT JOIN y mostrar todos los usuarios
                return \App\Models\User::query()
                    ->select([
                        'users.id',
                        'users.name as user_name',
                        DB::raw($projectColumns),
                        DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
                    ])
                    ->leftJoin('time_entries', function ($join) use ($startDate, $endDate) {
                        $join->on('users.id', '=', 'time_entries.user_id')
                            ->whereBetween('time_entries.date', [$startDate, $endDate]);
                    })
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('users.name');
            })
            ->headerActions([
                Tables\Actions\Action::make('filter_dates')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('Desde')
                                    ->required()
                                    ->maxDate(now())
                                    ->minDate('2000-01-01')
                                    ->default(null)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (!$state || !$get('until')) return;

                                        if (Carbon::parse($state)->gt(Carbon::parse($get('until')))) {
                                            $set('from', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('Error')
                                                ->body('La fecha inicial no puede ser mayor que la fecha final')
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('until')
                                    ->label('Hasta')
                                    ->required()
                                    ->maxDate(now())
                                    ->minDate('2000-01-01')
                                    ->default(null)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (!$state || !$get('from')) return;

                                        if (Carbon::parse($get('from'))->gt(Carbon::parse($state))) {
                                            $set('until', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('Error')
                                                ->body('La fecha final no puede ser menor que la fecha inicial')
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Filtrar por Rango de Fechas')
                    ->modalDescription('Seleccione el rango de fechas para el reporte')
                    ->modalSubmitActionLabel('Aplicar Filtros')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (array $data) use ($livewire): void {
                        // Validación final antes de aplicar los filtros
                        if (!isset($data['from']) || !isset($data['until'])) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Ambas fechas son requeridas')
                                ->send();
                            return;
                        }

                        $from = Carbon::parse($data['from']);
                        $until = Carbon::parse($data['until']);

                        if ($from->gt($until)) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('La fecha inicial no puede ser mayor que la fecha final')
                                ->send();
                            return;
                        }

                        $livewire->tableFilters['date_range'] = $data;
                        $livewire->resetTable();
                    })
                    ->label('Filtrar por Fechas')
                    ->button(),

                Tables\Actions\Action::make('next_projects')
                    ->label('Siguientes Proyectos')
                    ->icon('heroicon-o-chevron-right')
                    ->action(function () use ($livewire) {
                        $currentPage = ($livewire->tableFilters['project_page'] ?? 0) + 1;
                        $livewire->tableFilters['project_page'] = $currentPage;
                        $livewire->resetTable();
                    })
                    ->visible(function () use ($livewire) {
                        if (!isset($livewire->tableFilters['date_range'])) {
                            return false;
                        }

                        $totalProjects = Project::count();
                        return $totalProjects > 5 &&
                            ($livewire->tableFilters['project_page'] ?? 0) <
                            ceil($totalProjects / 5) - 1;
                    }),

                Tables\Actions\Action::make('prev_projects')
                    ->label('Proyectos Anteriores')
                    ->icon('heroicon-o-chevron-left')
                    ->action(function () use ($livewire) {
                        $currentPage = max(0, ($livewire->tableFilters['project_page'] ?? 0) - 1);
                        $livewire->tableFilters['project_page'] = $currentPage;
                        $livewire->resetTable();
                    })
                    ->visible(function () use ($livewire) {
                        if (!isset($livewire->tableFilters['date_range'])) {
                            return false;
                        }

                        $totalProjects = Project::count();
                        return $totalProjects > 5 &&
                            ($livewire->tableFilters['project_page'] ?? 0) > 0;
                    }),

                Tables\Actions\Action::make('download')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () use ($filters) {
                        if (!isset($filters['from']) || !isset($filters['until'])) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Debe aplicar un filtro de fechas antes de exportar')
                                ->send();
                            return;
                        }

                        return Excel::download(
                            new TimeEntryUserProjectReportExport($filters['from'], $filters['until']),
                            'reporte_horas_usuario_proyecto_' . date('Y-m-d') . '.xlsx'
                        );
                    })
                    ->visible(fn () => isset($filters['from']) && isset($filters['until']))
            ])
            ->striped()
            ->paginated()
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->filtersFormWidth(MaxWidth::SevenExtraLarge)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TimeEntryUserProjectReportResource\Pages\ListTimeEntryUserProjectReports::route('/'),
        ];
    }
}
