<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewAttendanceSelfieController extends Controller
{
    public function __invoke(AttendanceRecord $attendanceRecord, string $slot): StreamedResponse
    {
        abort_unless(in_array($slot, ['masuk', 'pulang'], true), 404);

        /** @var User $user */
        $user = auth()->user();
        $allowed = $user->isAdminInduk()
            || $attendanceRecord->user_id === $user->getKey()
            || ($user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH)
                && $user->accessibleSchoolIds()->contains($attendanceRecord->school_id));
        abort_unless($allowed, 403);

        $path = $slot === 'masuk'
            ? $attendanceRecord->check_in_selfie_path
            : $attendanceRecord->check_out_selfie_path;
        $disk = Storage::disk(AttendanceRecord::DISK);
        abort_unless(filled($path) && $disk->exists($path), 404);

        return $disk->response($path, basename($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
