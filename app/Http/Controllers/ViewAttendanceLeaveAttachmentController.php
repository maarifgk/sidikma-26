<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewAttendanceLeaveAttachmentController extends Controller
{
    public function __invoke(AttendanceLeaveRequest $attendanceLeaveRequest): StreamedResponse
    {
        /** @var User $user */
        $user = auth()->user();
        abort_unless($user->isAdminInduk() || ($user->can('attendance.leave.review') && $user->accessibleSchoolIds()->contains($attendanceLeaveRequest->school_id)), 403);

        $path = $attendanceLeaveRequest->attachment_path;
        $disk = Storage::disk(AttendanceRecord::DISK);
        abort_unless(filled($path) && $disk->exists($path), 404);

        return $disk->response($path, basename(str_replace('\\', '/', $path)), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
