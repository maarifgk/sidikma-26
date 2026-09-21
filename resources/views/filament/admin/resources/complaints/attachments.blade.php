<div style="display:grid;gap:.75rem">
    <p style="color:#475467">Lampiran ini bersifat privat. Jangan membagikan isinya kepada pihak yang tidak berkepentingan.</p>
    @foreach($record->attachments ?? [] as $index => $file)
        <a href="{{ route('complaints.attachments.view', [$record, $index]) }}" target="_blank" rel="noopener" style="display:block;border:1px solid #d0d5dd;border-radius:.65rem;padding:.75rem">
            📎 {{ $file['name'] ?? 'Lampiran '.($index + 1) }}
            @if(isset($file['size']))<small style="display:block;color:#667085">{{ number_format($file['size'] / 1024, 0, ',', '.') }} KB</small>@endif
        </a>
    @endforeach
</div>
