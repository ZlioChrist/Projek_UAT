<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return match (UserRole::fromUserValue(AuthenticatedUser::get()?->role)) {
            UserRole::Roastery => 'Manajemen Roastery',
            default => 'Manajemen Kafe',
        };
    }

    public static function getNavigationSort(): ?int
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Roastery ? 2 : 3;
    }

    public static function canViewAny(): bool
    {
        $role = UserRole::fromUserValue(AuthenticatedUser::get()?->role);

        return in_array($role, [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true);
    }

    public static function canCreate(): bool
    {
        $role = UserRole::fromUserValue(AuthenticatedUser::get()?->role);

        return $role === UserRole::Developer
            || ($role === UserRole::Admin && AuthenticatedUser::require()->cafes()->exists())
            || ($role === UserRole::Roastery && AuthenticatedUser::require()->roasteries()->exists());
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
        $user = AuthenticatedUser::get();

        return match (UserRole::fromUserValue($user?->role)) {
            UserRole::Developer => true,
            UserRole::Admin => $user !== null && $record->cafe_id && $user->cafes()->whereKey($record->cafe_id)->exists(),
            UserRole::Roastery => $user !== null && $record->roastery_id && $user->roasteries()->whereKey($record->roastery_id)->exists(),
            default => false,
        };
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['cafe', 'roastery']);

        $user = AuthenticatedUser::require();
        match (UserRole::fromUserValue($user?->role)) {
            UserRole::Admin => $query->whereIn('cafe_id', $user->cafes()->select('id')),
            UserRole::Roastery => $query->whereIn('roastery_id', $user->roasteries()->select('id')),
            UserRole::Developer => null,
            default => $query->whereRaw('1 = 0'),
        };

        return $query;
    }

    private static function canSelectCafe(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin], true);
    }

    private static function canSelectRoastery(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Roastery], true);
    }

    private static function cafeOptionsQuery(Builder $query): Builder
    {
        if (UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin) {
            return $query->whereIn('id', AuthenticatedUser::require()->cafes()->select('id'));
        }

        return $query;
    }

    private static function roasteryOptionsQuery(Builder $query): Builder
    {
        if (UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Roastery) {
            return $query->whereIn('id', AuthenticatedUser::require()->roasteries()->select('id'));
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
                    modifyQueryUsing: fn (Builder $query) => self::cafeOptionsQuery($query),
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->required(fn () => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin)
                ->visible(fn () => self::canSelectCafe())
                ->disabled(fn (?MenuItem $record): bool => $record !== null)
                ->dehydrated(),
            Forms\Components\Select::make('roastery_id')
                ->label('Roastery (pilih salah satu)')
                ->relationship(
                    name: 'roastery',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => self::roasteryOptionsQuery($query),
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->required(fn () => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Roastery)
                ->visible(fn () => self::canSelectRoastery())
                ->disabled(fn (?MenuItem $record): bool => $record !== null)
                ->dehydrated(),
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
        ])->columns(['default' => 1, 'md' => 2]);
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
                        ->where(fn (Builder $matches): Builder => $matches
                            ->whereHas('cafe', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('roastery', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")))),
                Tables\Columns\ImageColumn::make('image_path')->label('Foto')->disk('public')->square(),
                Tables\Columns\TextColumn::make('category')->label('Kategori')->searchable()->sortable()->placeholder('Belum dikategorikan'),
                Tables\Columns\TextColumn::make('price')->label('Harga')->money('IDR'),
                Tables\Columns\TextColumn::make('stock')->label('Stok fisik')->numeric(),
                Tables\Columns\TextColumn::make('reserved_stock')->label('Direservasi')->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('available_stock')->label('Tersedia')->numeric()->state(fn (MenuItem $record) => $record->available_stock),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->disabled(fn (MenuItem $record): bool => ! self::ownsRecord($record)),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->disabled(fn (MenuItem $record) => (int) $record->reserved_stock > 0),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->action(function ($records): void {
                    $deletable = $records->filter(
                        fn (MenuItem $record): bool => self::ownsRecord($record) && (int) $record->reserved_stock === 0
                    );
                    $blockedCount = $records->count() - $deletable->count();

                    foreach ($deletable as $record) {
                        $record->delete();
                    }

                    if ($blockedCount > 0) {
                        Notification::make()
                            ->title('Sebagian menu tidak dapat dihapus')
                            ->body("{$deletable->count()} menu dihapus; {$blockedCount} menu masih memiliki stok pesanan yang dipesan.")
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()->title('Menu berhasil dihapus')->success()->send();
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
