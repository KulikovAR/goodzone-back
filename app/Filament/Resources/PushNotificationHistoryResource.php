<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationHistoryResource\Pages;
use App\Models\PushNotificationHistory;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class PushNotificationHistoryResource extends Resource
{
    protected static ?string $model = PushNotificationHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'История Push-уведомлений';
    protected static ?string $pluralModelLabel = 'История Push-уведомлений';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('title')
                ->label('Заголовок')
                ->rows(2)
                ->disabled(),

            Textarea::make('message')
                ->label('Сообщение')
                ->rows(5)
                ->disabled(),

            TextInput::make('type')
                ->label('Тип')
                ->disabled(),

            TextInput::make('created_at')
                ->label('Отправлено')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->title)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Сообщение')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Отправлено')
                    ->dateTime()
                    ->searchable()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Запрет на создание, редактирование и удаление
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotificationHistories::route('/'),
            'view' => Pages\ViewPushNotificationHistory::route('/{record}'),
        ];
    }
}