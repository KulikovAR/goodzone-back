<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('customer_phone')->label('Телефон клиента'),
                Tables\Columns\TextColumn::make('total_price')->label('Итоговая стоимость'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->enum([
                        'new' => 'Новый',
                        'processing' => 'В обработке',
                        'completed' => 'Завершен',
                        'canceled' => 'Отменен',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('Дата создания'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новый',
                        'processing' => 'В обработке',
                        'completed' => 'Завершен',
                        'canceled' => 'Отменен',
                    ])
                    ->required(),
            ]);
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
