<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntityResource\Pages;
use App\Models\Entity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntityResource extends Resource
{
    protected static ?string $model = Entity::class;

    protected static ?string $navigationIcon = 'heroicon-s-users';
    protected static ?string $navigationGroup = 'Maestros';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Información Principal
                Forms\Components\Section::make('Información Principal')
                    ->description('Datos principales de la entidad')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('entity_type')
                            ->options([
                                'Cliente' => 'Cliente',
                                'Proveedor' => 'Proveedor',
                                'Planilla' => 'Planilla',
                            ])
                            ->required()
                            ->native(false)
                            ->label('Tipo de Entidad')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('business_name')
                            ->label('Razón Social')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('trade_name')
                            ->label('Nombre Comercial')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_id')
                            ->label('RUC')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('business_group')
                            ->label('Grupo Empresarial')
                            ->maxLength(255),
                    ]),

                // Información de Contacto
                Forms\Components\Section::make('Información de Contacto')
                    ->description('Datos de correo electrónico para comunicaciones')
                    ->icon('heroicon-o-envelope')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('billing_email')
                            ->label('Correo de Facturación')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),
                        Forms\Components\TextInput::make('copy_email')
                            ->label('Correo de Copia')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),
                    ]),

                // Información Bancaria
                Forms\Components\Section::make('Información Bancaria')
                    ->description('Datos bancarios para transacciones')
                    ->icon('heroicon-o-building-library')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('bank_id')
                            ->label('Banco')
                            ->relationship('bank', 'name')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('account_number')
                            ->label('Número de Cuenta')
                            ->maxLength(50)
                            ->prefix('N°'),
                        Forms\Components\TextInput::make('interbank_account_number')
                            ->label('Número de Cuenta Interbancaria')
                            ->maxLength(50)
                            ->prefix('CCI'),
                        Forms\Components\TextInput::make('detraccion_account_number')
                            ->label('Número de Cuenta de Detracción')
                            ->maxLength(50)
                            ->prefix('D-'),
                    ]),

                // Información Comercial
                Forms\Components\Section::make('Información Comercial')
                    ->description('Datos comerciales y de crédito')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('reference_recommendation')
                            ->label('Recomendación o Referido')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('credit_days')
                            ->label('Días de Crédito')
                            ->numeric()
                            ->suffix('días')
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Tipo de Entidad'),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('Razón Social')
                    ->searchable(),
                Tables\Columns\TextColumn::make('trade_name')
                    ->label('Nombre Comercial')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_id')
                    ->label('Ruc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_group')
                    ->label('Grupo Empresarial'),

            ])

            ->filters([])
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntities::route('/'),
            'create' => Pages\CreateEntity::route('/create'),
            'edit' => Pages\EditEntity::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Entidad';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Entidades';
    }
}
