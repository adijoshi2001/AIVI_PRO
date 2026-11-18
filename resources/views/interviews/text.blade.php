@extends('layouts.app')

@section('title', 'Text Interview — AIVI PRO')

@section('content')
<div class="container-narrow">
    <!-- Sticky Session HUD -->
    <div class="glass session-hud">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.85rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.25rem;">
                    <span class="pill">Text Session</span>
                    <span class="pill pill-amber">{{ ucfirst($interview->difficulty) }}</span>
                </div>
                <h1 class="section-heading" style="font-size: 1.45rem;">{{ $interview->target_role }}</h1>
            </div>

            <div class="glass-subtle" style="padding: 0.55rem 1.05rem; display: flex; align-items: center; gap: 0.65rem;">
                <span class="pulse-dot"></span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Elapsed</span>
                <strong id="timer-display" class="mono" style="font-variant-numeric: tabular-nums; font-size: 1.05rem; color: #fff;">00:00</strong>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.45rem; font-size: 0.82rem;">
            <span style="color: var(--text-muted);">Answer Completion</span>
            <strong id="progress-label" class="mono" style="color: var(--primary-bright);">0 / {{ $interview->questions->count() }} answered</strong>
        </div>
        <div class="progress-track">
            <div id="progress-bar" class="progress-fill" style="width: 0%;"></div>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.75rem;">
            @foreach ((array) $interview->skills as $skill)
                <span class="pill" style="font-size: 0.72rem; padding: 0.2rem 0.6rem;">{{ $skill }}</span>
            @endforeach
        </div>
    </div>

    <!-- Questions Form -->
    <form id="text-interview-form" action="{{ route('interviews.submit', $interview) }}" method="POST">
        @csrf
        <input type="hidden" name="duration_seconds" id="duration_seconds" value="0">
        <input type="hidden" name="movement_alerts" value="0">

        @foreach ($interview->questions as $idx => $question)
            <div class="glass question-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.45rem;">
                            <span class="pill mono">Question {{ $idx + 1 }} of {{ $interview->questions->count() }}</span>
                            @if ($question->topic)
                                <span class="pill pill-cyan">Topic: {{ $question->topic }}</span>
                            @endif
                        </div>
                        <h3 style="font-family: var(--font-display); font-size: 1.12rem; font-weight: 700; margin-top: 0.35rem; line-height: 1.48; color: #f8fafc;">
                            {{ $question->question_text }}
                        </h3>
                    </div>

                    @if ($roadmap->isFeatureEnabled('voice_dictation'))
                        <button type="button" class="btn-secondary btn-sm mic-btn" data-target="answer-{{ $question->id }}" title="Dictate answer with microphone">
                            🎙️ Speak
                        </button>
                    @else
                        <button type="button" class="btn-locked btn-sm" disabled title="Voice dictation unlocks in Phase 9">
                            🔒 Voice (Phase 9)
                        </button>
                    @endif
                </div>

                <textarea
                    id="answer-{{ $question->id }}"
                    name="answers[{{ $question->id }}]"
                    class="form-textarea answer-field"
                    rows="4"
                    placeholder="Write a detailed response with real examples, architectural trade-offs, and technical reasoning..."
                ></textarea>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.55rem; font-size: 0.8rem; color: var(--text-muted);">
                    <span class="word-count mono" data-for="answer-{{ $question->id }}">0 words</span>
                    <span>Tip: Aim for 30+ words with concrete project examples</span>
                </div>
            </div>
        @endforeach

        <div class="glass" style="padding: 1.25rem; display: flex; gap: 0.9rem; flex-wrap: wrap; margin-top: 1.5rem;">
            <button type="submit" id="submit-btn" class="btn-primary" style="flex: 2; padding: 0.95rem 1.4rem;">
                <span>Submit &amp; Generate AI Report</span>
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
            <button type="button" class="btn-danger" style="flex: 1;" onclick="if(confirm('Finish now and evaluate answered questions?')) submitForm();">
                Quit &amp; Evaluate
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const startTime = Date.now();
    const timerDisplay = document.getElementById('timer-display');
    const durationInput = document.getElementById('duration_seconds');
    const fields = document.querySelectorAll('.answer-field');
    const progressBar = document.getElementById('progress-bar');
    const progressLabel = document.getElementById('progress-label');
    const form = document.getElementById('text-interview-form');

    setInterval(() => {
        const secs = Math.floor((Date.now() - startTime) / 1000);
        durationInput.value = secs;
        const m = String(Math.floor(secs / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        timerDisplay.textContent = `${m}:${s}`;
    }, 1000);

    function updateProgress() {
        let filled = 0;
        fields.forEach(f => {
            const words = f.value.trim() ? f.value.trim().split(/\s+/).length : 0;
            const counter = document.querySelector(`.word-count[data-for="${f.id}"]`);
            if (counter) {
                counter.textContent = `${words} words`;
                counter.style.color = words >= 30 ? 'var(--success)' : (words >= 10 ? 'var(--warning)' : 'var(--text-muted)');
            }
            if (words >= 3) filled++;
        });
        const pct = Math.round((filled / fields.length) * 100);
        progressBar.style.width = `${pct}%`;
        progressLabel.textContent = `${filled} / ${fields.length} answered`;
    }

    fields.forEach(f => f.addEventListener('input', updateProgress));

    function submitForm() {
        durationInput.value = Math.floor((Date.now() - startTime) / 1000);
        document.getElementById('submit-btn').disabled = true;
        document.getElementById('submit-btn').textContent = 'Evaluating responses...';
        form.submit();
    }

    form.addEventListener('submit', () => {
        durationInput.value = Math.floor((Date.now() - startTime) / 1000);
        document.getElementById('submit-btn').disabled = true;
        document.getElementById('submit-btn').textContent = 'Evaluating responses...';
    });

    // Optional Browser Speech-to-Text per question (Phase 8)
    const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    document.querySelectorAll('.mic-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!SpeechRec) {
                alert('Speech recognition is not supported in this browser. Try Chrome or Edge.');
                return;
            }
            const textarea = document.getElementById(btn.dataset.target);
            const rec = new SpeechRec();
            rec.lang = 'en-US';
            rec.interimResults = false;
            btn.textContent = '🔴 Listening...';
            btn.disabled = true;

            rec.onresult = (e) => {
                const transcript = e.results[0][0].transcript;
                textarea.value = (textarea.value ? textarea.value + ' ' : '') + transcript;
                updateProgress();
            };
            rec.onerror = () => {};
            rec.onend = () => {
                btn.textContent = '🎙️ Speak';
                btn.disabled = false;
            };
            rec.start();
        });
    });
</script>
@endpush
@endsection
