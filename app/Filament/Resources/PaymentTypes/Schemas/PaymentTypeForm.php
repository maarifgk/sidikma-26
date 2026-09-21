<?php

namespace App\Filament\Resources\PaymentTypes\Schemas;

use App\Models\Foundation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PaymentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('JENIS PEMBAYARAN')
                    ->placeholder('Masukan Jenis Pembayaran')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule
                            ->where('foundation_id', Foundation::application()->getKey()),
                    )
                    ->maxLength(255)
                    ->required(),
                Select::make('is_active')
                    ->label('STATUS')
                    ->placeholder('--Pilih--')
                    ->options([
                        1 => 'ON',
                        0 => 'OFF',
                    ])
                    ->formatStateUsing(fn (mixed $state): ?string => $state === null
                        ? null
                        : ((bool) $state ? '1' : '0'))
                    ->dehydrateStateUsing(fn (mixed $state): bool => (string) $state === '1')
                    ->native(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
