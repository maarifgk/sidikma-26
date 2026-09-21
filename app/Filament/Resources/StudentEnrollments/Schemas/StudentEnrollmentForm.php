<?php

namespace App\Filament\Resources\StudentEnrollments\Schemas;

use App\Models\School;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StudentEnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('academic_year')
                    ->label('TAHUN PELAJARAN')
                    ->placeholder('-- Pilih --')
                    ->options(fn (): array => StudentEnrollment::academicYearOptions())
                    ->default(StudentEnrollment::currentAcademicYear())
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
                    ->rules(fn (Get $get, ?StudentEnrollment $record): array => [
                        Rule::in(array_keys(School::activeOptionsFor())),
                        self::schoolYearUniqueRule($get('academic_year'), $record),
                    ])
                    ->required(),
                ...collect(StudentEnrollment::GRADE_FIELDS)
                    ->map(fn (string $field): TextInput => TextInput::make($field)
                        ->label(strtoupper($field))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required())
                    ->all(),
            ])
            ->columns(3);
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

    private static function schoolYearUniqueRule(?string $academicYear, ?StudentEnrollment $record): Unique
    {
        $rule = Rule::unique('student_enrollments', 'school_id')
            ->where(fn ($query) => $query->where('academic_year', $academicYear));

        return $record ? $rule->ignore($record) : $rule;
    }
}
