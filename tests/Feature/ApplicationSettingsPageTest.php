<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\ApplicationSettings;
use App\Models\ApplicationSetting;
use App\Models\Foundation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    public function test_application_setting_menu_opens_requested_form(): void
    {
        $this->get(ApplicationSettings::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Aplikasi')
            ->assertSee('PEMILIK')
            ->assertSee('TELEPHONE')
            ->assertSee('TITLE')
            ->assertSee('NAMA APLIKASI')
            ->assertSee('LOGO')
            ->assertSee('COPY RIGHT')
            ->assertSee('VERSI')
            ->assertSee('TOKEN WHATSAPP')
            ->assertSee('SERVER KEY')
            ->assertSee('CLIENT KEY')
            ->assertSee('ALAMAT')
            ->assertSee('Simpan')
            ->assertSee('Kembali')
            ->assertSee('SiDIKMa-GK');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Setting')
            ->assertSee('Aplikasi');
    }

    public function test_admin_can_update_application_identity_and_encrypted_keys(): void
    {
        Livewire::test(ApplicationSettings::class)
            ->set('ownerName', "L.P. Ma'arif NU PCNU Gunungkidul")
            ->set('phone', '081234567890')
            ->set('shortTitle', 'Yayasan-GK')
            ->set('applicationName', 'Sistem Informasi Yayasan Gunungkidul')
            ->set('copyrightText', 'Copyright Yayasan-GK')
            ->set('version', '2.0.0')
            ->set('whatsappToken', 'token-rahasia')
            ->set('serverKey', 'server-rahasia')
            ->set('clientKey', 'client-rahasia')
            ->set('address', 'Wonosari, Gunungkidul')
            ->call('save')
            ->assertHasNoErrors();

        $setting = ApplicationSetting::current()->refresh();

        $this->assertSame('Yayasan-GK', $setting->short_title);
        $this->assertSame('Sistem Informasi Yayasan Gunungkidul', $setting->application_name);
        $this->assertSame('token-rahasia', $setting->whatsapp_token);
        $this->assertSame('server-rahasia', $setting->server_key);
        $this->assertSame('client-rahasia', $setting->client_key);
        $this->assertDatabaseMissing('application_settings', [
            'whatsapp_token' => 'token-rahasia',
        ]);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Yayasan-GK')
            ->assertSee('Copyright Yayasan-GK')
            ->assertSee('WhatsApp: 081234567890')
            ->assertSee('Versi: 2.0.0');
    }
}
