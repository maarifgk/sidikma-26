<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use App\Models\AcademicYear;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('TAHUN AJARAN')
                    ->placeholder('Contoh: 2027/2028')
                    ->helperText('Gunakan format tahun awal/tahun berikutnya.')
                    ->rules([
                        'regex:/^\d{4}\/\d{4}$/',
                        fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! AcademicYear::isValidPeriod((string) $value)) {
                                $fail('Tahun ajaran harus berurutan, contoh 2027/2028.');
                            }
                        },
                    ])
                    ->unique(ignoreRecord: true)
                    ->maxLength(20)
                    ->required(),
                Toggle::make('is_active')
                    ->label('STATUS')
                    ->onColor('success')
                    ->offColor('danger')
                    ->default(true)
                    ->required(),
            ])
            ->columns(2);
    }
}
