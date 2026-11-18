@extends('layouts.app')

@section('title', 'New Interview — AIVI PRO')

@section('content')
<div class="container-narrow">
    <div style="margin-bottom: 1.75rem;">
        <a href="{{ route('dashboard') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 0.35rem;">
            ← Back to Mission Control
        </a>
        <h1 class="page-title gradient-text" style="margin-top: 0.55rem;">Configure Your AI Interview</h1>
        <p class="subtitle" style="margin-top: 0.35rem;">Upload your PDF resume or paste a quick summary—AIVI will generate 5 tailored technical &amp; behavioral questions.</p>
    </div>

    @if (! $cloudLlmEnabled)
        <div class="alert alert-info">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>
                <strong>Milestone Mode ({{ $roadmap->completionPercentage() }}% — Phases 1–{{ $roadmap->completedCount() }} Active):</strong>
                Running on <strong>Phase 5 Smart Local Heuristic Engine</strong>. Cloud LLM integration (Gemini &amp; Groq) is scheduled for <strong>Phase 9</strong>.
                <a href="{{ route('roadmap') }}" style="color: #c7d2fe; font-weight: 600; margin-left: 0.35rem;">View Roadmap →</a>
            </span>
        </div>
    @elseif (! $aiConfigured)
        <div class="alert alert-info">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>
                Running in <strong>Smart Local Heuristic Mode</strong> (no API key set). Test the complete workflow right now, or add <code>GEMINI_API_KEY</code> / <code>GROQ_API_KEY</code> in <code>.env</code> for live LLM interviews.
            </span>
        </div>
    @endif

    <div class="glass" style="padding: 2.25rem;">
        <form action="{{ route('interviews.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Mode Selector -->
            <div class="form-group">
                <label class="form-label">
                    <span class="step-badge">01</span>
                    <span>Select Interview Mode</span>
                </label>
                <div class="mode-grid-3">
                    <label class="mode-option">
                        <input type="radio" name="mode" value="text" {{ old('mode', 'text') === 'text' || (! $liveModeEnabled && ! $codingRoundEnabled) ? 'checked' : '' }}>
                        <div class="mode-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                <span class="stat-icon" style="color: #c7d2fe;">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M8 12h.01M12 12h.01M16 12h.01M7 16h10"/></svg>
                                </span>
                                <span class="pill pill-green">Phase 5 • Active</span>
                            </div>
                            <div style="font-weight: 700; margin-bottom: 0.3rem; font-size: 0.96rem;">Structured Text Q&amp;A</div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                                Topic-tagged technical &amp; behavioral Q&amp;A with live timer, ATS audit, weak-topic plan, job roadmap &amp; email report.
                            </div>
                        </div>
                    </label>

                    @if ($liveModeEnabled)
                        <label class="mode-option">
                            <input type="radio" name="mode" value="live" {{ old('mode') === 'live' ? 'checked' : '' }}>
                            <div class="mode-card">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                    <span class="stat-icon" style="color: #d8b4fe;">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                                    </span>
                                    <span class="pill pill-green">Phase 7 • Active</span>
                                </div>
                                <div style="font-weight: 700; margin-bottom: 0.3rem; font-size: 0.96rem;">Live Video &amp; Voice</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                                    Webcam stream with motion proctoring, AI spoken questions (TTS), and live voice answers (STT).
                                </div>
                            </div>
                        </label>
                    @else
                        <div class="mode-option mode-option-locked" title="Scheduled for Phase 7">
                            <div class="mode-card mode-card-locked">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                    <span class="stat-icon">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                                    </span>
                                    <span class="pill pill-amber">🔒 Phase 7</span>
                                </div>
                                <div style="font-weight: 700; margin-bottom: 0.3rem; font-size: 0.96rem;">Live Video &amp; Proctoring</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                                    Webcam proctoring, AI spoken TTS questions &amp; live voice answers — unlocking in Phase 7.
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($codingRoundEnabled)
                        <label class="mode-option">
                            <input type="radio" name="mode" value="coding" {{ old('mode') === 'coding' ? 'checked' : '' }}>
                            <div class="mode-card">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                    <span class="stat-icon" style="color: var(--accent);">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    </span>
                                    <span class="pill pill-green">Phase 8 • Active</span>
                                </div>
                                <div style="font-weight: 700; margin-bottom: 0.3rem; font-size: 0.96rem;">Technical Coding Round</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                                    Role-specific DSA, SQL &amp; live coding challenges tailored to your target job.
                                </div>
                            </div>
                        </label>
                    @else
                        <div class="mode-option mode-option-locked" title="Scheduled for Phase 8">
                            <div class="mode-card mode-card-locked">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                    <span class="stat-icon" style="color: var(--accent);">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    </span>
                                    <span class="pill pill-amber">🔒 Phase 8</span>
                                </div>
                                <div style="font-weight: 700; margin-bottom: 0.3rem; font-size: 0.96rem;">Technical Coding Round</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">
                                    Target-job coding &amp; DSA problems with code editor &amp; test cases — coming in Phase 8.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @error('mode')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Target Role & Difficulty -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                <div class="form-group">
                    <label class="form-label" for="target_role">
                        <span class="step-badge">02</span>
                        <span>Target Job Role</span>
                    </label>
                    <input id="target_role" type="text" name="target_role" value="{{ old('target_role', 'Full Stack Laravel Developer') }}" class="form-input" placeholder="e.g. Backend Developer, Data Scientist">
                </div>

                <div class="form-group">
                    <label class="form-label" for="difficulty">
                        <span class="step-badge">03</span>
                        <span>Difficulty Level</span>
                    </label>
                    <select id="difficulty" name="difficulty" class="form-select">
                        <option value="beginner" {{ old('difficulty') === 'beginner' ? 'selected' : '' }}>Beginner / Fresher</option>
                        <option value="intermediate" {{ old('difficulty', 'intermediate') === 'intermediate' ? 'selected' : '' }}>Intermediate (1–3 yrs)</option>
                        <option value="advanced" {{ old('difficulty') === 'advanced' ? 'selected' : '' }}>Advanced / Senior</option>
                    </select>
                </div>
            </div>

            <!-- Resume Upload -->
            <div class="form-group">
                <label class="form-label">
                    <span class="step-badge">04</span>
                    <span>Upload PDF Resume</span>
                </label>
                <div class="dropzone" id="dropzone" onclick="document.getElementById('resume').click()">
                    <div class="feature-icon" style="margin: 0 auto 0.75rem;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    </div>
                    <div id="dropzone-label" style="font-weight: 700; font-size: 0.96rem;">Click or drag &amp; drop your PDF resume here</div>
                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.25rem;">Max 5 MB • Automatically parses skills + runs full ATS friendliness audit &amp; fix suggestions</div>
                    <input type="file" id="resume" name="resume" accept=".pdf" style="display: none;">
                </div>
                @error('resume')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <!-- Or Paste Resume Text -->
            <div class="form-group">
                <label class="form-label" for="resume_text">Or Paste Resume / Skills Summary (Optional if PDF uploaded)</label>
                <textarea id="resume_text" name="resume_text" class="form-textarea" placeholder="Example: Built REST APIs with PHP, Laravel, MySQL, React, Docker, and deployed on AWS...">{{ old('resume_text') }}</textarea>
                @error('resume_text')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem; font-size: 0.98rem;">
                <span>Initialize AI Interview Session</span>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const resumeInput = document.getElementById('resume');
    const dropzone = document.getElementById('dropzone');
    const label = document.getElementById('dropzone-label');

    function setFileLabel(file) {
        if (!file) return;
        label.textContent = 'Selected: ' + file.name;
        dropzone.classList.add('has-file');
    }

    resumeInput.addEventListener('change', () => {
        if (resumeInput.files.length > 0) {
            setFileLabel(resumeInput.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach(evt => {
        dropzone.addEventListener(evt, e => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
        });
    });

    dropzone.addEventListener('drop', e => {
        if (e.dataTransfer.files.length > 0) {
            resumeInput.files = e.dataTransfer.files;
            setFileLabel(e.dataTransfer.files[0]);
        }
    });
</script>
@endpush
@endsection
