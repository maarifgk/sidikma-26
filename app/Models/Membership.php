<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;

#[Fillable([
    'user_id',
    'foundation_id',
    'school_id',
    'status',
    'start_date',
    'end_date',
])]
class Membership extends Model
{
    use HasFactory;

    /**
     * Limit memberships to those active at a given date.
     */
    public function scopeActive(Builder $query, ?CarbonInterface $at = null): Builder
    {
        $date = Carbon::instance($at ?? now())->toDateString();

        return $query
            ->where('status', 'active')
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('start_date')
                    ->orWhereDate('start_date', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            });
    }

    /**
     * Get the user that owns the membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the foundation assigned to the membership.
     */
    public function foundation(): BelongsTo
    {
        return $this->belongsTo(Foundation::class);
    }

    /**
     * Get the optional school assigned to the membership.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
