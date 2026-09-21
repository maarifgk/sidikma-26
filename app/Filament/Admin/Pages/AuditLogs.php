<?php
namespace App\Filament\Admin\Pages;
use App\Services\AuditLogQueryService;
use App\Models\AuditLog;
use App\Models\School;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
class AuditLogs extends Page
{
 protected static ?string $navigationLabel = 'Audit Log Presensi'; protected static ?string $slug = 'presensi/audit-log'; protected static bool $shouldRegisterNavigation = true; protected static string|\UnitEnum|null $navigationGroup = 'HOMES';
 public ?int $selectedSchoolId = null; public string $actionFilter = ''; public string $entityType = ''; public string $dateFrom = ''; public string $dateTo = '';
 public static function canAccess(): bool { return auth()->user()?->hasAnyRole([\App\Models\User::ROLE_ADMIN_INDUK,\App\Models\User::ROLE_ADMIN_SEKOLAH_MADRASAH]) ?? false; }
 public function mount(): void { $this->dateFrom = now(config('attendance.timezone'))->subDays(30)->toDateString(); $this->dateTo = now(config('attendance.timezone'))->toDateString(); }
 public function content(Schema $schema): Schema { return $schema->components([View::make('filament.admin.pages.audit-logs')->viewData(fn () => $this->pageData())]); }
 private function pageData(): array { $query = app(AuditLogQueryService::class)->forUser(auth()->user())->with(['actor','target','employee','school'])->when($this->selectedSchoolId, fn(Builder $q) => $q->where('school_id',$this->selectedSchoolId))->when($this->actionFilter, fn(Builder $q) => $q->where('action','like','%'.$this->actionFilter.'%'))->when($this->entityType, fn(Builder $q) => $q->where('entity_type',$this->entityType))->when($this->dateFrom, fn(Builder $q) => $q->whereDate('created_at','>=',$this->dateFrom))->when($this->dateTo, fn(Builder $q) => $q->whereDate('created_at','<=',$this->dateTo)); return ['logs'=>$query->latest()->limit(300)->get(),'schoolOptions'=>School::query()->whereKey(auth()->user()->accessibleSchoolIds())->pluck('name','id'),'from'=>$this->dateFrom,'to'=>$this->dateTo]; }
}
