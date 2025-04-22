<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryProjectReport;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\MaxWidth;
use App\Exports\TimeEntryProjectReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TimeEntryProjectReportResource extends Resource
{
    protected static ?string $model = TimeEntryProjectReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte de Horas por Proyecto';
    protected static ?string $modelLabel = 'reporte de horas por proyecto';
    protected static ?string $pluralModelLabel = 'reportes de horas por proyecto';
    protected static ?string $slug = 'reportes-horas-proyecto';

    public static function table(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $filters = $livewire->tableFilters['date_range'] ?? [];
        $currentUserPage = (int)($livewire->tableFilters['user_page'] ?? 0);
        $currentProjectPage = (int)($livewire->tableFilters['project_page'] ?? 0);

        // Columnas base que siempre estarán presentes
        $columns = [
            Tables\Columns\TextColumn::make('project_name')
                ->label('Proyecto')
                ->searchable(['projects.name'])
                ->icon('heroicon-o-briefcase'),
        ];

        // Solo agregamos columnas dinámicas si hay filtros aplicados
        if (isset($filters['from']) && isset($filters['until'])) {
            $startDate = Carbon::parse($filters['from'])->startOfDay();
            $endDate = Carbon::parse($filters['until'])->endOfDay();

            // Obtenemos todos los usuarios
            $users = \App\Models\User::query()
                ->select('users.id', 'users.name')
                ->orderBy('users.name')
                ->get();

            // Calculamos el total de páginas de usuarios
            $totalUserPages = ceil($users->count() / 5);
            $currentUserPage = min($currentUserPage, $totalUserPages - 1);

            // Obtenemos los usuarios para la página actual
            $currentUsers = $users->slice($currentUserPage * 5, 5);

            // Agregamos una columna por cada usuario de la página actual
            foreach ($currentUsers as $user) {
                $columns[] = Tables\Columns\TextColumn::make("user_{$user->id}")
                    ->label($user->name)
                    ->numeric()
                    ->alignEnd()
                    ->summarize([]);
            }

            $columns[] = Tables\Columns\TextColumn::make('total_hours')
                ->label('Total')
                ->numeric()
                ->alignEnd()
                ->summarize([]);
        }

        return $table
            ->deferLoading()
            ->columns($columns)
            ->query(function (Builder $query) use ($filters, $currentUserPage, $currentProjectPage): Builder {
                // Si no hay filtros, retornamos una query vacía
                if (!isset($filters['from']) || !isset($filters['until'])) {
                    return TimeEntryProjectReport::query()->whereRaw('1 = 0');
                }

                $startDate = Carbon::parse($filters['from'])->startOfDay();
                $endDate = Carbon::parse($filters['until'])->endOfDay();

                // Obtenemos todos los usuarios
                $users = \App\Models\User::query()
                    ->select('users.id', 'users.name')
                    ->orderBy('users.name')
                    ->get();

                // Calculamos el total de páginas de usuarios
                $totalUserPages = ceil($users->count() / 5);
                $currentUserPage = min($currentUserPage, $totalUserPages - 1);

                // Obtenemos los usuarios para la página actual
                $currentUsers = $users->slice($currentUserPage * 5, 5);

                // Creamos las columnas para cada usuario de la página actual
                $userColumns = $currentUsers->map(function ($user) {
                    return "SUM(CASE WHEN time_entries.user_id = {$user->id} THEN time_entries.hours ELSE 0 END) as user_{$user->id}";
                })->implode(', ');

                // Construimos la consulta principal
                $query = TimeEntryProjectReport::query()
                    ->join('projects', 'projects.id', '=', 'time_entries.project_id')
                    ->select([
                        'projects.id',
                        'projects.name as project_name',
                        DB::raw($userColumns),
                        DB::raw('COALESCE(SUM(time_entries.hours), 0) as total_hours'),
                    ])
                    ->whereBetween('time_entries.date', [$startDate, $endDate])
                    ->groupBy('projects.id', 'projects.name')
                    ->orderBy('projects.name')
                    ->offset($currentProjectPage * 5)
                    ->limit(5);

                return $query;
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
                        $livewire->tableFilters['user_page'] = 0;
                        $livewire->tableFilters['project_page'] = 0;
                        $livewire->resetTable();
                    })
                    ->label('Filtrar por Fechas')
                    ->button(),

                Tables\Actions\Action::make('next_users')
                    ->label('Siguientes Usuarios')
                    ->icon('heroicon-o-chevron-right')
                    ->action(function () use ($livewire) {
                        $currentFilters = $livewire->tableFilters;
                        $currentPage = (int)($currentFilters['user_page'] ?? 0);
                        $currentFilters['user_page'] = $currentPage + 1;
                        $livewire->tableFilters = $currentFilters;
                        $livewire->resetTable();
                    })
                    ->visible(function () use ($livewire) {
                        if (!isset($livewire->tableFilters['date_range'])) {
                            return false;
                        }

                        $totalUsers = \App\Models\User::count();
                        $currentPage = (int)($livewire->tableFilters['user_page'] ?? 0);
                        return $totalUsers > 5 && $currentPage < ceil($totalUsers / 5) - 1;
                    }),

                Tables\Actions\Action::make('prev_users')
                    ->label('Usuarios Anteriores')
                    ->icon('heroicon-o-chevron-left')
                    ->action(function () use ($livewire) {
                        $currentFilters = $livewire->tableFilters;
                        $currentPage = (int)($currentFilters['user_page'] ?? 0);
                        $currentFilters['user_page'] = max(0, $currentPage - 1);
                        $livewire->tableFilters = $currentFilters;
                        $livewire->resetTable();
                    })
                    ->visible(function () use ($livewire) {
                        if (!isset($livewire->tableFilters['date_range'])) {
                            return false;
                        }

                        return (int)($livewire->tableFilters['user_page'] ?? 0) > 0;
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
                            new TimeEntryProjectReportExport($filters['from'], $filters['until']),
                            'reporte_horas_proyecto_' . date('Y-m-d') . '.xlsx'
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
            'index' => \App\Filament\Resources\TimeEntryProjectReportResource\Pages\ListTimeEntryProjectReports::route('/'),
        ];
    }
}
