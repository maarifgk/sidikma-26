<?php

namespace Tests\Feature;

use App\Filament\Resources\LearningModules\LearningModuleResource;
use App\Filament\Resources\LearningModules\Pages\ListLearningModules;
use App\Models\Foundation;
use App\Models\LearningModule;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LearningModuleResourceTest extends TestCase
{
    use RefreshDatabase;

    private Foundation $foundation;

    protected function setUp(): void
    {
        parent::setUp();

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);

        $this->foundation = Foundation::query()->create([
            'name' => Foundation::APPLICATION_NAME,
            'code' => Foundation::APPLICATION_CODE,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_module_upload_and_list_page(): void
    {
        $this->get(LearningModuleResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Upload Modul Baru')
            ->assertSee('Kelas')
            ->assertSee('Jenis Modul')
            ->assertSee('Semester')
            ->assertSee('Mata Pelajaran')
            ->assertSee('BAB')
            ->assertSee('Upload File Modul')
            ->assertSee('PDF/DOC/DOCX/PPT/PPTX/XLS/XLSX')
            ->assertSee('Daftar Modul')
            ->assertSee('Tanggal Upload');
    }

    public function test_admin_can_upload_module_file_and_metadata(): void
    {
        Storage::fake(LearningModule::DISK);

        Livewire::test(ListLearningModules::class)
            ->set('className', 'Kelas 1')
            ->set('moduleType', 'Modul Ajar IKM')
            ->set('semester', 1)
            ->set('subject', 'Bahasa Indonesia')
            ->set('chapter', 'BAB 1 - Bunyi Apa')
            ->set('moduleFile', UploadedFile::fake()->create('modul-ajar.pdf', 100, 'application/pdf'))
            ->call('uploadModule')
            ->assertHasNoErrors();

        $module = LearningModule::query()->sole();

        $this->assertTrue($module->foundation->is($this->foundation));
        $this->assertSame('Kelas 1', $module->class_name);
        $this->assertSame('Modul Ajar IKM', $module->module_type);
        $this->assertSame(1, $module->semester);
        $this->assertSame('modul-ajar.pdf', $module->original_name);
        Storage::disk(LearningModule::DISK)->assertExists($module->file_path);
    }

    public function test_invalid_module_extension_is_rejected(): void
    {
        Storage::fake(LearningModule::DISK);

        Livewire::test(ListLearningModules::class)
            ->set('className', 'Kelas 1')
            ->set('moduleType', 'Modul Ajar')
            ->set('semester', 1)
            ->set('subject', 'Bahasa Indonesia')
            ->set('chapter', 'BAB 1')
            ->set('moduleFile', UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream'))
            ->call('uploadModule')
            ->assertHasErrors(['moduleFile']);

        $this->assertDatabaseCount('learning_modules', 0);
    }

    public function test_deleting_module_cleans_stored_file(): void
    {
        Storage::fake(LearningModule::DISK);
        Storage::disk(LearningModule::DISK)->put('files/module.pdf', 'module');
        $module = LearningModule::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'class_name' => 'Kelas 1',
            'module_type' => 'Modul Ajar',
            'semester' => 1,
            'subject' => 'Bahasa Indonesia',
            'chapter' => 'BAB 1',
            'file_path' => 'files/module.pdf',
            'original_name' => 'module.pdf',
            'uploaded_at' => now(),
        ]);

        $module->delete();

        Storage::disk(LearningModule::DISK)->assertMissing('files/module.pdf');
    }
}
