<?php
namespace App\Filament\Resources\FoundationSkTemplates\Pages;
use App\Filament\Resources\FoundationSkNumberSettings\FoundationSkNumberSettingResource;
use App\Filament\Resources\FoundationSkTemplates\FoundationSkTemplateResource;
use App\Models\FoundationSkNumberSetting;
use Filament\Actions\{Action,CreateAction};
use Filament\Resources\Pages\ListRecords;
class ListFoundationSkTemplates extends ListRecords {
 protected static string $resource=FoundationSkTemplateResource::class;
 protected function getHeaderActions():array{return [Action::make('numberSettings')->label('Pengaturan SK Yayasan')->url(fn():string=>FoundationSkNumberSettingResource::getUrl('index',panel:'admin',isAbsolute:false)),CreateAction::make()->label('Buat Template')];}
 public function getSubheading():?string{$active=FoundationSkNumberSetting::query()->where('is_active',true)->count();return "Kelola template SK Yayasan dan gunakan untuk generate dokumen. Periode aktif: {$active}";}
}
