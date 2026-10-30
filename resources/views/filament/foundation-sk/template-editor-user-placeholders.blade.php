<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h2 class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Placeholder Data Users</h2>
    <div class="space-y-1.5 text-xs leading-5 text-gray-600 dark:text-gray-300">
        @foreach (\App\Services\FoundationSkPdfService::placeholderDefinitions() as $token => $description)
            <div><span class="placeholder-token">&#123;&#123;{{ $token }}&#125;&#125;</span> <span>— {{ $description }}</span></div>
        @endforeach
    </div>
</div>
<style>
    .placeholder-token { color: #dc2626; font-weight: 600; }
</style>
