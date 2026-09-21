<?php

namespace App\Filament\Admin\Pages;

use App\Models\ApplicationSetting;
use App\Models\Foundation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class ApplicationSettings extends Page
{
    use WithFileUploads;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'application-settings';

    protected static ?string $title = 'Aplikasi';

    public string $ownerName = '';

    public string $phone = '';

    public string $shortTitle = '';

    public string $applicationName = '';

    public mixed $logo = null;

    public ?string $existingLogoPath = null;

    public string $copyrightText = '';

    public string $version = '';

    public string $whatsappToken = '';

    public string $serverKey = '';

    public string $clientKey = '';

    public string $address = '';

    public function mount(): void
    {
        $this->loadSettings();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', Foundation::application()) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.admin.pages.application-settings'),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $validated = $this->validate([
            'ownerName' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s().]+$/'],
            'shortTitle' => ['required', 'string', 'max:100'],
            'applicationName' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024000'],
            'copyrightText' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:50'],
            'whatsappToken' => ['nullable', 'string', 'max:2000'],
            'serverKey' => ['nullable', 'string', 'max:2000'],
            'clientKey' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'ownerName' => 'pemilik',
            'phone' => 'telephone',
            'shortTitle' => 'title',
            'applicationName' => 'nama aplikasi',
            'copyrightText' => 'copy right',
            'whatsappToken' => 'token WhatsApp',
            'serverKey' => 'server key',
            'clientKey' => 'client key',
            'address' => 'alamat',
        ]);

        $setting = ApplicationSetting::current();
        $logoPath = $setting->logo_path;

        if ($this->logo !== null) {
            $newLogoPath = $this->logo->store('application-logos', 'public');

            if (filled($logoPath) && $logoPath !== $newLogoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $newLogoPath;
        }

        $setting->update([
            'owner_name' => $validated['ownerName'],
            'phone' => $validated['phone'],
            'short_title' => $validated['shortTitle'],
            'application_name' => $validated['applicationName'],
            'logo_path' => $logoPath,
            'copyright_text' => $validated['copyrightText'],
            'version' => $validated['version'],
            'whatsapp_token' => $validated['whatsappToken'],
            'server_key' => $validated['serverKey'],
            'client_key' => $validated['clientKey'],
            'address' => $validated['address'],
        ]);

        $this->logo = null;
        $this->existingLogoPath = $logoPath;

        Notification::make()
            ->title('Pengaturan aplikasi berhasil disimpan')
            ->success()
            ->send();

        $this->dispatch('refresh-topbar');
    }

    public function removeLogo(): void
    {
        abort_unless(static::canAccess(), 403);

        $setting = ApplicationSetting::current();

        if (filled($setting->logo_path)) {
            Storage::disk('public')->delete($setting->logo_path);
        }

        $setting->update(['logo_path' => null]);
        $this->reset(['logo', 'existingLogoPath']);

        Notification::make()
            ->title('Logo aplikasi berhasil dihapus')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    private function loadSettings(): void
    {
        $setting = ApplicationSetting::current();

        $this->ownerName = $setting->owner_name;
        $this->phone = $setting->phone ?? '';
        $this->shortTitle = $setting->short_title;
        $this->applicationName = $setting->application_name;
        $this->existingLogoPath = $setting->logo_path;
        $this->copyrightText = $setting->copyright_text ?? '';
        $this->version = $setting->version ?? '';
        $this->whatsappToken = $setting->whatsapp_token ?? '';
        $this->serverKey = $setting->server_key ?? '';
        $this->clientKey = $setting->client_key ?? '';
        $this->address = $setting->address ?? '';
    }
}
