<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PaymentPage;
use App\Filament\Resources\Billings\Pages\ListBillings;
use App\Models\ApplicationSetting;
use App\Models\Employee;
use App\Models\Foundation;
use App\Models\MidtransTransaction;
use App\Models\PaymentInvoice;
use App\Models\PaymentTransaction;
use App\Models\School;
use App\Models\User;
use App\Services\Payments\MidtransPaymentService;
use App\Services\Payments\SafeLocalPaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class MidtransCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private PaymentInvoice $invoice;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole(User::ROLE_ADMIN_INDUK);
        $this->actingAs($admin);
        $foundation = Foundation::query()->create(['name' => Foundation::APPLICATION_NAME, 'code' => Foundation::APPLICATION_CODE, 'is_active' => true]);
        $school = School::query()->create(['foundation_id' => $foundation->id, 'name' => 'MI Test', 'npsn' => '12345678', 'school_level' => 'MI', 'is_active' => true]);
        $this->employee = Employee::factory()->create(['foundation_id' => $foundation->id, 'school_id' => $school->id, 'user_id' => $admin->id]);
        $this->invoice = PaymentInvoice::query()->create([
            'foundation_id' => $foundation->id, 'school_id' => $school->id, 'user_id' => $admin->id,
            'employee_id' => $this->employee->id, 'legacy_invoice_id' => 292, 'invoice_number' => 'INV-TEST',
            'academic_year' => '2026/2027', 'description' => 'Pembayaran SK', 'amount' => 25000, 'status' => 'unpaid',
        ]);
        ApplicationSetting::current()->update(['server_key' => 'SB-Mid-server-test', 'client_key' => 'SB-Mid-client-test']);
    }

    public function test_checkout_uses_settings_and_reuses_order_for_legacy_invoice(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'test-token'])]);
        $first = app(MidtransPaymentService::class)->checkout($this->invoice);
        $second = app(MidtransPaymentService::class)->checkout($this->invoice);
        $this->assertSame($first['token'], $second['token']);
        $this->assertFalse($first['production']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 25000
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-test:')));
        $this->assertDatabaseCount('midtrans_transactions', 1);
        $this->assertNotSame('test-token', DB::table('midtrans_transactions')->value('snap_token'));
        $this->assertArrayNotHasKey('snap_token', MidtransTransaction::first()->toArray());
    }

    public function test_pay_button_opens_modal_and_dispatches_snap_checkout(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'test-token'])]);
        Livewire::test(PaymentPage::class)
            ->call('openPayment', $this->employee->id)
            ->assertDispatched('open-modal', id: 'payment-checkout')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Lanjutkan ke Midtrans')
            ->call('payOnline', $this->invoice->id)
            ->assertHasNoErrors()
            ->assertDispatched('midtrans-checkout', token: 'test-token', clientKey: 'SB-Mid-client-test', production: false);
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
    }

    public function test_verified_notification_marks_invoice_paid_once_without_login(): void
    {
        $transaction = $this->transaction();
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->remoteStatus($transaction))]);
        Auth::logout();
        $payload = $this->notification($transaction);
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertOk();
        $this->assertSame('paid', $this->invoice->refresh()->status);
        $this->assertSame('online_gateway', $this->invoice->payment_method);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertSame('paid', $transaction->refresh()->transaction_status);
    }

    public function test_invalid_signature_cannot_change_invoice(): void
    {
        $transaction = $this->transaction();
        $payload = $this->notification($transaction);
        $payload['signature_key'] = str_repeat('0', 128);
        $this->postJson(route('payments.midtrans.notification'), $payload)->assertForbidden();
        Http::assertNothingSent();
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
    }

    public function test_notification_cannot_override_verified_pending_status(): void
    {
        $transaction = $this->transaction();
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response($this->remoteStatus($transaction, 'pending'))]);
        $this->postJson(route('payments.midtrans.notification'), $this->notification($transaction) + ['transaction_status' => 'settlement'])->assertOk();
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_notification_rejects_wrong_amount(): void
    {
        $transaction = $this->transaction();
        $status = $this->remoteStatus($transaction);
        $status['gross_amount'] = '1.00';
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response($status)]);
        $this->postJson(route('payments.midtrans.notification'), $this->notification($transaction))->assertUnprocessable();
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
    }

    public function test_local_callback_cannot_modify_online_transaction(): void
    {
        $transaction = $this->transaction();
        $this->expectException(ValidationException::class);
        app(SafeLocalPaymentService::class)->callback($transaction->order_id, 'paid');
    }

    public function test_missing_keys_are_rejected_before_creating_order(): void
    {
        ApplicationSetting::current()->update(['server_key' => null]);
        try {
            app(MidtransPaymentService::class)->checkout($this->invoice);
            $this->fail('Missing keys accepted');
        } catch (ValidationException) {
            Http::assertNothingSent();
            $this->assertDatabaseCount('midtrans_transactions', 0);
        }
    }

    public function test_production_uses_production_endpoint_and_client_key(): void
    {
        ApplicationSetting::current()->update(['server_key' => 'Mid-server-test', 'client_key' => 'Mid-client-test', 'midtrans_is_production' => true]);
        Http::fake(['app.midtrans.com/*' => Http::response(['token' => 'production-token'])]);
        $checkout = app(MidtransPaymentService::class)->checkout($this->invoice);
        $this->assertTrue($checkout['production']);
        $this->assertDatabaseHas('midtrans_transactions', ['source' => 'midtrans_production']);
    }

    public function test_production_keys_with_copy_paste_whitespace_are_normalized(): void
    {
        ApplicationSetting::current()->update([
            'server_key' => "  Mid-server-test\n",
            'client_key' => "\tMid-client-test  ",
            'midtrans_is_production' => true,
        ]);
        Http::fake(['app.midtrans.com/*' => Http::response(['token' => 'production-token'])]);
        $checkout = app(MidtransPaymentService::class)->checkout($this->invoice);
        $this->assertSame('Mid-client-test', $checkout['clientKey']);
        $this->assertTrue($checkout['production']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Basic '.base64_encode('Mid-server-test:')));
    }

    public function test_production_keys_with_sandbox_setting_explain_saved_mode_without_sending_request(): void
    {
        ApplicationSetting::current()->update([
            'server_key' => 'Mid-server-test',
            'client_key' => 'Mid-client-test',
            'midtrans_is_production' => false,
        ]);
        try {
            app(MidtransPaymentService::class)->checkout($this->invoice);
            $this->fail('A mismatched mode must not silently enable real payments');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('mode tersimpan Sandbox', $exception->errors()['payment'][0]);
            Http::assertNothingSent();
        }
    }

    public function test_other_school_admin_cannot_create_checkout(): void
    {
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);
        $this->actingAs($user);
        $this->expectException(AuthorizationException::class);
        app(MidtransPaymentService::class)->checkout($this->invoice);
    }

    public function test_timeout_keeps_same_reserved_order_for_retry(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::failedConnection()]);
        foreach ([1, 2] as $attempt) {
            try {
                app(MidtransPaymentService::class)->checkout($this->invoice);
                $this->fail('Expected checkout error');
            } catch (ValidationException) {
                $this->assertDatabaseCount('midtrans_transactions', 1);
            }
        }
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
    }

    public function test_capture_with_fraud_challenge_does_not_mark_invoice_paid(): void
    {
        $transaction = $this->transaction();
        $status = $this->remoteStatus($transaction, 'capture') + ['fraud_status' => 'challenge'];
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response($status)]);
        $this->postJson(route('payments.midtrans.notification'), $this->notification($transaction))->assertOk();
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
        $this->assertDatabaseCount('payment_transactions', 0);
    }

    public function test_old_pending_notification_cannot_revert_paid_invoice(): void
    {
        $transaction = $this->transaction();
        Http::fake(['api.sandbox.midtrans.com/*' => Http::sequence()
            ->push($this->remoteStatus($transaction))
            ->push($this->remoteStatus($transaction, 'pending'))]);
        $this->postJson(route('payments.midtrans.notification'), $this->notification($transaction))->assertOk();
        $this->postJson(route('payments.midtrans.notification'), $this->notification($transaction))->assertOk();
        $this->assertSame('paid', $this->invoice->refresh()->status);
        $this->assertSame('paid', $transaction->refresh()->transaction_status);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_paid_invoice_cannot_create_checkout(): void
    {
        $this->invoice->update(['status' => 'paid']);
        $this->expectException(ValidationException::class);
        app(MidtransPaymentService::class)->checkout($this->invoice);
    }

    public function test_unpaid_invoice_with_previous_payment_requires_reconciliation(): void
    {
        PaymentTransaction::query()->create([
            'payment_invoice_id' => $this->invoice->id,
            'legacy_payment_id' => 123,
            'amount' => 10000,
            'status' => 'paid',
        ]);
        try {
            app(MidtransPaymentService::class)->checkout($this->invoice);
            $this->fail('Previously paid amount must be reconciled');
        } catch (ValidationException) {
            Http::assertNothingSent();
            $this->assertDatabaseCount('midtrans_transactions', 0);
        }
    }

    public function test_sandbox_keys_cannot_be_used_in_production_mode(): void
    {
        ApplicationSetting::current()->update(['midtrans_is_production' => true]);
        $this->expectException(ValidationException::class);
        app(MidtransPaymentService::class)->checkout($this->invoice);
    }

    public function test_modal_rejects_invoice_belonging_to_another_employee(): void
    {
        $this->invoice->update(['employee_id' => null, 'user_id' => null]);
        Livewire::test(PaymentPage::class)
            ->call('openPayment', $this->employee->id)
            ->call('payOnline', $this->invoice->id)
            ->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseCount('midtrans_transactions', 0);
    }

    public function test_app_billing_pay_action_opens_midtrans_for_owned_unpaid_invoice(): void
    {
        $this->actAsBillingUser();
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'user-token'])]);
        Livewire::test(ListBillings::class)
            ->assertCanSeeTableRecords([$this->invoice])
            ->assertTableActionVisible('pay', $this->invoice)
            ->callTableAction('pay', $this->invoice, data: ['payment_method' => 'online'])
            ->assertHasNoErrors()
            ->assertDispatched('midtrans-checkout', token: 'user-token');
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
        Http::assertSentCount(1);
    }

    public function test_app_billing_paid_invoice_has_no_pay_action(): void
    {
        $this->actAsBillingUser();
        $this->invoice->update(['status' => 'paid']);
        Livewire::test(ListBillings::class)->assertTableActionHidden('pay', $this->invoice);
        Http::assertNothingSent();
    }

    public function test_app_billing_manual_payment_does_not_create_online_order(): void
    {
        $this->actAsBillingUser();
        Livewire::test(ListBillings::class)
            ->callTableAction('pay', $this->invoice, data: ['payment_method' => 'manual'])
            ->assertHasNoErrors()
            ->assertNotDispatched('midtrans-checkout');
        Http::assertNothingSent();
        $this->assertDatabaseCount('midtrans_transactions', 0);
        $this->assertSame('unpaid', $this->invoice->refresh()->status);
    }

    public function test_app_billing_user_cannot_read_another_users_payment_status(): void
    {
        $transaction = $this->transaction();
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($user);
        Livewire::test(ListBillings::class)
            ->assertCanNotSeeTableRecords([$this->invoice])
            ->call('refreshOnlinePayment', $transaction->order_id)
            ->assertForbidden();
        Http::assertNothingSent();
    }

    private function actAsBillingUser(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('app'));
        $user = User::factory()->create();
        $user->assignRole(User::ROLE_GURU_PEGAWAI);
        $this->actingAs($user);
        $this->employee->update(['user_id' => $user->id]);
        $this->invoice->update(['user_id' => $user->id]);
    }

    private function transaction(): MidtransTransaction
    {
        return MidtransTransaction::query()->create([
            'payment_invoice_id' => $this->invoice->id, 'user_id' => $this->invoice->user_id,
            'order_id' => 'SIDIKMA-TEST', 'idempotency_key' => 'SIDIKMA-TEST',
            'gross_amount' => 25000, 'transaction_status' => 'pending', 'source' => 'midtrans_sandbox',
        ]);
    }

    private function remoteStatus(MidtransTransaction $transaction, string $state = 'settlement'): array
    {
        return ['order_id' => $transaction->order_id, 'transaction_id' => 'TX-TEST', 'transaction_status' => $state,
            'gross_amount' => '25000.00', 'currency' => 'IDR', 'payment_type' => 'bank_transfer', 'status_code' => '200'];
    }

    private function notification(MidtransTransaction $transaction): array
    {
        return ['order_id' => $transaction->order_id, 'status_code' => '200', 'gross_amount' => '25000.00',
            'signature_key' => hash('sha512', $transaction->order_id.'20025000.00SB-Mid-server-test')];
    }
}
