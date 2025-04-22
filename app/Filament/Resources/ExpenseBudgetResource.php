<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseBudgetResource\Pages;
use App\Models\ExpenseBudgetVersion;
use App\Models\CostCenter;
use App\Models\Category;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;

class ExpenseBudgetResource extends Resource
{
    protected static ?string $model = ExpenseBudgetVersion::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $modelLabel = 'Presupuesto de Gastos';
    protected static ?string $pluralModelLabel = 'Presupuestos de Gastos';
    protected static ?string $navigationGroup = 'Finanzas';
    protected static ?int $navigationSort = 2;

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
            ->schema([
                Select::make('year')
                    ->label('Año')
                    ->options(fn() => ExpenseBudgetVersion::distinct()->pluck('year')->toArray())
                    ->required(),

                Section::make('Seleccionar Centro de Costos')
                    ->schema([
                        CheckboxList::make('selected_centers')
                            ->label('Seleccione los centros de costo')
                            ->options(CostCenter::pluck('center_name', 'id'))
                            ->default(CostCenter::pluck('id'))
                            ->columns(2)
                            ->live()
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

                                ...collect(CostCenter::with('categories')->get())->map(
                                    fn($center) =>
                                    Section::make($center->center_name)
                                    ->schema([
                                        ...collect($center->categories)->map(
                                            fn($category) =>
                                            Grid::make(13)
                                                ->schema([
                                                    Placeholder::make('')
                                                        ->content($category->name)
                                                        ->extraAttributes(['class' => 'text-xs font-medium']),
                                                    ...collect(self::$months)->map(
                                                        fn($label, $month) =>
                                                        TextInput::make("expenseBudgets.{$center->id}.{$category->id}.{$month}_amount")
                                                            ->label('')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->mask('999999.99')
                                                            ->rules(['numeric', 'min:0'])
                                                    )->toArray(),
                                                ])
                                        )->toArray()
                                    ])
                                        ->visible(
                                            fn(callable $get): bool =>
                                            in_array($center->id, $get('selected_centers') ?? [])
                                        )
                                )->toArray()
                            ])
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->filters([
                SelectFilter::make('expense_budget')
                    ->label('Seleccione filtros')
                    ->form([
                        Select::make('year')
                            ->label('Seleccione año')
                            ->options(fn() => ExpenseBudgetVersion::distinct()->pluck('year', 'year')->toArray())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $set('version_id', null);
                            }),

                        Select::make('version_id')
                            ->label('Seleccione versión')
                            ->options(function (callable $get) {
                                $year = (int) $get('year');
                                if ($year) {
                                    return ExpenseBudgetVersion::where('year', $year)
                                        ->pluck('version_number', 'id')
                                        ->toArray();
                                }
                                return ['' => 'Seleccione un año primero'];
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['year'],
                                fn($query, $year) => $query->where('year', $year)
                            )
                            ->when(
                                $data['version_id'],
                                fn($query, $versionId) => $query->where('id', $versionId)
                            );
                    })
            ])
            ->headerActions([
                Tables\Actions\Action::make('aprobar')
                    ->label('APROBAR')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(function (HasTable $livewire) {
                        $filters = $livewire->tableFilters['expense_budget'] ?? [];
                        $version = ExpenseBudgetVersion::find($filters['version_id'] ?? null);
                        return $version && $version->status === 'draft';
                    })
                    ->action(function (HasTable $livewire) {
                        $filters = $livewire->tableFilters['expense_budget'] ?? [];
                        $version = ExpenseBudgetVersion::find($filters['version_id'] ?? null);

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
                $filters = $livewire->tableFilters['expense_budget'] ?? [];

                if (empty($filters['year'])) {
                    return view('filament.tables.expense-budget-matrix-empty', [
                        'message' => 'Seleccione un año',
                    ]);
                }

                if (empty($filters['version_id'])) {
                    return view('filament.tables.expense-budget-matrix-empty', [
                        'message' => 'Seleccione una versión',
                    ]);
                }

                $version = ExpenseBudgetVersion::with(['expenseBudgets.costCenter', 'expenseBudgets.category'])
                    ->find($filters['version_id']);

                if (!$version) {
                    return view('filament.tables.expense-budget-matrix-empty', [
                        'message' => 'No se encontró la versión seleccionada',
                    ]);
                }

                return view('filament.tables.expense-budget-matrix', [
                    'costCenters' => CostCenter::with('categories')->get(),
                    'months' => self::$months,
                    'expenseBudgets' => $version->expenseBudgets
                        ->groupBy(fn($budget) => $budget->cost_center_id . '-' . $budget->category_id),
                    'version' => $version,
                ]);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenseBudgets::route('/'),
            'create' => Pages\CreateExpenseBudget::route('/create'),
            'edit' => Pages\EditExpenseBudget::route('/{record}/edit'),
        ];
    }
}
