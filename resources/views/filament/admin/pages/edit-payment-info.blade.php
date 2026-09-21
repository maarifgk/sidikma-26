<form wire:submit="save" class="ui-page">
    <section class="ui-page-intro"><div><h2>Kelola Informasi Pembayaran</h2><p>Atur kelompok, label, dan nominal yang ditampilkan pada halaman pembayaran.</p></div></section>

    <div class="ui-edit-grid">
        @foreach ($items as $index => $item)
            <section class="ui-card" wire:key="payment-fee-item-{{ $index }}">
                <header class="ui-card-head">
                    <div><h2>Informasi {{ $index + 1 }}</h2><p>Lengkapi kelompok dan nominal pembayaran.</p></div>
                    <button type="button" wire:click="removeItem({{ $index }})" class="ui-btn ui-btn-danger">Hapus</button>
                </header>
                <div class="ui-card-body ui-form-grid">
                    <label class="ui-field ui-span-full">
                        <span class="ui-label">Kelompok Informasi</span>
                        <select wire:model="items.{{ $index }}.section" class="ui-select" required>
                            @foreach ($sectionOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </label>
                    <label class="ui-field"><span class="ui-label">Label</span><input wire:model="items.{{ $index }}.label" type="text" class="ui-input" required>@error("items.{$index}.label") <small class="ui-error">{{ $message }}</small> @enderror</label>
                    <label class="ui-field"><span class="ui-label">Nominal</span><input wire:model="items.{{ $index }}.amount" type="number" min="0" step="1" class="ui-input" required>@error("items.{$index}.amount") <small class="ui-error">{{ $message }}</small> @enderror</label>
                </div>
            </section>
        @endforeach
    </div>

    @if (empty($items))<section class="ui-card ui-empty">Belum ada informasi pembayaran. Klik Tambah Kolom untuk membuat data baru.</section>@endif

    <div class="ui-actions">
        <button type="button" wire:click="addItem" class="ui-btn ui-btn-secondary">+ Tambah Kolom</button>
        <button type="submit" class="ui-btn ui-btn-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan Perubahan</span><span wire:loading wire:target="save">Menyimpan...</span></button>
    </div>
</form>
