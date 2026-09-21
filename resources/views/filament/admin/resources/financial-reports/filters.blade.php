<div class="fi-section" style="padding: 1.25rem;">
    <div style="display: grid; grid-template-columns: repeat(3, minmax(12rem, 1fr)) auto; align-items: end; gap: 1rem;">
        <label>
            <span style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Tahun Ajaran</span>
            <select wire:model.live="academicYear" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; background: transparent;">
                @foreach ($academicYearOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Asal Madrasah</span>
            <select wire:model.live="schoolId" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; background: transparent;">
                @foreach ($schoolOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">Jenis Pembayaran</span>
            <select wire:model.live="paymentType" style="width: 100%; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; background: transparent;">
                @foreach ($paymentTypeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-arrow-down-tray">
            Excel
        </x-filament::button>
    </div>
</div>

<style>
    @media (max-width: 900px) {
        .fi-section > div[style*="grid-template-columns: repeat(3"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
