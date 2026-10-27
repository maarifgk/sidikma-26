<?php

namespace Tests\Feature;

use App\Filament\Resources\Arrears\ArrearsResource;
use App\Filament\Resources\Arrears\Pages\ListArrears;
use App\Models\Foundation;
use App\Models\Membership;
use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArrearsResourceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PaymentInvoice $unpaid;

    private PaymentInvoice $paid;

    private PaymentInvoice $otherSchoolInvoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);
        $foundation = Foundation::query()->create(['name' => Foundation::APPLICATION_NAME, 'code' => Foundation::APPLICATION_CODE, 'is_active' => true]);
        $this->school = School::query()->create(['foundation_id' => $foundation->id, 'name' => 'MI Pertama', 'npsn' => '12345678', 'school_level' => 'MI', 'is_active' => true]);
        $otherSchool = School::query()->create(['foundation_id' => $foundation->id, 'name' => 'MI Kedua', 'npsn' => '87654321', 'school_level' => 'MI', 'is_active' => true]);
        $this->unpaid = $this->invoice($this->school, 'unpaid', '2025/2026');
        $this->paid = $this->invoice($this->school, 'paid', '2025/2026');
        $this->otherSchoolInvoice = $this->invoice($otherSchool, 'unpaid', '2026/2027');
    }

    public function test_sidebar_links_to_working_arrears_page_and_only_unpaid_invoices_are_listed(): void
    {
        $url = ArrearsResource::getUrl(panel: 'admin', isAbsolute: false);
        $this->get('/admin')->assertOk()->assertSee($url, false)->assertDontSee('Modul Tunggakan belum tersedia');
        $this->get($url)->assertOk()->assertSee('Tunggakan');
        Livewire::test(ListArrears::class)
            ->assertCanSeeTableRecords([$this->unpaid, $this->otherSchoolInvoice])
            ->assertCanNotSeeTableRecords([$this->paid])
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');
    }

    public function test_academic_year_filter_keeps_paid_invoices_excluded(): void
    {
        Livewire::test(ListArrears::class)
            ->filterTable('academic_year', '2025/2026')
            ->assertCanSeeTableRecords([$this->unpaid])
            ->assertCanNotSeeTableRecords([$this->paid, $this->otherSchoolInvoice]);
    }

    public function test_school_admin_scope_does_not_include_another_schools_invoices(): void
    {
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        Membership::query()->create(['user_id' => $user->id, 'foundation_id' => $this->school->foundation_id, 'school_id' => $this->school->id, 'status' => 'active']);
        $this->actingAs($user);
        $this->assertSame([$this->unpaid->id], ArrearsResource::getEloquentQuery()->pluck('id')->all());
        $this->get(ArrearsResource::getUrl(panel: 'admin', isAbsolute: false))->assertForbidden();
    }

    private function invoice(School $school, string $status, string $year): PaymentInvoice
    {
        return PaymentInvoice::query()->create([
            'foundation_id' => $school->foundation_id, 'school_id' => $school->id,
            'invoice_number' => PaymentInvoice::generateInvoiceNumber(), 'description' => 'Tagihan '.$status,
            'academic_year' => $year, 'amount' => 25000, 'status' => $status,
        ]);
    }
}
