<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\RoasteryResource\Pages;
use App\Models\Roastery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoasteryResource extends Resource
{
    protected static ?string $model = Roastery::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Roastery Kita';

    protected static ?string $modelLabel = 'Roastery';

    protected static ?string $pluralModelLabel = 'Daftar Roastery';

    protected static ?string $navigationGroup = 'Manajemen Roastery';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return in_array(AuthenticatedUser::get()?->role, ['developer', 'roastery'], true);
    }

    public static function canCreate(): bool
    {
        return UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: SHARED CORE INFO
                Forms\Components\Section::make('Informasi Utama')
                    ->icon('heroicon-o-information-circle')
                    ->description('Data dasar roastery kamu.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Wadah Sangrai Smd')
                            ->label('Nama Roastery'),

                        Forms\Components\Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Kota Lokasi')
                            ->placeholder('Pilih kota...')
                            ->required(),
                    ])
                    ->columns(2),

                // SECTION 2: ADMIN ONLY CONTROLS
                Forms\Components\Section::make('Kendali Admin')
                    ->icon('heroicon-o-shield-check')
                    ->description('Hanya tim internal yang bisa otir-atik bagian ini.')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->relationship(
                                'owner',
                                'name',
                                fn (Builder $query): Builder => $query->where('role', 'roastery')->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->label('Pemilik Roastery')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Masih Draft',
                                'review' => 'Lagi Di-Review',
                                'published' => 'Udah Tayang',
                            ])
                            ->label('Status')
                            ->default('draft')
                            ->required(),
                    ])
                    ->columns(2)
                    ->visible(fn () => AuthenticatedUser::get()?->role === 'developer'),

                // SECTION: ROASTERY OWNER CONTENT
                Forms\Components\Section::make('Detail Roastery')
                    ->icon('heroicon-o-sparkles')
                    ->description('Lengkapi info roastery kamu biar makin dikenal!')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->readOnly()
                            ->label('Nama Roastery'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'review' => 'Minta Approval Admin',
                                'published' => 'Langsung Tayangin!',
                            ])
                            ->label('Update Status')
                            ->visible(fn () => AuthenticatedUser::get()?->role === 'developer'),

                        Forms\Components\Textarea::make('description')
                            ->label('Tentang Roastery')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->disk('public')
                            ->directory('roasteries')
                            ->label('Foto Utama'),

                        Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('roasteries/gallery')
                            ->reorderable()
                            ->label('Galeri Suasana Roastery')
                            ->helperText('Foto interior, eksterior, dan vibes roastery.')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('menu_images')
                            ->image()
                            ->multiple()
                            ->disk('public')
                            ->directory('roasteries/menus')
                            ->reorderable()
                            ->label('Daftar Menu Biji Kopi')
                            ->helperText('Foto daftar menu, varian beans, atau packaging.')
                            ->columnSpanFull(),

                        Forms\Components\Section::make('Lokasi & Kontak')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->label('Alamat Lengkap')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('google_maps_url')
                                    ->url()
                                    ->label('Link Google Maps'),
                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->tel()
                                    ->label('Nomor WhatsApp'),
                            ])->columns(2),

                        Forms\Components\Section::make('Pembayaran pesanan')
                            ->description('Isi informasi transfer bank dan unggah QRIS statis. Sistem menyimpan informasi dan gambar, tetapi tidak terhubung ke bank.')
                            ->schema([
                                Forms\Components\FileUpload::make('payment_qr_path')
                                    ->label('Gambar QRIS')
                                    ->image()
                                    ->disk('public')
                                    ->directory('payment-qrs')
                                    ->visibility('public')
                                    ->maxSize(5120)
                                    ->helperText('Gambar disimpan di penyimpanan publik agar bisa ditampilkan pada checkout. Unggah ulang untuk mengganti QRIS.'),
                                Forms\Components\TextInput::make('bank_name')
                                    ->label('Nama bank')
                                    ->placeholder('Contoh: BCA, Mandiri, BRI, BNI, atau BSI')
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('bank_account_number')
                                    ->label('Nomor rekening')
                                    ->placeholder('Masukkan nomor rekening')
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('bank_account_name')
                                    ->label('Nama pemilik rekening')
                                    ->placeholder('Sesuai nama pada rekening')
                                    ->prefixIcon('heroicon-o-user')
                                    ->maxLength(120)
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('payment_instructions')
                                    ->label('Catatan transfer (opsional)')
                                    ->rows(4)
                                    ->maxLength(2000)
                                    ->placeholder('Contoh: Cantumkan kode pesanan pada berita transfer.')
                                    ->helperText('Ditampilkan sebagai catatan tambahan pada checkout. Jangan masukkan PIN atau kata sandi bank.')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Section::make('Jam Operasional')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Toggle::make('is_24_hours')
                                    ->label('Buka 24 Jam')
                                    ->helperText('Aktifkan jika roastery kamu buka seharian penuh!')
                                    ->live()
                                    ->columnSpanFull(),

                                ...self::dailyHoursFields(),

                                Forms\Components\ViewField::make('location_trigger')
                                    ->view('filament.components.location-button')
                                    ->hiddenLabel(),
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->placeholder('Contoh: -6.xxxx')
                                    ->label('Latitude')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('getLocation')
                                            ->icon('heroicon-m-map-pin')
                                            ->tooltip('Ambil Lokasi Saya')
                                            ->action(function () {})
                                            ->extraAttributes([
                                                'class' => 'cursor-pointer text-primary-500',
                                                'title' => 'Ambil Lokasi Saat Ini',
                                                'x-on:click.prevent' => <<<'JS'
                                                    if (!navigator.geolocation) {
                                                        alert('Browser kamu tidak mendukung geolocation.');
                                                        return;
                                                    }
                                                    navigator.geolocation.getCurrentPosition(
                                                        (position) => {
                                                            $wire.set('data.latitude', position.coords.latitude);
                                                            $wire.set('data.longitude', position.coords.longitude);
                                                            new Notification('Lokasi Berhasil Diambil!');
                                                        },
                                                        (error) => {
                                                            console.error(error);
                                                            alert('Gagal mengambil lokasi: ' + error.message);
                                                        }
                                                    );
                                                JS,
                                            ])
                                    ),
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->label('Longitude')
                                    ->placeholder('Contoh: 106.xxxx'),
                            ])->columns(2),

                        Forms\Components\Section::make('Social Media')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Repeater::make('social_links')
                                    ->schema([
                                        Forms\Components\Select::make('platform')
                                            ->options([
                                                'instagram' => 'Instagram',
                                                'tiktok' => 'TikTok',
                                                'facebook' => 'Facebook',
                                                'twitter' => 'Twitter/X',
                                                'whatsapp' => 'WhatsApp',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('url')
                                            ->url()
                                            ->required(),
                                        Forms\Components\Toggle::make('show')
                                            ->label('Tampilkan')
                                            ->default(true),
                                    ])
                                    ->columns(3)
                                    ->defaultItems(0)
                                    ->addActionLabel('+ Tambah Sosmed')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn () => in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Roastery], true)),
            ]);
    }

    private static function dailyHoursFields(): array
    {
        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu',
        ];

        return array_map(function (string $day, string $label): Forms\Components\Fieldset {
            $legacyGroup = in_array($day, ['saturday', 'sunday'], true) ? 'weekend' : 'weekday';

            return Forms\Components\Fieldset::make("Jam {$label}")
                ->schema([
                    Forms\Components\TimePicker::make("operating_hours.{$day}.open")
                        ->label('Buka')
                        ->seconds(false)
                        ->format('H:i')
                        ->displayFormat('H:i')
                        ->formatStateUsing(fn ($state, $record) => array_key_exists($day, $record?->operating_hours ?? [])
                            ? $state
                            : ($state ?? data_get($record?->operating_hours, "{$legacyGroup}.open"))),
                    Forms\Components\TimePicker::make("operating_hours.{$day}.close")
                        ->label('Tutup')
                        ->seconds(false)
                        ->format('H:i')
                        ->displayFormat('H:i')
                        ->formatStateUsing(fn ($state, $record) => array_key_exists($day, $record?->operating_hours ?? [])
                            ? $state
                            : ($state ?? data_get($record?->operating_hours, "{$legacyGroup}.close"))),
                ])
                ->columns(2)
                ->hidden(fn (Forms\Get $get): bool => (bool) $get('is_24_hours'));
        }, array_keys($days), array_values($days));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Nama Roastery'),
                Tables\Columns\TextColumn::make('owner_display')
                    ->state(fn (Roastery $record): string => $record->owner?->name ?? 'Belum ditentukan')
                    ->label('Owner')
                    ->visible(fn () => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Kota')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'review' => 'warning',
                        'published' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'review' => 'Pending Review',
                        'published' => 'Published',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'review' => 'Pending Review',
                        'published' => 'Published',
                    ]),
                Tables\Filters\SelectFilter::make('city_id')
                    ->relationship('city', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['owner', 'city']);

        if (UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Roastery) {
            $query->where('owner_id', AuthenticatedUser::id());
        }

        return $query;
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
            'index' => Pages\ListRoasteries::route('/'),
            'create' => Pages\CreateRoastery::route('/create'),
            'edit' => Pages\EditRoastery::route('/{record}/edit'),
        ];
    }
}
