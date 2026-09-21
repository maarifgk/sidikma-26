<section class="fi-section" style="padding: 1.5rem;">
    <form wire:submit="save" style="display: grid; gap: 1.25rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr)); gap: 1rem;">
            <label style="display: grid; gap: 0.4rem;">
                <span>PEMILIK</span>
                <input wire:model="ownerName" type="text" class="fi-input" required>
                @error('ownerName') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>TELEPHONE</span>
                <input wire:model="phone" type="tel" class="fi-input">
                @error('phone') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>TITLE</span>
                <input wire:model="shortTitle" type="text" class="fi-input" required>
                @error('shortTitle') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>NAMA APLIKASI</span>
                <input wire:model="applicationName" type="text" class="fi-input" required>
                @error('applicationName') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem; align-items: start;">
            <label style="display: grid; gap: 0.4rem;">
                <span>LOGO</span>
                <input wire:model="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="fi-input">
                <small>JPG, PNG, atau WEBP. Maksimal 1000 MB.</small>
                @error('logo') <small style="color: #dc2626;">{{ $message }}</small> @enderror

                @if ($this->existingLogoPath)
                    <span style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.35rem;">
                        <img src="{{ Storage::disk('public')->url($this->existingLogoPath) }}" alt="Logo aplikasi" style="height: 3rem; width: auto; object-fit: contain;">
                        <button type="button" wire:click="removeLogo" wire:confirm="Hapus logo aplikasi?" class="fi-btn fi-size-sm fi-color-danger">Hapus Logo</button>
                    </span>
                @endif
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>COPY RIGHT</span>
                <input wire:model="copyrightText" type="text" class="fi-input">
                @error('copyrightText') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>VERSI</span>
                <input wire:model="version" type="text" class="fi-input">
                @error('version') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem;">
            <label style="display: grid; gap: 0.4rem;">
                <span>TOKEN WHATSAPP</span>
                <input wire:model="whatsappToken" type="password" class="fi-input" autocomplete="new-password">
                @error('whatsappToken') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>SERVER KEY</span>
                <input wire:model="serverKey" type="password" class="fi-input" autocomplete="new-password">
                @error('serverKey') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
            <label style="display: grid; gap: 0.4rem;">
                <span>CLIENT KEY</span>
                <input wire:model="clientKey" type="password" class="fi-input" autocomplete="new-password">
                @error('clientKey') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
        </div>

        <label style="display: grid; gap: 0.4rem;">
            <span>ALAMAT</span>
            <textarea wire:model="address" rows="3" class="fi-input"></textarea>
            @error('address') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </label>

        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
            <button type="submit" class="fi-btn fi-size-md fi-color-primary" wire:loading.attr="disabled" wire:target="save,logo">
                <span wire:loading.remove wire:target="save">Simpan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
            <a href="{{ \App\Filament\Admin\Pages\Dashboard::getUrl(['menu' => 'setting'], panel: 'admin', isAbsolute: false) }}" class="fi-btn fi-size-md fi-color-success">Kembali</a>
        </div>
    </form>
</section>
