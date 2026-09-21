<?php

namespace Tests\Feature;

use App\Filament\Resources\TreasuryTransactions\Pages\CreateTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\Pages\EditTreasuryTransaction;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\Foundation;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TreasuryTransactionResourceTest extends TestCase
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

    public function test_admin_can_open_treasury_page_and_both_input_forms(): void
    {
        $this->get(TreasuryTransactionResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('DATA BENDAHARA')
            ->assertSee('Input Pemasukan')
            ->assertSee('Input Pengeluaran')
            ->assertSee('Saldo Total')
            ->assertSee('Total Pemasukan')
            ->assertSee('Total Pengeluaran')
            ->assertSee('Rekap Pemasukan')
            ->assertSee('Rekap Pengeluaran')
            ->assertSee('Bukti Transaksi');

        $this->get(TreasuryTransactionResource::getUrl(
            'create',
            ['type' => TreasuryTransaction::TYPE_INCOME],
            panel: 'admin',
            isAbsolute: false,
        ))
            ->assertOk()
            ->assertSee('Tambah Data Pemasukan')
            ->assertSee('JENIS PEMASUKAN')
            ->assertSee('JUMLAH PEMASUKAN')
            ->assertSee('Batal')
            ->assertSee('Saldo Awal');

        $this->get(TreasuryTransactionResource::getUrl(
            'create',
            ['type' => TreasuryTransaction::TYPE_EXPENSE],
            panel: 'admin',
            isAbsolute: false,
        ))
            ->assertOk()
            ->assertSee('Tambah Data Pengeluaran')
            ->assertSee('JENIS PENGELUARAN')
            ->assertSee('JUMLAH PENGELUARAN')
            ->assertSee('Honor Admin');
    }

    public function test_admin_can_create_income_and_expense_and_balance_is_calculated(): void
    {
        Storage::fake(TreasuryTransaction::DISK);

        Livewire::withQueryParams(['type' => TreasuryTransaction::TYPE_INCOME])
            ->test(CreateTreasuryTransaction::class)
            ->fillForm([
                'transaction_date' => '2026-08-10',
                'category' => 'student_teacher_employee_dues',
                'amount' => 1500000,
                'description' => 'Iuran madrasah bulan Agustus.',
                'receipt_path' => UploadedFile::fake()->image('bukti-iuran.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::withQueryParams(['type' => TreasuryTransaction::TYPE_EXPENSE])
            ->test(CreateTreasuryTransaction::class)
            ->fillForm([
                'transaction_date' => '2026-08-11',
                'category' => 'admin_honorarium',
                'amount' => 400000,
                'description' => 'Honor admin bulan Agustus.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $income = TreasuryTransaction::query()
            ->where('transaction_type', TreasuryTransaction::TYPE_INCOME)
            ->sole();
        $expense = TreasuryTransaction::query()
            ->where('transaction_type', TreasuryTransaction::TYPE_EXPENSE)
            ->sole();

        $this->assertTrue($income->foundation->is($this->foundation));
        $this->assertTrue($expense->foundation->is($this->foundation));
        Storage::disk(TreasuryTransaction::DISK)->assertExists($income->receipt_path);

        $this->get(TreasuryTransactionResource::getUrl(panel: 'admin', isAbsolute: false))
            ->assertOk()
            ->assertSee('Rp 1.500.000')
            ->assertSee('Rp 400.000')
            ->assertSee('Rp 1.100.000')
            ->assertSee('Iuran Siswa/Guru/Pegawai')
            ->assertSee('Honor Admin');
    }

    public function test_admin_can_edit_transaction_and_deleting_it_cleans_receipt(): void
    {
        Storage::fake(TreasuryTransaction::DISK);
        Storage::disk(TreasuryTransaction::DISK)->put('receipts/receipt.pdf', 'receipt');

        $transaction = TreasuryTransaction::query()->create([
            'foundation_id' => $this->foundation->getKey(),
            'transaction_date' => '2026-08-10',
            'transaction_type' => TreasuryTransaction::TYPE_EXPENSE,
            'category' => 'operational',
            'description' => 'Biaya operasional.',
            'amount' => 200000,
            'receipt_path' => 'receipts/receipt.pdf',
        ]);

        Livewire::test(EditTreasuryTransaction::class, ['record' => $transaction->getRouteKey()])
            ->fillForm([
                'amount' => 250000,
                'description' => 'Biaya operasional kantor.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('250000.00', $transaction->fresh()->amount);

        $transaction->delete();

        Storage::disk(TreasuryTransaction::DISK)->assertMissing('receipts/receipt.pdf');
    }
}
