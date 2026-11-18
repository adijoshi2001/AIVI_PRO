@extends('layouts.app')

@section('title', 'Live Video Interview — AIVI PRO')

@section('content')
<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                <span class="pill pill-purple">🎥 Live Video & Voice Session</span>
                <span class="pill pill-amber">{{ ucfirst($interview->difficulty) }}</span>
            </div>
            <h1 style="font-size: 1.85rem; font-weight: 800;">{{ $interview->target_role }}</h1>
        </div>

        <div style="display: flex; gap: 0.75rem; align-items: center;">
            <div class="glass" style="padding: 0.6rem 1.1rem; font-size: 0.88rem;">
                Alerts: <strong id="alert-count" style="color: var(--success);">0</strong>
            </div>
            <div class="glass" style="padding: 0.6rem 1.1rem; font-size: 0.88rem; font-variant-numeric: tabular-nums;">
                ⏱️ <strong id="live-timer">00:00</strong>
            </div>
        </div>
    </div>

    <div class="live-grid">
        <!-- Left: Webcam & Proctoring Stage -->
        <div class="glass" style="padding: 1.5rem;">
            <div class="video-stage">
                <video id="webcam" autoplay playsinline muted></video>
                <canvas id="motion-canvas" width="160" height="100" style="display: none;"></canvas>

                <div class="live-overlay-top">
                    <div class="live-pill">
                        <span class="pulse-dot" id="cam-dot"></span>
                        <span id="cam-status">PROCTORING READY</span>
                    </div>
                    <div class="live-pill" id="motion-badge" style="display: none; border-color: rgba(248, 113, 113, 0.6); color: #fecaca;">
                        ⚠️ Movement Detected
                    </div>
                </div>

                <div id="camera-fallback" style="display: none; position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; padding: 2rem; text-align: center; background: rgba(2,6,23,0.92);">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📷</div>
                    <strong>Webcam Unavailable</strong>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 0.35rem; max-width: 320px;">
                        Camera permission was blocked or no webcam was found. You can still continue the voice/text interview smoothly.
                    </p>
                </div>
            </div>

            <div style="margin-top: 1.25rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.84rem; color: var(--text-muted); margin-bottom: 0.45rem;">
                    <span>Session Progress</span>
                    <span id="step-indicator">Question 0 of {{ $interview->questions->count() }}</span>
                </div>
                <div class="progress-track">
                    <div id="live-progress" class="progress-fill" style="width: 0%;"></div>
                </div>
            </div>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.35rem;">
                <button type="button" id="start-session-btn" class="btn-primary" style="flex: 1;">
                    ▶ Start Live Interview
                </button>
                <button type="button" id="repeat-btn" class="btn-secondary" style="display: none;">
                    🔊 Repeat Question
                </button>
                <button type="button" id="listen-btn" class="btn-secondary" style="display: none;">
                    🎙️ Record Voice Answer
                </button>
                <button type="button" id="next-q-btn" class="btn-primary" style="display: none; flex: 1;">
                    Next Question →
                </button>
                <button type="button" id="quit-live-btn" class="btn-danger" style="display: none;">
                    Finish Now
                </button>
            </div>
        </div>

        <!-- Right: AI Interviewer Console -->
        <div class="glass" style="padding: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
                <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--primary-light);">Interviewer Console</h2>
                <span class="pill" id="ai-state-pill">Idle</span>
            </div>

            <div style="padding: 1.1rem; border-radius: 0.9rem; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(129, 140, 248, 0.25); margin-bottom: 1.25rem; min-height: 110px;">
                <div style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.06em; color: #a5b4fc; margin-bottom: 0.4rem;">
                    Current Question
                </div>
                <p id="current-q-text" style="font-size: 1.05rem; font-weight: 700; line-height: 1.45;">
                    Click "Start Live Interview" to enable webcam proctoring and hear Question 1.
                </p>
            </div>

            <div class="form-group" style="margin-bottom: 0.75rem;">
                <label class="form-label" for="live-answer-box">Your Answer (Live STT + Editable)</label>
                <textarea
                    id="live-answer-box"
                    class="form-textarea"
                    rows="6"
                    placeholder="Speak into your microphone or type/refine your answer here..."
                    disabled
                ></textarea>
            </div>

            <div style="font-size: 0.8rem; color: var(--text-muted);">
                Detected Skills: {{ implode(', ', (array) $interview->skills) }}
            </div>
        </div>
    </div>

    <form id="live-submit-form" action="{{ route('interviews.submit', $interview) }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="movement_alerts" id="hidden-movement-alerts" value="0">
        <input type="hidden" name="duration_seconds" id="hidden-duration" value="0">
    </form>
</div>

@push('scripts')
<script>
    const questions = @json($interview->questions->map(fn ($q) => ['id' => $q->id, 'text' => $q->question_text])->values());
    const answersMap = {};
    let currentIdx = -1;
    let movementAlerts = 0;
    let lastAlertAt = 0;
    let sessionStartedAt = null;
    let timerHandle = null;
    let proctorHandle = null;
    let prevFrameData = null;
    let activeRecognition = null;

    const video = document.getElementById('webcam');
    const canvas = document.getElementById('motion-canvas');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const camFallback = document.getElementById('camera-fallback');
    const camStatus = document.getElementById('cam-status');
    const motionBadge = document.getElementById('motion-badge');
    const alertCountEl = document.getElementById('alert-count');
    const timerEl = document.getElementById('live-timer');

    const startBtn = document.getElementById('start-session-btn');
    const repeatBtn = document.getElementById('repeat-btn');
    const listenBtn = document.getElementById('listen-btn');
    const nextBtn = document.getElementById('next-q-btn');
    const quitBtn = document.getElementById('quit-live-btn');

    const qTextEl = document.getElementById('current-q-text');
    const answerBox = document.getElementById('live-answer-box');
    const aiStatePill = document.getElementById('ai-state-pill');
    const stepIndicator = document.getElementById('step-indicator');
    const progressBar = document.getElementById('live-progress');

    async function initWebcamAndProctoring() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            video.srcObject = stream;
            camStatus.textContent = 'LIVE PROCTORING ACTIVE';

            proctorHandle = setInterval(() => {
                if (video.readyState < 2) return;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const frame = ctx.getImageData(0, 0, canvas.width, canvas.height).data;

                if (prevFrameData) {
                    let changedPixels = 0;
                    for (let i = 0; i < frame.length; i += 16) {
                        const diff = Math.abs(frame[i] - prevFrameData[i])
                                   + Math.abs(frame[i + 1] - prevFrameData[i + 1])
                                   + Math.abs(frame[i + 2] - prevFrameData[i + 2]);
                        if (diff > 95) changedPixels++;
                    }
                    const ratio = changedPixels / (frame.length / 16);
                    if (ratio > 0.14) {
                        motionBadge.style.display = 'inline-flex';
                        const now = Date.now();
                        if (now - lastAlertAt > 3500) {
                            movementAlerts++;
                            lastAlertAt = now;
                            alertCountEl.textContent = movementAlerts;
                            alertCountEl.style.color = movementAlerts > 4 ? '#f87171' : '#fbbf24';
                        }
                    } else {
                        motionBadge.style.display = 'none';
                    }
                }
                prevFrameData = new Uint8ClampedArray(frame);
            }, 600);
        } catch (err) {
            camFallback.style.display = 'flex';
            camStatus.textContent = 'AUDIO / TEXT MODE';
        }
    }

    function speakText(text, onDone) {
        if (!('speechSynthesis' in window)) {
            if (onDone) onDone();
            return;
        }
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.rate = 0.98;
        utterance.pitch = 1.0;
        aiStatePill.textContent = '🔊 AI Speaking';
        utterance.onend = () => {
            aiStatePill.textContent = '🎙️ Your Turn';
            if (onDone) onDone();
        };
        utterance.onerror = () => {
            aiStatePill.textContent = '🎙️ Your Turn';
            if (onDone) onDone();
        };
        window.speechSynthesis.speak(utterance);
    }

    function startListening() {
        const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRec) {
            aiStatePill.textContent = '⌨️ Type Your Answer';
            return;
        }
        if (activeRecognition) {
            try { activeRecognition.stop(); } catch (_) {}
        }

        const rec = new SpeechRec();
        activeRecognition = rec;
        rec.lang = 'en-US';
        rec.continuous = true;
        rec.interimResults = false;

        aiStatePill.textContent = '🔴 Listening...';
        listenBtn.textContent = '⏹ Stop Mic';

        rec.onresult = (event) => {
            let chunk = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) {
                    chunk += event.results[i][0].transcript + ' ';
                }
            }
            if (chunk.trim()) {
                answerBox.value = (answerBox.value ? answerBox.value.trim() + ' ' : '') + chunk.trim();
            }
        };

        rec.onend = () => {
            activeRecognition = null;
            listenBtn.textContent = '🎙️ Record Voice Answer';
            aiStatePill.textContent = 'Ready';
        };

        rec.start();
    }

    function saveCurrentAnswer() {
        if (currentIdx >= 0 && currentIdx < questions.length) {
            answersMap[questions[currentIdx].id] = answerBox.value.trim();
        }
    }

    function showQuestion(index) {
        if (activeRecognition) {
            try { activeRecognition.stop(); } catch (_) {}
        }

        currentIdx = index;
        const q = questions[currentIdx];
        qTextEl.textContent = q.text;
        answerBox.disabled = false;
        answerBox.value = answersMap[q.id] || '';
        stepIndicator.textContent = `Question ${currentIdx + 1} of ${questions.length}`;
        progressBar.style.width = `${Math.round(((currentIdx + 1) / questions.length) * 100)}%`;

        nextBtn.textContent = currentIdx === questions.length - 1
            ? 'Finish & Generate AI Report ✓'
            : 'Next Question →';

        speakText(q.text, () => {
            answerBox.focus();
        });
    }

    function submitLiveInterview() {
        saveCurrentAnswer();
        if (activeRecognition) {
            try { activeRecognition.stop(); } catch (_) {}
        }
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
        if (timerHandle) clearInterval(timerHandle);
        if (proctorHandle) clearInterval(proctorHandle);

        const elapsed = sessionStartedAt ? Math.floor((Date.now() - sessionStartedAt) / 1000) : 0;
        document.getElementById('hidden-movement-alerts').value = movementAlerts;
        document.getElementById('hidden-duration').value = elapsed;

        const form = document.getElementById('live-submit-form');
        questions.forEach(q => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `answers[${q.id}]`;
            input.value = answersMap[q.id] || '';
            form.appendChild(input);
        });

        nextBtn.disabled = true;
        nextBtn.textContent = 'Evaluating session...';
        form.submit();
    }

    startBtn.addEventListener('click', async () => {
        startBtn.style.display = 'none';
        repeatBtn.style.display = 'inline-flex';
        listenBtn.style.display = 'inline-flex';
        nextBtn.style.display = 'inline-flex';
        quitBtn.style.display = 'inline-flex';

        sessionStartedAt = Date.now();
        timerHandle = setInterval(() => {
            const secs = Math.floor((Date.now() - sessionStartedAt) / 1000);
            const m = String(Math.floor(secs / 60)).padStart(2, '0');
            const s = String(secs % 60).padStart(2, '0');
            timerEl.textContent = `${m}:${s}`;
        }, 1000);

        await initWebcamAndProctoring();
        showQuestion(0);
    });

    repeatBtn.addEventListener('click', () => {
        if (currentIdx >= 0) speakText(questions[currentIdx].text);
    });

    listenBtn.addEventListener('click', () => {
        if (activeRecognition) {
            activeRecognition.stop();
        } else {
            startListening();
        }
    });

    nextBtn.addEventListener('click', () => {
        saveCurrentAnswer();
        if (currentIdx + 1 < questions.length) {
            showQuestion(currentIdx + 1);
        } else {
            submitLiveInterview();
        }
    });

    quitBtn.addEventListener('click', () => {
        if (confirm('End the live interview now and generate your AI evaluation report?')) {
            submitLiveInterview();
        }
    });
</script>
@endpush
@endsection
