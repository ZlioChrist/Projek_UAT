<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\MerchantApplicationResource\Pages;
use App\Models\Cafe;
use App\Models\MerchantApplication;
use App\Models\Roastery;
use App\Models\User;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MerchantApplicationResource extends Resource
{
    protected static ?string $model = MerchantApplication::class;

    protected static ?string $navigationLabel = 'Pengajuan Mitra';

    protected static ?string $modelLabel = 'Pengajuan Mitra';

    protected static ?string $pluralModelLabel = 'Pengajuan Mitra';

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Kendali Admin';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'city', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $count = MerchantApplication::query()->where('status', MerchantApplication::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Nama usaha')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('business_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'cafe' ? 'Kafe' : 'Roastery'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pemohon')
                    ->description(fn (MerchantApplication $record): string => $record->user?->email ?? '')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Kota'),
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MerchantApplication::STATUS_PENDING => 'Menunggu tinjauan',
                        MerchantApplication::STATUS_APPROVED => 'Disetujui',
                        MerchantApplication::STATUS_REJECTED => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        MerchantApplication::STATUS_PENDING => 'warning',
                        MerchantApplication::STATUS_APPROVED => 'success',
                        MerchantApplication::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('Detail')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn (MerchantApplication $record): string => 'Pengajuan '.$record->business_name)
                    ->modalContent(fn (MerchantApplication $record) => view('filament.resources.merchant-application-details', [
                        'application' => $record,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui & buat akun pengelola')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Akun customer akan diubah menjadi akun pengelola. Usaha dibuat sebagai draft dan belum tampil publik sampai disiapkan dan diterbitkan.')
                    ->visible(fn (MerchantApplication $record): bool => $record->status === MerchantApplication::STATUS_PENDING)
                    ->action(function (MerchantApplication $record): void {
                        DB::transaction(function () use ($record): void {
                            $application = MerchantApplication::query()->lockForUpdate()->findOrFail($record->id);
                            if ($application->status !== MerchantApplication::STATUS_PENDING) {
                                throw ValidationException::withMessages(['application' => 'Pengajuan ini sudah diproses.']);
                            }

                            $user = User::query()->lockForUpdate()->findOrFail($application->user_id);
                            if (UserRole::fromUserValue($user->role) !== UserRole::User
                                || $user->cafes()->exists()
                                || $user->roasteries()->exists()) {
                                throw ValidationException::withMessages(['application' => 'Akun ini sudah bukan akun customer atau sudah memiliki usaha.']);
                            }

                            $businessData = [
                                'name' => $application->business_name,
                                'city_id' => $application->city_id,
                                'address' => $application->address,
                                'whatsapp_number' => $application->whatsapp_number,
                                'description' => $application->description,
                                'owner_id' => $user->id,
                                'status' => 'draft',
                            ];

                            if ($application->business_type === 'cafe') {
                                Cafe::query()->create($businessData);
                                $user->role = UserRole::Admin->value;
                            } else {
                                Roastery::query()->create($businessData);
                                $user->role = UserRole::Roastery->value;
                            }

                            $user->save();
                            $application->status = MerchantApplication::STATUS_APPROVED;
                            $application->reviewed_by = AuthenticatedUser::id();
                            $application->reviewed_at = now();
                            $application->save();
                        });

                        Notification::make()
                            ->title('Pengajuan disetujui')
                            ->body('Usaha dibuat sebagai draft dan akses admin telah diaktifkan.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak pengajuan')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('review_notes')
                            ->label('Alasan penolakan')
                            ->required()
                            ->minLength(5)
                            ->maxLength(2000),
                    ])
                    ->visible(fn (MerchantApplication $record): bool => $record->status === MerchantApplication::STATUS_PENDING)
                    ->action(function (MerchantApplication $record, array $data): void {
                        $record->update([
                            'status' => MerchantApplication::STATUS_REJECTED,
                            'review_notes' => trim($data['review_notes']),
                            'reviewed_by' => AuthenticatedUser::id(),
                            'reviewed_at' => now(),
                        ]);
                        Notification::make()->title('Pengajuan ditolak')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchantApplications::route('/'),
        ];
    }
}
