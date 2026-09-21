<x-filament::section>
    <x-slot name="heading">
        DOKUMEN UNTUK MENGAJUKAN PENGAJUAN PENONAKTIFAN GURU DAN PEGAWAI
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

    <div style="display: grid; gap: 0.25rem;">
        @forelse ($requirements as $requirement)
            <div
                style="display: grid; grid-template-columns: minmax(5rem, auto) minmax(0, 1fr); gap: 0.75rem; align-items: start; padding: 0.75rem; border-radius: 0.5rem;"
                @class(['bg-gray-50 dark:bg-white/5' => $loop->odd])
            >
                <span style="font-weight: 600;">{{ $requirement->number }}</span>
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
                    <span style="flex: 1 1 24rem;">{{ $requirement->description }}</span>

                    @if (filled($requirement->template_path) && ! str_starts_with($requirement->template_path, 'http'))
                        <x-filament::button
                            tag="a"
                            size="sm"
                            color="danger"
                            icon="heroicon-o-document-text"
                            href="{{ route('administration-templates.download', ['type' => 'activity', 'record' => $requirement]) }}"
                            target="_blank"
                        >
                            Download Word
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @empty
            <p style="padding: 1rem; text-align: center;">
                Belum ada dokumen atau keterangan. Klik Edit untuk menambahkannya.
            </p>
        @endforelse
    </div>
</x-filament::section>
