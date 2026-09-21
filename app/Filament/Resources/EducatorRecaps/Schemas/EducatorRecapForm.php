<?php

namespace App\Filament\Resources\EducatorRecaps\Schemas;

use App\Models\EducatorRecap;
use App\Models\School;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class EducatorRecapForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year')
                    ->label('TAHUN PELAJARAN')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => EducatorRecap::academicYearOptions())
                    ->default(EducatorRecap::currentAcademicYear())
                    ->live()
                    ->native(false)
                    ->required(),
                Select::make('school_id')
                    ->label('SEKOLAH/MADRASAH')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => self::schoolOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->rules(fn (Get $get, ?EducatorRecap $record): array => [
                        Rule::in(array_keys(School::activeOptionsFor())),
                        self::schoolYearUniqueRule($get('academic_year'), $record),
                    ])
                    ->required(),
                TextInput::make('asn_certified')
                    ->label('ASN SERTIFIKASI')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('asn_uncertified')
                    ->label('ASN NON SERTIFIKASI')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('foundation_certified_inpassing')
                    ->label('YAYASAN SERTIFIKASI/INPASSING')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('foundation_uncertified')
                    ->label('YAYASAN NON-SERTIFIKASI')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ])
            ->columns(2);
    }

    /** @return array<int, string> */
    private static function schoolOptions(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return School::query()
            ->accessibleTo($user)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private static function schoolYearUniqueRule(?string $academicYear, ?EducatorRecap $record): Unique
    {
        $rule = Rule::unique('educator_recaps', 'school_id')
            ->where(fn ($query) => $query->where('academic_year', $academicYear));

        return $record ? $rule->ignore($record) : $rule;
    }
}
