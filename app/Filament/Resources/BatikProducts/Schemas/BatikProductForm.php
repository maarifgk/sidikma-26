<?php

namespace App\Filament\Resources\BatikProducts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BatikProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('image_path')
                    ->label('GAMBAR BATIK')
                    ->disk('public')
                    ->directory('batik-products')
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->image()
                    ->imageEditor()
                    ->maxSize(1024000)
                    ->downloadable()
                    ->openable()
                    ->helperText('Format JPG, PNG, atau WebP; maksimal 1000 MB. Gambar dapat diganti atau dihapus.')
                    ->columnSpanFull(),
                TextInput::make('name')
                    ->label('NAMA BATIK')
                    ->maxLength(255)
                    ->required(),
                TextInput::make('stock')
                    ->label('STOK')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('price')
                    ->label('HARGA')
                    ->prefix('Rp')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('size_label')
                    ->label('UKURAN/SATUAN BATIK')
                    ->placeholder('Contoh: meter')
                    ->maxLength(100)
                    ->required(),
            ])
            ->columns(2);
    }
}
