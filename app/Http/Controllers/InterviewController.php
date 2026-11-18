<?php

namespace App\Http\Controllers;

use App\Mail\InterviewReportMail;
use App\Models\Interview;
use App\Services\AiService;
use App\Services\ResumeParserService;
use App\Services\RoadmapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class InterviewController extends Controller
{
    public function __construct(
        private readonly ResumeParserService $resumeParser,
        private readonly AiService $aiService,
        private readonly RoadmapService $roadmap,
    ) {}

    public function dashboard(): View
    {
        $user = Auth::user();
        $interviews = $user->interviews()
            ->withCount('questions')
            ->latest()
            ->get();

        $scored = $interviews->whereNotNull('score');
        $avgScore = $scored->count() > 0 ? (int) round($scored->avg('score')) : null;
        $bestScore = $scored->count() > 0 ? (int) $scored->max('score') : null;
        $latestAtsScore = $interviews->firstWhere('ats_score', '!=', null)?->ats_score;

        $skillCounts = [];
        foreach ($interviews as $interview) {
            foreach ((array) $interview->skills as $skill) {
                $skillCounts[$skill] = ($skillCounts[$skill] ?? 0) + 1;
            }
        }
        arsort($skillCounts);
        $topSkills = array_slice(array_keys($skillCounts), 0, 6);

        return view('dashboard', [
            'interviews' => $interviews,
            'totalInterviews' => $interviews->count(),
            'completedCount' => $interviews->where('status', 'completed')->count(),
            'avgScore' => $avgScore,
            'bestScore' => $bestScore,
            'latestAtsScore' => $latestAtsScore,
            'topSkills' => $topSkills,
        ]);
    }

    public function create(): View
    {
        $cloudLlmEnabled = $this->roadmap->isFeatureEnabled('cloud_llm');
        $liveModeEnabled = $this->roadmap->isFeatureEnabled('live_mode');
        $codingRoundEnabled = $this->roadmap->isFeatureEnabled('coding_round');
        $aiConfigured = $cloudLlmEnabled && (
            (trim((string) config('services.gemini.key')) !== '' && ! str_starts_with((string) config('services.gemini.key'), 'your_'))
            || (trim((string) config('services.groq.key')) !== '' && ! str_starts_with((string) config('services.groq.key'), 'your_'))
        );

        return view('interviews.create', [
            'aiConfigured' => $aiConfigured,
            'cloudLlmEnabled' => $cloudLlmEnabled,
            'liveModeEnabled' => $liveModeEnabled,
            'codingRoundEnabled' => $codingRoundEnabled,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'in:text,live,coding'],
            'difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'target_role' => ['nullable', 'string', 'max:120'],
            'resume' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'resume_text' => ['nullable', 'string', 'max:15000'],
        ]);

        if ($validated['mode'] === 'live' && ! $this->roadmap->isFeatureEnabled('live_mode')) {
            return back()
                ->withErrors(['mode' => 'Live Video & Voice Proctoring is scheduled for Phase 7 and is currently locked.'])
                ->withInput();
        }

        if ($validated['mode'] === 'coding' && ! $this->roadmap->isFeatureEnabled('coding_round')) {
            return back()
                ->withErrors(['mode' => 'Technical Coding Round & DSA Challenges are scheduled for Phase 8 and are currently locked.'])
                ->withInput();
        }

        if (! $request->hasFile('resume') && trim((string) ($validated['resume_text'] ?? '')) === '') {
            return back()
                ->withErrors(['resume' => 'Please upload a PDF resume OR paste your resume/skills summary to begin.'])
                ->withInput();
        }

        $resumeFilename = null;
        $extractedText = trim((string) ($validated['resume_text'] ?? ''));
        $pdfUploaded = $request->hasFile('resume');

        if ($pdfUploaded) {
            $file = $request->file('resume');
            $resumeFilename = $file->store('resumes', 'local');
            $pdfPath = Storage::disk('local')->path($resumeFilename);
            $pdfText = $this->resumeParser->extractTextFromPdf($pdfPath);
            if ($pdfText !== '') {
                $extractedText = trim($pdfText . "\n" . $extractedText);
            }
        }

        $targetRole = trim((string) ($validated['target_role'] ?? '')) ?: 'Software Developer';

        $skills = $this->resumeParser->extractSkills(
            $extractedText . ' ' . $targetRole
        );

        $atsAnalysis = $this->resumeParser->analyzeAts(
            $extractedText,
            $skills,
            $targetRole,
            $pdfUploaded
        );

        $generated = $this->aiService->generateQuestions(
            $extractedText,
            $skills,
            $validated['difficulty'],
            $targetRole
        );

        $interview = Interview::create([
            'user_id' => Auth::id(),
            'mode' => $validated['mode'],
            'difficulty' => $validated['difficulty'],
            'target_role' => $targetRole,
            'resume_filename' => $resumeFilename,
            'skills' => $skills,
            'ats_score' => $atsAnalysis['score'],
            'ats_analysis' => $atsAnalysis,
            'evaluation_provider' => $generated['provider'],
            'status' => 'in_progress',
        ]);

        foreach ($generated['questions'] as $index => $item) {
            $interview->questions()->create([
                'order_index' => $index + 1,
                'topic' => $item['topic'],
                'question_text' => $item['question'],
            ]);
        }

        return redirect()->route('interviews.session', $interview);
    }

    public function session(Interview $interview): View|RedirectResponse
    {
        $this->authorizeInterview($interview);

        if ($interview->status === 'completed') {
            return redirect()->route('interviews.report', $interview);
        }

        if ($interview->mode === 'live' && ! $this->roadmap->isFeatureEnabled('live_mode')) {
            return redirect()->route('dashboard')
                ->with('error', 'Live Video & Voice Proctoring is scheduled for Phase 7 and is currently locked.');
        }

        $interview->load('questions');

        return $interview->mode === 'live'
            ? view('interviews.live', ['interview' => $interview])
            : view('interviews.text', ['interview' => $interview]);
    }

    public function submit(Request $request, Interview $interview): RedirectResponse
    {
        $this->authorizeInterview($interview);

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:10000'],
            'movement_alerts' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $interview->load(['questions', 'user']);
        $answers = $validated['answers'] ?? [];
        $qaPairs = [];

        foreach ($interview->questions as $idx => $question) {
            $ans = trim((string) ($answers[$question->id] ?? $answers[$idx] ?? ''));
            $question->answer_text = $ans;
            $qaPairs[] = [
                'topic' => $question->topic ?: 'Core Engineering',
                'question' => $question->question_text,
                'answer' => $ans,
            ];
        }

        $movementAlerts = (int) ($validated['movement_alerts'] ?? 0);
        $durationSeconds = (int) ($validated['duration_seconds'] ?? 0);

        $evaluation = $this->aiService->evaluateInterview(
            $qaPairs,
            (array) $interview->skills,
            $interview->difficulty,
            $movementAlerts,
            $interview->target_role,
            is_array($interview->ats_analysis) ? $interview->ats_analysis : null
        );

        foreach ($interview->questions as $idx => $question) {
            $perQ = $evaluation['per_question'][$idx] ?? null;
            $question->question_score = $perQ['score'] ?? $evaluation['score'];
            $question->question_feedback = $perQ['feedback'] ?? null;
            $question->save();
        }

        $interview->update([
            'score' => $evaluation['score'],
            'feedback' => $evaluation['feedback'],
            'strengths' => $evaluation['strengths'],
            'improvements' => $evaluation['improvements'],
            'weak_topics' => $evaluation['weak_topics'],
            'career_roadmap' => $evaluation['career_roadmap'],
            'evaluation_provider' => $evaluation['provider'],
            'movement_alerts' => $movementAlerts,
            'duration_seconds' => $durationSeconds,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $interview->refresh()->load(['questions', 'user']);
        $emailed = $this->dispatchReportEmail($interview);

        $message = $emailed
            ? 'Interview evaluated! Your complete report, ATS fixes, weak topics & career roadmap have been sent to ' . $interview->user->email . '.'
            : 'Interview evaluated! Here is your complete AI performance report, ATS audit, and target job roadmap.';

        return redirect()->route('interviews.report', $interview)
            ->with('success', $message);
    }

    public function sendEmail(Interview $interview): RedirectResponse
    {
        $this->authorizeInterview($interview);
        $interview->load(['questions', 'user']);

        if ($this->dispatchReportEmail($interview)) {
            $mailer = (string) config('mail.default', 'log');
            $suffix = $mailer === 'log'
                ? ' (Local MAIL_MAILER=log mode: configure SMTP in .env for real inbox delivery.)'
                : '';

            return redirect()->route('interviews.report', $interview)
                ->with('success', 'Interview report emailed to ' . $interview->user->email . '!' . $suffix);
        }

        return redirect()->route('interviews.report', $interview)
            ->with('error', 'Could not deliver email right now. Please verify your MAIL_* settings in .env.');
    }

    public function report(Interview $interview): View
    {
        $this->authorizeInterview($interview);
        $interview->load(['questions', 'user']);

        return view('interviews.report', [
            'interview' => $interview,
            'mailDriver' => (string) config('mail.default', 'log'),
        ]);
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $this->authorizeInterview($interview);

        if ($interview->resume_filename && Storage::disk('local')->exists($interview->resume_filename)) {
            Storage::disk('local')->delete($interview->resume_filename);
        }

        $interview->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Interview session deleted.');
    }

    private function dispatchReportEmail(Interview $interview): bool
    {
        $recipient = trim((string) ($interview->user?->email ?? ''));
        if ($recipient === '') {
            return false;
        }

        try {
            Mail::to($recipient)->send(new InterviewReportMail($interview));
            $interview->forceFill(['report_emailed_at' => now()])->save();

            return true;
        } catch (Throwable $e) {
            Log::warning('AIVI interview report email failed: ' . $e->getMessage());

            return false;
        }
    }

    private function authorizeInterview(Interview $interview): void
    {
        abort_if($interview->user_id !== Auth::id(), 403, 'Unauthorized access to interview record.');
    }
}
