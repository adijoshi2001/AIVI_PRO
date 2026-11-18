<?php

namespace App\Mail;

use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Interview $interview,
    ) {}

    public function envelope(): Envelope
    {
        $role = $this->interview->target_role ?: 'Technical Interview';
        $score = (int) ($this->interview->score ?? 0);

        return new Envelope(
            subject: "AIVI PRO Report: {$role} — Score {$score}%",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview-report',
            with: [
                'interview' => $this->interview,
                'user' => $this->interview->user,
                'ats' => (array) ($this->interview->ats_analysis ?? []),
                'weakTopics' => (array) ($this->interview->weak_topics ?? []),
                'careerRoadmap' => (array) ($this->interview->career_roadmap ?? []),
            ],
        );
    }
}
