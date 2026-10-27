<div class="ui-page"
    x-data="{
        checkoutError: '',
        orderId: null,
        async openSnap(data) {
            this.checkoutError = '';
            this.orderId = data.orderId;
            try {
                const url = data.production ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js';
                let script = document.getElementById('sidikma-midtrans-snap');
                if (script &amp;&amp; (script.src !== url || script.dataset.clientKey !== data.clientKey)) {
                    throw new Error('Konfigurasi Midtrans berubah. Muat ulang halaman.');
                }
                if (!window.snap) {
                    if (!window.sidikmaSnapLoading) {
                        window.sidikmaSnapLoading = new Promise((resolve, reject) => {
                            script = document.createElement('script');
                            script.id = 'sidikma-midtrans-snap';
                            script.src = url;
                            script.dataset.clientKey = data.clientKey;
                            script.onload = resolve;
                            script.onerror = () => {
                                script.remove();
                                window.sidikmaSnapLoading = null;
                                reject(new Error('Midtrans tidak dapat dimuat. Periksa koneksi dan coba kembali.'));
                            };
                            document.head.appendChild(script);
                        });
                    }
                    await window.sidikmaSnapLoading;
                }
                window.snap.pay(data.token, {
                    onSuccess: () => this.$wire.refreshOnlinePayment(data.orderId),
                    onPending: () => this.$wire.refreshOnlinePayment(data.orderId),
                    onError: () => { this.checkoutError = 'Pembayaran belum berhasil. Periksa status sebelum mencoba kembali.'; },
                    onClose: () => this.$wire.refreshOnlinePayment(data.orderId),
                });
            } catch (error) {
                this.checkoutError = error.message || 'Checkout belum dapat dibuka. Silakan coba kembali.';
            }
        },
    }"
    x-on:midtrans-checkout.window="openSnap($event.detail)"
>
    <p x-cloak x-show="checkoutError" x-text="checkoutError" role="alert" class="ui-error"></p>
    <div x-cloak x-show="orderId">
        <button type="button" class="ui-btn ui-btn-secondary" x-on:click="$wire.refreshOnlinePayment(orderId)" wire:loading.attr="disabled">Periksa Status Pembayaran</button>
    </div>
    <section class="ui-page-intro">
        <div><h2>Informasi Pembayaran</h2><p>Lihat ketentuan dan nominal iuran LP Ma'arif NU PCNU Gunungkidul.</p></div>
    </section>

    <section class="ui-card">
        <header class="ui-card-head"><div><h2>Filter Data</h2><p>Pilih madrasah atau nama untuk menemukan data pembayaran.</p></div></header>
        <div class="ui-card-body">
            <form wire:submit="searchPayments" class="ui-filter-grid">
                <label class="ui-field">
                    <span class="ui-label">Asal Madrasah</span>
                    <select wire:model.live="schoolId" class="ui-select">
                        <option value="">Pilih madrasah</option>
                        @foreach ($schools as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('schoolId') <small class="ui-error">{{ $message }}</small> @enderror
                </label>
                <label class="ui-field">
                    <span class="ui-label">EWANUGK/Nama</span>
                    <select wire:model="employeeId" class="ui-select">
                        <option value="">Pilih nama</option>
                        @foreach ($employees as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                    </select>
                    @error('employeeId') <small class="ui-error">{{ $message }}</small> @enderror
                </label>
                <div class="ui-filter-actions">
                    <button type="submit" class="ui-btn ui-btn-primary" wire:loading.attr="disabled" wire:target="searchPayments">
                        <span wire:loading.remove wire:target="searchPayments">Cari</span><span wire:loading wire:target="searchPayments">Mencari...</span>
                    </button>
                    <button type="button" wire:click="refreshPayments" class="ui-btn ui-btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </section>

    <section class="ui-card">
        <header class="ui-card-head"><div><h2>Informasi Pembayaran Iuran</h2><p>LP Ma'arif NU PCNU Gunungkidul</p></div></header>
        @foreach ($feeSections as $section)
            <div class="ui-fee-group">
                <div class="ui-fee-heading"><span>{{ $section['heading'] }}</span><span class="ui-text-right">Nominal</span></div>
                @forelse ($section['items'] as $item)
                    <div class="ui-fee-row"><strong>{{ $item['label'] }}</strong><span class="ui-money ui-text-right">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span></div>
                @empty
                    <div class="ui-empty">Belum ada informasi pada kelompok ini.</div>
                @endforelse
            </div>
        @endforeach
    </section>

    @if ($hasSearched)
        <section class="ui-card">
            <header class="ui-card-head"><div><h2>Hasil Pencarian Pembayaran</h2><p>Maksimal 100 data ditampilkan pada satu pencarian.</p></div></header>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead><tr><th>No</th><th>EWANUGK</th><th>Nama</th><th>Asal Madrasah</th><th>Status Pegawai</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($results as $employee)
                            <tr>
                                <td>{{ $loop->iteration }}</td><td>{{ $employee->employee_code ?: '-' }}</td><td>{{ $employee->name }}</td><td>{{ $employee->school?->name ?: '-' }}</td>
                                <td><span class="ui-badge {{ $employee->is_active ? 'ui-badge-success' : 'ui-badge-muted' }}">{{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                                <td>
                                    <button type="button" class="ui-btn ui-btn-primary" wire:click="openPayment({{ $employee->getKey() }})" wire:loading.attr="disabled" wire:target="openPayment">Bayar</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="ui-empty">Data pembayaran tidak ditemukan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <x-filament::modal id="payment-checkout" width="5xl">
        <x-slot name="heading">Pembayaran {{ $paymentEmployee?->name }}</x-slot>
        @if ($paymentEmployee)
        <section aria-label="Tagihan pembayaran">
            <header class="ui-card-head">
                <div><p>Pilih metode dan tagihan yang ingin dibayar.</p></div>
                <button type="button" class="ui-btn ui-btn-secondary" wire:click="closePayment">Tutup</button>
            </header>
            <div class="ui-card-body">
                <label class="ui-field">
                    <span class="ui-label">Metode Pembayaran</span>
                    <select wire:model.live="paymentMethod" class="ui-select">
                        <option value="online">Online — Midtrans (VA, QRIS, dan metode yang tersedia)</option>
                        <option value="manual">Manual — melalui admin</option>
                    </select>
                </label>
                @error('payment') <p class="ui-error" role="alert">{{ $message }}</p> @enderror
                @error('paymentMethod') <p class="ui-error" role="alert">{{ $message }}</p> @enderror
                @if ($paymentMethod === 'online')
                    <p>Pilihan bank, QRIS, dan metode lain akan ditampilkan oleh Midtrans setelah Anda melanjutkan.</p>
                @else
                    <p>Pembayaran manual dicatat oleh admin setelah dana diterima.</p>
                @endif
            </div>
            <div class="ui-table-wrap">
                <table class="ui-table">
                    <thead><tr><th>Nomor Invoice</th><th>Jenis Pembayaran</th><th>Tahun Ajaran</th><th>Nominal</th><th>Action</th></tr></thead>
                    <tbody>
                        @forelse ($unpaidInvoices as $invoice)
                            <tr wire:key="payment-invoice-{{ $invoice->getKey() }}">
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->description }}</td>
                                <td>{{ $invoice->academic_year }}</td>
                                <td class="ui-money">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                                <td>
                                    @if ($paymentMethod === 'online')
                                        <button type="button" class="ui-btn ui-btn-primary" wire:click="payOnline({{ $invoice->getKey() }})" wire:loading.attr="disabled" wire:target="payOnline">Lanjutkan ke Midtrans</button>
                                    @else
                                        @can('update', $invoice)
                                            <a class="ui-btn ui-btn-secondary" href="{{ \App\Filament\Resources\Billings\BillingResource::getUrl('edit', ['record' => $invoice], panel: 'admin') }}">Kelola Tagihan</a>
                                        @else
                                            <span>Hubungi admin untuk pembayaran</span>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="ui-empty">Tidak ada tagihan belum lunas yang dapat diakses untuk pegawai ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </x-filament::modal>
</div>
