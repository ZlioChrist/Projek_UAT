<?php

namespace App\Filament\Resources;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use App\Filament\Resources\CafeResource\Pages;
use App\Models\Cafe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CafeResource extends Resource
{
    protected static ?string $model = Cafe::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Daftar Kafe';

    protected static ?string $modelLabel = 'Cafe';

    protected static ?string $pluralModelLabel = 'Daftar Cafe';

    protected static ?string $navigationGroup = 'Manajemen Kafe';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $role = UserRole::fromUserValue(AuthenticatedUser::get()?->role);

        return in_array($role, [UserRole::Developer, UserRole::Admin], true);
    }

    public static function canCreate(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin], true);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Cafe
            && ! $record->menuItems()->exists()
            && ! $record->orders()->exists()
            && ! $record->feedback()->exists()
            && ! $record->checkIns()->exists()
            && ! $record->wfcScores()->exists()
            && ! $record->vibeVotes()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: SHARED CORE INFO
                Forms\Components\Section::make('Informasi Utama')
                    ->icon('heroicon-o-information-circle')
                    ->description(fn (): string => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin
                        ? 'Perubahan profil kafe akan ditinjau sebelum ditayangkan.'
                        : 'Data dasar cafe kamu.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kopi Malem Jumat')
                            ->label('Nama Cafe'),

                        Forms\Components\Select::make('city_id')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Kota Lokasi')
                            ->placeholder('Pilih kota...')
                            ->required(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                // SECTION 2: ADMIN/DEV CONTROLS
                Forms\Components\Section::make('Kendali Admin')
                    ->icon('heroicon-o-shield-check')
                    ->description('Hanya tim internal yang bisa otir-atik bagian ini.')
                    ->schema([
                        Forms\Components\Select::make('owner_id')
                            ->relationship(
                                'owner',
                                'name',
                                fn (Builder $query): Builder => $query->where('role', 'admin')->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->label('Siapa Pemiliknya?')
                            ->placeholder('Pilih nama kafe...')
                            ->required()
                            ->visible(fn () => AuthenticatedUser::get()?->role === 'developer'),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Masih Draft',
                                'review' => 'Lagi Di-Review',
                                'published' => 'Udah Tayang',
                            ])
                            ->label('Status Sekarang')
                            ->default('draft')
                            ->required()
                            ->visible(fn () => AuthenticatedUser::get()?->role === 'developer'),

                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->visible(fn () => AuthenticatedUser::get()?->role === 'developer'),

                // SECTION: MAIN CONTENT
                Forms\Components\Section::make('Detail Cafe')
                    ->icon('heroicon-o-building-storefront')
                    ->description('Isi semua info biar orang-orang pada mampir!')
                    ->schema([
                        Forms\Components\Section::make('Katalog Foto Menu')
                            ->icon('heroicon-o-photo')
                            ->description('Cara paling gampang! Cukup upload foto daftar menu kamu (lembaran/katalog). User tinggal klik buat nge-zoom.')
                            ->schema([
                                Forms\Components\Repeater::make('menu_images')
                                    ->label('Foto Daftar Menu')
                                    ->schema([
                                        Forms\Components\FileUpload::make('image')
                                            ->image()
                                            ->directory('cafes/menu-gallery')
                                            ->visibility('public')
                                            ->label('Upload Foto Menu')
                                            ->required()
                                            ->maxSize(5120)
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('tag')
                                            ->label('Kategori')
                                            ->placeholder('Misal: Menu Utama, Promo, Minuman')
                                            ->required()
                                            ->columnSpan(2),
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                                    ->reorderable()
                                    ->addActionLabel('+ Tambah Halaman Menu')
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\RichEditor::make('description')

                            ->label('Tentang Cafe Kamu')
                            ->placeholder('Ceritain dong apa yang bikin cafe kamu spesial...')
                            ->fileAttachmentsDirectory('cafes/description-images')
                            ->fileAttachmentsVisibility('public')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('image_path')
                            ->image()
                            ->directory('cafes')
                            ->visibility('public')
                            ->label('Foto Profil Utama')
                            // ->imageResizeMode('cover')
                            // ->imageCropAspectRatio('1:1')
                            // ->imageResizeTargetWidth('1080')
                            // ->imageResizeTargetHeight('1080')
                            ->maxSize(5120) // 5MB Limit
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),

                        Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->directory('cafes/gallery')
                            ->visibility('public')
                            ->label('Galeri Foto (Biar Makin Estetik)')
                            ->helperText('Maksimal 5 foto ya boss, usahain yang resolusinya mantap!')
                            ->reorderable()
                            // ->imageResizeMode('cover')
                            // ->imageResizeTargetWidth('1280')
                            // ->imageResizeTargetHeight('1280')
                            ->maxSize(5120) // 5MB Limit
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull(),

                        Forms\Components\Section::make('Fasilitas Cafe')
                            ->icon('heroicon-o-squares-2x2')
                            ->description('Apa aja yang ada di cafe kamu?')
                            ->schema([
                                Forms\Components\Repeater::make('facilities')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Fasilitas')
                                            ->placeholder('Contoh: Wifi Kenceng, Musholla, AC Dingin')
                                            ->required(),
                                    ])
                                    ->columns(1)
                                    ->defaultItems(0)
                                    ->addActionLabel('+ Tambah Fasilitas')
                                    ->grid(3)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Lokasi & Kontak')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->required()
                                    ->label('Alamat Lengkapnya')
                                    ->placeholder('Tulis alamat yang bener biar kaga nyasar...')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('google_maps_url')
                                    ->url()
                                    ->placeholder('https://maps.google.com/...')
                                    ->label('Link Google Maps'),
                                Forms\Components\TextInput::make('whatsapp_number')
                                    ->tel()
                                    ->placeholder('0812xxxxxxxx')
                                    ->label('Nomor WhatsApp (Aktif)')
                                    ->helperText('Format bebas (08xx atau 628xx). Sistem otomatis konversi ke link WA.'),
                            ])->columns(['default' => 1, 'md' => 2]),

                        Forms\Components\Section::make('Pembayaran Pesanan')
                            ->icon('heroicon-o-qr-code')
                            ->description('Atur informasi transfer bank dan unggah QRIS statis. Sistem tidak terhubung ke bank; staf memeriksa bukti dan mencatat status pembayaran secara manual.')
                            ->schema([
                                Forms\Components\FileUpload::make('payment_qr_path')
                                    ->image()
                                    ->disk('public')
                                    ->directory('cafes/payment-qr')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(5120)
                                    ->label('Gambar QRIS statis')
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
                                    ->maxLength(1000)
                                    ->rows(3)
                                    ->placeholder('Contoh: Cantumkan kode pesanan pada berita transfer.')
                                    ->helperText('Ditampilkan sebagai catatan tambahan pada checkout. Jangan masukkan PIN atau kata sandi bank.')
                                    ->columnSpanFull(),
                            ])->columns(['default' => 1, 'md' => 2]),

                        Forms\Components\Section::make('Jam Operasional')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                // Toggle 24 Jam
                                Forms\Components\Toggle::make('is_24_hours')
                                    ->label('Buka 24 Jam')
                                    ->helperText('Aktifkan jika cafe kamu buka seharian penuh!')
                                    ->live()
                                    ->columnSpanFull(),

                                ...self::dailyHoursFields(),

                                Forms\Components\ViewField::make('location_trigger')
                                    ->view('filament.components.location-button')
                                    ->hiddenLabel(),
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->placeholder('Contoh: -6.xxxx')
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
                                    ->placeholder('Contoh: 106.xxxx'),
                            ])->columns(['default' => 1, 'md' => 2]),

                        Forms\Components\Section::make('Social Media')
                            ->icon('heroicon-o-globe-alt')
                            ->description('Tambahin link sosmedmu biar makin gampang dikenal!')
                            ->schema([
                                Forms\Components\Repeater::make('social_links')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('platform')
                                            ->options([
                                                'instagram' => 'Instagram',
                                                'tiktok' => 'TikTok',
                                                'facebook' => 'Facebook',
                                                'twitter' => 'Twitter/X',
                                                'whatsapp' => 'WhatsApp',
                                            ])
                                            ->required()
                                            ->placeholder('Pilih platform...')
                                            ->columnSpan(1),
                                        Forms\Components\TextInput::make('url')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->columnSpan(2),
                                        Forms\Components\Toggle::make('show')
                                            ->label('Tampilkan')
                                            ->default(true)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 4])
                                    ->defaultItems(0)
                                    ->maxItems(4)
                                    ->addActionLabel('+ Tambah Sosmed')
                                    ->reorderable(false)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->visible(fn () => in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [UserRole::Developer, UserRole::Admin], true)),
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
                ->columns(['default' => 1, 'sm' => 2])
                ->hidden(fn (Forms\Get $get): bool => (bool) $get('is_24_hours'));
        }, array_keys($days), array_values($days));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto')
                    ->visible(fn () => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Nama Cafe'),

                Tables\Columns\TextColumn::make('owner_display')
                    ->state(fn (Cafe $record): string => $record->owner?->name ?? 'Belum ditentukan')
                    ->label('Owner')
                    ->toggleable()
                    ->visible(fn () => UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Developer),

                Tables\Columns\TextColumn::make('city.name')
                    ->label('Kota')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

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
                    ->label('Berdasarkan Kota')
                    ->relationship('city', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Cafe $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['owner']);

        if (UserRole::fromUserValue(AuthenticatedUser::get()?->role) === UserRole::Admin) {
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
            'index' => Pages\ListCafes::route('/'),
            'create' => Pages\CreateCafe::route('/create'),
            'edit' => Pages\EditCafe::route('/{record}/edit'),
        ];
    }
}
