<?php

namespace App\Models;

use App\Enums\EffectiveArea;
use App\Models\Concerns\HasVotes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\QuestionFactory> */
    use HasFactory, HasVotes, SoftDeletes;

    protected bool $allowDownvotes = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'body',
        'user_id',
        'effective_area',
        'province_id',
        'city_id',
        'visits',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_area' => EffectiveArea::class,
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
     * @return HasMany<QuestionVisit, $this>
     */
    public function questionVisits(): HasMany
    {
        return $this->hasMany(QuestionVisit::class);
    }
}
