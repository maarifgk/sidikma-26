<div>
    <dl style="display: grid; gap: 0.75rem;">
        <div><dt>Nomor Invoice</dt><dd>{{ $invoice->invoice_number }}</dd></div>
        <div><dt>Jenis Pembayaran</dt><dd>{{ $invoice->description }}</dd></div>
        <div><dt>Tahun Ajaran</dt><dd>{{ $invoice->academic_year }}</dd></div>
        <div><dt>Total Tagihan</dt><dd><strong>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</strong></dd></div>
    </dl>
    <p>Pilih Online untuk menampilkan pilihan metode pembayaran dari Midtrans.</p>
    @error('payment') <p role="alert" style="color: #dc2626;">{{ $message }}</p> @enderror
</div>
