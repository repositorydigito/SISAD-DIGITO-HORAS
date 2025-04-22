<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryUserBusinessLineReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\BusinessLine;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\MaxWidth;
use App\Exports\TimeEntryUserBusinessLineReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class TimeEntryUserBusinessLineReportResource extends Resource
{
    protected static ?string $model = TimeEntryUserBusinessLineReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte de Horas Usuario-Línea';
    protected static ?string $modelLabel = 'reporte de horas usuario-línea';
    protected static ?string $pluralModelLabel = 'reportes de horas usuario-línea';
    protected static ?string $slug = 'reportes-horas-usuario-linea';

    // Obtenemos todas las líneas de negocio
    protected static function getAllBusinessLines(): array
    {
        return BusinessLine::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $filters = $livewire->tableFilters['date_range'] ?? [];
        $currentPage = $livewire->tableFilters['user_page'] ?? 0;

        // Columnas base que siempre estarán presentes
        $columns = [
            Tables\Columns\TextColumn::make('user_name')
                ->label('Usuario')
                ->sortable()
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where('users.name', 'like', "%{$search}%");
                })
                ->icon('heroicon-o-user'),
        ];

        // Agregamos una columna por cada línea de negocio
        foreach (self::getAllBusinessLines() as $businessLineId => $businessLineName) {
            $columns[] = Tables\Columns\TextColumn::make("business_line_{$businessLineId}")
                ->label($businessLineName)
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
            ->query(function (Builder $query) use ($filters, $currentPage): Builder {
                // Si no hay filtros, retornamos una query vacía
                if (!isset($filters['from']) || !isset($filters['until'])) {
                    return TimeEntryUserBusinessLineReport::query()->whereRaw('1 = 0');
                }

                $startDate = Carbon::parse($filters['from'])->startOfDay();
                $endDate = Carbon::parse($filters['until'])->endOfDay();

                // Creamos las columnas para cada línea de negocio
                $businessLineColumns = collect(self::getAllBusinessLines())->map(function ($name, $id) {
                    return "SUM(CASE WHEN projects.business_line_id = {$id} THEN time_entries.hours ELSE 0 END) as business_line_{$id}";
                })->implode(', ');

                // Obtenemos el total de usuarios
                $totalUsers = \App\Models\User::count();

                // Calculamos el total de páginas
                $totalPages = ceil($totalUsers / 5);
                $currentPage = min($currentPage, $totalPages - 1);

                // Agregamos el offset y limit para la paginación
                $query = \App\Models\User::query()
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
                    ->orderBy('users.name')
                    ->offset($currentPage * 5)
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
                        $livewire->resetTable();
                    })
                    ->label('Filtrar por Fechas')
                    ->button(),



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
                            new TimeEntryUserBusinessLineReportExport($filters['from'], $filters['until']),
                            'reporte_horas_usuario_linea_' . date('Y-m-d') . '.xlsx'
                        );
                    })
                    ->visible(fn () => isset($filters['from']) && isset($filters['until']))
            ])
            ->striped()
            ->paginated(5)
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
            'index' => \App\Filament\Resources\TimeEntryUserBusinessLineReportResource\Pages\ListTimeEntryUserBusinessLineReports::route('/'),
        ];
    }
}
