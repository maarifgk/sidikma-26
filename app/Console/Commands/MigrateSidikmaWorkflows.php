<?php

namespace App\Console\Commands;

use App\Support\SidikmaMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateSidikmaWorkflows extends Command
{
    protected $signature = 'sidikma:migrate-workflows {--dry-run} {--force}';

    protected $description = 'Migrasi usulan SK dan mutasi SIDIKMA';

    public function handle(): int
    {
        $a = $this->analyze();
        $dry = (bool) $this->option('dry-run');
        $this->table(['Item', 'Source', 'Eligible', 'Existing', 'Snapshot', 'Invalid'], [['Usulan SK', $a['proposal_source'], $a['proposal_eligible'], $a['proposal_existing'], $a['proposal_snapshot'], $a['invalid']], ['Mutasi', $a['mutation_source'], $a['mutation_eligible'], $a['mutation_existing'], $a['mutation_snapshot'], $a['invalid']]]);
        if ($a['invalid']) {
            return self::FAILURE;
        }if ($dry) {
            $this->info('DRY RUN SELESAI.');

            return self::SUCCESS;
        }if (! $this->option('force') && ! $this->confirm('Lanjut?')) {
            return self::SUCCESS;
        }
        try {
            $this->migrate($a);
        } catch (Throwable $e) {
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        }$v = $this->analyze();
        if ($v['proposal_eligible'] || $v['mutation_eligible'] || $v['invalid']) {
            return self::FAILURE;
        }$this->info('MIGRASI WORKFLOW SELESAI.');

        return self::SUCCESS;
    }

    private function analyze(): array
    {
        $m = DB::connection('mysql_sidikma');
        $p = DB::connection('pgsql');
        $employees = $p->table('employees')->whereNull('deleted_at')->get();
        $byEmail = $employees->whereNotNull('email')->groupBy(fn ($e) => SidikmaMasterData::normalized($e->email));
        $byName = $employees->groupBy(fn ($e) => SidikmaMasterData::normalized($e->name));
        $schools = $p->table('schools')->whereNull('deleted_at')->get();
        $proposals = [];
        $a = ['proposal_source' => 0, 'proposal_eligible' => 0, 'proposal_existing' => 0, 'proposal_snapshot' => 0, 'mutation_source' => 0, 'mutation_eligible' => 0, 'mutation_existing' => 0, 'mutation_snapshot' => 0, 'invalid' => 0];
        foreach ($m->table('usulan')->orderBy('id')->get() as $u) {
            $a['proposal_source']++;
            $matches = $byEmail->get(SidikmaMasterData::normalized($u->email)) ?? $byName->get(SidikmaMasterData::normalized($u->nama));
            $employee = $matches?->count() === 1 ? $matches->first() : null;
            if (! $employee) {
                $a['proposal_snapshot']++;
            }$existing = $p->table('decree_proposals')->where('legacy_proposal_id', $u->id)->first();
            if ($existing) {
                $a['proposal_existing']++;
                $status = 'EXISTING';
            } else {
                $a['proposal_eligible']++;
                $status = 'ELIGIBLE';
            }$state = SidikmaMasterData::normalized($u->s_pengajuan);
            $proposalStatus = str_contains($state, 'selesai') ? 'approved' : (str_contains($state, 'tinjau') ? 'under_review' : (trim($state) === '' ? 'submitted' : 'revision'));
            $proposals[] = ['status' => $status, 'payload' => ['legacy_proposal_id' => (int) $u->id, 'employee_id' => $employee?->id, 'candidate_name' => trim($u->nama), 'candidate_email' => $this->nullable($u->email), 'candidate_phone' => $this->nullable($u->no_telepon), 'candidate_school' => $this->nullable($u->kelas), 'partner_admin_number' => $this->nullable($u->no_mitra), 'status' => $proposalStatus, 'notes' => $proposalStatus === 'revision' ? $u->s_pengajuan : null, 'photo_path' => $this->nullable($u->foto), 'diploma_path' => $this->nullable($u->ijazah), 'application_letter_path' => $this->nullable($u->permohonan), 'service_statement_path' => $this->nullable($u->pernyataan), 'mwc_recommendation_request_path' => $this->nullable($u->s_pengajuan), 'submitted_by' => $employee?->user_id, 'legacy_snapshot' => json_encode((array) $u, JSON_UNESCAPED_SLASHES)]];
        }
        $mutations = [];
        foreach ($m->table('mutasi')->orderBy('id')->get() as $u) {
            $a['mutation_source']++;
            $matches = $byName->get(SidikmaMasterData::normalized((string) $u->nama));
            $employee = $matches?->count() === 1 ? $matches->first() : null;
            if (! $employee) {
                $a['mutation_snapshot']++;
            }$origin = $this->matchSchool($schools, $u->skl_asal);
            $destination = $this->matchSchool($schools, $u->skl_tujuan);
            $existing = $p->table('employee_mutations')->where('legacy_mutation_id', $u->id)->first();
            if ($existing) {
                $a['mutation_existing']++;
                $status = 'EXISTING';
            } else {
                $a['mutation_eligible']++;
                $status = 'ELIGIBLE';
            }$typeName = SidikmaMasterData::normalized((string) $u->jenis_mutasi);
            $type = str_contains($typeName, 'internal') ? 'internal' : (str_contains($typeName, 'masuk') ? 'incoming' : 'outgoing');
            $mutations[] = ['status' => $status, 'payload' => ['legacy_mutation_id' => (int) $u->id, 'employee_id' => $employee?->id, 'employee_code' => $employee?->employee_code ?: $this->nullable($u->ewanu), 'employee_name' => $this->nullable($u->nama), 'phone' => $this->nullable($u->no_telepon), 'birth_place' => $this->nullable($u->tempat_lahir), 'birth_date' => $this->date($u->tgl_lahir), 'mutation_type' => $type, 'effective_date' => $this->date($u->tmt_pindah), 'origin_school_id' => $origin?->id, 'origin_school_name' => $this->nullable($u->skl_asal), 'destination_school_id' => $destination?->id, 'destination_school_name' => $this->nullable($u->skl_tujuan), 'request_letter_path' => $this->nullable($u->mutasi) ?? 'legacy-missing-request-letter', 'submitted_by' => $employee?->user_id, 'legacy_snapshot' => json_encode((array) $u, JSON_UNESCAPED_SLASHES)]];
        }

        return $a + compact('proposals', 'mutations');
    }

    private function migrate(array $a): void
    {
        DB::connection('pgsql')->transaction(function () use ($a) {
            $p = DB::connection('pgsql');
            $now = now();
            foreach ($a['proposals'] as $r) {
                if ($r['status'] === 'ELIGIBLE') {
                    $p->table('decree_proposals')->insert($r['payload'] + ['created_at' => $now, 'updated_at' => $now]);
                }
            }foreach ($a['mutations'] as $r) {
                if ($r['status'] === 'ELIGIBLE') {
                    $p->table('employee_mutations')->insert($r['payload'] + ['created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }

    private function matchSchool($schools, mixed $name): ?object
    {
        $needle = SidikmaMasterData::normalized((string) $name);
        if ($needle === '' || $needle === '-') {
            return null;
        }$exact = $schools->first(fn ($s) => SidikmaMasterData::normalized($s->name) === $needle);
        if ($exact) {
            return $exact;
        }$simplify = fn ($x) => str_replace(["'", '.', ' '], '', SidikmaMasterData::normalized($x));
        $hits = $schools->filter(fn ($s) => $simplify($s->name) === $simplify($name));

        return $hits->count() === 1 ? $hits->first() : null;
    }

    private function nullable(mixed $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' || $v === '-' ? null : $v;
    }

    private function date(mixed $v): ?string
    {
        $v = $this->nullable($v);

        return $v === null || str_starts_with($v, '0000-00-00') ? null : substr($v,0,10);
    }
}
