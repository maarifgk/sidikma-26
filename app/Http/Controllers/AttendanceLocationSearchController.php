<?php

namespace App\Http\Controllers;

use App\Filament\Concerns\ScopesAttendanceToAccessibleSchools;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceLocationSearchController extends Controller
{
    use ScopesAttendanceToAccessibleSchools;

    public function __invoke(Request $request, GeocodingService $geocoding): JsonResponse
    {
        abort_unless(self::canUseAttendanceManagement() && ($request->user()?->can('attendance.settings') ?? false), 403);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:160'],
        ]);

        return response()->json(['results' => $geocoding->search($validated['q'])]);
    }
}
