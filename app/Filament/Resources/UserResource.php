<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $navigationGroup = 'Kendali Admin';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        // Only Developer can see User Management
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function canViewAny(): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function canCreate(): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function canEdit(Model $record): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function canDelete(Model $record): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi pengguna')
                    ->description('Atur nama, email, dan peran akun.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama lengkap')
                            ->placeholder('Contoh: Andi Saputra')
                            ->prefixIcon('heroicon-o-user')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Alamat email')
                            ->placeholder('nama@email.com')
                            ->prefixIcon('heroicon-o-envelope')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->label('Peran akun')
                            ->options([
                                'developer' => 'Developer',
                                'admin' => 'Admin Cafe (Owner)',
                                'roastery' => 'Admin Roastery (Owner)',
                            ])
                            ->native(false)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Forms\Components\Section::make('Keamanan akun')
                    ->description('Gunakan kata sandi yang kuat untuk melindungi akun.')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Kata sandi')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText(fn (string $context): string => $context === 'create'
                                ? 'Wajib diisi untuk akun baru.'
                                : 'Kosongkan jika tidak ingin mengganti kata sandi.')
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Pengguna')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable()
                    ->icon('heroicon-m-user-circle')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => UserRole::fromUserValue($state)?->label() ?? ucfirst($state ?? ''))
                    ->colors([
                        'danger' => 'developer',
                        'warning' => 'admin',
                        'info' => 'roastery',
                        'success' => 'user',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Peran akun')
                    ->options(collect(UserRole::cases())
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                        ->all()),
            ])
            ->searchPlaceholder('Cari nama atau email...')
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription('Pengguna yang terdaftar akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-users')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
