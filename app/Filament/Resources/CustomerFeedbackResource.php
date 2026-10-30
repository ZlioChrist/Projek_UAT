<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\CustomerFeedbackResource\Pages;
use App\Models\CustomerFeedback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedbackResource extends Resource
{
    protected static ?string $model = CustomerFeedback::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Masukan Pelanggan';

    protected static ?string $modelLabel = 'Masukan';

    protected static ?string $pluralModelLabel = 'Masukan Pelanggan';

    protected static ?string $navigationGroup = 'Manajemen Warung';

    public static function canViewAny(): bool
    {
        return UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['cafe.owner', 'roastery.owner']);
        if (auth()->user()?->role !== 'developer') {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('shop_label')->label('Kafe / roastery')->formatStateUsing(fn (CustomerFeedback $record): ?string => $record->shop?->name)->disabled(),
            Forms\Components\TextInput::make('category')->label('Jenis masukan')->disabled(),
            Forms\Components\TextInput::make('customer_name')->label('Nama pelanggan')->disabled(),
            Forms\Components\TextInput::make('customer_contact')->label('Kontak')->disabled(),
            Forms\Components\Textarea::make('message')->label('Pesan')->rows(6)->disabled()->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->label('Status tindak lanjut')
                ->options(['new' => 'Baru', 'in_progress' => 'Ditindaklanjuti', 'responded' => 'Sudah dihubungi', 'closed' => 'Selesai'])
                ->required(),
            Forms\Components\Textarea::make('admin_note')->label('Catatan tindak lanjut internal')->rows(4)->maxLength(2000)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('Kafe / roastery')->state(fn (CustomerFeedback $record): ?string => $record->shop?->name),
            Tables\Columns\TextColumn::make('category')->label('Jenis')->badge(),
            Tables\Columns\TextColumn::make('customer_name')->label('Pelanggan')->searchable(),
            Tables\Columns\TextColumn::make('customer_contact')->label('Kontak')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('Tindak lanjut')->badge(),
            Tables\Columns\TextColumn::make('created_at')->label('Diterima')->dateTime('d M Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerFeedback::route('/'),
            'edit' => Pages\EditCustomerFeedback::route('/{record}/edit'),
        ];
    }
}
