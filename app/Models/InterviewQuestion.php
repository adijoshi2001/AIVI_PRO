<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'interview_id',
        'order_index',
        'topic',
        'question_text',
        'answer_text',
        'question_score',
        'question_feedback',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }
}
