<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Gastos';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Listado';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gastos';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationItems(): array
    {
        return [
            \Filament\Navigation\NavigationItem::make('Listado')
                ->icon('heroicon-o-list-bullet')
                ->url('/admin/expenses')
                ->group('Gastos')
                ->sort(1),

            \Filament\Navigation\NavigationItem::make('Por pagar')
                ->icon('heroicon-o-document')
                ->url('/admin/expenses/billing')
                ->badge(static::getModel()::where('status', 'por pagar')->count())
                ->group('Gastos')
                ->sort(2),

            \Filament\Navigation\NavigationItem::make('Por reembolsar')
                ->icon('heroicon-o-currency-dollar')
                ->url('/admin/expenses/receivable')
                ->badge(static::getModel()::where('status', 'por reembolsar')->count())
                ->group('Gastos')
                ->sort(3),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos')
                    ->description('Información principal del registro')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Select::make('entity_id')
                            ->label('Razón Social')
                            ->relationship('entity', 'business_name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'Recibo por Honorarios' => 'Recibo por Honorarios',
                                'Recibo de Compra' => 'Recibo de Compra',
                                'Nota de crédito' => 'Nota de crédito',
                                'Boleta de pago' => 'Boleta de pago',
                                'Nota de Pago' => 'Nota de Pago',
                                'Sin Documento' => 'Sin Documento',
                                'Ticket' => 'Ticket',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->maxLength(50)
                            ->prefix('#'),

                        Forms\Components\DatePicker::make('document_date')
                            ->label('Fecha de Documento'),

                            Forms\Components\Select::make('cost_center_id')
                            ->label('Centro de Costos')
                            ->relationship(
                                'costcenter', 
                                'center_name',
                                fn (Builder $query) => $query->orderBy('center_name')
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('category_id', null))
                            ->required()
                            ->helperText('Seleccione un centro de costos para ver las categorías disponibles')
                            
                            ,

                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                            Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->options(function (Get $get) {
                                $costCenterId = $get('cost_center_id');
                                
                                if (!$costCenterId) {
                                    return [];
                                }
                                
                                return \App\Models\Category::query()
                                    ->where('cost_center_id', $costCenterId)
                                    ->orderBy('category_name')
                                    ->pluck('category_name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (Get $get): bool => !$get('cost_center_id'))
                            ->helperText(function (Get $get) {
                                if (!$get('cost_center_id')) {
                                    return 'Primero seleccione un centro de costos';
                                }
                                return 'Seleccione una categoría del centro de costos';
                            })
                            ->live(),

                        Forms\Components\TextInput::make('remark')
                            ->label('Observación')
                            ->maxLength(255),
                    ])
                    ->columns(2), // Ajustado a 2 columnas para una mejor disposición

                Forms\Components\Section::make('Moneda')
                    ->description('Información de montos y tipo de cambio')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'Soles' => 'Soles',
                                'Dolares' => 'Dolares',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('amount_pen')
                            ->label('Monto Soles')
                            ->numeric()
                            ->prefix('S/.'),

                        Forms\Components\TextInput::make('amount_usd')
                            ->label('Monto Dolares')
                            ->numeric()
                            ->prefix('$'),

                        Forms\Components\TextInput::make('exchange_rate')
                            ->label('Tipo de Cambio')
                            ->numeric(),

                        Forms\Components\TextInput::make('withholding_amount')
                            ->label('Monto de Detraccion')
                            ->numeric(),

                        Forms\Components\TextInput::make('amount_to_pay')
                            ->label('Monto a Pagar')
                            ->numeric(),

                    ])
                    ->columns(2), // Ajustado a 2 columnas

                Forms\Components\Section::make('Información Adicional')
                    ->description('Datos complementarios del gasto')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'por revisar' => 'Por Revisar',
                                'por pagar' => 'Por Pagar',
                                'por pagar detraccion' => 'Por Pagar Detracción',
                                'por reembolsar' => 'Por Reembolsar',
                                'pagado' => 'Pagado',
                            ])
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Estado de Pago')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'pagado' => 'Pagado',
                                'anulado' => 'Anulado',
                            ])
                            ->nullable(),

                        Forms\Components\DatePicker::make('planned_payment_date')
                            ->label('Fecha de Pago Planificado'),

                        Forms\Components\DatePicker::make('actual_payment_date')
                            ->label('Fecha de Pago Real'),

                        Forms\Components\Select::make('expense_type')
                            ->label('Tipo de Costo')
                            ->options([
                                'fijo' => 'Fijo',
                                'variable' => 'Variable',
                            ])
                            ->nullable(),

                        Forms\Components\RichEditor::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('has_attachment')
                            ->label('Tiene Adjunto')
                            ->inline(false),

                        Forms\Components\Toggle::make('accounting')
                            ->label('Contabilizado')
                            ->inline(false),

                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Adjuntar Documento')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(2048)
                            ->downloadable(),
                    ])
                    ->columns(3), // Ajustado a 3 columnas para esta sección
            ]);
    }

    public static function table(Table $table): Table
    {
        $segment = request()->segment(3);

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($segment) {
                if ($segment === 'billing') {
                    $query->where('status', 'por pagar');
                } elseif ($segment === 'receivable') {
                    $query->where('status', 'por reembolsar');
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('entity.business_name')
                    ->label('Entidad')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_type')
                    ->badge()
                    ->label('Tipo de Documento'),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Número de Documento')
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_date')
                    ->label('Fecha de Documento')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('costcenter.center_name')
                    ->label('Centro de Costos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.category_name')
                    ->label('Categoría')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remark')
                    ->label('Observación')
                    ->searchable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda'),

                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('Monto en USD')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount_pen')
                    ->label('Monto en PEN')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('exchange_rate')
                    ->label('Tipo de Cambio')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('withholding_amount')
                    ->label('Monto de Retención')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado de Pago'),

                Tables\Columns\TextColumn::make('planned_payment_date')
                    ->label('Fecha de Pago Planificado')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('actual_payment_date')
                    ->label('Fecha de Pago Real')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expense_type')
                    ->label('Tipo de Costo'),

                Tables\Columns\TextColumn::make('amount_to_pay')
                    ->label('Monto a Pagar')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_attachment')
                    ->label('Tiene Adjunto')
                    ->boolean(),

                Tables\Columns\IconColumn::make('accounting')
                    ->label('Contabilizado')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'Recibo por Honorarios' => 'Recibo por Honorarios',
                        'Recibo de Compra' => 'Recibo de Compra',
                        'Nota de crédito' => 'Nota de crédito',
                        'Boleta de pago' => 'Boleta de pago',
                        'Nota de Pago' => 'Nota de Pago',
                        'Sin Documento' => 'Sin Documento',
                        'Ticket' => 'Ticket',
                    ])
                    ->placeholder('Todos'),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'por revisar' => 'Por Revisar',
                        'por pagar' => 'Por Pagar',
                        'por pagar detraccion' => 'Por Pagar Detracción',
                        'por reembolsar' => 'Por Reembolsar',
                        'pagado' => 'Pagado',
                    ])
                    ->placeholder('Todos'),

                Filter::make('document_number')
                    ->form([
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['document_number'],
                            fn (Builder $query, $value): Builder => $query->where('document_number', 'like', "%{$value}%")
                        );
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
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
            // Relaciones si son necesarias
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'billing' => Pages\ListExpenses::route('/billing'),
            'receivable' => Pages\ListExpenses::route('/receivable'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Gasto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Gastos';
    }
}
