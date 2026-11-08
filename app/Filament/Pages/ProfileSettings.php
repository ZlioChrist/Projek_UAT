<?php

namespace App\Filament\Pages;

use App\Support\AuthenticatedUser;
use App\Enums\UserRole;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProfileSettings extends Page
{
    protected static ?string $title = 'Kelola User';

    protected static ?string $navigationLabel = 'Kelola User';

    protected static ?string $navigationGroup = 'Manajemen User';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.profile-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return in_array(UserRole::fromUserValue(AuthenticatedUser::get()?->role), [
            UserRole::Developer,
            UserRole::Admin,
            UserRole::Roastery,
        ], true);
    }

    public function mount(): void
    {
        $user = AuthenticatedUser::require();
        $this->form->fill([
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi akun')
                    ->schema([
                        FileUpload::make('profile_photo_path')
                            ->label('Foto profil')
                            ->image()
                            ->avatar()
                            ->directory('profile-photos')
                            ->visibility('public')
                            ->maxSize(2048),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('username')
                            ->label('Username')
                            ->alphaDash()
                            ->minLength(3)
                            ->maxLength(40)
                            ->unique('users', 'username', ignoreRecord: true),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email', ignoreRecord: true),
                    ])
                    ->columns(2),
                Section::make('Ganti password')
                    ->description('Kosongkan kedua kolom jika tidak ingin mengganti password.')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password baru')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrated(fn ($state): bool => filled($state)),
                        TextInput::make('password_confirmation')
                            ->label('Ulangi password baru')
                            ->password()
                            ->revealable()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model(AuthenticatedUser::get());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = AuthenticatedUser::require();

        $user->name = $data['name'];
        $user->username = filled($data['username'] ?? null) ? $data['username'] : null;
        $user->email = $data['email'];
        $user->profile_photo_path = $data['profile_photo_path'] ?? null;

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();
        $this->form->fill([
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
        ]);

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();
    }
}
