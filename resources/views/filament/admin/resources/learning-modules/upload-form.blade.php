<section class="fi-section" style="padding: 1.5rem; margin-bottom: 1rem;">
    <h2 style="margin-bottom: 1.25rem; font-size: 1.75rem; font-weight: 700;">Upload Modul Baru</h2>

    <form wire:submit="uploadModule" style="display: grid; gap: 1rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 1rem; align-items: start;">
            <label style="display: grid; gap: 0.35rem;">
                <span>Kelas</span>
                <input wire:model="className" type="text" class="fi-input" required>
                @error('className') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>

            <label style="display: grid; gap: 0.35rem;">
                <span>Jenis Modul</span>
                <input wire:model="moduleType" type="text" class="fi-input" required>
                @error('moduleType') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>

            <label style="display: grid; gap: 0.35rem;">
                <span>Semester</span>
                <select wire:model="semester" class="fi-input" required>
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
                @error('semester') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>

            <label style="display: grid; gap: 0.35rem;">
                <span>Mata Pelajaran</span>
                <input wire:model="subject" type="text" class="fi-input" required>
                @error('subject') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>

            <label style="display: grid; gap: 0.35rem;">
                <span>BAB</span>
                <input wire:model="chapter" type="text" class="fi-input" required>
                @error('chapter') <small style="color: #dc2626;">{{ $message }}</small> @enderror
            </label>
        </div>

        <label style="display: grid; gap: 0.35rem;">
            <span>Upload File Modul</span>
            <input
                wire:model="moduleFile"
                type="file"
                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx"
                class="fi-input"
                required
            >
            <small>Format: PDF/DOC/DOCX/PPT/PPTX/XLS/XLSX (maksimal 1000 MB)</small>
            @error('moduleFile') <small style="color: #dc2626;">{{ $message }}</small> @enderror
        </label>

        <div>
            <button type="submit" class="fi-btn fi-size-md fi-color-primary" wire:loading.attr="disabled" wire:target="uploadModule,moduleFile">
                <span wire:loading.remove wire:target="uploadModule">Upload</span>
                <span wire:loading wire:target="uploadModule">Mengunggah...</span>
            </button>
        </div>
    </form>
</section>
