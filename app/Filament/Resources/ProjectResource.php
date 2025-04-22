<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationGroup = 'Proyectos';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Proyecto')
                    ->description('Información básica del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ingrese el nombre del proyecto')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('code')
                                    ->label('Código Proyecto')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: PROJ-001'),

                                Forms\Components\Select::make('entity_id')
                                    ->label('Entidad')
                                    ->relationship('entity', 'business_name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('business_line_id')
                                    ->label('Linea de Negocio')
                                    ->relationship('businessLine', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripcion')
                                            ->maxLength(65535),
                                    ]),

                                Forms\Components\Select::make('category')
                                    ->label('Categoría')
                                    ->options([
                                        'PROYECTO' => 'PROYECTO',
                                        'BOLSA DE HORAS' => 'BOLSA DE HORAS',
                                        'ADENDA' => 'ADENDA',
                                        'FEE MENSUAL' => 'FEE MENSUAL',
                                    ])
                                    ->placeholder('Seleccione una categoría'),

                                Forms\Components\Select::make('state')
                                    ->label('Estado')
                                    ->options([
                                        'En Curso' => 'En Curso',
                                        'Completado' => 'Completado',
                                        'En Garantia' => 'En Garantia'
                                    ])
                                    ->placeholder('Seleccione un estado'),

                                Forms\Components\Select::make('phase')
                                    ->label('Fase')
                                    ->options([
                                        'Inicio' => 'Inicio',
                                        'Planificación' => 'Planificación',
                                        'Ejecución' => 'Ejecución',
                                        'Control' => 'Control',
                                        'Cierre' => 'Cierre',
                                    ])
                                    ->placeholder('Seleccione una fase')
                            ]),
                    ]),

                Forms\Components\Section::make('Fechas del Proyecto')
                    ->description('Establezca el período del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de Finalización')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date']),

                                Forms\Components\DatePicker::make('end_date_projected')
                                    ->label('Fecha de Finalización Proyectada')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date']),

                                Forms\Components\DatePicker::make('end_date_real')
                                    ->label('Fecha de Finalización Real')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date'])
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state, $get) {
                                        $endDate = $get('end_date');
                                        $endDateReal = $state;

                                        if (!$endDate || !$endDateReal) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        $endDateObj = Carbon::parse($endDate);
                                        $endDateRealObj = Carbon::parse($endDateReal);

                                        // Si la fecha real es anterior a la fecha proyectada, no hay desfase
                                        if ($endDateRealObj->lte($endDateObj)) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        // Calcular días laborables entre las dos fechas
                                        $delayDays = 0;
                                        $currentDate = clone $endDateObj;

                                        while ($currentDate->lt($endDateRealObj)) {
                                            // Si no es sábado (6) ni domingo (0)
                                            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                                                $delayDays++;
                                            }
                                            $currentDate->addDay();
                                        }

                                        $set('delay_days', $delayDays);
                                    }),

                                Forms\Components\TextInput::make('delay_days')
                                    ->label('Días de desfase')
                                    ->helperText('Días laborables entre la fecha de finalización y la fecha de finalización real')
                                    ->disabled()
                                    ->dehydrated(true) // Cambiado a true para que se guarde en la base de datos
                                    ->numeric()
                                    ->suffix(' días')
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                        if (!$record || !$record->end_date || !$record->end_date_real) {
                                            $component->state(0);
                                            return;
                                        }

                                        $endDateObj = Carbon::parse($record->end_date);
                                        $endDateRealObj = Carbon::parse($record->end_date_real);

                                        // Si la fecha real es anterior a la fecha proyectada, no hay desfase
                                        if ($endDateRealObj->lte($endDateObj)) {
                                            $component->state(0);
                                            return;
                                        }

                                        // Calcular días laborables entre las dos fechas
                                        $delayDays = 0;
                                        $currentDate = clone $endDateObj;

                                        while ($currentDate->lt($endDateRealObj)) {
                                            // Si no es sábado (6) ni domingo (0)
                                            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                                                $delayDays++;
                                            }
                                            $currentDate->addDay();
                                        }

                                        $component->state($delayDays);
                                    }),
                            ]),
                    ]),

                Forms\Components\Section::make('Progreso del Proyecto')
                    ->description('Información sobre el avance del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('real_progress')
                                    ->label('Progreso Real (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->placeholder('Ingrese el porcentaje de avance'),

                                Forms\Components\TextInput::make('planned_progress')
                                    ->label('Progreso Planificado (%)')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('%')
                                    ->numeric()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                        if (!$record || !$record->start_date || !$record->end_date) {
                                            $component->state(0);
                                            return;
                                        }

                                        $startDate = Carbon::parse($record->start_date);
                                        $endDate = Carbon::parse($record->end_date);
                                        $today = Carbon::now();

                                        // Si la fecha actual es anterior a la fecha de inicio, el progreso es 0%
                                        if ($today->lt($startDate)) {
                                            $component->state(0);
                                            return;
                                        }

                                        // Si la fecha actual es posterior a la fecha de finalización, el progreso es 100%
                                        if ($today->gt($endDate)) {
                                            $component->state(100);
                                            return;
                                        }

                                        // Calcular el progreso planificado
                                        $totalDays = $startDate->diffInDays($endDate) ?: 1; // Evitar división por cero
                                        $daysElapsed = $startDate->diffInDays($today);
                                        $plannedProgress = ($daysElapsed / $totalDays) * 100;

                                        $component->state(round($plannedProgress, 2));
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('billing')
                                    ->label('Facturación (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->placeholder('Ingrese el porcentaje de facturación')
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $billing = $state ?: 0;
                                        $pendingBilling = 100 - $billing;
                                        $set('pending_billing', max(0, round($pendingBilling, 2)));
                                    }),

                                Forms\Components\TextInput::make('pending_billing')
                                    ->label('Facturación Pendiente (%)')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('%')
                                    ->numeric()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                        if (!$record || !isset($record->billing)) {
                                            $component->state(100);
                                            return;
                                        }

                                        $pendingBilling = 100 - $record->billing;
                                        $component->state(max(0, round($pendingBilling, 2)));
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Components\TextInput $component, $state, callable $set, $get) {
                                        $billing = $get('billing') ?: 0;
                                        $pendingBilling = 100 - $billing;
                                        $component->state(max(0, round($pendingBilling, 2)));
                                    }),
                            ]),
                    ]),

                Forms\Components\Section::make('Descripción del Proyecto')
                    ->description('Detalle la información del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Descripción')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Incidencias')
                    ->description('Registro de incidencias del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\RichEditor::make('description_incidence')
                            ->label('Descripción de la Incidencia')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('reason_incidence')
                            ->label('Motivo de la Incidencia')
                            ->options([
                                'Externo' => 'Externo',
                                'Interno' => 'Interno',
                            ])
                            ->placeholder('Seleccione un motivo'),
                    ]),

                Forms\Components\Section::make('Riesgos')
                    ->description('Registro de riesgos del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\RichEditor::make('description_risk')
                            ->label('Descripción del Riesgo')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\Select::make('state_risk')
                            ->label('Estado del Riesgo')
                            ->options([
                                'Cerrado' => 'Cerrado',
                                'Controlado' => 'Controlado',
                                'Abierto' => 'Abierto',
                            ])
                            ->placeholder('Seleccione un estado'),
                    ]),

                Forms\Components\Section::make('Control de Cambios')
                    ->description('Registro de control de cambios del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\RichEditor::make('description_change_control')
                            ->label('Descripción del Control de Cambios')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Asignación de Usuarios')
                    ->description('Asignar usuarios al proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('users')
                            ->label('Usuarios Asignados')
                            ->multiple()
                            ->relationship('users', 'name')
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->label('Contraseña')
                                    ->password()
                                    ->required()
                                    ->minLength(8)
                                    ->maxLength(255),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable() // Ordenamiento por Nombre
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código Proyecto')
                    ->sortable() // Ordenamiento por Código de Proyecto
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity.business_name')
                    ->label('Entidad')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de Inicio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Activo' => 'success',
                        'Inactivo' => 'gray',
                        'Completado' => 'info',
                        'Suspendido' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('real_progress')
                    ->label('Progreso Real')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('planned_progress')
                    ->label('Progreso Planificado')
                    ->suffix('%')
                    ->state(function (Project $record): float {
                        if (!$record->start_date || !$record->end_date) {
                            return 0;
                        }

                        $startDate = Carbon::parse($record->start_date);
                        $endDate = Carbon::parse($record->end_date);
                        $today = Carbon::now();

                        // Si la fecha actual es anterior a la fecha de inicio, el progreso es 0%
                        if ($today->lt($startDate)) {
                            return 0;
                        }

                        // Si la fecha actual es posterior a la fecha de finalización, el progreso es 100%
                        if ($today->gt($endDate)) {
                            return 100;
                        }

                        // Calcular el progreso planificado
                        $totalDays = $startDate->diffInDays($endDate) ?: 1; // Evitar división por cero
                        $daysElapsed = $startDate->diffInDays($today);
                        $plannedProgress = ($daysElapsed / $totalDays) * 100;

                        return round($plannedProgress, 2);
                    }),
                Tables\Columns\TextColumn::make('billing')
                    ->label('Facturación')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_billing')
                    ->label('Facturación Pendiente')
                    ->suffix('%')
                    ->state(function (Project $record): float {
                        if (!isset($record->billing)) {
                            return 100;
                        }

                        $pendingBilling = 100 - $record->billing;
                        return max(0, round($pendingBilling, 2));
                    }),
                Tables\Columns\TextColumn::make('delay_days')
                    ->label('Días de desfase')
                    ->suffix(' días')
                    ->state(function (Project $record): int {
                        if (!$record->end_date || !$record->end_date_real) {
                            return 0;
                        }

                        $endDateObj = Carbon::parse($record->end_date);
                        $endDateRealObj = Carbon::parse($record->end_date_real);

                        // Si la fecha real es anterior a la fecha proyectada, no hay desfase
                        if ($endDateRealObj->lte($endDateObj)) {
                            return 0;
                        }

                        // Calcular días laborables entre las dos fechas
                        $delayDays = 0;
                        $currentDate = clone $endDateObj;

                        while ($currentDate->lt($endDateRealObj)) {
                            // Si no es sábado (6) ni domingo (0)
                            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                                $delayDays++;
                            }
                            $currentDate->addDay();
                        }

                        return $delayDays;
                    }),
                Tables\Columns\TextColumn::make('state_risk')
                    ->label('Riesgo')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Alto' => 'danger',
                        'Medio' => 'warning',
                        'Bajo' => 'success',
                        'Controlado' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('users.name')
                    ->label('Usuarios Asignados')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
            ])
            ->filters([
                // Aquí puedes agregar filtros si es necesario
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relacionamientos si es necesario
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Proyecto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Proyectos';
    }
}
