<?php

namespace App\Filament\Resources\StudentEnrollments;

use App\Filament\Resources\StudentEnrollments\Pages\CreateStudentEnrollment;
use App\Filament\Resources\StudentEnrollments\Pages\EditStudentEnrollment;
use App\Filament\Resources\StudentEnrollments\Pages\ListStudentEnrollments;
use App\Filament\Resources\StudentEnrollments\Schemas\StudentEnrollmentForm;
use App\Filament\Resources\StudentEnrollments\Tables\StudentEnrollmentsTable;
use App\Models\StudentEnrollment;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentEnrollmentResource extends Resource
{
    protected static ?string $model = StudentEnrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Data Jumlah Siswa';

    protected static ?string $pluralModelLabel = 'Data Jumlah Siswa';

    public static function form(Schema $schema): Schema
    {
        return StudentEnrollmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentEnrollmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentEnrollments::route('/'),
            'create' => CreateStudentEnrollment::route('/create'),
            'edit' => EditStudentEnrollment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'school',
            fn (Builder $schoolQuery): Builder => $schoolQuery
                ->accessibleTo($user)
                ->where('is_active', true),
        );
    }
}
