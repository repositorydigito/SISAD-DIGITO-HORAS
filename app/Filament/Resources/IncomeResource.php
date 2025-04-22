<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeResource\Pages;
use App\Models\Income;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IncomeResource extends Resource
{
    protected static ?string $model = Income::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Ingresos';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Listado';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Ingresos';
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
                ->url('/admin/incomes')
                ->group('Ingresos')
                ->sort(1),

            \Filament\Navigation\NavigationItem::make('Por Facturar')
                ->icon('heroicon-o-document')
                ->url('/admin/incomes/billing')
                ->badge(static::getModel()::where('status', 'Por Facturar')->count())
                ->group('Ingresos')
                ->sort(2),

            \Filament\Navigation\NavigationItem::make('Por Cobrar')
                ->icon('heroicon-o-currency-dollar')
                ->url('/admin/incomes/receivable')
                ->badge(static::getModel()::where('status', 'Por Cobrar')->count())
                ->group('Ingresos')
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
                            ->relationship('entity', 'business_name', function ($query) {
                                $query->where('entity_type', 'Cliente');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'Boleta de venta' => 'Boleta de venta',
                                'Factura' => 'Factura',
                                'Nota de abono' => 'Nota de abono',
                                'Nota de débito' => 'Nota de débito',
                                'Valor residual' => 'Valor residual',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->required()
                            ->maxLength(50)
                            ->prefix('#'),
                        Forms\Components\DatePicker::make('document_date')
                            ->label('Fecha de Documento')
                            ->required(),
                        Forms\Components\TextInput::make('description')
                            ->label('Glosa')
                            ->maxLength(255),
                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Moneda')
                    ->description('Información de montos y tipo de cambio')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->label('Moneda')
                            ->options([
                                'Soles' => 'Soles',
                                'Dólares' => 'Dólares',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount_usd')
                            ->label('Monto USD (sin IGV)')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_pen')
                            ->label('Monto PEN (sin IGV)')
                            ->numeric()
                            ->prefix('S/.'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Información Adicional')
                    ->description('Datos complementarios del ingreso')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\DatePicker::make('payment_plan_date')
                            ->label('Fecha Pago Plan'),
                        Forms\Components\DatePicker::make('real_payment_date')
                            ->label('Fecha Pago Real'),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'Por Revisar' => 'Por Revisar',
                                'Por Facturar' => 'Por Facturar',
                                'Por Cobrar' => 'Por Cobrar',
                                'Cobrado' => 'Cobrado',
                                'Suspendido' => 'Suspendido',
                                'Provisionado' => 'Provisionado',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('service_percentage')
                            ->label('% Servicio')
                            ->numeric()
                            ->suffix('%'),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->label('Monto a Depositar')
                            ->numeric()
                            ->prefix('S/.'),
                        Forms\Components\TextInput::make('detraccion_amount')
                            ->label('Monto de Detracción')
                            ->numeric()
                            ->prefix('S/.'),
                        Forms\Components\RichEditor::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_accounted')
                            ->label('Contable')
                            ->required()
                            ->inline(false),
                        Forms\Components\FileUpload::make('attachment_path')
                            ->label('Adjuntar Documento')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(2048)
                            ->downloadable(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        $segment = request()->segment(3);

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($segment) {
                if ($segment === 'billing') {
                    $query->where('status', 'Por Facturar');
                } elseif ($segment === 'receivable') {
                    $query->where('status', 'Por Cobrar');
                }
                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('entity.business_name')
                    ->label('Razón Social')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('document_type')
                    ->label('Tipo de Documento')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Número de Documento')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('document_date')
                    ->label('Fecha de Documento')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Glosa')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currency')
                    ->label('Moneda')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount_usd')
                    ->label('Monto USD (sin IGV)')
                    ->money('usd')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount_pen')
                    ->label('Monto PEN (sin IGV)')
                    ->money('pen')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_plan_date')
                    ->label('Fecha Pago Plan')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('real_payment_date')
                    ->label('Fecha Pago Real')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state)
                    ->colors([
                        'warning' => 'Por Revisar',
                        'info' => 'Por Facturar',
                        'info' => 'Por Cobrar',
                        'success' => 'Cobrado',
                        'danger' => 'Suspendido',
                        'gray' => 'Provisionado',
                    ])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('service_percentage')
                    ->label('% Servicio')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('deposit_amount')
                    ->label('Monto a Depositar')
                    ->money('pen')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('detraccion_amount')
                    ->label('Monto de Detracción')
                    ->money('pen')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_accounted')
                    ->label('Contable')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'Boleta de venta' => 'Boleta de venta',
                        'Factura' => 'Factura',
                        'Nota de abono' => 'Nota de abono',
                        'Nota de débito' => 'Nota de débito',
                        'Valor residual' => 'Valor residual',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Por Revisar' => 'Por Revisar',
                        'Por Facturar' => 'Por Facturar',
                        'Por Cobrar' => 'Por Cobrar',
                        'Cobrado' => 'Cobrado',
                        'Suspendido' => 'Suspendido',
                        'Provisionado' => 'Provisionado',
                    ]),

                Tables\Filters\Filter::make('document_number')
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

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomes::route('/'),
            'billing' => Pages\ListIncomes::route('/billing'),
            'receivable' => Pages\ListIncomes::route('/receivable'),
            'create' => Pages\CreateIncome::route('/create'),
            'edit' => Pages\EditIncome::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Ingreso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Ingresos';
    }
}
