<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLeaveRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class DownloadAttendanceLeaveAttachmentController extends Controller
{
    public function __invoke(AttendanceLeaveRequest $leaveRequest): Response
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && $user->hasAnyRole([User::ROLE_ADMIN_INDUK, User::ROLE_ADMIN_SEKOLAH_MADRASAH])
                && $user->can('attendance.leave.review')
                && $user->accessibleSchoolIds()->contains($leaveRequest->school_id),
            403,
        );

        abort_unless(filled($leaveRequest->attachment_path), 404);

        $disk = Storage::disk(AttendanceRecord::DISK);
        abort_unless($disk->exists($leaveRequest->attachment_path), 404);

        return $disk->download(
            $leaveRequest->attachment_path,
            basename($leaveRequest->attachment_path),
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }
}
