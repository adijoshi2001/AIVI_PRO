@extends('layouts.app')

@section('title', 'Mission Control — AIVI PRO')

@section('content')
<div class="container">
    <!-- Top Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.25rem; margin-bottom: 2rem;">
        <div>
            <span class="pill pill-cyan" style="margin-bottom: 0.55rem;">CANDIDATE WORKSPACE</span>
            <h1 class="page-title gradient-text">Mission Control</h1>
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                @if (auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="user-avatar" referrerpolicy="no-referrer">
                @endif
                <p class="subtitle">
                    Welcome back, <strong style="color: #fff;">{{ auth()->user()->name }}</strong>. Ready to sharpen your interview skills?
                </p>
            </div>
        </div>
        <a href="{{ route('interviews.create') }}" class="btn-primary">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Start New Interview</span>
        </a>
    </div>

    <!-- Project Roadmap Progress -->
    <div class="glass roadmap-banner">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <strong style="font-size: 1rem;">Project Milestone Progress</strong>
                    <span class="pill pill-green">{{ $roadmap->completionPercentage() }}% Complete</span>
                    <span class="pill mono">{{ $roadmap->completedCount() }} / {{ $roadmap->totalCount() }} Phases</span>
                </div>
                <div style="font-size: 0.84rem; color: var(--text-muted); margin-top: 0.25rem;">
                    @if ($roadmap->remainingCount() > 0)
                        Phases 1–{{ $roadmap->completedCount() }} live • Phases {{ $roadmap->completedCount() + 1 }}–{{ $roadmap->totalCount() }} scheduled next
                    @else
                        All 10 project phases are unlocked and active
                    @endif
                </div>
            </div>
            <a href="{{ route('roadmap') }}" class="btn-secondary btn-sm">View 10-Phase Roadmap →</a>
        </div>
        <div class="phase-segments">
            @foreach ($roadmap->phases() as $phase)
                <div class="phase-segment {{ $phase['completed'] ? 'done' : '' }}" title="Phase {{ $phase['number'] }}: {{ $phase['title'] }}"></div>
            @endforeach
        </div>
    </div>

    <!-- KPI Stats -->
    <div class="stats-grid">
        <div class="glass stat-card stat-card-accent-indigo">
            <div class="stat-header">
                <div class="stat-label">Total Sessions</div>
                <span class="stat-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </span>
            </div>
            <div class="stat-value">{{ $totalInterviews }}</div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.3rem;">{{ $completedCount }} completed</div>
        </div>

        <div class="glass stat-card stat-card-accent-green">
            <div class="stat-header">
                <div class="stat-label">Average Score</div>
                <span class="stat-icon" style="color: var(--success);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
            </div>
            <div class="stat-value" style="color: var(--success);">
                {{ $avgScore !== null ? $avgScore . '%' : 'N/A' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.3rem;">Across evaluated sessions</div>
        </div>

        <div class="glass stat-card stat-card-accent-cyan">
            <div class="stat-header">
                <div class="stat-label">Peak Score</div>
                <span class="stat-icon" style="color: var(--accent);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
            </div>
            <div class="stat-value" style="color: var(--accent);">
                {{ $bestScore !== null ? $bestScore . '%' : 'N/A' }}
            </div>
            <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.3rem;">Personal best record</div>
        </div>

        <div class="glass stat-card stat-card-accent-purple">
            <div class="stat-header">
                <div class="stat-label">Top Detected Skills</div>
                <span class="stat-icon" style="color: #d8b4fe;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                </span>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.85rem;">
                @forelse ($topSkills as $skill)
                    <span class="pill">{{ $skill }}</span>
                @empty
                    <span style="color: var(--text-muted); font-size: 0.88rem;">Upload a resume to detect skills</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Interviews -->
    <div style="margin-top: 0.75rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.15rem;">
            <h2 class="section-heading">Recent Interview History</h2>
            <span class="pill mono">{{ $interviews->count() }} {{ \Illuminate\Support\Str::plural('record', $interviews->count()) }}</span>
        </div>

        @forelse ($interviews as $interview)
            <div class="glass interview-row">
                <div style="flex: 1; min-width: 260px;">
                    <div style="display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; margin-bottom: 0.45rem;">
                        <strong style="font-size: 1.08rem; font-family: var(--font-display);">{{ $interview->target_role ?: 'Technical Interview' }}</strong>
                        <span class="pill {{ $interview->mode === 'live' ? 'pill-purple' : '' }}">
                            {{ $interview->mode === 'live' ? 'Live Video/Voice' : 'Text Mode' }}
                        </span>
                        <span class="pill pill-amber">{{ ucfirst($interview->difficulty) }}</span>
                        @if ($interview->ats_score !== null)
                            <span class="pill {{ $interview->ats_score >= 70 ? 'pill-green' : 'pill-red' }} mono">
                                ATS {{ $interview->ats_score }}%
                            </span>
                        @endif
                        @if ($interview->report_emailed_at)
                            <span class="pill pill-cyan">Emailed ✓</span>
                        @endif
                        @if ($interview->status === 'in_progress')
                            <span class="pill pill-red">In Progress</span>
                        @endif
                    </div>

                    <div style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 0.6rem;">
                        {{ $interview->created_at->format('M d, Y • h:i A') }}
                        • {{ $interview->questions_count }} questions
                        @if ($interview->duration_seconds > 0)
                            • {{ $interview->formattedDuration() }}
                        @endif
                    </div>

                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                        @foreach (array_slice((array) $interview->skills, 0, 6) as $skill)
                            <span style="font-size: 0.74rem; padding: 0.16rem 0.58rem; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.06); color: #cbd5e1;">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap;">
                    @if ($interview->score !== null)
                        <div class="score-pill-box">
                            <div style="font-family: var(--font-display); font-size: 1.45rem; font-weight: 800; line-height: 1.1; color: {{ $interview->score >= 70 ? 'var(--success)' : ($interview->score >= 45 ? 'var(--warning)' : 'var(--danger)') }};">
                                {{ $interview->score }}%
                            </div>
                            <div class="mono" style="font-size: 0.66rem; color: var(--text-muted); text-transform: uppercase; margin-top: 0.15rem;">
                                {{ $interview->evaluation_provider ?? 'AI' }}
                            </div>
                        </div>
                    @endif

                    @if ($interview->status === 'in_progress')
                        <a href="{{ route('interviews.session', $interview) }}" class="btn-primary btn-sm">Resume →</a>
                    @else
                        <a href="{{ route('interviews.report', $interview) }}" class="btn-secondary btn-sm">View Report</a>
                    @endif

                    <form action="{{ route('interviews.destroy', $interview) }}" method="POST" onsubmit="return confirm('Delete this interview session permanently?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm" title="Delete session">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="glass" style="padding: 3.5rem 2rem; text-align: center;">
                <div class="feature-icon" style="width: 58px; height: 58px; margin: 0 auto 1rem; border-radius: 16px;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                </div>
                <h3 class="section-heading" style="margin-bottom: 0.5rem;">No interviews yet</h3>
                <p class="subtitle" style="max-width: 460px; margin: 0 auto 1.65rem; font-size: 0.94rem;">
                    Upload your PDF resume or paste your tech stack to generate your first tailored 5-question interview session.
                </p>
                <a href="{{ route('interviews.create') }}" class="btn-primary">Start Your First Interview →</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
