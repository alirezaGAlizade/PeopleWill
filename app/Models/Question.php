<?php

namespace App\Models;

use App\Enums\EffectiveArea;
use App\Enums\QuestionStatus;
use App\Models\Concerns\HasVotes;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory, HasVotes, SoftDeletes;

    protected bool $allowDownvotes = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'body',
        'user_id',
        'official_role_id',
        'status',
        'effective_area',
        'province_id',
        'city_id',
        'visits',
        'response_deadline_at',
        'response_validation_ends_at',
        'second_response_deadline_at',
        'remediation_review_ends_at',
        'threshold_met_at',
        'second_response_posted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_area' => EffectiveArea::class,
            'status' => QuestionStatus::class,
            'response_deadline_at' => 'datetime',
            'response_validation_ends_at' => 'datetime',
            'second_response_deadline_at' => 'datetime',
            'remediation_review_ends_at' => 'datetime',
            'threshold_met_at' => 'datetime',
            'second_response_posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
     * @return BelongsTo<OfficialRole, $this>
     */
    public function officialRole(): BelongsTo
    {
        return $this->belongsTo(OfficialRole::class);
    }

    public function isComplete(): bool
    {
        return $this->status !== QuestionStatus::Incomplete;
    }

    /**
     * @return HasMany<QuestionVisit, $this>
     */
    public function questionVisits(): HasMany
    {
        return $this->hasMany(QuestionVisit::class);
    }

    /**
     * @return HasMany<QuestionResponse, $this>
     */
    public function questionResponses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
