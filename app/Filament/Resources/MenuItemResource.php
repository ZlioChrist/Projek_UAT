<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationLabel = 'Menu & Stok';

    protected static ?string $modelLabel = 'Menu Jual';

    protected static ?string $pluralModelLabel = 'Menu Jual';

    protected static ?string $navigationGroup = 'Manajemen Warung';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return self::ownsRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return self::ownsRecord($record) && (int) $record->reserved_stock === 0;
    }

    private static function ownsRecord(Model $record): bool
    {
        return UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['cafe', 'roastery']);
        if (UserRole::fromUserValue(auth()->user()?->role) !== UserRole::Developer) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('cafe_id')
                ->label('Kafe (pilih salah satu)')
                ->relationship(
                    name: 'cafe',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer
                        ? $query
                        : $query->whereRaw('1 = 0'),
                )
                ->dehydrated()
                ->searchable()
                ->preload()
                ->nullable()
                ->rules(['prohibited_with:roastery_id']),
            Forms\Components\Select::make('roastery_id')
                ->label('Roastery (pilih salah satu)')
                ->relationship(
                    name: 'roastery',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => UserRole::fromUserValue(auth()->user()?->role) === UserRole::Developer
                        ? $query
                        : $query->whereRaw('1 = 0'),
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->rules(['required_without:cafe_id', 'prohibited_with:cafe_id']),
            Forms\Components\TextInput::make('name')->label('Nama Menu')->required()->maxLength(160),
            Forms\Components\TextInput::make('category')
                ->label('Kategori')
                ->datalist(['Espresso Based', 'Flavour Coffee', 'Signature', 'Non Coffee', 'Makanan', 'Dessert'])
                ->helperText('Pilih saran atau ketik kategori khusus untuk kedai ini.')
                ->required()
                ->maxLength(100),
            Forms\Components\Textarea::make('description')->label('Deskripsi')->maxLength(1000)->columnSpanFull(),
            Forms\Components\TextInput::make('price')->label('Harga')->numeric()->minValue(0)->prefix('Rp')->required(),
            Forms\Components\TextInput::make('stock')
                ->label('Stok fisik')
                ->helperText('Stok tersedia dikurangi reservasi pesanan yang belum lunas.')
                ->numeric()
                ->integer()
                ->minValue(fn (?MenuItem $record): int => (int) ($record?->reserved_stock ?? 0))
                ->required(),
            Forms\Components\FileUpload::make('image_path')
                ->label('Foto produk')
                ->image()
                ->directory('menu-items')
                ->visibility('public')
                ->maxSize(5120),
            Forms\Components\Toggle::make('is_active')->label('Tampilkan dan terima pesanan')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Menu')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('cafe.name')
                    ->label('Kafe / roastery')
                    ->state(fn (MenuItem $record): ?string => $record->cafe?->name ?? $record->roastery?->name)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('cafe', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('roastery', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))),
                Tables\Columns\ImageColumn::make('image_path')->label('Foto')->disk('public')->square(),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->searchable()->sortable()->placeholder('Belum dikategorikan'),
                Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR'),
                Tables\Columns\TextColumn::make('stock')->label('Stok fisik')->numeric(),
                Tables\Columns\TextColumn::make('reserved_stock')->label('Direservasi')->numeric(),
                Tables\Columns\TextColumn::make('available_stock')->label('Tersedia')->numeric()->state(fn (MenuItem $record) => $record->available_stock),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->disabled(fn (MenuItem $record) => (int) $record->reserved_stock > 0),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->action(function ($records) {
                    foreach ($records as $record) {
                        if ((int) $record->reserved_stock === 0 && self::ownsRecord($record)) {
                            $record->delete();
                        }
                    }
                }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
