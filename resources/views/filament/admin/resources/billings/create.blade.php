<div style="display: grid; gap: 1rem;">
    @if ($this->step === 1)
    <section class="fi-section" style="padding: 1.5rem;">
        <form wire:submit="searchTargets" style="display: grid; gap: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); gap: 1rem;">
                <label style="display: grid; gap: 0.4rem;">
                    <span>TAHUN AJARAN</span>
                    <select wire:model="academicYear" class="fi-input" required>
                        <option value="">--Pilih--</option>
                        @foreach ($academicYearOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('academicYear') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </label>

                <label style="display: grid; gap: 0.4rem;">
                    <span>ASAL MADRASAH</span>
                    <select wire:model="schoolId" class="fi-input" required>
                        <option value="">--Pilih--</option>
                        @foreach ($schoolOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('schoolId') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </label>

                <label style="display: grid; gap: 0.4rem;">
                    <span>JENIS PEMBAYARAN</span>
                    <select wire:model.live="paymentType" class="fi-input" required>
                        <option value="">--Pilih--</option>
                        @foreach ($paymentTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('paymentType') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                </label>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                <button type="submit" class="fi-btn fi-size-md fi-color-primary">Cari &amp; Selanjutnya</button>
                <a href="{{ \App\Filament\Resources\Billings\BillingResource::getUrl(panel: 'admin', isAbsolute: false) }}" class="fi-btn fi-size-md fi-color-success">Kembali</a>
            </div>
        </form>
    </section>
    @endif

    @if ($this->step === 2 && $this->hasSearched)
        <section class="fi-section" style="padding: 1rem; background: #ff3b24; color: white;">
            <strong style="display: block; margin-bottom: 0.75rem; font-size: 1.75rem;">INFO PENTING!!!!</strong>
            <p style="margin: 0; font-size: 1.1rem;">
                Nama Admin Madrasah/Guru/Pegawai yang terdaftar dan belum mempunyai pembayaran ini akan muncul dalam daftar pilihan.
            </p>
        </section>

        <section class="fi-section" style="padding: 1.5rem;">
            <form wire:submit="addBills" style="display: grid; gap: 1rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(24rem, 1fr)); gap: 1rem; align-items: start;">
                    <label style="display: grid; gap: 0.4rem;">
                        <span>EWANUGK / KARTANU / ADMIN MADRASAH / GURU / PEGAWAI</span>
                        <select wire:model="selectedUserIds" class="fi-input" multiple size="{{ min(max(count($availableTargets), 3), 10) }}" required>
                            @forelse ($availableTargets as $target)
                                <option value="{{ $target['id'] }}">{{ $target['label'] }}</option>
                            @empty
                                <option disabled>Tidak ada akun yang belum memiliki pembayaran ini.</option>
                            @endforelse
                        </select>
                        <small>Tekan Ctrl untuk memilih lebih dari satu akun.</small>
                        @error('selectedUserIds') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                        @error('selectedUserIds.*') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </label>

                    <label style="display: grid; gap: 0.4rem;">
                        <span>NOMINAL</span>
                        <input wire:model="amount" type="number" min="0" step="1" class="fi-input" placeholder="Masukkan Nilai" required>
                        @error('amount') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </label>

                    <label style="display: grid; gap: 0.4rem;">
                        <span>KETERANGAN</span>
                        <input wire:model="notes" type="text" class="fi-input" placeholder="Masukkan keterangan">
                        @error('notes') <small style="color: #dc2626;">{{ $message }}</small> @enderror
                    </label>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <button type="submit" class="fi-btn fi-size-md fi-color-primary" @disabled($availableTargets === [])>Tambah</button>
                    <button type="button" wire:click="backToSearch" class="fi-btn fi-size-md fi-color-success">Kembali ke Pencarian</button>
                </div>
            </form>
        </section>
    @endif
</div>
