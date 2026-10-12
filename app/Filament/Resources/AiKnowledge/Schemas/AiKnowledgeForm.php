<?php

namespace App\Filament\Resources\AiKnowledge\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AiKnowledgeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basis Pengetahuan')
                    ->description('Bila pesan pengguna memuat salah satu kata kunci, jawaban ini langsung diberikan (tanpa memanggil AI).')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Hanya untuk penanda di panel admin, mis. "Tentang developer".'),

                        Textarea::make('keywords')
                            ->label('Kata kunci')
                            ->required()
                            ->rows(2)
                            ->helperText('Pisahkan dengan koma. Contoh: developer, pembuat, siapa yang membuat, pengembang aplikasi.'),

                        Textarea::make('answer')
                            ->label('Jawaban')
                            ->required()
                            ->rows(4)
                            ->helperText('Jawaban yang dikirim ke pengguna bila cocok.'),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->helperText('Urutan pemeriksaan; kecil diperiksa lebih dulu.'),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
