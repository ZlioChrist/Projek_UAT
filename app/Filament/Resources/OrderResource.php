<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Daftar Pesanan';

    protected static ?string $modelLabel = 'Pesanan';

    protected static ?string $pluralModelLabel = 'Daftar Pesanan';

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Atur Pesanan';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['cafe', 'roastery', 'items']);
        $user = AuthenticatedUser::require();

        $query = match (UserRole::fromUserValue($user?->role)) {
            UserRole::Admin => $query->whereIn('cafe_id', $user->cafes()->select('id')),
            UserRole::Roastery => $query->whereIn('roastery_id', $user->roasteries()->select('id')),
            UserRole::Developer => $query,
            default => $query->whereRaw('1 = 0'),
        };

        return $query->whereIn('status', [
            Order::STATUS_AWAITING_PAYMENT,
            Order::STATUS_PENDING_VERIFICATION,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $query = static::getEloquentQuery()->whereIn('status', [
            Order::STATUS_AWAITING_PAYMENT,
            Order::STATUS_PENDING_VERIFICATION,
        ]);
        $count = $query->count();

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
                Tables\Columns\TextColumn::make('display_code')
                    ->label('Kode pesanan')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('shop_name')
                    ->label('Kedai')
                    ->state(fn (Order $record): string => $record->shop_name)
                    ->searchable(['cafe.name', 'roastery.name']),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->description(fn (Order $record): string => $record->customer_whatsapp ?: 'Tanpa WhatsApp'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'submitted' => 'Menunggu verifikasi',
                        'rejected' => 'Perlu pembayaran ulang',
                        default => 'Belum dibayar',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'submitted' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status pesanan')
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->status_label)
                    ->color(fn (Order $record): string => match ($record->status) {
                        Order::STATUS_READY, Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, Order::STATUS_COMPLETED => 'success',
                        Order::STATUS_CANCELLED => 'danger',
                        Order::STATUS_AWAITING_PAYMENT, Order::STATUS_PENDING_VERIFICATION => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Order::STATUS_AWAITING_PAYMENT => 'Menunggu Pembayaran',
                        Order::STATUS_PENDING_VERIFICATION => 'Menunggu Konfirmasi',
                    ]),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status pembayaran')
                    ->options([
                        'unpaid' => 'Belum dibayar',
                        'submitted' => 'Menunggu verifikasi',
                        'rejected' => 'Perlu pembayaran ulang',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('Detail / nota')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => route('owner.orders.show', $record->public_code)),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('confirmPayment')
                        ->label('Konfirmasi pembayaran')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Pastikan pembayaran sudah masuk dan jumlahnya sesuai pesanan.')
                        ->visible(fn (Order $record): bool => $record->status === Order::STATUS_PENDING_VERIFICATION)
                        ->action(function (Order $record, OrderService $service): void {
                            $service->confirmPayment($record, AuthenticatedUser::get());
                            Notification::make()->title('Pembayaran dikonfirmasi')->success()->send();
                        }),
                    Tables\Actions\Action::make('confirmCashPayment')
                        ->label('Catat pembayaran tunai')
                        ->icon('heroicon-m-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record): bool => $record->status === Order::STATUS_AWAITING_PAYMENT && $record->payment_method === 'cash' && $record->fulfillment_method === 'pickup')
                        ->action(function (Order $record, OrderService $service): void {
                            $service->confirmCashPayment($record, AuthenticatedUser::get());
                            Notification::make()->title('Pembayaran tunai dicatat')->success()->send();
                        }),
                    Tables\Actions\Action::make('rejectPayment')
                        ->label('Tolak bukti')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Alasan penolakan')
                                ->required()
                                ->minLength(3)
                                ->maxLength(500),
                        ])
                        ->visible(fn (Order $record): bool => $record->status === Order::STATUS_PENDING_VERIFICATION)
                        ->action(function (Order $record, array $data, OrderService $service): void {
                            $service->rejectPayment($record, AuthenticatedUser::get(), $data['reason']);
                            Notification::make()->title('Bukti pembayaran ditolak')->success()->send();
                        }),
                    Tables\Actions\Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Alasan')
                                ->maxLength(500),
                        ])
                        ->visible(fn (Order $record): bool => $record->canBeCancelled())
                        ->action(function (Order $record, array $data, OrderService $service): void {
                            $service->cancel($record, AuthenticatedUser::get(), $data['reason'] ?: 'Dibatalkan oleh kedai.');
                            Notification::make()->title('Pesanan dibatalkan')->success()->send();
                        }),
                ])->label('Proses pesanan')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->button(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
        ];
    }
}
