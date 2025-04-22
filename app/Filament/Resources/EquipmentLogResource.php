<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentLogResource\Pages;
use App\Models\EquipmentLog;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class EquipmentLogResource extends Resource
{
    protected static ?string $model = EquipmentLog::class;

    protected static ?string $navigationGroup = 'Recursos';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Recursos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de información básica
                Forms\Components\Section::make('Información General')
                    ->description('Información principal del registro')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/M/Y')
                            ->format('Y-m-d')  // Formato para la base de datos
                            ->columnSpan(1),

                        Forms\Components\Select::make('equipment_id')
                            ->label('Equipo')
                            ->relationship('equipment', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                    ]),

                // Sección de tiempos y horas
                Forms\Components\Section::make('Control de Horas')
                    ->description('Registro de tiempos y horas trabajadas')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('start_time')
                            ->label('Horometro Inicial')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->suffixIcon('heroicon-m-play')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('end_time')
                            ->label('Horometro Final')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->suffixIcon('heroicon-m-stop')
                            ->columnSpan(1),

                        // Campo oculto para horas motor con valor por defecto 0
                        Forms\Components\Hidden::make('engine_hours')
                            ->default(0),
                    ]),

                Forms\Components\Section::make('Kilometraje y Carga')
                    ->description('Registro de kilometraje y toneladas transportadas')
                    ->icon('heroicon-o-truck')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('initial_mileage')
                            ->label('Kilometraje Inicial')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->placeholder('0.00')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('final_mileage')
                            ->label('Kilometraje Final')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->placeholder('0.00')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('tons')
                            ->label('Toneladas')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->placeholder('0.00')
                            ->columnSpan(1),
                    ]),

                // Sección de demoras
                Forms\Components\Section::make('Registro de Demoras')
                    ->description('Información sobre demoras y sus causas')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('delay_hours')
                            ->label('Horas de Demora')
                            ->default(0)
                            ->numeric()
                            ->inputMode('decimal')
                            ->minValue(0)
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\Select::make('delay_activity')
                            ->label('Actividad de Demora')
                            ->options([
                                'CALENTAMIENTO' => 'CALENTAMIENTO',
                                'TRASLADO_EQUIPO' => 'TRASLADO DE EQUIPO',
                                'MANTENIMIENTO_PREVIO' => 'MANTENIMIENTO PREVIO',
                                'MANTENIMIENTO_PROGRAMADO' => 'MANTENIMIENTO PROGRAMADO',
                                'HORAS_MOTOR_MANTENIMIENTO' => 'HORAS MOTOR MANTENIMIENTO',
                                'HORAS_MOTOR_MANTENIMIENTO_NO_PROGRAMADO' => 'HORAS MOTOR MANTENIMIENTO NO PROGRAMADO',
                            ])
                            ->searchable()
                            ->native(false)
                            ->columnSpan(1),
                    ]),

                // Sección de consumo
                Forms\Components\Section::make('Consumo de Combustible')
                    ->description('Registro de consumo de diesel')
                    ->icon('heroicon-o-beaker')
                    ->columns(1)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('diesel_gal')
                            ->label('Diesel en Galones')
                            ->numeric()
                            ->inputMode('decimal')
                            ->step('0.01')
                            ->minValue(0)
                            ->suffixIcon('heroicon-m-beaker'),
                    ]),
            ]);
    }

    public static function calculateEndTime($endTime, $startTime)
    {
        if (is_numeric($endTime) && is_numeric($startTime)) {
            try {
                return number_format($endTime - $startTime, 2, '.', '');
            } catch (\Exception $e) {
                return 0;
            }
        }
        return 0;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('equipment.name')
                    ->label('Equipamiento')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Horometro Inicial')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Horometro Final')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('hours_worked')
                    ->label('Horas Trabajadas')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->state(function (EquipmentLog $record): float {
                        return self::calculateEndTime($record->end_time, $record->start_time);
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('delay_hours')
                    ->label('Horas de Demora')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('initial_mileage')
                    ->label('Km. Inicial')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('final_mileage')
                    ->label('Km. Final')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_mileage')
                    ->label('Km. Recorridos')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->state(function ($record) {
                        if ($record->final_mileage && $record->initial_mileage) {
                            return number_format($record->final_mileage - $record->initial_mileage, 2);
                        }
                        return 0;
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tons')
                    ->label('Toneladas')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('delay_activity')
                    ->label('Actividad de Demora')
                    ->toggleable()
                    ->colors([
                        'warning' => 'CALENTAMIENTO',
                        'danger' => 'TRASLADO_EQUIPO',
                        'success' => 'MANTENIMIENTO_PREVIO',
                        'primary' => 'MANTENIMIENTO_PROGRAMADO',
                        'info' => 'HORAS_MOTOR_MANTENIMIENTO',
                        'secondary' => 'HORAS_MOTOR_MANTENIMIENTO_NO_PROGRAMADO',
                    ])
                    ->icons([
                        'heroicon-m-arrow-path' => 'CALENTAMIENTO',
                        'heroicon-m-truck' => 'TRASLADO_EQUIPO',
                        'heroicon-m-wrench' => 'MANTENIMIENTO_PREVIO',
                        'heroicon-m-calendar' => 'MANTENIMIENTO_PROGRAMADO',
                        'heroicon-m-clock' => 'HORAS_MOTOR_MANTENIMIENTO',
                        'heroicon-m-exclamation-triangle' => 'HORAS_MOTOR_MANTENIMIENTO_NO_PROGRAMADO',
                    ])
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('diesel_gal')
                    ->label('Diesel en Galones')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ','
                    )
                    ->sortable()
                    ->toggleable(),
            ])
            ->toggleColumnsTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Mostrar/Ocultar Columnas')
            )
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->label('Editar')
                    ->color('primary')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentLogs::route('/'),
            'create' => Pages\CreateEquipmentLog::route('/create'),
            'edit' => Pages\EditEquipmentLog::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Recurso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Recursos';
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel . ' (' . static::getModel()::count() . ')';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
