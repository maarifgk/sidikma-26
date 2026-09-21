<?php

namespace App\Filament\Resources\FoundationSkNumberSettings\Pages;

use App\Filament\Resources\FoundationSkNumberSettings\FoundationSkNumberSettingResource;
use App\Filament\Resources\FoundationSkTemplates\FoundationSkTemplateResource;
use App\Models\FoundationSkNumberSetting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListFoundationSkNumberSettings extends ListRecords
{
    private const PERIODS = ['Januari', 'Juli', 'Sebagai Kepala Madrasah', 'Belum Memilih Periode', 'PNS diperbantukan'];
    protected static string $resource = FoundationSkNumberSettingResource::class;
    protected string $view = 'filament.admin.pages.foundation-sk-number-settings';

    public array $settings = [];

    public function mount(): void
    {
        parent::mount();
        $existing = FoundationSkNumberSetting::query()->get()->keyBy('periode');
        $this->settings = collect(self::PERIODS)->map(function (string $periode) use ($existing): array {
            $setting = $existing->get($periode);
            return [
                'id' => $setting?->getKey(),
                'periode' => $periode,
                'nomor_pattern' => $setting->nomor_pattern ?? '',
                'digit_nomor' => $setting->digit_nomor ?? 4,
                'nomor_awal' => $setting->nomor_awal ?? 1,
                'nomor_berikutnya' => $setting->nomor_berikutnya ?? 1,
                'is_active' => (bool) ($setting->is_active ?? false),
            ];
        })->values()->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')->label('Kembali ke Template')
                ->url(fn (): string => FoundationSkTemplateResource::getUrl('index', panel: 'admin', isAbsolute: false)),
            Action::make('placeholders')->label('Placeholder Nomor')->extraAttributes(['x-on:click' => "document.getElementById('number-placeholders')?.scrollIntoView({behavior:'smooth'})"]),
        ];
    }

    public function saveSettings(): void
    {
        abort_unless(auth()->user()?->isAdminInduk() && auth()->user()?->can('sk-yayasan.manage'), 403);

        $this->validate([
            'settings' => ['required', 'array'],
            'settings.*.periode' => ['required', 'string', 'max:20'],
            'settings.*.nomor_pattern' => ['required', 'string'],
            'settings.*.digit_nomor' => ['required', 'integer', 'min:1', 'max:10'],
            'settings.*.nomor_awal' => ['required', 'integer', 'min:1'],
            'settings.*.nomor_berikutnya' => ['required', 'integer', 'min:1'],
            'settings.*.is_active' => ['boolean'],
        ]);

        $periods = collect($this->settings)->pluck('periode');
        if ($periods->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['settings' => 'Periode tidak boleh duplikat.']);
        }
        foreach ($this->settings as $index => $row) {
            if ((int) $row['nomor_berikutnya'] < (int) $row['nomor_awal']) {
                throw ValidationException::withMessages(["settings.{$index}.nomor_berikutnya" => 'Nomor berikutnya tidak boleh lebih kecil dari nomor awal.']);
            }
        }

        DB::transaction(function (): void {
            foreach ($this->settings as $row) {
                FoundationSkNumberSetting::query()->updateOrCreate(
                    isset($row['id']) ? ['id' => $row['id']] : ['periode' => $row['periode']],
                    collect($row)->except('id')->all(),
                );
            }
        });

        Notification::make()->success()->title('Pengaturan berhasil disimpan.')->send();
        $this->mount();
    }

    public function getSubheading(): ?string
    {
        return 'Atur format dan urutan nomor SK per periode. Placeholder nomor tersedia pada setiap pengaturan.';
    }

    public function getTitle(): string
    {
        return 'Pengaturan SK Yayasan';
    }
}
