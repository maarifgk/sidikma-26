<?php

namespace App\Filament\Resources\FoundationAnnualReports\Schemas;

use App\Models\Foundation;
use App\Models\FoundationAnnualReport;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class FoundationAnnualReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('budget_year')
                    ->label('TAHUN ANGGARAN')
                    ->placeholder('Contoh: 2026/2027')
                    ->maxLength(20)
                    ->rules(fn (?FoundationAnnualReport $record): array => [
                        self::uniqueBudgetYearRule($record),
                    ])
                    ->required()
                    ->columnSpanFull(),
                self::pdfUpload(
                    'work_program_report_path',
                    'LAPORAN PROGRAM KERJA',
                    'work-program-reports',
                ),
                self::pdfUpload(
                    'financial_report_path',
                    'LAPORAN KEUANGAN',
                    'financial-reports',
                ),
            ])
            ->columns(2);
    }

    private static function pdfUpload(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk(FoundationAnnualReport::DISK)
            ->visibility('private')
            ->directory($directory)
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize(1024000)
            ->downloadable()
            ->openable()
            ->previewable(false)
            ->required()
            ->helperText('Format PDF, maksimal 1000 MB.');
    }

    private static function uniqueBudgetYearRule(?FoundationAnnualReport $record): Unique
    {
        $rule = Rule::unique('foundation_annual_reports', 'budget_year')
            ->where(fn ($query) => $query->where('foundation_id', Foundation::application()->getKey()));

        return $record ? $rule->ignore($record) : $rule;
    }
}
