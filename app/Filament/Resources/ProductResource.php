<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Цена')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('weight')
                    ->label('Вес')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('calories')
                    ->label('Калории')
                    ->numeric()
                    ->required(),
                Forms\Components\Checkbox::make('hidden')
                    ->label('Скрыть товар'),
                Forms\Components\TextInput::make('priority')
                    ->label('Приоритет')
                    ->numeric()
                    ->default(0),
                Forms\Components\Repeater::make('media')
                    ->relationship('media')
                    ->schema([
                        Forms\Components\FileUpload::make('path')
                            ->label('Изображение')
                            ->image(),
                    ])
                    ->label('Галерея изображений')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название'),
                Tables\Columns\TextColumn::make('category.name')->label('Категория'),
                Tables\Columns\TextColumn::make('price')->label('Цена'),
                Tables\Columns\BooleanColumn::make('hidden')->label('Скрыт'),
                Tables\Columns\TextColumn::make('priority')->label('Приоритет'),
            ])
            ->defaultSort('priority', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
