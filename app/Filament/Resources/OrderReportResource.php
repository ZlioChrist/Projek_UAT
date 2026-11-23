<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\OrderReportResource\Pages;
use App\Models\Order;
use App\Models\ReceiptPrintJob;
use App\Services\OrderService;
use App\Support\AuthenticatedUser;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class OrderReportResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Laporan Pesanan';

    protected static ?string $modelLabel = 'Laporan Pesanan';

    protected static ?string $pluralModelLabel = 'Laporan Pesanan';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Atur Pesanan';

    protected static ?int $navigationSort = 2;

    private const REPORT_STATUSES = [
        Order::STATUS_PAID,
        Order::STATUS_PREPARING,
        Order::STATUS_READY,
        Order::STATUS_DELIVERING,
        Order::STATUS_AWAITING_RECEIPT_CONFIRMATION,
        Order::STATUS_COMPLETED,
    ];

    public static function canViewAny(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin, UserRole::Roastery], true);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['cafe', 'roastery', 'items', 'printJobs'])
            ->whereIn('status', self::REPORT_STATUSES);
        $user = AuthenticatedUser::require();

        return match (UserRole::fromUserValue($user->role)) {
            UserRole::Admin => $query->whereIn('cafe_id', $user->cafes()->select('id')),
            UserRole::Roastery => $query->whereIn('roastery_id', $user->roasteries()->select('id')),
            UserRole::Developer => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }

        $count = static::getEloquentQuery()
            ->whereIn('status', [
                Order::STATUS_PAID,
                Order::STATUS_PREPARING,
                Order::STATUS_READY,
                Order::STATUS_DELIVERING,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
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
                Tables\Columns\TextColumn::make('fulfillment_method')
                    ->label('Cara menerima')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'delivery' ? 'Diantar kurir' : 'Ambil sendiri'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status pesanan')
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->status_label)
                    ->color(fn (Order $record): string => match ($record->status) {
                        Order::STATUS_READY, Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, Order::STATUS_COMPLETED => 'success',
                        Order::STATUS_DELIVERING => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status pesanan')
                    ->options([
                        Order::STATUS_PAID => 'Pembayaran terverifikasi',
                        Order::STATUS_PREPARING => 'Sedang disiapkan',
                        Order::STATUS_READY => 'Siap diambil',
                        Order::STATUS_DELIVERING => 'Sedang dikirim',
                        Order::STATUS_AWAITING_RECEIPT_CONFIRMATION => 'Menunggu konfirmasi diterima',
                        Order::STATUS_COMPLETED => 'Selesai',
                    ]),
                Tables\Filters\SelectFilter::make('fulfillment_method')
                    ->label('Cara menerima')
                    ->options([
                        'pickup' => 'Ambil sendiri',
                        'delivery' => 'Diantar kurir',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('Detail / nota')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record): string => route('owner.orders.show', $record->public_code)),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('markReadyOrDelivering')
                        ->label(fn (Order $record): string => $record->fulfillment_method === 'delivery' ? 'Tandai sedang dikirim' : 'Tandai siap diambil')
                        ->icon(fn (Order $record): string => $record->fulfillment_method === 'delivery' ? 'heroicon-m-truck' : 'heroicon-m-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Checkbox::make('ready_confirmed')
                                ->label(fn (Order $record): string => $record->fulfillment_method === 'delivery'
                                    ? 'Pesanan sudah selesai disiapkan dan diserahkan kepada kurir.'
                                    : 'Pesanan sudah selesai disiapkan dan siap diambil pelanggan.')
                                ->accepted()
                                ->required(),
                        ])
                        ->visible(fn (Order $record): bool => in_array($record->status, [Order::STATUS_PAID, Order::STATUS_PREPARING], true)
                            && $record->printJobs->contains(fn (ReceiptPrintJob $job): bool => $job->state === 'printed'))
                        ->action(function (Order $record, OrderService $service): void {
                            $target = $record->fulfillment_method === 'delivery'
                                ? Order::STATUS_DELIVERING
                                : Order::STATUS_READY;
                            $service->transition($record, AuthenticatedUser::require(), $target);
                            Notification::make()
                                ->title($record->fulfillment_method === 'delivery' ? 'Pesanan sedang dikirim' : 'Pesanan siap diambil')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('startLegacyDelivery')
                        ->label('Tandai sedang dikirim')
                        ->icon('heroicon-m-truck')
                        ->color('success')
                        ->form([
                            Forms\Components\Checkbox::make('delivery_confirmed')
                                ->label('Pesanan sudah diserahkan kepada kurir untuk diantar.')
                                ->accepted()
                                ->required(),
                        ])
                        ->visible(fn (Order $record): bool => $record->fulfillment_method === 'delivery' && $record->status === Order::STATUS_READY)
                        ->action(function (Order $record, OrderService $service): void {
                            $service->transition($record, AuthenticatedUser::require(), Order::STATUS_DELIVERING);
                            Notification::make()->title('Pesanan sedang dikirim')->success()->send();
                        }),
                    Tables\Actions\Action::make('confirmPickupHandoff')
                        ->label('Catat pesanan sudah diserahkan')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('code')
                                ->label('Cocokkan kode pengambilan')
                                ->required()
                                ->length(7)
                                ->regex('/^WN-\d{4}$/i'),
                        ])
                        ->visible(fn (Order $record): bool => $record->fulfillment_method === 'pickup' && $record->status === Order::STATUS_READY)
                        ->action(function (Order $record, array $data, OrderService $service): void {
                            if (strtoupper(trim($data['code'])) !== $record->display_code) {
                                throw ValidationException::withMessages(['code' => 'Kode pengambilan tidak sesuai dengan pesanan ini.']);
                            }

                            $service->markPickedUp($record, AuthenticatedUser::require());
                            Notification::make()->title('Serah-terima dicatat; menunggu konfirmasi pelanggan')->success()->send();
                        }),
                    Tables\Actions\Action::make('confirmDeliveryHandoff')
                        ->label('Catat pesanan sudah diserahkan')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Setelah pelanggan menerima pesanan, mereka akan diminta mengonfirmasi penerimaan.')
                        ->visible(fn (Order $record): bool => $record->fulfillment_method === 'delivery' && $record->status === Order::STATUS_DELIVERING)
                        ->action(function (Order $record, OrderService $service): void {
                            $service->transition($record, AuthenticatedUser::require(), Order::STATUS_AWAITING_RECEIPT_CONFIRMATION);
                            Notification::make()->title('Serah-terima dicatat; menunggu konfirmasi pelanggan')->success()->send();
                        }),
                ])->label('Atur pesanan')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->button()
                    ->visible(fn (Order $record): bool => match ($record->status) {
                        Order::STATUS_PAID, Order::STATUS_PREPARING => $record->printJobs->contains(
                            fn (ReceiptPrintJob $job): bool => $job->state === 'printed',
                        ),
                        Order::STATUS_READY, Order::STATUS_DELIVERING => true,
                        default => false,
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrderReports::route('/'),
        ];
    }
}
