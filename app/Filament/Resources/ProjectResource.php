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
                                    ->placeholder('Seleccione una categoría')
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('validity', null)),

                                Forms\Components\Select::make('validity')
                                    ->label('Vigencia')
                                    ->options([
                                        'Vigente' => 'Vigente',
                                        'Sin Vigencia' => 'Sin Vigencia',
                                    ])
                                    ->placeholder('Seleccione la vigencia')
                                    ->visible(fn (Forms\Get $get): bool => $get('category') === 'BOLSA DE HORAS')
                                    ->required(fn (Forms\Get $get): bool => $get('category') === 'BOLSA DE HORAS')
                                    ->helperText('Este campo solo aplica para proyectos de tipo BOLSA DE HORAS'),

                                Forms\Components\Select::make('state')
                                    ->label('Estado')
                                    ->options([
                                        'En Curso' => 'En Curso',
                                        'Completado' => 'Completado',
                                        'En Garantia' => 'En Garantia',
                                        'Bloqueado' => 'Bloqueado',
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
                                    ->label('Fecha de Inicio Planificada (según cronograma)')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de Finalización Planificada (según cronograma)')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date'])
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state, $get) {
                                        $endDate = $state;
                                        $endDateProjected = $get('end_date_projected');

                                        if (!$endDate || !$endDateProjected) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        $endDateObj = Carbon::parse($endDate);
                                        $endDateProjectedObj = Carbon::parse($endDateProjected);

                                        // Si la fecha proyectada es anterior a la fecha planificada, no hay desfase
                                        if ($endDateProjectedObj->lte($endDateObj)) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        // Calcular días laborables entre las dos fechas
                                        $delayDays = 0;
                                        $currentDate = clone $endDateObj;

                                        while ($currentDate->lt($endDateProjectedObj)) {
                                            // Si no es sábado (6) ni domingo (0)
                                            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                                                $delayDays++;
                                            }
                                            $currentDate->addDay();
                                        }

                                        $set('delay_days', $delayDays);
                                    }),

                                Forms\Components\DatePicker::make('end_date_projected')
                                    ->label('Fecha de Finalización Proyectada')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date'])
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state, $get) {
                                        $endDate = $get('end_date');
                                        $endDateProjected = $state;

                                        if (!$endDate || !$endDateProjected) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        $endDateObj = Carbon::parse($endDate);
                                        $endDateProjectedObj = Carbon::parse($endDateProjected);

                                        // Si la fecha proyectada es anterior a la fecha planificada, no hay desfase
                                        if ($endDateProjectedObj->lte($endDateObj)) {
                                            $set('delay_days', 0);
                                            return;
                                        }

                                        // Calcular días laborables entre las dos fechas
                                        $delayDays = 0;
                                        $currentDate = clone $endDateObj;

                                        while ($currentDate->lt($endDateProjectedObj)) {
                                            // Si no es sábado (6) ni domingo (0)
                                            if (!in_array($currentDate->dayOfWeek, [0, 6])) {
                                                $delayDays++;
                                            }
                                            $currentDate->addDay();
                                        }

                                        $set('delay_days', $delayDays);
                                    }),

                                Forms\Components\DatePicker::make('end_date_real')
                                    ->label('Fecha de Finalización Real')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date']),

                                Forms\Components\TextInput::make('delay_days')
                                    ->label('Días de desfase')
                                    ->helperText('Días laborables entre la fecha de finalización planificada y la fecha de finalización proyectada')
                                    ->disabled()
                                    ->dehydrated(true) // Cambiado a true para que se guarde en la base de datos
                                    ->numeric()
                                    ->suffix(' días')
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                        if (!$record || !$record->end_date || !$record->end_date_projected) {
                                            $component->state(0);
                                            return;
                                        }

                                        $endDateObj = Carbon::parse($record->end_date);
                                        $endDateProjectedObj = Carbon::parse($record->end_date_projected);

                                        // Si la fecha proyectada es anterior a la fecha planificada, no hay desfase
                                        if ($endDateProjectedObj->lte($endDateObj)) {
                                            $component->state(0);
                                            return;
                                        }

                                        // Calcular días laborables entre las dos fechas
                                        $delayDays = 0;
                                        $currentDate = clone $endDateObj;

                                        while ($currentDate->lt($endDateProjectedObj)) {
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

                Forms\Components\Section::make('Hitos del Proyecto')
                    ->description('Gestione los hitos del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('milestones')
                            ->relationship()
                            ->label('Hitos')
                            ->orderColumn('order')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Hito 1 - Entrega de diseño'),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Fecha de Inicio')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection(),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('Fecha de Finalización')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->afterOrEqual('start_date')
                                            ->rules(['after_or_equal:start_date']),
                                        Forms\Components\TextInput::make('progress')
                                            ->label('Progreso del Hito (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->placeholder('Ingrese el progreso del hito')
                                            ->default(0)
                                            ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : 0)
                                            ->formatStateUsing(fn ($state) => $state ? $state * 100 : 0),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('billing_percentage')
                                            ->label('Facturación (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->required(),

                                        Forms\Components\Select::make('status')
                                            ->label('Estado')
                                            ->options([
                                                'Pendiente' => 'Pendiente',
                                                'En Progreso' => 'En Progreso',
                                                'Completado' => 'Completado',
                                                'Retrasado' => 'Retrasado',
                                            ])
                                            ->required()
                                            ->default('Pendiente'),

                                        Forms\Components\TextInput::make('total_hours')
                                            ->label('Horas Registradas')
                                            ->numeric()
                                            ->disabled()
                                            ->suffix(' h')
                                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record) {
                                                if ($record) {
                                                    $component->state($record->total_hours ?? 0);
                                                } else {
                                                    $component->state(0);
                                                }
                                            })
                                            ->dehydrated(false),
                                    ]),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(3)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->collapseAllAction(
                                fn (Forms\Components\Actions\Action $action) => $action->label('Colapsar todos'),
                            )
                            ->expandAllAction(
                                fn (Forms\Components\Actions\Action $action) => $action->label('Expandir todos'),
                            ),
                    ]),

                    Forms\Components\Section::make('Hitos de Facturación')
                    ->description('Gestione los hitos de facturación')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('billing_milestones')
                            ->relationship('billingMilestones')
                            ->label('Hitos')
                            ->orderColumn('order')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\DatePicker::make('planned_date')
                                            ->label('Fecha de Pago Planificada')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->reactive(),

                                        Forms\Components\DatePicker::make('real_date')
                                            ->label('Fecha de Pago Real')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->closeOnDateSelection()
                                            ->reactive(),
                 
                                        Forms\Components\TextInput::make('progress')
                                            ->label('Porcentaje del Hito (%)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->placeholder('Ingrese el porcentaje del hito')
                                            ->default(0)
                                            ->dehydrateStateUsing(fn ($state) => $state ? $state / 100 : 0)
                                            ->formatStateUsing(fn ($state) => $state ? $state * 100 : 0),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('amount')
                                            ->label('Monto ($)')
                                            ->numeric(),

                                            Forms\Components\Select::make('status')
                                            ->label('Estado')
                                            ->options([
                                                'Pago con retraso' => 'Pago con retraso',
                                                'Pago a tiempo' => 'Pago a tiempo',
                                                'Futuro' => 'Futuro',
                                                'Retrasado' => 'Retrasado',
                                            ])
                                            ->required()
                                            ->reactive()
                                            ->afterStateHydrated(function (\Filament\Forms\Set $set, $state, \Filament\Forms\Get $get) {
                                                // Si ya hay estado guardado, no lo cambiamos automáticamente
                                                if ($state) return;
                                        
                                                $plannedDate = $get('planned_date');
                                                $realDate = $get('real_date');
                                                $today = now()->startOfDay();
                                        
                                                if ($realDate) {
                                                    if (\Carbon\Carbon::parse($realDate)->greaterThan(\Carbon\Carbon::parse($plannedDate))) {
                                                        $set('status', 'Pago con retraso');
                                                    } else {
                                                        $set('status', 'Pago a tiempo');
                                                    }
                                                } elseif ($plannedDate) {
                                                    if (\Carbon\Carbon::parse($plannedDate)->greaterThan($today)) {
                                                        $set('status', 'Futuro');
                                                    } else {
                                                        $set('status', 'Retrasado');
                                                    }
                                                }
                                            })
                                            ->afterStateUpdated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                                                $plannedDate = $get('planned_date');
                                                $realDate = $get('real_date');
                                                $today = now()->startOfDay();
                                        
                                                if ($realDate) {
                                                    if (\Carbon\Carbon::parse($realDate)->greaterThan(\Carbon\Carbon::parse($plannedDate))) {
                                                        $set('status', 'Pago con retraso');
                                                    } else {
                                                        $set('status', 'Pago a tiempo');
                                                    }
                                                } elseif ($plannedDate) {
                                                    if (\Carbon\Carbon::parse($plannedDate)->greaterThan($today)) {
                                                        $set('status', 'Futuro');
                                                    } else {
                                                        $set('status', 'Retrasado');
                                                    }
                                                }
                                            }),
                                    ]),

                                Forms\Components\Textarea::make('comments')
                                    ->label('Comentarios')
                                    ->rows(3)
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->reorderable()
                            ->cloneable()
                            ->collapsible()
                            ->collapseAllAction(
                                fn (Forms\Components\Actions\Action $action) => $action->label('Colapsar todos'),
                            )
                            ->expandAllAction(
                                fn (Forms\Components\Actions\Action $action) => $action->label('Expandir todos'),
                            ),
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
                Tables\Columns\TextColumn::make('validity')
                    ->label('Vigencia')
                    ->sortable()
                    ->searchable()
                    ->visible(fn ($record): bool => $record && $record->category === 'BOLSA DE HORAS'),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'En Curso' => 'success',
                        'En Garantia' => 'gray',
                        'Completado' => 'info',
                        'Bloqueado' => 'warning',
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
                        if (!$record->end_date || !$record->end_date_projected) {
                            return 0;
                        }

                        $endDateObj = Carbon::parse($record->end_date);
                        $endDateProjectedObj = Carbon::parse($record->end_date_projected);

                        // Si la fecha proyectada es anterior a la fecha planificada, no hay desfase
                        if ($endDateProjectedObj->lte($endDateObj)) {
                            return 0;
                        }

                        // Calcular días laborables entre las dos fechas
                        $delayDays = 0;
                        $currentDate = clone $endDateObj;

                        while ($currentDate->lt($endDateProjectedObj)) {
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
                Tables\Filters\SelectFilter::make('business_line_id')
                    ->label('Línea de Negocio')
                    ->relationship('businessLine', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options([
                        'PROYECTO' => 'PROYECTO',
                        'BOLSA DE HORAS' => 'BOLSA DE HORAS',
                        'ADENDA' => 'ADENDA',
                        'FEE MENSUAL' => 'FEE MENSUAL',
                    ]),
                Tables\Filters\SelectFilter::make('state')
                    ->label('Estado')
                    ->options([
                        'En Curso' => 'En Curso',
                        'Completado' => 'Completado',
                        'En Garantia' => 'En Garantia',
                        'Bloqueado' => 'Bloqueado',
                    ]),

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
