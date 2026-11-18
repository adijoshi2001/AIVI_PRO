@extends('layouts.app')

@section('title', 'Interview Report — AIVI PRO')

@section('content')
@php
    $score = (int) ($interview->score ?? 0);
    $scoreColor = $score >= 75 ? '#34d399' : ($score >= 45 ? '#fbbf24' : '#f87171');
    $verdict = $score >= 75 ? 'Strong Performance' : ($score >= 45 ? 'Solid Foundation' : 'Needs Practice');
    $verdictClass = $score >= 75 ? 'pill-green' : ($score >= 45 ? 'pill-amber' : 'pill-red');
    $circumference = 2 * pi() * 62;
    $dashOffset = $circumference - ($score / 100) * $circumference;

    $ats = (array) ($interview->ats_analysis ?? []);
    $atsScore = (int) ($interview->ats_score ?? ($ats['score'] ?? 0));
    $atsFriendly = (bool) ($ats['is_ats_friendly'] ?? ($atsScore >= 70));
    $atsColor = $atsScore >= 70 ? '#34d399' : ($atsScore >= 45 ? '#fbbf24' : '#f87171');

    $weakTopics = (array) ($interview->weak_topics ?? []);
    $careerRoadmap = (array) ($interview->career_roadmap ?? []);
@endphp

<div class="container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <a href="{{ route('dashboard') }}" class="no-print" style="color: var(--text-muted); text-decoration: none; font-size: 0.88rem;">← Back to Mission Control</a>
            <h1 class="page-title gradient-text" style="margin-top: 0.5rem;">Performance Report</h1>
            <p class="subtitle" style="margin-top: 0.25rem;">
                {{ $interview->target_role }} • {{ ($interview->completed_at ?? $interview->created_at)->format('F d, Y \a\t h:i A') }}
            </p>
        </div>

        <div class="no-print" style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
            <form action="{{ route('interviews.email', $interview) }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <span>{{ $interview->report_emailed_at ? 'Resend Report to Email' : 'Email Report to Me' }}</span>
                </button>
            </form>

            @if ($roadmap->isFeatureEnabled('pdf_export'))
                <button type="button" class="btn-secondary" onclick="window.print()">🖨️ Print / Save PDF</button>
            @else
                <button type="button" class="btn-locked" disabled title="PDF Export unlocks in Phase 10">
                    🔒 Export PDF (Phase 10)
                </button>
            @endif
            <a href="{{ route('interviews.create') }}" class="btn-primary">New Interview →</a>
        </div>
    </div>

    <!-- Email Delivery Status Banner -->
    @if ($interview->report_emailed_at)
        <div class="alert alert-success no-print" style="margin-bottom: 1.25rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" style="flex-shrink: 0;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>
                Result report dispatched to <strong>{{ $interview->user->email }}</strong> on {{ $interview->report_emailed_at->format('M d, Y \a\t h:i A') }}.
                @if (($mailDriver ?? 'log') === 'log')
                    <span style="opacity: 0.85;">(Currently using local <code>MAIL_MAILER=log</code> — set SMTP/Gmail in <code>.env</code> for live inbox delivery.)</span>
                @endif
            </span>
        </div>
    @endif

    <!-- Sticky Section Quick-Jump Nav -->
    <div class="glass report-nav no-print">
        <a href="#summary" class="report-nav-link">Executive Summary</a>
        <a href="#weak-topics" class="report-nav-link">
            Weak Topics ({{ count($weakTopics) }})
        </a>
        <a href="#ats-audit" class="report-nav-link">
            ATS Resume Audit ({{ $atsScore }}%)
        </a>
        <a href="#job-roadmap" class="report-nav-link">Target Job Roadmap</a>
        <a href="#qa-breakdown" class="report-nav-link">Q&amp;A Breakdown</a>
    </div>

    <!-- 1. Top Score Card -->
    <section id="summary" class="glass preview-window" style="padding: 2.25rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
            <div style="flex: 1; min-width: 260px;">
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem;">
                    <span class="pill {{ $verdictClass }}">{{ $verdict }}</span>
                    <span class="pill {{ $interview->mode === 'live' ? 'pill-purple' : '' }}">
                        {{ $interview->mode === 'live' ? 'Live Video/Voice' : 'Text Mode' }}
                    </span>
                    <span class="pill pill-amber">{{ ucfirst($interview->difficulty) }}</span>
                    <span class="pill pill-cyan mono">{{ strtoupper($interview->evaluation_provider ?? 'AI') }}</span>
                    @if ($interview->duration_seconds > 0)
                        <span class="pill mono">{{ $interview->formattedDuration() }}</span>
                    @endif
                    @if ($ats !== [])
                        <span class="pill {{ $atsFriendly ? 'pill-green' : 'pill-red' }}">
                            ATS: {{ $atsScore }}% {{ $atsFriendly ? '✓' : '!' }}
                        </span>
                    @endif
                </div>

                <h2 class="section-heading" style="margin-bottom: 0.6rem;">Executive Summary</h2>
                <p style="color: #e2e8f0; font-size: 1rem; line-height: 1.7;">
                    {{ $interview->feedback }}
                </p>

                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 1.15rem;">
                    @foreach ((array) $interview->skills as $skill)
                        <span class="pill">{{ $skill }}</span>
                    @endforeach
                </div>
            </div>

            <!-- Circular SVG Gauge -->
            <div style="text-align: center; padding: 0.5rem 1rem;">
                <div class="score-circle">
                    <svg width="158" height="158" viewBox="0 0 158 158">
                        <circle cx="79" cy="79" r="62" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="12" />
                        <circle
                            cx="79" cy="79" r="62"
                            fill="none"
                            stroke="{{ $scoreColor }}"
                            stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="{{ number_format($circumference, 2, '.', '') }}"
                            stroke-dashoffset="{{ number_format($dashOffset, 2, '.', '') }}"
                            transform="rotate(-90 79 79)"
                            style="filter: drop-shadow(0 0 10px {{ $scoreColor }}66);"
                        />
                    </svg>
                    <div class="score-circle-value" style="color: {{ $scoreColor }};">{{ $score }}%</div>
                </div>
                <div class="mono" style="font-size: 0.76rem; color: var(--text-muted); margin-top: 0.45rem; font-weight: 700; letter-spacing: 0.08em;">OVERALL AI SCORE</div>
            </div>
        </div>
    </section>

    <!-- 2. Weak Topics Focus Matrix -->
    <section id="weak-topics" class="glass" style="padding: 2rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.25rem;">
            <div>
                <span class="pill {{ $weakTopics !== [] ? 'pill-red' : 'pill-green' }}" style="margin-bottom: 0.45rem;">TOPIC GAP ANALYSIS</span>
                <h2 class="section-heading">Weak Topics &amp; What to Focus On</h2>
                <p class="subtitle" style="font-size: 0.9rem; margin-top: 0.2rem;">
                    Topics where your interview answers scored below 65%—prioritize these before your next round.
                </p>
            </div>
            <span class="pill mono {{ $weakTopics !== [] ? 'pill-amber' : 'pill-green' }}">
                {{ count($weakTopics) }} Focus {{ \Illuminate\Support\Str::plural('Topic', count($weakTopics)) }}
            </span>
        </div>

        @if ($weakTopics !== [])
            <div class="weak-topic-grid">
                @foreach ($weakTopics as $wt)
                    @php
                        $priority = (string) ($wt['priority'] ?? 'High');
                        $cardClass = match ($priority) {
                            'Critical' => '',
                            'High' => 'priority-high',
                            default => 'priority-moderate',
                        };
                        $badgeClass = match ($priority) {
                            'Critical' => 'pill-red',
                            'High' => 'pill-amber',
                            default => 'pill-cyan',
                        };
                    @endphp
                    <div class="weak-topic-card {{ $cardClass }}">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                            <strong style="font-family: var(--font-display); font-size: 1.05rem; color: #fff;">{{ $wt['topic'] }}</strong>
                            <span class="pill {{ $badgeClass }} mono">{{ $wt['avg_score'] }}% • {{ $priority }}</span>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Missed / weak in {{ $wt['missed_count'] }} {{ \Illuminate\Support\Str::plural('question', (int) $wt['missed_count']) }}
                        </div>
                        <p style="font-size: 0.88rem; color: #e2e8f0; line-height: 1.55;">
                            <strong style="color: var(--primary-bright);">Focus Plan:</strong> {{ $wt['focus_plan'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="glass-subtle" style="padding: 1.4rem; border-color: rgba(52, 211, 153, 0.3); display: flex; align-items: center; gap: 1rem;">
                <span class="stat-icon" style="color: var(--success); border-color: rgba(52, 211, 153, 0.35);">✓</span>
                <div>
                    <strong style="color: var(--success);">No Critical Weak Topics Detected!</strong>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 0.15rem;">
                        You scored 65%+ across every tested topic. Move up to Advanced difficulty or focus on system design trade-offs.
                    </p>
                </div>
            </div>
        @endif
    </section>

    <!-- 3. ATS Resume Audit & Fixes -->
    @if ($ats !== [])
        <section id="ats-audit" class="glass" style="padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.15rem;">
                <div>
                    <span class="pill {{ $atsFriendly ? 'pill-green' : 'pill-red' }}" style="margin-bottom: 0.45rem;">
                        {{ $atsFriendly ? '✓ ATS FRIENDLY RESUME' : '⚠ ATS OPTIMIZATION NEEDED' }}
                    </span>
                    <h2 class="section-heading">Resume ATS Analysis &amp; Recommended Fixes</h2>
                    <p class="subtitle" style="font-size: 0.9rem; margin-top: 0.2rem;">
                        Verdict: <strong style="color: #fff;">{{ $ats['verdict'] ?? 'Evaluated' }}</strong>
                        @if (isset($ats['word_count']))
                            • {{ $ats['word_count'] }} words scanned
                        @endif
                    </p>
                </div>

                <div class="score-pill-box" style="min-width: 112px; padding: 0.65rem 1rem;">
                    <div style="font-family: var(--font-display); font-size: 1.85rem; font-weight: 800; color: {{ $atsColor }}; line-height: 1.05;">
                        {{ $atsScore }}%
                    </div>
                    <div class="mono" style="font-size: 0.68rem; color: var(--text-muted); margin-top: 0.2rem;">ATS SCORE</div>
                </div>
            </div>

            <div class="progress-track" style="height: 8px; margin-bottom: 1.25rem;">
                <div style="height: 100%; width: {{ $atsScore }}%; background: {{ $atsColor }}; border-radius: 999px;"></div>
            </div>

            <!-- ATS Checklist -->
            @if (! empty($ats['checks']))
                <div class="ats-checks-grid">
                    @foreach ((array) $ats['checks'] as $check)
                        <div class="ats-check-item {{ ! empty($check['passed']) ? 'passed' : 'failed' }}">
                            <span style="font-weight: 800; color: {{ ! empty($check['passed']) ? 'var(--success)' : 'var(--danger)' }}; flex-shrink: 0;">
                                {{ ! empty($check['passed']) ? '✓' : '✕' }}
                            </span>
                            <div>
                                <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc;">{{ $check['label'] }}</div>
                                <div style="font-size: 0.81rem; color: var(--text-muted); margin-top: 0.2rem;">{{ $check['detail'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Actionable ATS Fixes -->
            @if (! empty($ats['fixes']))
                <div style="margin-top: 1.4rem;">
                    <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--warning); margin-bottom: 0.5rem;">
                        Actionable ATS Fixes for Your Resume
                    </h3>
                    <div class="ats-fix-list">
                        @foreach ((array) $ats['fixes'] as $idx => $fix)
                            <div class="ats-fix-item">
                                <span class="step-badge" style="flex-shrink: 0;">FIX {{ $idx + 1 }}</span>
                                <span>{{ $fix }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    <!-- 4. Target Job Achievement Roadmap -->
    @if ($careerRoadmap !== [])
        <section id="job-roadmap" class="glass" style="padding: 2rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="pill pill-cyan" style="margin-bottom: 0.45rem;">POST-INTERVIEW CAREER PLAN</span>
                    <h2 class="section-heading">How to Achieve: {{ $careerRoadmap['target_role'] ?? $interview->target_role }}</h2>
                    <p class="subtitle" style="font-size: 0.92rem; margin-top: 0.25rem; max-width: 720px;">
                        {{ $careerRoadmap['readiness_summary'] ?? '' }}
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem;">
                    <span class="pill pill-green">{{ $careerRoadmap['readiness_level'] ?? 'In Progress' }}</span>
                    @if (! empty($careerRoadmap['estimated_timeline']))
                        <span class="pill mono">{{ $careerRoadmap['estimated_timeline'] }}</span>
                    @endif
                </div>
            </div>

            <!-- Missing Skills for Target Role -->
            @if (! empty($careerRoadmap['missing_skills']))
                <div class="glass-subtle" style="padding: 1rem 1.2rem; margin-top: 1.15rem;">
                    <div style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--warning); margin-bottom: 0.55rem;">
                        Remaining Role-Critical Skills to Add / Strengthen
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.45rem;">
                        @foreach ((array) $careerRoadmap['missing_skills'] as $skill)
                            <span class="pill pill-amber">+ {{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 4-Stage Career Roadmap Timeline -->
            <div class="roadmap-timeline">
                @foreach ((array) ($careerRoadmap['steps'] ?? []) as $step)
                    <div class="roadmap-step">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap;">
                                <span class="step-badge">{{ $step['stage'] }}</span>
                                <strong style="font-family: var(--font-display); font-size: 1.04rem; color: #fff;">{{ $step['title'] }}</strong>
                            </div>
                            <span class="pill mono">{{ $step['timeline'] }}</span>
                        </div>
                        <ul class="phase-deliverables">
                            @foreach ((array) ($step['tasks'] ?? []) as $task)
                                <li>
                                    <span style="color: var(--accent);">→</span>
                                    <span>{{ $task }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <!-- Upcoming Phase 8 Technical Coding Round Teaser -->
            <div class="coding-teaser">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <span class="pill pill-amber">🔒 Phase 8 • Upcoming</span>
                        <strong style="font-size: 0.96rem; color: #fff;">Technical Coding Round &amp; DSA Prep</strong>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">
                        Next milestone adds hands-on coding questions, SQL/debugging rounds, and automated test cases tailored to <strong>{{ $interview->target_role }}</strong>.
                    </p>
                </div>
                <a href="{{ route('roadmap') }}" class="btn-secondary btn-sm no-print">View Phase 8 Details →</a>
            </div>
        </section>
    @endif

    <!-- 5. Strengths & Improvements -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
        <div class="glass stat-card-accent-green" style="padding: 1.75rem; position: relative; overflow: hidden;">
            <h3 style="color: var(--success); font-size: 1.08rem; margin-bottom: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Key Strengths</span>
            </h3>
            <ul class="phase-deliverables">
                @forelse ((array) $interview->strengths as $item)
                    <li><span style="color: var(--success);">✓</span><span>{{ $item }}</span></li>
                @empty
                    <li><span style="color: var(--success);">✓</span><span>Completed the interview session.</span></li>
                @endforelse
            </ul>
        </div>

        <div class="glass stat-card-accent-purple" style="padding: 1.75rem; position: relative; overflow: hidden;">
            <h3 style="color: var(--warning); font-size: 1.08rem; margin-bottom: 0.9rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"/></svg>
                <span>Areas to Improve</span>
            </h3>
            <ul class="phase-deliverables">
                @forelse ((array) $interview->improvements as $item)
                    <li><span style="color: var(--warning);">▲</span><span>{{ $item }}</span></li>
                @empty
                    <li><span style="color: var(--warning);">▲</span><span>Continue practicing advanced technical scenarios.</span></li>
                @endforelse
            </ul>
        </div>
    </div>

    <!-- 6. Q&A Transcript -->
    <section id="qa-breakdown" class="glass" style="padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1.5rem;">
            <h2 class="section-heading">Detailed Q&amp;A Breakdown</h2>
            <span class="pill mono">{{ $interview->questions->count() }} Questions Evaluated</span>
        </div>

        @foreach ($interview->questions as $idx => $q)
            @php
                $qScore = (int) ($q->question_score ?? 0);
                $qPill = $qScore >= 70 ? 'pill-green' : ($qScore >= 45 ? 'pill-amber' : 'pill-red');
                $qBarColor = $qScore >= 70 ? 'var(--success)' : ($qScore >= 45 ? 'var(--warning)' : 'var(--danger)');
            @endphp
            <div class="glass-subtle" style="padding: 1.4rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
                    <div>
                        @if ($q->topic)
                            <span class="pill pill-cyan" style="margin-bottom: 0.4rem;">Topic: {{ $q->topic }}</span>
                        @endif
                        <div style="font-family: var(--font-display); font-weight: 700; color: #e0e7ff; font-size: 1.02rem; line-height: 1.45;">
                            Q{{ $idx + 1 }}. {{ $q->question_text }}
                        </div>
                    </div>
                    <span class="pill {{ $qPill }} mono" style="white-space: nowrap;">Score: {{ $qScore }}%</span>
                </div>

                <div class="progress-track" style="height: 5px; margin-bottom: 0.9rem;">
                    <div style="height: 100%; width: {{ $qScore }}%; background: {{ $qBarColor }}; border-radius: 999px;"></div>
                </div>

                <div style="padding: 0.95rem 1.05rem; border-radius: 0.75rem; background: rgba(3, 7, 18, 0.55); border: 1px solid rgba(255, 255, 255, 0.06); color: #f8fafc; margin-bottom: 0.75rem; white-space: pre-wrap; font-size: 0.93rem;">{{ $q->answer_text ?: '(No answer provided)' }}</div>

                @if ($q->question_feedback)
                    <div style="font-size: 0.88rem; color: var(--text-muted);">
                        <strong style="color: #e2e8f0;">Feedback:</strong> {{ $q->question_feedback }}
                    </div>
                @endif
            </div>
        @endforeach
    </section>
</div>
@endsection
