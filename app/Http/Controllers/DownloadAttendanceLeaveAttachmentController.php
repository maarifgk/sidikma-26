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

        $path = ltrim(str_replace('\\', '/', $leaveRequest->attachment_path), '/');
        $candidates = [
            [AttendanceRecord::DISK, $path],
            ['local', $path],
        ];

        if (str_starts_with($path, 'attendance/')) {
            $candidates[] = [AttendanceRecord::DISK, substr($path, strlen('attendance/'))];
        }

        foreach ($candidates as [$diskName, $candidatePath]) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($candidatePath)) {
                return $disk->download(
                    $candidatePath,
                    basename($candidatePath),
                    ['X-Content-Type-Options' => 'nosniff'],
                );
            }
        }

        abort(404);

        return response()->noContent();
    }
}
