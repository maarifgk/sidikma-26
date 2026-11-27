<?php

namespace Tests\Feature;

use App\Filament\Resources\DecreeProposals\Pages\ListDecreeProposals;
use App\Models\DecreeProposal;
use App\Models\EmployeePosition;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use App\Services\DecreeProposalEditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class DecreeProposalDocumentReplacementTest extends TestCase
{
    public function test_both_roles_can_replace_all_documents_for_all_statuses(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk, $adminSekolah] = $this->makeContext();

        foreach ([
            'Admin Induk' => $adminInduk,
            'Admin Sekolah' => $adminSekolah,
        ] as $role => $actor) {
            foreach (DecreeProposal::statusOptions() as $status => $_label) {
                $proposal = $this->makeProposal($foundation, $school, $position, $status, $actor);
                $oldPaths = $this->documentPaths($proposal);
                $data = $this->editData($proposal, $position) + $this->replacementFiles($role, $status);

                app(DecreeProposalEditService::class)->save($proposal, $actor, $data);

                $fresh = $proposal->fresh();
                $newPaths = $this->documentPaths($fresh);
                $this->assertNotSame($oldPaths, $newPaths, "$role/$status tidak mengganti path dokumen.");
                foreach ($newPaths as $path) {
                    $this->assertTrue(Storage::disk('decree-proposals')->exists($path));
                }
                foreach ($oldPaths as $path) {
                    $this->assertTrue(Storage::disk('decree-proposals')->exists($path));
                }
                $this->assertSame($status, $fresh->status);
                $this->assertSame($proposal->employee_id, $fresh->employee_id);
                $this->assertSame('Decision lama', json_decode((string) $fresh->notes, true)['decision_reason']);
            }
        }
    }

    public function test_no_replacement_file_keeps_existing_documents(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_REJECTED, $adminInduk);
        $oldPaths = $this->documentPaths($proposal);

        app(DecreeProposalEditService::class)->save($proposal, $adminInduk, $this->editData($proposal, $position));

        $fresh = $proposal->fresh();
        $this->assertSame($oldPaths, $this->documentPaths($fresh));
        foreach ($oldPaths as $path) {
            $this->assertTrue(Storage::disk('decree-proposals')->exists($path));
        }
    }

    public function test_filament_edit_action_replaces_document_for_school_admin(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $_adminInduk, $adminSekolah] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_COMPLETED, $adminSekolah);
        $oldDiploma = $proposal->diploma_path;
        $data = $this->editData($proposal, $position) + [
            'diploma_path' => UploadedFile::fake()->create('filament-replacement.pdf', 12, 'application/pdf'),
        ];

        filament()->setCurrentPanel(filament()->getPanel('app'));
        Livewire::actingAs($adminSekolah)
            ->test(ListDecreeProposals::class)
            ->assertTableActionVisible('edit', $proposal)
            ->callTableAction('edit', $proposal, data: $data);

        $fresh = $proposal->fresh();
        $this->assertNotSame($oldDiploma, $fresh->diploma_path);
        $this->assertTrue(Storage::disk('decree-proposals')->exists($fresh->diploma_path));
        $this->assertTrue(Storage::disk('decree-proposals')->exists($oldDiploma));
        $this->assertSame(DecreeProposal::STATUS_COMPLETED, $fresh->status);
    }

    public function test_replaced_document_is_readable_through_authorized_document_route(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);
        $data = $this->editData($proposal, $position) + [
            'diploma_path' => UploadedFile::fake()->create('latest-diploma.pdf', 12, 'application/pdf'),
        ];
        app(DecreeProposalEditService::class)->save($proposal, $adminInduk, $data);

        config()->set('filesystems.disks.decree-proposals.root', Storage::disk('decree-proposals')->path(''));
        $response = $this->actingAs($adminInduk)->get(route('decree-proposals.documents.view', [
            'proposal' => $proposal->fresh(),
            'document' => 'diploma_path',
        ]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_invalid_replacement_file_is_rejected_before_database_change(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);
        $oldPaths = $this->documentPaths($proposal);
        $data = $this->editData($proposal, $position) + [
            'diploma_path' => UploadedFile::fake()->create('not-a-pdf.exe', 12, 'application/octet-stream'),
        ];

        try {
            app(DecreeProposalEditService::class)->save($proposal, $adminInduk, $data);
            $this->fail('File tidak valid seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $fresh = $proposal->fresh();
        $this->assertSame($oldPaths, $this->documentPaths($fresh));
        $this->assertSame(DecreeProposal::STATUS_SUBMITTED, $fresh->status);
    }

    public function test_oversized_replacement_file_is_rejected_before_database_change(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_SUBMITTED, $adminInduk);
        $oldPaths = $this->documentPaths($proposal);
        $data = $this->editData($proposal, $position) + [
            'diploma_path' => UploadedFile::fake()->create('too-large.pdf', 10241, 'application/pdf'),
        ];

        try {
            app(DecreeProposalEditService::class)->save($proposal, $adminInduk, $data);
            $this->fail('File melebihi batas ukuran seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertSame($oldPaths, $this->documentPaths($proposal->fresh()));
        $this->assertSame([], Storage::disk('decree-proposals')->allFiles('edits'));
    }

    public function test_database_failure_keeps_old_file_and_cleans_new_file(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $adminInduk] = $this->makeContext();
        $proposal = $this->makeProposal($foundation, $school, $position, DecreeProposal::STATUS_COMPLETED, $adminInduk);
        $oldPhoto = $proposal->photo_path;
        $data = $this->editData($proposal, $position) + [
            'photo_path' => UploadedFile::fake()->image('database-failure.jpg'),
        ];
        $eventName = 'eloquent.saving: '.DecreeProposal::class;
        Event::listen($eventName, function (DecreeProposal $record): void {
            if (str_starts_with((string) $record->photo_path, 'edits/')) {
                throw new \RuntimeException('Synthetic database save failure.');
            }
        });

        $caught = false;
        try {
            app(DecreeProposalEditService::class)->save($proposal, $adminInduk, $data);
        } catch (\RuntimeException $exception) {
            $caught = true;
        } finally {
            Event::forget($eventName);
        }

        $this->assertTrue($caught, 'Kegagalan database sintetis tidak terjadi.');
        $fresh = $proposal->fresh();
        $this->assertSame($oldPhoto, $fresh->photo_path);
        $this->assertTrue(Storage::disk('decree-proposals')->exists($oldPhoto));
        $this->assertSame([], Storage::disk('decree-proposals')->allFiles('edits'));
    }

    public function test_school_admin_cannot_replace_documents_from_another_school(): void
    {
        Storage::fake('decree-proposals');
        [$foundation, $school, $position, $_adminInduk, $adminSekolah] = $this->makeContext();
        $otherSchool = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => 'Other Document School',
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $proposal = $this->makeProposal($foundation, $otherSchool, $position, DecreeProposal::STATUS_SUBMITTED, $adminSekolah);
        $oldPaths = $this->documentPaths($proposal);
        $data = $this->editData($proposal, $position) + [
            'diploma_path' => UploadedFile::fake()->create('unauthorized.pdf', 12, 'application/pdf'),
        ];

        try {
            app(DecreeProposalEditService::class)->save($proposal, $adminSekolah, $data);
            $this->fail('Admin Sekolah berhasil mengganti dokumen sekolah lain.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $this->assertSame($oldPaths, $this->documentPaths($proposal->fresh()));
    }

    /** @return array{0: Foundation, 1: School, 2: EmployeePosition, 3: User, 4: User} */
    private function makeContext(): array
    {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $foundation = Foundation::query()->create([
            'name' => "Document Foundation {$token}",
            'code' => "DOC-{$token}",
            'is_active' => true,
        ]);
        $school = School::on('sidikma_induk')->create([
            'foundation_id' => $foundation->getKey(),
            'name' => "Document School {$token}",
            'npsn' => (string) random_int(10000000, 99999999),
            'school_level' => 'SMA',
            'is_active' => true,
        ]);
        $position = EmployeePosition::on('sidikma_induk')->create([
            'code' => "DOC-POS-{$token}",
            'name' => "Document Position {$token}",
            'category' => EmployeePosition::CATEGORY_TEACHING,
            'is_active' => true,
        ]);
        $adminInduk = User::factory()->create(['email' => "document-induk-{$token}@example.test"]);
        $adminInduk->assignRole(User::ROLE_ADMIN_INDUK);
        $adminSekolah = User::factory()->create(['email' => "document-sekolah-{$token}@example.test"]);
        $adminSekolah->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create([
            'user_id' => $adminSekolah->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'status' => 'active',
        ]);

        return [$foundation, $school, $position, $adminInduk, $adminSekolah];
    }

    private function makeProposal(
        Foundation $foundation,
        School $school,
        EmployeePosition $position,
        string $status,
        User $submittedBy,
    ): DecreeProposal {
        $token = strtolower(substr(bin2hex(random_bytes(8)), 0, 8));
        $paths = [
            'photo_path' => "originals/{$token}/photo.jpg",
            'diploma_path' => "originals/{$token}/diploma.pdf",
            'application_letter_path' => "originals/{$token}/application.pdf",
            'service_statement_path' => "originals/{$token}/statement.pdf",
            'task_assignment_certificate_path' => "originals/{$token}/task.pdf",
        ];
        foreach ($paths as $path) {
            Storage::disk('decree-proposals')->put($path, "old {$path}");
        }
        $snapshot = [
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
            'school_name' => $school->name,
            'employee_code' => "DOC-CANDIDATE-{$token}",
            'name' => "Document Candidate {$token}",
            'email' => "document-candidate-{$token}@example.test",
            'phone' => '081234567890',
            'employment_status' => 'GTY',
            'nip' => null,
            'rank' => null,
            'grade' => null,
            'birth_date' => '1990-01-01',
            'birth_place' => 'Yogyakarta',
            'position_id' => $position->getKey(),
            'position_name' => $position->name,
            'assignment_start_date' => '2020-01-01',
            'last_education' => 'S1',
            'nuptk' => null,
            'program_study' => 'Pendidikan',
        ];

        return DecreeProposal::on('sidikma_induk')->create([
            'candidate_name' => $snapshot['name'],
            'candidate_email' => $snapshot['email'],
            'candidate_phone' => $snapshot['phone'],
            'candidate_school' => $school->name,
            'legacy_snapshot' => $snapshot,
            'employee_id' => null,
            'status' => $status,
            'notes' => json_encode([
                'initial' => 'Catatan awal dokumen',
                'decision_reason' => 'Decision lama',
                'rejection_reason' => 'Rejection lama',
            ], JSON_THROW_ON_ERROR),
            'submitted_by' => $submittedBy->getKey(),
            ...$paths,
        ]);
    }

    /** @return array<string, mixed> */
    private function editData(DecreeProposal $proposal, EmployeePosition $position): array
    {
        $snapshot = $proposal->legacy_snapshot;

        return [
            'edit_version' => $proposal->getRawOriginal('updated_at'),
            'name' => $proposal->candidate_name,
            'employee_code' => $snapshot['employee_code'],
            'email' => $proposal->candidate_email,
            'phone' => $proposal->candidate_phone,
            'partner_admin_number' => null,
            'employment_status' => 'GTY',
            'nip' => null,
            'rank' => null,
            'grade' => null,
            'birth_place' => 'Bantul',
            'birth_date' => '1991-02-02',
            'position_id' => $position->getKey(),
            'assignment_start_date' => '2021-02-02',
            'last_education' => 'S2',
            'nuptk' => null,
            'program_study' => 'Manajemen Pendidikan',
            'notes' => 'Catatan edit tanpa mengubah keputusan',
        ];
    }

    /** @return array<string, UploadedFile> */
    private function replacementFiles(string $role, string $status): array
    {
        $prefix = strtolower(str_replace(' ', '-', $role))."-{$status}";

        return [
            'photo_path' => UploadedFile::fake()->image("{$prefix}.jpg"),
            'diploma_path' => UploadedFile::fake()->create("{$prefix}-diploma.pdf", 12, 'application/pdf'),
            'application_letter_path' => UploadedFile::fake()->create("{$prefix}-application.pdf", 12, 'application/pdf'),
            'service_statement_path' => UploadedFile::fake()->create("{$prefix}-statement.pdf", 12, 'application/pdf'),
            'task_assignment_certificate_path' => UploadedFile::fake()->create("{$prefix}-task.pdf", 12, 'application/pdf'),
        ];
    }

    /** @return array<int, string|null> */
    private function documentPaths(DecreeProposal $proposal): array
    {
        return [
            $proposal->photo_path,
            $proposal->diploma_path,
            $proposal->application_letter_path,
            $proposal->service_statement_path,
            $proposal->task_assignment_certificate_path,
        ];
    }
}
