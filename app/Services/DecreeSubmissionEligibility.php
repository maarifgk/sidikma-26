<?php

namespace App\Services;

use App\Models\PaymentInvoice;
use App\Models\School;
use App\Models\StudentEnrollment;

class DecreeSubmissionEligibility
{
    public function schoolCanSubmit(School|int $school): bool
    {
        $schoolId = $school instanceof School ? $school->getKey() : $school;
        $invoices = PaymentInvoice::query()
            ->where('school_id', $schoolId)
            ->where('academic_year', StudentEnrollment::currentAcademicYear());

        return (clone $invoices)->exists()
            && ! (clone $invoices)->where('status', '!=', PaymentInvoice::STATUS_PAID)->exists();
    }

    public function message(): string
    {
        return 'Pengajuan SK belum dapat dilakukan karena pembayaran/iuran madrasah belum lunas.';
    }
}
