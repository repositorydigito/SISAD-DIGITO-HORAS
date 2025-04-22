<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesTargetResource\Pages;
use App\Models\SalesTarget;
use App\Models\BusinessLine;
use App\Models\SalesTargetVersion;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;

class SalesTargetResource extends Resource
{
    protected static ?string $model = SalesTargetVersion::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Meta de Venta';
    protected static ?string $pluralModelLabel = 'Metas de Venta';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?int $navigationSort = 1;

    public static array $months = [
        'january' => 'Enero',
        'february' => 'Febrero',
        'march' => 'Marzo',
        'april' => 'Abril',
        'may' => 'Mayo',
        'june' => 'Junio',
        'july' => 'Julio',
        'august' => 'Agosto',
        'september' => 'Setiembre',
        'october' => 'Octubre',
        'november' => 'Noviembre',
        'december' => 'Diciembre'
    ];

    public static function form(Form $form): Form
    {
        $years = range(2024, date('Y') + 5);

        return $form
            ->disabled(fn($record) => $record && $record->status === 'approved')
            ->schema([
                Select::make('year')
                    ->label('Año')
                    ->options(array_combine($years, $years))
                    ->required(),

                Section::make('Agregar línea de negocio')
                    ->schema([
                        CheckboxList::make('selected_lines')
                            ->label('Seleccione las líneas de negocio')
                            ->options(BusinessLine::pluck('name', 'id'))
                            ->default(BusinessLine::pluck('id'))
                            ->columns(2)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('show_lines', $state);
                            }),
                    ])->collapsed(),

                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Grid::make(13)
                                    ->schema([
                                        Placeholder::make('')
                                            ->content('')
                                            ->extraAttributes(['class' => 'text-sm font-semibold']),
                                        ...collect(self::$months)->map(
                                            fn($label) =>
                                            Placeholder::make('')
                                                ->content($label)
                                                ->extraAttributes(['class' => 'text-center font-medium'])
                                        )->toArray(),
                                    ]),

                                ...collect(BusinessLine::all())->map(
                                    fn($line) =>
                                    Grid::make(13)
                                        ->schema([
                                            Placeholder::make('')
                                                ->content($line->name)
                                                ->extraAttributes(['class' => 'text-xs font-medium']),
                                            ...collect(self::$months)->map(
                                                fn($label, $month) =>
                                                TextInput::make("salesTargets.{$line->id}.{$month}_amount")
                                                    ->label('')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->minValue(0)
                                                    ->mask('999999.99')
                                                    ->rules(['numeric', 'min:0'])
                                            )->toArray(),
                                        ])
                                        ->visible(
                                            fn(callable $get): bool =>
                                            in_array($line->id, $get('selected_lines') ?? [])
                                        )
                                )->toArray()
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                SelectFilter::make('sales_target')
                    ->label('Seleccione filtros')
                    ->form([
                        Select::make('year')
                            ->label('Seleccione año')
                            ->options(fn() => SalesTargetVersion::distinct()->pluck('year', 'year')->toArray())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $set('version_id', null);
                            }),

                        Select::make('version_id')
                            ->label('Seleccione versión')
                            ->options(function (callable $get) {
                                $year = (int) $get('year');
                                if ($year) {
                                    return SalesTargetVersion::where('year', $year)
                                        ->pluck('version_number', 'id')
                                        ->toArray();
                                }
                                return ['' => 'Seleccione un año primero'];
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $year = (int) $data['year'] ?? null;
                        $versionId = (int) $data['version_id'] ?? null;

                        if (!empty($year)) {
                            $query->where('year', $year);
                        }

                        if (!empty($versionId)) {
                            $query->where('id', $versionId);
                        }
                    }),
            ])
            ->headerActions([


                Tables\Actions\Action::make('aprobar')
                    ->label('APROBAR')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(function (HasTable $livewire) {
                        $year = $livewire->tableFilters['sales_target']['year'] ?? null;
                        $versionId = $livewire->tableFilters['sales_target']['version_id'] ?? null;

                        if (!$year || !$versionId) {
                            return false;
                        }

                        $version = SalesTargetVersion::where('year', $year)
                            ->where('id', $versionId)
                            ->first();

                        return $version && $version->status === 'draft';
                    })
                    ->action(function (HasTable $livewire) {
                        $year = $livewire->tableFilters['sales_target']['year'] ?? null;
                        $versionId = $livewire->tableFilters['sales_target']['version_id'] ?? null;

                        if (!$year || !$versionId) {
                            return;
                        }

                        $version = SalesTargetVersion::where('year', $year)
                            ->where('id', $versionId)
                            ->first();

                        if ($version && $version->status === 'draft') {
                            $version->update([
                                'status' => 'approved',
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]);
                        }
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->button()
                    ->color('warning')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->visible(fn($record) => $record->status === 'draft'),

                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->visible(fn($record) => $record->status === 'draft')
            ])
            ->content(function (HasTable $livewire) {
                $year = $livewire->tableFilters['sales_target']['year'] ?? null;
                $versionId = $livewire->tableFilters['sales_target']['version_id'] ?? null;

                if (!$year) {
                    return view('filament.tables.sales-target-matrix-empty', [
                        'message' => 'Seleccione un año',
                    ]);
                }

                if (!$versionId) {
                    return view('filament.tables.sales-target-matrix-empty', [
                        'message' => 'Seleccione una versión',
                    ]);
                }

                $version = SalesTargetVersion::where('year', $year)
                    ->where('id', $versionId)
                    ->with(['salesTargets.businessLine'])
                    ->first();

                if (!$version) {
                    return view('filament.tables.sales-target-matrix-empty', [
                        'message' => 'No se encontró la versión seleccionada',
                    ]);
                }

                return view('filament.tables.sales-target-matrix', [
                    'businessLines' => BusinessLine::all(),
                    'months' => self::$months,
                    'salesTargets' => $version->salesTargets->groupBy('business_line_id'),
                    'version' => $version,
                ]);
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesTargets::route('/'),
            'create' => Pages\CreateSalesTarget::route('/create'),
            'edit' => Pages\EditSalesTarget::route('/{record}/edit'),
        ];
    }
}
