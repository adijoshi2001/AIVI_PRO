@extends('layouts.app')

@section('title', 'AIVI PRO — AI Virtual Interviewer (Laravel)')

@section('content')
<div class="container">
    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-layout">
            <div>
                <div class="hero-badge">
                    <span class="pulse-dot"></span>
                    <span>AI-Powered Technical &amp; Behavioral Interview Studio</span>
                </div>

                <h1 class="hero-title gradient-text">
                    Master Your Next Interview with AIVI PRO
                </h1>

                <p class="subtitle" style="max-width: 560px; font-size: 1.05rem; line-height: 1.7;">
                    Upload your PDF resume, get role-specific technical questions tailored to your stack,
                    and receive structured scoring with per-question feedback in seconds.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('register') }}" class="btn-primary">
                        <span>Start Free Mock Interview</span>
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary">Sign In</a>
                    <a href="{{ route('roadmap') }}" class="btn-secondary">Roadmap ({{ $roadmap->completionPercentage() }}%)</a>
                </div>

                <div class="hero-metrics">
                    <div>
                        <div class="hero-metric-val">{{ $roadmap->completedCount() }} / {{ $roadmap->totalCount() }}</div>
                        <div class="hero-metric-lbl">Phases Live ({{ $roadmap->completionPercentage() }}%)</div>
                    </div>
                    <div>
                        <div class="hero-metric-val">25+</div>
                        <div class="hero-metric-lbl">Auto-Detected Tech Skills</div>
                    </div>
                    <div>
                        <div class="hero-metric-val">OAuth 2.0</div>
                        <div class="hero-metric-lbl">Google + Email Auth</div>
                    </div>
                </div>
            </div>

            <!-- Interactive-Style Preview Panel -->
            <div class="glass preview-window">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.1rem; padding-bottom: 0.85rem; border-bottom: 1px solid var(--glass-border);">
                    <div class="window-dots">
                        <span class="window-dot" style="background: #f87171;"></span>
                        <span class="window-dot" style="background: #fbbf24;"></span>
                        <span class="window-dot" style="background: #34d399;"></span>
                    </div>
                    <span class="pill pill-cyan mono" style="font-size: 0.7rem;">SESSION PREVIEW • LIVE RUBRIC</span>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; margin-bottom: 1rem;">
                    <span class="pill">Laravel</span>
                    <span class="pill">PHP 8.5</span>
                    <span class="pill">MySQL</span>
                    <span class="pill">REST API</span>
                    <span class="pill pill-green">Resume Parsed ✓</span>
                </div>

                <div class="glass-subtle" style="padding: 1rem 1.1rem; margin-bottom: 1rem;">
                    <div style="font-size: 0.74rem; color: var(--primary-bright); font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.35rem;">
                        Question 2 of 5 • Intermediate
                    </div>
                    <div style="font-weight: 600; font-size: 0.94rem; color: #f8fafc; line-height: 1.5;">
                        "What is the N+1 query problem in Laravel Eloquent, and how do you solve it using eager loading?"
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="glass-subtle" style="padding: 0.85rem 1rem;">
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">AI Evaluation Score</div>
                        <div style="font-family: var(--font-display); font-size: 1.75rem; font-weight: 800; color: var(--success); margin-top: 0.15rem;">86%</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted);">Strong technical clarity</div>
                    </div>
                    <div class="glass-subtle" style="padding: 0.85rem 1rem;">
                        <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Active Engine</div>
                        <div style="font-family: var(--font-mono); font-size: 0.92rem; font-weight: 700; color: #c7d2fe; margin-top: 0.4rem;">Phase 5 Local</div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 0.25rem;">Zero-latency grading</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Milestone Progress Banner -->
    <div class="glass roadmap-banner" style="margin-top: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <strong style="font-size: 1rem;">Project Delivery Milestone</strong>
                    <span class="pill pill-green">{{ $roadmap->completionPercentage() }}% Complete</span>
                    <span class="pill mono">Phase {{ $roadmap->completedCount() }} / {{ $roadmap->totalCount() }}</span>
                </div>
                <p style="font-size: 0.86rem; color: var(--text-muted); margin-top: 0.25rem;">
                    Phases 1–{{ $roadmap->completedCount() }} are fully operational right now. Explore all 10 milestones on the roadmap.
                </p>
            </div>
            <a href="{{ route('roadmap') }}" class="btn-secondary btn-sm">Inspect 10-Phase Roadmap →</a>
        </div>
        <div class="phase-segments" aria-label="10 Phase Milestone Progress">
            @foreach ($roadmap->phases() as $phase)
                <div class="phase-segment {{ $phase['completed'] ? 'done' : '' }}" title="Phase {{ $phase['number'] }}: {{ $phase['title'] }}"></div>
            @endforeach
        </div>
    </div>

    <!-- Core Capabilities -->
    <div style="margin-top: 3rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <span class="pill pill-cyan" style="margin-bottom: 0.5rem;">PLATFORM MODULES</span>
                <h2 class="section-heading" style="font-size: 1.75rem;">Built for Realistic Technical Prep</h2>
            </div>
            <p class="subtitle" style="font-size: 0.92rem;">Clean Laravel 13 architecture with phased feature rollout.</p>
        </div>

        <div class="features-grid" style="margin-top: 1.5rem;">
            <div class="glass feature-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                    <div class="feature-icon" style="margin-bottom: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <span class="pill pill-green">Phase 4 • Live</span>
                </div>
                <h3 style="margin-bottom: 0.45rem; font-size: 1.08rem;">ATS Resume Audit &amp; Fixes</h3>
                <p class="subtitle" style="font-size: 0.9rem;">
                    Extracts skills from PDF resumes, checks ATS friendliness (0–100%), and gives concrete ATS resume fixes.
                </p>
            </div>

            <div class="glass feature-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                    <div class="feature-icon" style="margin-bottom: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
                    </div>
                    <span class="pill pill-green">Phase 6 • Live</span>
                </div>
                <h3 style="margin-bottom: 0.45rem; font-size: 1.08rem;">Weak Topics, Job Roadmap &amp; Email</h3>
                <p class="subtitle" style="font-size: 0.9rem;">
                    Identifies missed topics to focus on, maps out how to land your target job, and emails the complete report.
                </p>
            </div>

            <div class="glass feature-card {{ ! $roadmap->isFeatureEnabled('live_mode') ? 'phase-card-locked' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                    <div class="feature-icon" style="margin-bottom: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                    </div>
                    <span class="pill {{ $roadmap->isFeatureEnabled('live_mode') ? 'pill-green' : 'pill-amber' }}">
                        {{ $roadmap->isFeatureEnabled('live_mode') ? 'Phase 7 • Live' : '🔒 Phase 7 • Upcoming' }}
                    </span>
                </div>
                <h3 style="margin-bottom: 0.45rem; font-size: 1.08rem;">Live Voice &amp; Proctoring</h3>
                <p class="subtitle" style="font-size: 0.9rem;">
                    Webcam feed with real-time motion proctoring, AI spoken questions (TTS), and live voice dictation (STT).
                </p>
            </div>

            <div class="glass feature-card {{ ! $roadmap->isFeatureEnabled('coding_round') ? 'phase-card-locked' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                    <div class="feature-icon" style="margin-bottom: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    </div>
                    <span class="pill {{ $roadmap->isFeatureEnabled('coding_round') ? 'pill-green' : 'pill-amber' }}">
                        {{ $roadmap->isFeatureEnabled('coding_round') ? 'Phase 8 • Live' : '🔒 Phase 8 • Upcoming' }}
                    </span>
                </div>
                <h3 style="margin-bottom: 0.45rem; font-size: 1.08rem;">Technical Coding Round</h3>
                <p class="subtitle" style="font-size: 0.9rem;">
                    Target-job coding &amp; DSA problems with in-browser code editor, test cases, and complexity review.
                </p>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div style="margin-top: 3.5rem;">
        <span class="pill" style="margin-bottom: 0.5rem;">WORKFLOW</span>
        <h2 class="section-heading" style="font-size: 1.65rem;">From Resume to Report in 3 Steps</h2>
        <div class="steps-grid">
            <div class="glass step-card">
                <div class="step-number">STEP 01</div>
                <h3 style="font-size: 1.05rem; margin-bottom: 0.4rem;">Sign In &amp; Upload Resume</h3>
                <p class="subtitle" style="font-size: 0.9rem;">Authenticate with Google OAuth or email, upload a PDF resume, and pick your target role &amp; difficulty.</p>
            </div>
            <div class="glass step-card">
                <div class="step-number">STEP 02</div>
                <h3 style="font-size: 1.05rem; margin-bottom: 0.4rem;">Complete Tailored Q&amp;A</h3>
                <p class="subtitle" style="font-size: 0.9rem;">Answer 5 skill-matched questions in a distraction-free workspace with live timer and progress tracking.</p>
            </div>
            <div class="glass step-card">
                <div class="step-number">STEP 03</div>
                <h3 style="font-size: 1.05rem; margin-bottom: 0.4rem;">Review Detailed Score</h3>
                <p class="subtitle" style="font-size: 0.9rem;">Get an instant circular score gauge, executive summary, strengths, and per-question technical feedback.</p>
            </div>
        </div>
    </div>
</div>
@endsection
