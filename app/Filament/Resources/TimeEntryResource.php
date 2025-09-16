<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Filament\Resources\TimeEntryResource\Pages\ImportTimeEntries;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Gestión de Tiempo';

    protected static ?string $label = 'hoja de tiempo';
    protected static ?string $pluralLabel = 'hojas de tiempo';
    protected static ?string $navigationLabel = 'Ingreso de Horas';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'manage_other_users_hours',
        ];
    }

    // Filtrar por usuario autenticado o seleccionado
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Si el usuario tiene permiso para gestionar horas de otros
        if (auth()->user()->can('manage_other_users_hours')) {
            return $query;
        }

        // Si no tiene permiso, solo ver sus propias horas
        return $query->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        $canManageOtherUsers = auth()->user()->can('manage_other_users_hours');

        return $form
            ->schema([
                Forms\Components\Section::make('Registrar Horas Trabajadas')
                    ->description('Ingresa los detalles de tu tiempo trabajado')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        // Selector de usuario si tiene permiso
                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id())
                            ->disabled(!$canManageOtherUsers)
                            ->visible($canManageOtherUsers)
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Selecciona el usuario para registrar sus horas'),

                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecciona un proyecto')
                            ->prefixIcon('heroicon-o-briefcase')
                            ->helperText('Selecciona el proyecto en el que trabajaste'),

                        Forms\Components\DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->prefixIcon('heroicon-o-calendar')
                            ->default(now())
                            ->maxDate(now())
                            ->helperText('Selecciona la fecha del trabajo realizado'),

                        Forms\Components\TextInput::make('hours')
                            ->label('Horas trabajadas')
                            ->required()
                            ->numeric()
                            ->minValue(0.5)
                            ->maxValue(24)
                            ->step(0.5)
                            ->prefixIcon('heroicon-o-clock')
                            ->suffix('hrs')
                            ->helperText('Mínimo 0.5 horas, máximo 24 horas. Formato decimal (ej: 1.5 = 1h 30min)'),

                        Forms\Components\Select::make('phase')
                            ->label('Fase del Proyecto')
                            ->options(TimeEntry::PHASES)
                            ->required()
                            ->searchable()
                            ->placeholder('Selecciona la fase')
                            ->prefixIcon('heroicon-o-chart-bar')
                            ->helperText('Indica la fase del proyecto en la que trabajaste'),

                        Forms\Components\RichEditor::make('description')
                            ->label('Descripción del trabajo')
                            ->placeholder('Describe las actividades realizadas...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull()
                            ->helperText('Proporciona detalles sobre las actividades realizadas')
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $canManageOtherUsers = auth()->user()->can('manage_other_users_hours');

        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                // Columna de usuario si tiene permiso
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->visible($canManageOtherUsers)
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-o-briefcase'),

                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas')
                    ->numeric(
                        decimalPlaces: 1,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->formatStateUsing(fn($state) => TimeEntry::PHASES[$state] ?? $state)
                    ->colors([
                        'primary' => 'inicio',
                        'warning' => 'planificacion',
                        'success' => 'ejecucion',
                        'danger' => 'control',
                        'secondary' => 'cierre',
                    ])
                    ->icon('heroicon-o-chart-bar')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->words(10)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),
            ])
            ->filters([
                // Filtro de usuario si tiene permiso
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->visible($canManageOtherUsers)
                    ->label('Usuario'),

                Filter::make('date')
                    ->label('Rango de Fechas')
                    ->form([
                        Forms\Components\DatePicker::make('start')
                            ->label('Desde')
                            ->default(fn() => now()->startOfMonth()),
                        Forms\Components\DatePicker::make('end')
                            ->label('Hasta')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['start'] && ! $data['end']) {
                            return null;
                        }

                        $start = $data['start'] ? date('d/m/Y', strtotime($data['start'])) : null;
                        $end = $data['end'] ? date('d/m/Y', strtotime($data['end'])) : null;

                        if ($start && $end) {
                            return "Del {$start} al {$end}";
                        }

                        return $start ? "Desde {$start}" : "Hasta {$end}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['end'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('No hay registros de tiempo')
            ->emptyStateDescription('Comienza registrando tu primer entrada de tiempo.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear registro')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
            'import' => Pages\ImportTimeEntries::route('/import'),
        ];
    }
}
