<?php

namespace Tests\Unit;

use App\Console\Commands\MigrateSidikmaFinance;
use PHPUnit\Framework\TestCase;

class MigrateSidikmaFinanceQuarantineTest extends TestCase
{
    public function test_valid_invoice_is_ready(): void
    {
        $this->assertSame('ready', MigrateSidikmaFinance::classify(['amount' => 25000])['classification']);
    }

    public function test_payment_without_invoice_is_orphan(): void
    {
        $this->assertSame('orphan', MigrateSidikmaFinance::classify(['amount' => 25000, 'orphan_payment_invoice' => true])['classification']);
    }

    public function test_zero_amount_requires_business_decision(): void
    {
        $this->assertSame('requires_business_decision', MigrateSidikmaFinance::classify(['amount' => 0])['classification']);
    }

    public function test_negative_amount_is_invalid(): void
    {
        $this->assertSame('invalid', MigrateSidikmaFinance::classify(['amount' => -1])['classification']);
    }

    public function test_duplicate_order_is_duplicate(): void
    {
        $result = MigrateSidikmaFinance::classify(['amount' => 25000, 'duplicate_order' => true]);
        $this->assertSame('duplicate', $result['classification']);
        $this->assertContains('duplicate_order_id', $result['reason_codes']);
    }

    public function test_failed_status_is_not_ready(): void
    {
        $this->assertSame('requires_business_decision', MigrateSidikmaFinance::classify(['amount' => 25000, 'failed' => true])['classification']);
    }

    public function test_empty_period_requires_business_decision(): void
    {
        $this->assertSame('requires_business_decision', MigrateSidikmaFinance::classify(['amount' => 25000, 'bulan_empty' => true])['classification']);
    }

    public function test_all_reasons_are_retained(): void
    {
        $result = MigrateSidikmaFinance::classify(['amount' => 0, 'orphan_payment_invoice' => true, 'duplicate_order' => true, 'bulan_empty' => true]);
        $this->assertSame('orphan', $result['classification']);
        $this->assertSame(['orphan', 'zero_amount', 'duplicate_order_id', 'period_unknown'], $result['reason_codes']);
    }
}
