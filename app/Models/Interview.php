<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Interview extends Model
{
    protected $fillable = [
        'user_id',
        'mode',
        'difficulty',
        'target_role',
        'resume_filename',
        'skills',
        'ats_score',
        'ats_analysis',
        'score',
        'feedback',
        'strengths',
        'improvements',
        'weak_topics',
        'career_roadmap',
        'evaluation_provider',
        'movement_alerts',
        'duration_seconds',
        'status',
        'completed_at',
        'report_emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'ats_analysis' => 'array',
            'strengths' => 'array',
            'improvements' => 'array',
            'weak_topics' => 'array',
            'career_roadmap' => 'array',
            'completed_at' => 'datetime',
            'report_emailed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class)->orderBy('order_index');
    }

    public function formattedDuration(): string
    {
        $seconds = (int) $this->duration_seconds;
        $mins = intdiv($seconds, 60);
        $secs = $seconds % 60;

        return sprintf('%dm %02ds', $mins, $secs);
    }
}
