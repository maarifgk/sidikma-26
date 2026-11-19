<x-filament::section>
    <x-slot name="heading">
        Kelengkapan syarat pengajuan SK
    </x-slot>

    <x-slot name="afterHeader">
        @if (auth()->user()?->can('approval.update'))
            <div style="display: flex; justify-content: flex-end; margin-inline-start: auto;">
                <x-filament::button
                    type="button"
                    size="sm"
                    color="warning"
                    wire:click="mountAction('editRequirements')"
                >
                    Edit
                </x-filament::button>
            </div>
        @endif
    </x-slot>

    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem;">
        @forelse ($requirements as $requirement)
            <div
                style="display: grid; grid-template-columns: 1.75rem minmax(0, 1fr); gap: 0.6rem; align-items: center; padding: 0.65rem 0.75rem; border: 1px solid rgba(148, 163, 184, .2); border-radius: 0.65rem;"
                @class(['bg-gray-50 dark:bg-white/5' => $loop->odd])
            >
                <span style="font-weight: 600;">
                    {{ $requirement->number }}
                </span>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.55rem;">
                    <span style="flex: 1 1 18rem; font-size: 0.9rem; line-height: 1.35;">
                        {{ $requirement->description }}
                    </span>

                    @if (filled($requirement->template_path) && ! str_starts_with($requirement->template_path, 'http'))
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="danger"
                            icon="heroicon-o-document-text"
                            href="{{ route('administration-templates.download', ['type' => 'decree', 'record' => $requirement]) }}"
                            target="_blank"
                        >
                            Template
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @empty
            <p style="padding: 1rem; text-align: center;">
                Belum ada kelengkapan. Klik Edit untuk menambahkan kolom keterangan.
            </p>
        @endforelse
    </div>
</x-filament::section>

<style>
    @media (max-width: 700px) {
        .fi-resource-decree-proposals .fi-section-content > div[style*="grid-template-columns: repeat(2"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
