<?php
namespace Tests\Feature;

use App\Models\MidtransTransaction;
use App\Models\PaymentInvoice;
use App\Services\Payments\LocalPaymentGateway;
use App\Services\Payments\LocalPaymentService;
use App\Services\Payments\SafeLocalPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LocalPaymentPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
        Schema::create('users', fn (Blueprint $t) => $t->id());
        Schema::create('payment_invoices', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('legacy_invoice_id')->nullable(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('invoice_number'); $t->decimal('amount', 15, 2); $t->string('status'); });
        Schema::create('midtrans_transactions', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('payment_invoice_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->string('order_id')->unique(); $t->string('transaction_id')->nullable()->unique(); $t->string('idempotency_key')->unique(); $t->decimal('gross_amount',15,2); $t->string('transaction_status'); $t->string('source'); $t->json('raw_status_snapshot')->nullable(); $t->timestamps(); });
        DB::table('users')->insert(['id'=>1]);
    }

    public function test_pending_persists_and_duplicate_create_reuses_transaction(): void
    {
        $invoice = $this->invoice(10, 1, 10000, 'unpaid'); $service = new LocalPaymentService(new LocalPaymentGateway());
        $first = $service->create($invoice, 1); $second = $service->create($invoice, 1);
        $this->assertSame('pending', $first->transaction_status); $this->assertSame($first->id, $second->id); $this->assertSame(1, MidtransTransaction::count());
    }

    public function test_callback_paid_is_idempotent_and_terminal(): void
    {
        $t=$this->transaction(11,10000,'pending');$service=new SafeLocalPaymentService(new LocalPaymentGateway());
        $paid=$service->callback($t->order_id,'paid','TX-1','10000',11);$again=$service->callback($t->order_id,'failed','TX-1','10000',11);
        $this->assertSame('paid',$paid->transaction_status);$this->assertSame('paid',$again->transaction_status);$this->assertSame(1,MidtransTransaction::count());
    }

    public function test_callback_rejects_unknown_order(): void
    {
        $service=new SafeLocalPaymentService(new LocalPaymentGateway());$this->expectException(ValidationException::class);$service->callback('UNKNOWN','paid',null,'20000',12);
    }

    public function test_callback_rejects_amount_mismatch(): void
    {
        $service=new SafeLocalPaymentService(new LocalPaymentGateway());$t=$this->transaction(12,20000,'pending');
        $this->expectException(ValidationException::class);$service->callback($t->order_id,'paid',null,'1',12);
    }

    public function test_callback_rejects_invoice_mismatch(): void
    {
        $service=new SafeLocalPaymentService(new LocalPaymentGateway());$t=$this->transaction(13,20000,'pending');
        $this->expectException(ValidationException::class);$service->callback($t->order_id,'paid',null,'20000',999);
    }

    public function test_paid_invoice_legacy_and_invoice_837_are_rejected(): void
    {
        $service=new LocalPaymentService(new LocalPaymentGateway());
        foreach([[837,null],[20,999]] as [$id,$legacy]){$invoice=$this->invoice($id,1,10000,'unpaid',$legacy);$this->expectException(ValidationException::class);$service->create($invoice,1);}
    }

    private function invoice(int $id,int $user,float $amount,string $status,?int $legacy=null): PaymentInvoice {DB::table('payment_invoices')->insert(['id'=>$id,'legacy_invoice_id'=>$legacy,'user_id'=>$user,'invoice_number'=>'INV-'.$id,'amount'=>$amount,'status'=>$status]);return PaymentInvoice::query()->findOrFail($id);}
    private function transaction(int $invoice,float $amount,string $status): MidtransTransaction {return MidtransTransaction::query()->create(['payment_invoice_id'=>$invoice,'user_id'=>1,'order_id'=>'LOCAL-'.$invoice,'idempotency_key'=>'LOCAL-'.$invoice,'gross_amount'=>$amount,'transaction_status'=>$status,'source'=>'local_mock']);}
}
