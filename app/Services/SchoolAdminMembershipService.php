<?php

namespace App\Services;

use App\Models\Foundation;
use App\Models\Membership;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

class SchoolAdminMembershipService
{
    public function sync(User $user): ?Membership
    {
        if ((! $user->is_active) || (! $user->hasRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH))) {
            return null;
        }

        $activeMembership = $user->memberships()
            ->active()
            ->whereNotNull('school_id')
            ->first();

        if ($activeMembership instanceof Membership) {
            return $activeMembership;
        }

        $foundation = Foundation::application();
        $schools = School::query()
            ->where('foundation_id', $foundation->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $normalizedUserName = $this->normalizeName($user->name);
        $normalizedUserNameWithoutAdmin = Str::of($normalizedUserName)
            ->replaceStart('admin ', '')
            ->trim()
            ->toString();

        $school = $schools->first(function (School $school) use ($normalizedUserName, $normalizedUserNameWithoutAdmin, $user): bool {
            $normalizedSchoolName = $this->normalizeName($school->name);

            return in_array($normalizedSchoolName, [$normalizedUserName, $normalizedUserNameWithoutAdmin], true)
                || (filled($school->email) && strcasecmp($school->email, $user->email) === 0);
        });

        if ((! $school instanceof School) && ($schools->count() === 1)) {
            $school = $schools->first();
        }

        if (! $school instanceof School) {
            return null;
        }

        return Membership::query()->updateOrCreate([
            'user_id' => $user->getKey(),
            'foundation_id' => $foundation->getKey(),
            'school_id' => $school->getKey(),
        ], [
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => null,
        ]);
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/u', ' ')
            ->squish()
            ->toString();
    }
}
