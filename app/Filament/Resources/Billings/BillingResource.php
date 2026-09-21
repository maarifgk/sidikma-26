<?php

namespace App\Filament\Resources\Billings;

use App\Filament\Resources\Billings\Pages\CreateBilling;
use App\Filament\Resources\Billings\Pages\EditBilling;
use App\Filament\Resources\Billings\Pages\ListBillings;
use App\Filament\Resources\Billings\Schemas\BillingForm;
use App\Filament\Resources\Billings\Tables\BillingsTable;
use App\Models\PaymentInvoice;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BillingResource extends Resource
{
    protected static ?string $model = PaymentInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMinus;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static ?string $slug = 'billings';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'description', 'academic_year', 'school.name', 'user.name', 'employee.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Sekolah' => $record->school?->name ?? '-',
            'Nilai' => 'Rp '.number_format((float) $record->amount, 0, ',', '.'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return BillingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BillingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBillings::route('/'),
            'create' => CreateBilling::route('/create'),
            'edit' => EditBilling::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'employee', 'school']);
        $user = auth()->user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdminInduk()) {
            return $query;
        }

        if ($user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)) {
            return $query->whereIn('school_id', $user->accessibleSchoolIds());
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query
                ->where('user_id', $user->getKey())
                ->orWhereHas(
                    'employee',
                    fn (Builder $employeeQuery): Builder => $employeeQuery
                        ->where('user_id', $user->getKey()),
                );
        });
    }
}
