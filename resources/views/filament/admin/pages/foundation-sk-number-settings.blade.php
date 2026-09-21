<x-filament-panels::page>
    <div class="space-y-6">
        <div id="number-placeholders" class="rounded-xl border border-primary-200 bg-primary-50 p-5 text-sm text-gray-700 dark:border-primary-800 dark:bg-primary-950 dark:text-gray-200">
            <h2 class="mb-3 text-lg font-semibold">Placeholder Nomor</h2>
            <div class="grid gap-2 md:grid-cols-2">
                <div><code>@{{nomor_urut}}</code> — Nomor urut dengan nol di depan sesuai digit</div>
                <div><code>@{{nomor_urut_raw}}</code> — Nomor urut tanpa nol di depan</div>
                <div><code>@{{teks_nomor_sk}}</code> — Teks nomor SK yayasan saat generate</div>
                <div><code>@{{periode}}</code> — Nama periode</div>
                <div><code>@{{periode_upper}}</code> — Nama periode huruf besar</div>
                <div><code>@{{tahun}}</code> — Tahun sekarang</div>
                <div><code>@{{bulan_romawi}}</code> — Bulan sekarang dalam angka Romawi</div>
            </div>
        </div>

        <form wire:submit="saveSettings" class="space-y-5">
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <tr><th class="w-48 px-4 py-3">Periode</th><th class="min-w-[360px] px-4 py-3">Format Nomor SK</th><th class="w-28 px-4 py-3">Digit</th><th class="w-32 px-4 py-3">Nomor Awal</th><th class="w-40 px-4 py-3">Nomor Berikutnya</th><th class="w-24 px-4 py-3">Aktif</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($settings as $index => $row)
                            <tr>
                                <td class="px-4 py-3 align-top"><input wire:model="settings.{{ $index }}.periode" class="fi-input w-full border-0 bg-transparent px-0 font-semibold text-gray-500 shadow-none" maxlength="50" readonly></td>
                                <td class="px-4 py-3 align-top"><input wire:model="settings.{{ $index }}.nomor_pattern" class="fi-input w-full"><p class="mt-2 text-xs text-gray-500">Contoh: @{{nomor_urut}}/@{{teks_nomor_sk}}/@{{periode}}/@{{tahun}}</p></td>
                                <td class="px-4 py-3 align-top"><input type="number" wire:model="settings.{{ $index }}.digit_nomor" class="fi-input w-full"></td>
                                <td class="px-4 py-3 align-top"><input type="number" wire:model="settings.{{ $index }}.nomor_awal" class="fi-input w-full"></td>
                                <td class="px-4 py-3 align-top"><input type="number" wire:model="settings.{{ $index }}.nomor_berikutnya" class="fi-input w-full"> @error("settings.{$index}.nomor_berikutnya")<p class="mt-1 text-xs text-danger-600">{{ $message }}</p>@enderror</td>
                                <td class="px-4 py-3 align-top text-center"><input type="checkbox" wire:model="settings.{{ $index }}.is_active" class="fi-checkbox"></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada pengaturan nomor SK.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('settings')<p class="text-sm text-danger-600">{{ $message }}</p>@enderror
            <div class="flex flex-wrap justify-end gap-3">
                <x-filament::button color="gray" tag="a" :href="\App\Filament\Resources\FoundationSkTemplates\FoundationSkTemplateResource::getUrl('index', panel: 'admin', isAbsolute: false)">Kembali</x-filament::button>
                <x-filament::button type="submit">Simpan Pengaturan</x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
