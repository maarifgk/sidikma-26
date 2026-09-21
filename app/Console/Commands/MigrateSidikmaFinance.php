<?php

namespace App\Console\Commands;

use App\Support\SidikmaFinanceData;
use App\Support\SidikmaMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaFinance extends Command
{
    protected $signature = 'sidikma:migrate-finance {--dry-run} {--force}';

    protected $description = 'Migrasi tagihan, histori pembayaran, dan kas SIDIKMA';

    public function handle(): int
    {
        $a = $this->analyze();
        $dry = (bool) $this->option('dry-run');
        $this->display($a, $dry);
        if ($dry) {
            $this->writeDryRunReports($a);
        }
        if ($a['invalid'] || $a['conflict']) {
            $this->error('Migrasi diblokir.');

            return self::FAILURE;
        }
        if ($dry) {
            $this->info('DRY RUN SELESAI — PostgreSQL tidak diubah.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm('Lanjutkan migrasi keuangan?')) {
            return self::SUCCESS;
        }
        try {
            $this->migrate($a);
        } catch (Throwable $e) {
            report($e);
            $this->error('Gagal; transaksi di-rollback.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $v = $this->analyze();
        if ($v['invoice_eligible'] || $v['transaction_eligible'] || $v['treasury_eligible'] || $v['invalid'] || $v['conflict']) {
            return self::FAILURE;
        }
        $this->info("SELESAI — {$a['invoice_eligible']} invoice, {$a['transaction_eligible']} transaksi, {$a['treasury_eligible']} kas.");

        return self::SUCCESS;
    }

    private function analyze(): array
    {
        $m = DB::connection('mysql_sidikma');
        $p = DB::connection('pgsql');
        $foundation = $p->table('foundations')->whereNull('deleted_at')->sole();
        $users = $p->table('users')->pluck('id')->flip();
        $employees = $p->table('employees')->whereNull('deleted_at')->whereNotNull('user_id')->pluck('id', 'user_id');
        $classNames = $m->table('kelas')->pluck('nama_kelas', 'id');
        $schools = $p->table('schools')->whereNull('deleted_at')->get()->keyBy(fn ($s) => SidikmaMasterData::normalized($s->name));
        $years = $m->table('tahun_ajaran')->pluck('tahun', 'id');
        $types = $m->table('jenis_pembayaran')->pluck('pembayaran', 'id');
        $targetInvoices = $p->table('payment_invoices')->whereNotNull('legacy_invoice_id')->get()->keyBy('legacy_invoice_id');
        $targetTransactions = $p->table('payment_transactions')->get()->keyBy('legacy_payment_id');
        $bills = $m->table('tagihan')->orderBy('id')->get();
        $billRows = [];
        $billMap = [];
        $a = ['invoice_source' => $bills->count(), 'invoice_eligible' => 0, 'invoice_existing' => 0, 'transaction_source' => 0, 'transaction_valid' => 0, 'transaction_eligible' => 0, 'transaction_existing' => 0, 'treasury_source' => 0, 'treasury_eligible' => 0, 'treasury_existing' => 0, 'treasury_skipped' => 0, 'invalid' => 0, 'conflict' => 0, 'orphan_invoice_user' => 0, 'orphan_transaction_invoice' => 0, 'zero_or_negative_amount' => 0, 'unknown_payment_status' => 0, 'unknown_payment_method' => 0, 'empty_bulan_id' => 0, 'suspicious_order_id' => 0, 'optional_installment_fields_missing' => 0];
        foreach ($bills as $b) {
            $schoolName = $classNames->get($b->kelas_id);
            $school = $schoolName ? $schools->get(SidikmaMasterData::normalized($schoolName)) : null;
            $year = SidikmaFinanceData::academicYear($years->get($b->thajaran_id), $b->created_at);
            $uid = (int) $b->user_id;
            $userId = $users->has($uid) ? $uid : null;
            if (! $userId) {
                $a['orphan_invoice_user']++;
            }$existing = $targetInvoices->get($b->id);
            if (! $school || ! $year || $b->nilai < 0) {
                $a['invalid']++;
                $status = 'INVALID';
            } elseif ($existing) {
                $a['invoice_existing']++;
                $status = 'EXISTING';
            } else {
                $a['invoice_eligible']++;
                $status = 'ELIGIBLE';
            }
            $general = SidikmaMasterData::generalPaymentType((string) $types->get($b->jenis_pembayaran)) ?? trim((string) $types->get($b->jenis_pembayaran));
            $desc = trim((string) $b->keterangan);
            if ($desc === '' || $desc === '-') {
                $desc = $general ?: 'Tagihan SIDIKMA';
            }$payload = ['legacy_invoice_id' => (int) $b->id, 'foundation_id' => (int) $foundation->id, 'school_id' => $school?->id, 'user_id' => $userId, 'employee_id' => $userId ? $employees->get($userId) : null, 'academic_year' => $year, 'invoice_number' => 'SIDIKMA-TAGIHAN-'.$b->id, 'description' => mb_substr($desc.' [SIDIKMA #'.$b->id.']', 0, 255), 'amount' => $b->nilai, 'status' => SidikmaFinanceData::invoiceStatus($b->status), 'payment_method' => null, 'due_date' => null, 'paid_at' => SidikmaFinanceData::invoiceStatus($b->status) === 'paid' ? ($b->updated_at ?: $b->created_at) : null, 'notes' => 'Jenis umum: '.($general ?: '-')];
            $billRows[] = ['legacy_id' => (int) $b->id, 'status' => $status, 'target_id' => $existing?->id, 'payload' => $payload];
            $billMap[(int) $b->id] = $existing?->id;
        }
        $payments = $m->table('payment')->orderBy('id')->get();
        $a['transaction_source'] = $payments->count();
        $transactionRows = [];
        foreach ($payments as $t) {
            $existing = $targetTransactions->get($t->id);
            $schoolName = $classNames->get($t->kelas_id);
            $school = $schoolName ? $schools->get(SidikmaMasterData::normalized($schoolName)) : null;
            $uid = (int) $t->user_id;
            $userId = $users->has($uid) ? $uid : null;
            $invoiceId = $billMap[(int) $t->tagihan_id] ?? null;
            if (! $invoiceId && ! collect($billRows)->contains(fn ($r) => $r['legacy_id'] === (int) $t->tagihan_id)) {
                $a['orphan_transaction_invoice']++;
            }
            $amount = $this->amount($t->nilai);
            $rawStatus = SidikmaMasterData::normalized((string) $t->status);
            $rawMethod = SidikmaMasterData::normalized((string) $t->metode_pembayaran);
            if (! in_array($rawStatus, ['lunas', 'failed'], true)) $a['unknown_payment_status']++;
            if (! in_array($rawMethod, ['manual', 'online'], true)) $a['unknown_payment_method']++;
            if ($amount === null || (float) $amount <= 0) {
                $a['zero_or_negative_amount']++;
            }
            if ($t->bulan_id === null || $t->bulan_id === '') $a['empty_bulan_id']++;
            if ($t->order_id !== null && ((int) $t->order_id >= 2147483647 || (int) $t->order_id <= 0)) $a['suspicious_order_id']++;
            $hasInstallmentFields = property_exists($t, 'installment_group') || property_exists($t, 'installment_term') || property_exists($t, 'installment_sequence');
            if (! $hasInstallmentFields) $a['optional_installment_fields_missing']++;
            if ($amount === null || (float) $amount <= 0) {
                $a['invalid']++;
                $status = 'INVALID';
            } elseif ($existing) {
                $a['transaction_existing']++;
                $status = 'EXISTING';
            } else {
                $a['transaction_eligible']++;
                $status = 'ELIGIBLE';
            }
            $a['transaction_valid'] += $status !== 'INVALID' ? 1 : 0;
            $transactionRows[] = ['status' => $status, 'payload' => ['payment_invoice_id' => $invoiceId, 'user_id' => $userId, 'school_id' => $school?->id, 'legacy_payment_id' => (int) $t->id, 'legacy_invoice_id' => $t->tagihan_id ?: null, 'gateway_order_id' => $t->order_id ?: null, 'amount' => $amount, 'payment_method' => SidikmaFinanceData::method($t->metode_pembayaran), 'status' => SidikmaFinanceData::transactionStatus($t->status), 'receipt_url' => $this->nullable($t->pdf_url), 'installment_group' => $this->optional($t, 'installment_group'), 'installment_term' => $this->optional($t, 'installment_term'), 'installment_sequence' => $this->optional($t, 'installment_sequence'), 'transacted_at' => $this->dateTime($t->created_at), 'legacy_snapshot' => json_encode((array) $t, JSON_UNESCAPED_SLASHES)]];
        }
        $treasury = $m->table('bendaharas')->orderBy('id')->get();
        $a['treasury_source'] = $treasury->count();
        $treasuryRows = [];
        foreach ($treasury as $t) {
            $type = ((float) $t->pemasukan) > 0 ? 'income' : (((float) $t->pengeluaran) > 0 ? 'expense' : null);
            $amount = $type === 'income' ? (float) $t->pemasukan : (float) $t->pengeluaran;
            $marker = '[SIDIKMA BENDAHARA #'.$t->id.']';
            $existing = $p->table('treasury_transactions')->where('description', 'like', '%'.$marker.'%')->first();
            if (! $type || $amount <= 0) {
                $a['treasury_skipped']++;
                $status = 'SKIPPED';
            } elseif ($existing) {
                $a['treasury_existing']++;
                $status = 'EXISTING';
            } else {
                $a['treasury_eligible']++;
                $status = 'ELIGIBLE';
            }$legacyCategory = $type === 'income' ? $t->jenis_pemasukan_id : $t->jenis_pengeluaran_id;
            $treasuryRows[] = ['status' => $status, 'payload' => ['foundation_id' => (int) $foundation->id, 'transaction_date' => $t->tanggal, 'transaction_type' => $type, 'category' => $type ? SidikmaFinanceData::treasuryCategory($type, $legacyCategory) : 'other_income', 'description' => trim($t->uraian).' '.$marker, 'amount' => $amount, 'receipt_path' => $this->nullable($t->bukti_transaksi)]];
        }

        $a['duplicate_order_groups'] = $payments->whereNotNull('order_id')->groupBy('order_id')->filter(fn ($rows) => $rows->count() > 1)->count();
        $a['duplicate_order_rows'] = $payments->whereNotNull('order_id')->groupBy('order_id')->map(fn ($rows) => max(0, $rows->count() - 1))->sum();
        $a['duplicate_tagihan_bulan_groups'] = $payments->groupBy(fn ($row) => $row->tagihan_id.'|'.($row->bulan_id ?? 'NULL'))->filter(fn ($rows) => $rows->count() > 1)->count();
        $a['duplicate_user_tagihan_groups'] = $payments->groupBy(fn ($row) => $row->user_id.'|'.$row->tagihan_id)->filter(fn ($rows) => $rows->count() > 1)->count();
        return $a + compact('billRows', 'transactionRows', 'treasuryRows');
    }

    private function display(array $a, bool $dry): void
    {
        $this->info($dry ? 'DRY RUN KEUANGAN SIDIKMA' : 'MIGRASI KEUANGAN SIDIKMA');
        $this->table(['Pemeriksaan', 'Jumlah'], collect($a)->except(['billRows', 'transactionRows', 'treasuryRows'])->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->warn('Tindakan: data yatim, duplikat, status/metode tidak dikenal, nominal invalid, dan order mencurigakan tetap dilaporkan; tidak dihapus diam-diam. Data tanpa invoice/user akan masuk quarantine/error report sebelum migrasi aktual.');
        $this->info('Error fatal: '.($a['invalid'] + $a['conflict']).' | Warning audit: '.collect($a)->only(['orphan_invoice_user','orphan_transaction_invoice','duplicate_order_groups','duplicate_tagihan_bulan_groups','duplicate_user_tagihan_groups','zero_or_negative_amount','unknown_payment_status','unknown_payment_method','empty_bulan_id','suspicious_order_id'])->sum());
    }

    /** @return array{classification:string,reason_codes:array<int,string>,reason_detail:string,planned_action:string} */
    public static function classify(array $facts): array
    {
        $reasons = [];
        if (($facts['orphan_invoice_user'] ?? false) || ($facts['orphan_payment_invoice'] ?? false)) $reasons[] = 'orphan';
        if (($facts['amount'] ?? null) !== null && (float) $facts['amount'] < 0) $reasons[] = 'invalid_amount';
        if (($facts['amount'] ?? null) !== null && (float) $facts['amount'] === 0.0) $reasons[] = 'zero_amount';
        if (($facts['unknown_status'] ?? false)) $reasons[] = 'unknown_status';
        if (($facts['unknown_method'] ?? false)) $reasons[] = 'unknown_method';
        if (($facts['duplicate_order'] ?? false)) $reasons[] = 'duplicate_order_id';
        if (($facts['duplicate_tagihan_bulan'] ?? false)) $reasons[] = 'duplicate_tagihan_bulan';
        if (($facts['duplicate_user_tagihan'] ?? false)) $reasons[] = 'duplicate_user_tagihan';
        if (($facts['order_zero'] ?? false)) $reasons[] = 'suspicious_order_id_zero';
        if (($facts['order_max_int'] ?? false)) $reasons[] = 'suspicious_order_id_max_int';
        if (($facts['bulan_empty'] ?? false)) $reasons[] = 'period_unknown';
        if (($facts['failed'] ?? false)) $reasons[] = 'failed_status_requires_review';
        if (($facts['online_without_receipt'] ?? false)) $reasons[] = 'online_without_pdf';
        $reasons = array_values(array_unique($reasons));
        $classification = 'ready';
        if (array_intersect($reasons, ['invalid_amount','unknown_status','unknown_method'])) $classification = 'invalid';
        elseif (in_array('orphan', $reasons, true)) $classification = 'orphan';
        elseif (array_intersect($reasons, ['duplicate_order_id','duplicate_tagihan_bulan','duplicate_user_tagihan'])) $classification = 'duplicate';
        elseif ($reasons) $classification = 'requires_business_decision';
        return ['classification'=>$classification,'reason_codes'=>$reasons,'reason_detail'=>implode(', ', $reasons) ?: 'No audit issues','planned_action'=>$classification === 'ready' ? 'candidate_for_migration' : 'quarantine_and_review'];
    }

    private function writeDryRunReports(array $a): void
    {
        $stamp = now()->format('Ymd_His');
        $dir = storage_path('app/audits/sidikma-finance/'.$stamp);
        if (! is_dir($dir)) mkdir($dir, 0750, true);
        $orders = collect($a['transactionRows'])->groupBy(fn ($r) => (string) ($r['payload']['gateway_order_id'] ?? ''));
        $pairs = collect($a['transactionRows'])->groupBy(fn ($r) => $r['payload']['legacy_invoice_id'].'|'.($r['payload']['period_id'] ?? 'NULL'));
        $users = collect($a['transactionRows'])->groupBy(fn ($r) => $r['payload']['user_id'].'|'.$r['payload']['legacy_invoice_id']);
        $invoiceRecords = collect($a['billRows'])->map(fn ($r) => $this->auditRecord('tagihan', $r['legacy_id'], $r['payload'], self::classify(['amount'=>$r['payload']['amount'],'orphan_invoice_user'=>! $r['payload']['user_id']])))
            ->values();
        $paymentRecords = collect($a['transactionRows'])->map(function (array $r) use ($orders, $pairs, $users): array {
            $p = $r['payload']; $raw = json_decode($p['legacy_snapshot'] ?? '{}', true) ?: [];
            $facts = ['amount'=>$p['amount'],'orphan_payment_invoice'=>! $p['payment_invoice_id'],'unknown_status'=>! in_array($p['status'], ['paid','pending','failed'], true),'unknown_method'=>! in_array($p['payment_method'], ['cash','online_gateway'], true),'duplicate_order'=>($p['gateway_order_id'] !== null && $orders->get((string)$p['gateway_order_id'], collect())->count()>1),'duplicate_tagihan_bulan'=>$pairs->get($p['legacy_invoice_id'].'|NULL', collect())->count()>1,'duplicate_user_tagihan'=>$users->get($p['user_id'].'|'.$p['legacy_invoice_id'], collect())->count()>1,'order_zero'=>(string)$p['gateway_order_id']==='0','order_max_int'=>(string)$p['gateway_order_id']==='2147483647','bulan_empty'=>! ($raw['bulan_id'] ?? null),'failed'=>$p['status']==='failed','online_without_receipt'=>$p['payment_method']==='online_gateway' && empty($p['receipt_url'])];
            return $this->auditRecord('payment', $p['legacy_payment_id'], $p, self::classify($facts));
        })->values();
        $records = $invoiceRecords->merge($paymentRecords);
        $summary = ['audit_timestamp'=>now()->toIso8601String(),'counts'=>['invoice_source'=>$a['invoice_source'],'payment_source'=>$a['transaction_source'],'invoice_ready'=>$invoiceRecords->where('classification','ready')->count(),'invoice_quarantine'=>$invoiceRecords->whereIn('classification',['quarantine','requires_business_decision','duplicate','orphan','invalid'])->count(),'payment_ready'=>$paymentRecords->where('classification','ready')->count(),'payment_quarantine'=>$paymentRecords->whereIn('classification',['quarantine','requires_business_decision','duplicate','orphan','invalid'])->count(),'ready_nominal'=>$paymentRecords->where('classification','ready')->sum('original_amount'),'quarantine_nominal'=>$paymentRecords->where('classification','!=','ready')->sum('original_amount'),'invalid_nominal'=>$paymentRecords->whereIn('classification',['invalid'])->sum('original_amount'),'failed_count'=>$paymentRecords->where('original_status','failed')->count(),'zero_negative_count'=>$a['zero_or_negative_amount'],'warnings'=>$records->where('classification','!=','ready')->count()],'report_directory'=>$dir];
        file_put_contents($dir.'/dry-run-summary.json', json_encode($summary, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/ready-invoices.json', json_encode($invoiceRecords->where('classification','ready')->values(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/ready-payments.json', json_encode($paymentRecords->where('classification','ready')->values(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/quarantine-records.json', json_encode($records->where('classification','!=','ready')->values(), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        $h=fopen($dir.'/quarantine-summary.csv','wb'); fputcsv($h,['source_table','source_id','classification','reason_codes','planned_action']); foreach($records->where('classification','!=','ready') as $r) fputcsv($h,[$r['source_table'],$r['source_id'],$r['classification'],implode('|',$r['reason_codes']),$r['planned_action']]); fclose($h);
        $this->info('Laporan dry-run lokal: '.$dir);
    }

    private function auditRecord(string $table, int $id, array $payload, array $classification): array
    {
        return ['source_table'=>$table,'source_id'=>$id,'legacy_invoice_id'=>$payload['legacy_invoice_id'] ?? ($table==='tagihan' ? $id : null),'legacy_payment_id'=>$table==='payment' ? $id : null,'classification'=>$classification['classification'],'reason_codes'=>$classification['reason_codes'],'reason_detail'=>$classification['reason_detail'],'raw_snapshot'=>$payload['legacy_snapshot'] ?? json_encode($payload, JSON_UNESCAPED_UNICODE),'related_user_id'=>$payload['user_id'] ?? null,'related_invoice_id'=>$payload['payment_invoice_id'] ?? null,'related_school_id'=>$payload['school_id'] ?? null,'related_class_id'=>$payload['class_id'] ?? null,'related_academic_year'=>$payload['academic_year'] ?? null,'related_period_id'=>$payload['period_id'] ?? null,'original_status'=>$payload['status'] ?? null,'original_method'=>$payload['payment_method'] ?? null,'original_amount'=>$payload['amount'] ?? null,'original_order_id'=>$payload['gateway_order_id'] ?? null,'planned_action'=>$classification['planned_action']];
    }

    private function migrate(array $a): void
    {
        DB::connection('pgsql')->transaction(function () use ($a) {
            $p = DB::connection('pgsql');
            $now = now();
            $map = $p->table('payment_invoices')->whereNotNull('legacy_invoice_id')->pluck('id', 'legacy_invoice_id')->all();
            foreach ($a['billRows'] as $r) {
                if ($r['status'] === 'ELIGIBLE') {
                    $map[$r['legacy_id']] = $p->table('payment_invoices')->insertGetId($r['payload'] + ['created_at' => $now, 'updated_at' => $now]);
                }
            }foreach ($a['transactionRows'] as $r) {
                if ($r['status'] === 'ELIGIBLE') {
                    $x = $r['payload'];
                    $x['payment_invoice_id'] = $map[$x['legacy_invoice_id']] ?? null;
                    $p->table('payment_transactions')->insert($x + ['created_at' => $now, 'updated_at' => $now]);
                }
            }foreach ($a['treasuryRows'] as $r) {
                if ($r['status'] === 'ELIGIBLE') {
                    $p->table('treasury_transactions')->insert($r['payload'] + ['created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    private function amount(mixed $v): ?string
    {
        $v = preg_replace('/[^0-9,-]/', '', (string) $v) ?? '';
        $v = str_replace(',', '.', $v);

        return is_numeric($v) ? number_format((float) $v, 2, '.', '') : null;
    }

    private function nullable(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function optional(object $row, string $field): mixed
    {
        return property_exists($row, $field) ? ($row->{$field} ?? null) : null;
    }

    private function dateTime(mixed $v): ?string
    {
        $v = $this->nullable($v);

        return $v === null || str_starts_with($v, '0000-00-00') ? null : $v;
    }
}
