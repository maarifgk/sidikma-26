<div style="display: grid; gap: 1rem;">
    <section class="fi-section" style="overflow: hidden;">
        <div style="padding: 0.9rem 1rem; background: var(--primary-600); color: var(--primary-contrast-color, white); font-weight: 700;">
            @if ($section === \App\Filament\Admin\Pages\InstitutionProfile::SECTION_STRUCTURE)
                {{ $foundation->board_heading ?? "Susunan Pengurus {$foundation->name}" }}
                @if (filled($foundation->board_term))
                    Masa Jabatan {{ $foundation->board_term }}
                @endif
            @else
                {{ strtoupper($sectionLabel) }}
            @endif
        </div>

        @if ($section === \App\Filament\Admin\Pages\InstitutionProfile::SECTION_IDENTITY)
            @if (filled($foundation?->banner_path))
                <div style="padding: 1rem 1rem 0;">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($foundation->banner_path) }}"
                        alt="Banner {{ $foundation->name }}"
                        style="display: block; width: 100%; aspect-ratio: 4 / 1; object-fit: cover; border-radius: 0.5rem;"
                    >
                </div>
            @else
                <div style="margin: 1rem 1rem 0; display: grid; place-items: center; aspect-ratio: 4 / 1; min-height: 8rem; border: 1px dashed var(--gray-300); border-radius: 0.5rem; color: var(--gray-600);">
                    Banner lembaga belum diunggah. Ukuran 1600 × 400 px.
                </div>
            @endif

            <dl style="margin: 0; padding: 0.5rem 1rem 1rem;">
                @foreach ([
                    'Nama Lembaga' => $foundation?->name ?? \App\Models\Foundation::APPLICATION_NAME,
                    'Alamat' => $foundation?->address,
                    'Email' => $foundation?->email,
                    'Instagram' => $foundation?->instagram,
                    'Nomor Telepon/WhatsApp' => $foundation?->phone,
                ] as $label => $value)
                    <div style="display: grid; grid-template-columns: minmax(12rem, 34%) 1fr; gap: 1rem; padding: 0.75rem; border-bottom: 1px solid var(--gray-200);">
                        <dt style="color: var(--gray-600);">{{ $label }}</dt>
                        <dd style="margin: 0;"><span aria-hidden="true">:</span> {{ filled($value) ? $value : '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        @elseif ($section === \App\Filament\Admin\Pages\InstitutionProfile::SECTION_STRUCTURE)
            @if ($boardMembers->isNotEmpty())
                <dl style="margin: 0; padding: 1rem;">
                    @foreach ($boardMembers as $member)
                        <div style="display: grid; grid-template-columns: minmax(12rem, 55%) 1fr; gap: 1rem; padding: 0.75rem; border-bottom: 1px solid var(--gray-200);">
                            <dt style="color: var(--gray-600);">{{ $member->position }}</dt>
                            <dd style="margin: 0;"><span aria-hidden="true">:</span> {{ filled($member->name) ? $member->name : '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <div style="padding: 2rem; text-align: center; color: var(--gray-600);">
                    Belum ada data struktur pengurus. Gunakan tombol Edit Struktur untuk menambahkan data.
                </div>
            @endif
        @else
            <div style="padding: 2rem; text-align: center;">
                <strong style="display: block; margin-bottom: 0.5rem;">{{ $sectionLabel }}</strong>
                <span style="color: var(--gray-600);">Belum ada data {{ strtolower($sectionLabel) }}.</span>
            </div>
        @endif
    </section>
</div>
