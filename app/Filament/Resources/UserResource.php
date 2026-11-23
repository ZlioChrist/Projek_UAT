<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['cafes:id,owner_id,name,status', 'roasteries:id,owner_id,name,status']);
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
                Forms\Components\Section::make('Status usaha')
                    ->description('Pilih status untuk setiap kafe atau roastery yang dimiliki akun ini.')
                    ->visible(fn (?User $record, string $operation): bool => $operation === 'edit'
                        && $record !== null
                        && ($record->cafes->isNotEmpty() || $record->roasteries->isNotEmpty()))
                    ->schema(fn (?User $record): array => static::getBusinessStatusFields($record))
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
                Tables\Columns\TextColumn::make('business_status')
                    ->label('Status')
                    ->state(function (User $record): string {
                        $statusLabels = [
                            'published' => 'Publik',
                            'review' => 'Pending',
                            'draft' => 'Draf',
                        ];

                        $statuses = $record->cafes
                            ->concat($record->roasteries)
                            ->map(fn ($business): string => $statusLabels[$business->status] ?? ucfirst($business->status))
                            ->unique()
                            ->values();

                        return $statuses->isEmpty() ? 'Tidak ada usaha' : $statuses->join(', ');
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Publik' => 'success',
                        'Pending' => 'warning',
                        'Draf' => 'gray',
                        default => 'gray',
                    }),
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

    /**
     * @return array<Forms\Components\Component>
     */
    private static function getBusinessStatusFields(?User $record): array
    {
        if (! $record) {
            return [];
        }

        $fields = [];
        $statusOptions = [
            'published' => 'Publik',
            'review' => 'Pending',
            'draft' => 'Draf',
        ];

        foreach ($record->cafes as $cafe) {
            $fields[] = Forms\Components\TextInput::make('business_name_cafe_'.$cafe->getKey())
                ->label('Nama kafe')
                ->prefixIcon('heroicon-o-building-storefront')
                ->afterStateHydrated(fn (Forms\Components\TextInput $component) => $component->state($cafe->name))
                ->required()
                ->maxLength(255);

            $fields[] = Forms\Components\Select::make('business_status_cafe_'.$cafe->getKey())
                ->label('Status kafe')
                ->options($statusOptions)
                ->afterStateHydrated(fn (Forms\Components\Select $component) => $component->state($cafe->status))
                ->rules(['nullable', 'in:published,review,draft'])
                ->native();
        }

        foreach ($record->roasteries as $roastery) {
            $fields[] = Forms\Components\TextInput::make('business_name_roastery_'.$roastery->getKey())
                ->label('Nama roastery')
                ->prefixIcon('heroicon-o-building-storefront')
                ->afterStateHydrated(fn (Forms\Components\TextInput $component) => $component->state($roastery->name))
                ->required()
                ->maxLength(255);

            $fields[] = Forms\Components\Select::make('business_status_roastery_'.$roastery->getKey())
                ->label('Status roastery')
                ->options($statusOptions)
                ->afterStateHydrated(fn (Forms\Components\Select $component) => $component->state($roastery->status))
                ->rules(['nullable', 'in:published,review,draft'])
                ->native();
        }

        return $fields;
    }
}
