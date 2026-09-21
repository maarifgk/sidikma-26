<?php

namespace Tests\Feature;

use App\Filament\App\Pages\MyComplaints;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Models\Complaint;
use App\Models\User;
use App\Policies\ComplaintPolicy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComplaintPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_open_private_complaint_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $teacher = User::factory()->create();
        $teacher->assignRole(User::ROLE_GURU_PEGAWAI);

        $this->actingAs($teacher)
            ->get(MyComplaints::getUrl(panel: 'app', isAbsolute: false))
            ->assertOk()
            ->assertSee('Pengaduan Saya')
            ->assertSee('hanya dapat dibaca oleh Anda dan admin induk');
    }

    public function test_school_admin_cannot_open_teacher_complaint_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $schoolAdmin = User::factory()->create();
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        $this->actingAs($schoolAdmin)
            ->get(MyComplaints::getUrl(panel: 'app', isAbsolute: false))
            ->assertForbidden();
    }

    public function test_only_submitter_and_admin_induk_can_view_a_complaint(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $submitter = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $schoolAdmin = User::factory()->create();
        $adminInduk = User::factory()->create();
        $submitter->assignRole(User::ROLE_GURU_PEGAWAI);
        $otherTeacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $schoolAdmin->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);

        $complaint = Complaint::query()->create([
            'submitted_by' => $submitter->getKey(),
            'category' => 'workplace',
            'subject' => 'Masalah lingkungan kerja',
            'description' => 'Kronologi pengaduan privat yang hanya boleh dibaca pihak terkait.',
        ]);
        $policy = new ComplaintPolicy;

        $this->assertTrue($policy->view($submitter, $complaint));
        $this->assertTrue($policy->view($adminInduk, $complaint));
        $this->assertFalse($policy->view($otherTeacher, $complaint));
        $this->assertFalse($policy->view($schoolAdmin, $complaint));
    }

    public function test_only_admin_induk_can_open_complaint_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $adminInduk = User::factory()->create();
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);

        $this->actingAs($adminInduk)
            ->get(ComplaintResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk();
    }

    public function test_attachment_is_only_available_to_submitter_and_admin_induk(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Storage::fake(Complaint::DISK);
        Storage::disk(Complaint::DISK)->put('attachments/evidence.jpg', 'private-image');

        $submitter = User::factory()->create();
        $otherTeacher = User::factory()->create();
        $adminInduk = User::factory()->create();
        $submitter->assignRole(User::ROLE_GURU_PEGAWAI);
        $otherTeacher->assignRole(User::ROLE_GURU_PEGAWAI);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);
        $complaint = Complaint::query()->create([
            'submitted_by' => $submitter->getKey(),
            'category' => 'facilities',
            'subject' => 'Kerusakan fasilitas sekolah',
            'description' => 'Kerusakan fasilitas dilengkapi dengan bukti foto yang bersifat privat.',
            'attachments' => [[
                'path' => 'attachments/evidence.jpg',
                'name' => 'bukti.jpg',
                'mime_type' => 'image/jpeg',
                'size' => 13,
            ]],
        ]);
        $url = route('complaints.attachments.view', [$complaint, 0]);

        $this->actingAs($submitter)->get($url)->assertOk();
        $this->actingAs($adminInduk)->get($url)->assertOk();
        $this->actingAs($otherTeacher)->get($url)->assertForbidden();
    }
}
