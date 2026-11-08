<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\FacilityResource\Pages;
use App\Models\Facility;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FacilityResource extends Resource
{
    protected static ?string $model = Facility::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Daftar Fasilitas';

    protected static ?string $navigationGroup = 'Manajemen Kafe';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin], true);
    }

    public static function canCreate(): bool
    {
        return self::canViewAny() && (
            UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer
            || AuthenticatedUser::require()->cafes()->exists()
        );
    }

    public static function canEdit(Model $record): bool
    {
        return self::ownsRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return self::ownsRecord($record);
    }

    private static function ownsRecord(Model $record): bool
    {
        $user = AuthenticatedUser::get();

        return UserRole::fromUserValue($user?->role) === UserRole::Developer
            || (UserRole::fromUserValue($user?->role) === UserRole::Admin
                && $user !== null
                && $user->cafes()->whereKey($record->cafe_id)->exists());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('cafe');

        if (UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin) {
            $query->whereIn('cafe_id', AuthenticatedUser::require()->cafes()->select('id'));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('cafe_id')
                    ->relationship(
                        'cafe',
                        'name',
                        fn (Builder $query): Builder => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin
                            ? $query->whereIn('id', AuthenticatedUser::require()->cafes()->select('id'))
                            : $query,
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?Facility $record): bool => $record !== null)
                    ->dehydrated()
                    ->label('Milik Cafe'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon')
                    ->placeholder('e.g., wifi, parking')
                    ->helperText('Gunakan nama icon dari Phosphor Icons atau Heroicons')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cafe.name')
                    ->label('Kafe')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacilities::route('/'),
            'create' => Pages\CreateFacility::route('/create'),
            'edit' => Pages\EditFacility::route('/{record}/edit'),
        ];
    }
}
