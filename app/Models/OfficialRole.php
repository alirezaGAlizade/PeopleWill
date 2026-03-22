<?php

namespace App\Models;

use App\Enums\MandatoryResponseThresholdPercent;
use App\Enums\WindowDuration;
use App\Enums\WindowPlan;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\OfficialRoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class OfficialRole extends Model
{
    /** @use HasFactory<OfficialRoleFactory> */
    use HasFactory, HasSlug;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'country_id',
        'province_id',
        'city_id',
        'window_plan',
        'open_window_duration',
        'last_window_close_date',
        'mandatory_response_threshold',
        'response_deadline_days',
        'participation_quorum_percent',
        'response_rejection_downvote_percent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'window_plan' => WindowPlan::class,
            'open_window_duration' => WindowDuration::class,
            'last_window_close_date' => 'datetime',
            'mandatory_response_threshold' => MandatoryResponseThresholdPercent::class,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function isWindowOpen(?CarbonInterface $currentTime = null): bool
    {
        if ($this->window_plan === WindowPlan::Continuously) {
            return true;
        }

        $windowOpensAt = $this->windowOpensAt();
        $windowClosesAt = $this->windowClosesAt();

        if ($windowOpensAt === null || $windowClosesAt === null) {
            return false;
        }

        $now = $currentTime ? CarbonImmutable::instance($currentTime) : now()->toImmutable();

        return $now->greaterThanOrEqualTo($windowOpensAt)
            && $now->lessThan($windowClosesAt);
    }

    public function windowOpensAt(): ?CarbonImmutable
    {
        $monthsInterval = $this->window_plan?->monthsInterval();

        if ($monthsInterval === null || $this->last_window_close_date === null) {
            return null;
        }

        return $this->last_window_close_date->toImmutable()->addMonthsNoOverflow($monthsInterval);
    }

    public function windowClosesAt(): ?CarbonImmutable
    {
        $windowOpensAt = $this->windowOpensAt();
        $windowDurationDays = $this->open_window_duration?->value;

        if ($windowOpensAt === null || $windowDurationDays === null) {
            return null;
        }

        return $windowOpensAt->addDays($windowDurationDays);
    }

    public function scopeWithOpenWindow(Builder $query): void
    {
        $openRoleIds = static::query()
            ->select(['id', 'window_plan', 'open_window_duration', 'last_window_close_date'])
            ->get()
            ->filter(function (self $officialRole): bool {
                return $officialRole->isWindowOpen();
            })
            ->pluck('id');

        if ($openRoleIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereKey($openRoleIds);
    }
}
