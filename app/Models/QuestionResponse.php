<?php

namespace App\Models;

use App\Models\Concerns\HasVotes;
use Database\Factories\QuestionResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionResponse extends Model
{
    /** @use HasFactory<QuestionResponseFactory> */
    use HasFactory, HasVotes;

    protected bool $allowDownvotes = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'user_id',
        'body',
        'sequence',
    ];

    /**
     * @return BelongsTo<Question, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
