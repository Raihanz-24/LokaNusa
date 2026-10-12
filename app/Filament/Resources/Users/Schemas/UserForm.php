<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Peran')
                            ->options(fn (): array => self::roleOptions())
                            ->required()
                            ->native(false)
                            ->helperText('Developer memiliki akses penuh.'),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): string => $operation === 'edit'
                                ? 'Biarkan kosong bila tidak ingin mengubah password.'
                                : 'Minimal 8 karakter.'),

                        TextInput::make('ai_token_limit')
                            ->label('Batas token AI per hari')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Tanpa batas')
                            ->helperText('Batas token AI harian untuk pengguna ini. Kosongkan untuk tanpa batas.'),
                    ]),
            ]);
    }

    /**
     * Opsi peran. Hanya Developer yang boleh menetapkan peran Developer
     * (mencegah eskalasi hak akses oleh pengelola pengguna lain).
     *
     * @return array<string, string>
     */
    private static function roleOptions(): array
    {
        $options = UserRole::options();

        if (! Auth::user()?->isSuperAdmin()) {
            unset($options[UserRole::SuperAdmin->value]);
        }

        return $options;
    }
}
