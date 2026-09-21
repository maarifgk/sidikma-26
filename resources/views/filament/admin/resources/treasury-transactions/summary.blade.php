@php
    $rupiah = static fn (float $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<div style="display: grid; gap: 1.25rem; margin-bottom: 1rem;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem;">
        <div class="fi-section" style="padding: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.7rem; font-size: 1.1rem;">Saldo Total</strong>
            <span style="font-size: 1.35rem;">{{ $rupiah($balance) }}</span>
        </div>
        <div class="fi-section" style="padding: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.7rem; font-size: 1.1rem;">Total Pemasukan</strong>
            <span style="font-size: 1.35rem;">{{ $rupiah($totalIncome) }}</span>
        </div>
        <div class="fi-section" style="padding: 1.5rem;">
            <strong style="display: block; margin-bottom: 0.7rem; font-size: 1.1rem;">Total Pengeluaran</strong>
            <span style="font-size: 1.35rem;">{{ $rupiah($totalExpense) }}</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(18rem, 1fr)); gap: 1.25rem;">
        <div class="fi-section" style="overflow: hidden;">
            <h3 style="padding: 1rem 1.25rem; font-size: 1.25rem; font-weight: 700;">📈 Rekap Pemasukan</h3>
            <div style="padding: 0 1.25rem 1rem;">
                @forelse ($incomeRecap as $item)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.55rem 0; border-bottom: 1px solid #d1d5db;">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $rupiah($item['total']) }}</strong>
                    </div>
                @empty
                    <p style="padding: 0.75rem 0; color: #6b7280;">Belum ada pemasukan.</p>
                @endforelse
            </div>
        </div>

        <div class="fi-section" style="overflow: hidden;">
            <h3 style="padding: 1rem 1.25rem; font-size: 1.25rem; font-weight: 700;">📉 Rekap Pengeluaran</h3>
            <div style="padding: 0 1.25rem 1rem;">
                @forelse ($expenseRecap as $item)
                    <div style="display: flex; justify-content: space-between; gap: 1rem; padding: 0.55rem 0; border-bottom: 1px solid #d1d5db;">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $rupiah($item['total']) }}</strong>
                    </div>
                @empty
                    <p style="padding: 0.75rem 0; color: #6b7280;">Belum ada pengeluaran.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
