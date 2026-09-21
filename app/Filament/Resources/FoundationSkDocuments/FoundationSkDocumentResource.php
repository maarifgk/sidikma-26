<?php
namespace App\Filament\Resources\FoundationSkDocuments;
use App\Filament\Resources\FoundationSkDocuments\Pages\CreateFoundationSkDocument;
use App\Filament\Resources\FoundationSkDocuments\Pages\EditFoundationSkDocument;
use App\Filament\Resources\FoundationSkDocuments\Pages\ListFoundationSkDocuments;
use App\Filament\Resources\FoundationSkDocuments\Schemas\FoundationSkDocumentForm;
use App\Filament\Resources\FoundationSkDocuments\Tables\FoundationSkDocumentsTable;
use App\Models\FoundationSkDocument;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
class FoundationSkDocumentResource extends Resource
{
 protected static ?string $model=FoundationSkDocument::class;
 protected static bool $shouldRegisterNavigation=false;
 protected static string|BackedEnum|null $navigationIcon='heroicon-o-document-text';
 protected static ?string $navigationLabel='Dokumen SK';
 protected static ?string $modelLabel='Dokumen SK';
 protected static ?string $pluralModelLabel='Dokumen SK';
 public static function canViewAny(): bool { return auth()->user()?->isAdminInduk() || (auth()->user()?->can('sk-yayasan.view') ?? false); }
 public static function form(Schema $schema): Schema { return FoundationSkDocumentForm::configure($schema); }
 public static function table(Table $table): Table { return FoundationSkDocumentsTable::configure($table); }
 public static function getEloquentQuery(): Builder { $user=auth()->user();$q=parent::getEloquentQuery()->with(['user.employee.school','template','uploadedBy']);return $user instanceof User && $user->isAdminInduk() ? $q : $q->whereHas('user.employee',fn($e)=>$e->whereIn('school_id',$user?->accessibleSchoolIds() ?? [])); }
 public static function getPages(): array { return ['index'=>ListFoundationSkDocuments::route('/'),'create'=>CreateFoundationSkDocument::route('/create'),'edit'=>EditFoundationSkDocument::route('/{record}/edit')]; }
}
