<?php

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends BaseRegister
{
    protected static string $view = 'filament.pages.auth.register';

    /**
     * Allowed roles for public registration.
     * 'developer' is INTENTIONALLY excluded — only assignable via database/tinker.
     */
    private const ALLOWED_ROLES = ['admin', 'roastery', 'user'];

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    $this->getNameFormComponent()->label('Nama Lengkap'),
                    $this->getEmailFormComponent()->label('Alamat Email'),
                    $this->getPasswordFormComponent()->label('Kata Sandi'),
                    $this->getPasswordConfirmationFormComponent()->label('Konfirmasi Kata Sandi'),
                    $this->getRoleFormComponent(),
                ])
                ->statePath('data'),
        ];
    }

    protected function getRoleFormComponent(): Component
    {
        return Select::make('role')
            ->label('Daftar Sebagai')
            ->options([
                'user' => 'Pengunjung / User',
                'admin' => 'Owner Cafe',
                'roastery' => 'Owner Roastery',
            ])
            ->default('user')
            ->required()
            ->in(self::ALLOWED_ROLES);
    }

    /**
     * Override to enforce role whitelist at the database level.
     */
    protected function handleRegistration(array $data): Model
    {
        // Double-check: even if someone bypasses frontend, block unauthorized roles
        if (! in_array($data['role'] ?? '', self::ALLOWED_ROLES, true)) {
            $data['role'] = 'user'; // Default fallback
        }

        return parent::handleRegistration($data);
    }

    public function getRedirectUrl(): string
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user && $user->role === UserRole::User) {
            return route('home');
        }

        return parent::getRedirectUrl();
    }
}
