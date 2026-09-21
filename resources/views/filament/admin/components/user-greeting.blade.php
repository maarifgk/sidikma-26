@php
    $user = filament()->auth()->user();
    $hour = now('Asia/Jakarta')->hour;
    $greeting = match (true) {
        $hour >= 5 && $hour < 11 => 'Pagi',
        $hour >= 11 && $hour < 15 => 'Siang',
        $hour >= 15 && $hour < 18 => 'Sore',
        default => 'Malam',
    };
@endphp

@if ($user)
    <div
        x-data="{ showGreeting: window.innerWidth >= 768 }"
        x-on:resize.window.debounce.150ms="showGreeting = window.innerWidth >= 768"
        x-show="showGreeting"
        x-cloak
        aria-label="Selamat {{ $greeting }}, {{ filament()->getUserName($user) }}"
        style="margin-inline-end: 0.25rem; text-align: end; line-height: 1.25;"
    >
        <span style="display: block; font-size: 0.75rem;">Selamat {{ $greeting }},</span>
        <strong style="display: block; max-width: 14rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.875rem;">
            {{ filament()->getUserName($user) }}
        </strong>
    </div>
@endif
