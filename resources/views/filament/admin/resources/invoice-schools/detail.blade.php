<div style="display: grid; gap: 1rem;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 700;">{{ $school->name }}</h2>
            <p style="opacity: 0.7;">Tahun ajaran: {{ $academicYear === 'all' ? 'Semua tahun ajaran' : $academicYear }}</p>
        </div>
        <x-filament::button
            tag="a"
            color="gray"
            icon="heroicon-o-arrow-left"
            :href="\App\Filament\Resources\InvoiceSchools\InvoiceSchoolResource::getUrl('index', ['year' => $academicYear], panel: 'admin', isAbsolute: false)"
        >
            Kembali
        </x-filament::button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem;">
        <div class="fi-section" style="padding: 1rem 1.25rem;">
            <span style="display: block; opacity: 0.7;">Total Sudah Dibayar</span>
            <strong style="font-size: 1.35rem;">Rp {{ number_format($paidTotal, 0, ',', '.') }}</strong>
        </div>
        <div class="fi-section" style="padding: 1rem 1.25rem;">
            <span style="display: block; opacity: 0.7;">Total Belum Dibayar</span>
            <strong style="font-size: 1.35rem;">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="fi-section" style="overflow-x: auto;">
        <div style="padding: 1rem 1.25rem; font-weight: 700;">
            {{ $mode === 'class' ? 'Daftar Pembayaran Kelas' : 'Rincian Invoice Pembayaran' }}
        </div>
        <table style="width: 100%; border-collapse: collapse; min-width: 54rem;">
            <thead>
                <tr style="text-align: left;">
                    @foreach (['No', 'Nomor Invoice', 'Guru/Pegawai', 'Uraian', 'Nominal', 'Status', 'Jatuh Tempo', 'Tanggal Bayar'] as $heading)
                        <th style="padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $loop->iteration }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->invoice_number }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->employee?->name ?? 'Pembayaran sekolah/kelas' }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->description }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">Rp {{ number_format((float) $invoice->amount, 0, ',', '.') }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->status === \App\Models\PaymentInvoice::STATUS_PAID ? 'Lunas' : 'Belum Lunas' }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->due_date?->format('d-m-Y') ?? '-' }}</td>
                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">{{ $invoice->paid_at?->format('d-m-Y') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding: 2rem; text-align: center; opacity: 0.7;">Belum ada invoice pada tahun ajaran yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
