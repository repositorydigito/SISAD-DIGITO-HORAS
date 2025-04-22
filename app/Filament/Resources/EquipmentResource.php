<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationGroup = 'Recursos';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Equipos';
    
    protected static ?string $modelLabel = 'Equipo';
    
    protected static ?string $pluralModelLabel = 'Equipos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Equipo')
                    ->schema([
                        Forms\Components\Select::make('entity_id')
                            ->relationship('entity', 'business_name')
                            ->required()
                            ->label('Entidad')
                            ->placeholder('Seleccione una entidad')
                            ->searchable()
                            ->preload()
                            ,

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nombre')
                            ->placeholder('Ingrese el nombre')
                            ->maxLength(255),

                        Forms\Components\Select::make('vehicle_type')
                            ->required()
                            ->label('Tipo de vehículo')
                            ->options([
                                'Camión' => 'Camión',
                                'Excavadora' => 'Excavadora',
                                'Cargador' => 'Cargador',
                                'Volquete' => 'Volquete',
                                'Otro' => 'Otro',
                            ])
                            ->placeholder('Seleccione el tipo'),

                        Forms\Components\TextInput::make('brand')
                            ->required()
                            ->label('Marca')
                            ->placeholder('Ingrese la marca')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('model')
                            ->required()
                            ->label('Modelo')
                            ->placeholder('Ingrese el modelo')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Información del Conductor')
                    ->schema([
                        Forms\Components\TextInput::make('driver')
                            ->required()
                            ->label('Conductor')
                            ->placeholder('Ingrese el nombre del conductor')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('license')
                            ->required()
                            ->label('Licencia')
                            ->placeholder('Ingrese el número de licencia')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('plate_number1')
                            ->required()
                            ->label('Placa 1')
                            ->placeholder('Ingrese la placa 1')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('plate_number2')
                            ->label('Placa 2')
                            ->placeholder('Ingrese la placa 2 (opcional)')
                            ->maxLength(20),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entity.business_name')
                    ->label('Entidad')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('driver')
                    ->label('Conductor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plate_number1')
                    ->label('Placa 1')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_id')
                    ->relationship('entity', 'business_name')
                    ->label('Entidad')
                    ->placeholder('Filtrar por entidad'),

                Tables\Filters\SelectFilter::make('vehicle_type')
                    ->options([
                        'Camión' => 'Camión',
                        'Excavadora' => 'Excavadora',
                        'Cargador' => 'Cargador',
                        'Volquete' => 'Volquete',
                        'Otro' => 'Otro',
                    ])
                    ->label('Tipo de vehículo')
                    ->placeholder('Filtrar por tipo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}