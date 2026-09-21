<form method="POST" action="{{ route('foundation-sk.templates.generate', ['template' => $template->getKey()]) }}" class="space-y-4">
    @csrf
    <input type="hidden" name="mode" value="download_only">
    <label>User</label>
    <select name="user_id" required class="fi-input w-full">
        <option value="">Pilih user</option>
        @foreach(\App\Models\User::query()->when(! auth()->user()?->isAdminInduk(), fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->whereIn('school_id', auth()->user()?->accessibleSchoolIds() ?? [])))->orderBy('name')->get() as $user)
            <option value="{{ $user->getKey() }}">{{ $user->name }}</option>
        @endforeach
    </select>
    <label>Tahun/Periode</label>
    <input name="periode" required value="{{ now()->year }}" class="fi-input w-full">
    <label>Teks Nomor SK</label>
    <input name="nomor_text" class="fi-input w-full">
    <button type="submit" class="fi-btn fi-btn-color-primary">Generate PDF</button>
</form>
