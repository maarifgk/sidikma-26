<?php

namespace Tests\Feature;

use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));

        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_INDUK);

        $this->actingAs($user);
    }

    public function test_school_can_be_created_with_valid_form_data(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Nusantara',
            'code' => 'YPN-001',
            'is_active' => true,
        ]);

        Livewire::test(CreateSchool::class)
            ->assertFormFieldDoesNotExist('foundation_id')
            ->assertFormFieldDoesNotExist('phone')
            ->set('data.name', 'Madrasah Ibtidaiyah Nusantara')
            ->set('data.npsn', ' 00123456 ')
            ->set('data.school_level', 'MI')
            ->set('data.email', 'mi@example.test')
            ->set('data.address', 'Jalan Pendidikan Nomor 1')
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('schools', [
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Ibtidaiyah Nusantara',
            'npsn' => '00123456',
            'school_level' => 'MI',
            'phone' => null,
            'email' => 'mi@example.test',
            'is_active' => true,
        ]);
    }

    public function test_school_form_rejects_invalid_and_duplicate_data(): void
    {
        $foundation = Foundation::query()->create([
            'name' => 'Yayasan Pendidikan Ma\'arif',
            'code' => 'YPM-001',
            'is_active' => true,
        ]);

        School::query()->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Madrasah Pertama',
            'npsn' => '12345678',
            'school_level' => 'MI',
            'is_active' => true,
        ]);

        Livewire::test(CreateSchool::class)
            ->set('data.name', '')
            ->set('data.npsn', ' 12345678 ')
            ->set('data.school_level', null)
            ->set('data.email', 'email-tidak-valid')
            ->set('data.address', str_repeat('A', 2001))
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'npsn' => 'unique',
                'school_level' => 'required',
                'email' => 'email',
                'address' => 'max',
            ]);

        $this->assertSame(1, School::query()->count());
    }
}
