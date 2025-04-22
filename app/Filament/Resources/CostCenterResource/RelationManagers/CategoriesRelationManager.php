<?php

namespace App\Filament\Resources\CostCenterResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $createButtonLabel = 'Crear categorías';

    protected static ?string $recordTitleAttribute = 'category_name';
    
    protected static ?string $title = 'Categorías';
    
    protected static ?string $modelLabel = 'Categoría';
    
    protected static ?string $pluralModelLabel = 'Categorías';


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('category_name')
                    ->required()
                    ->label('Nombre de la Categoría')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category_name')
            
            ->columns([
                Tables\Columns\TextColumn::make('category_name')
                ->label('Nombre de la Categoría')
                ,
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
