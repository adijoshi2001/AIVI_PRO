<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Project Completion Phase
    |--------------------------------------------------------------------------
    |
    | Controls how many of the 10 project phases are currently unlocked.
    | Set AIVI_COMPLETED_PHASES=6 for 60% completion (Phases 1-6 live, 7-10 locked).
    | Set AIVI_COMPLETED_PHASES=10 anytime to unlock 100% of all features.
    |
    */

    'completed_phases' => (int) env('AIVI_COMPLETED_PHASES', 6),

    'phases' => [
        [
            'number' => 1,
            'title' => 'Core Architecture, Database & UI Theme',
            'summary' => 'Laravel 13 foundation, MySQL database migrations, and dark glassmorphic studio UI.',
            'feature_key' => 'core_setup',
            'deliverables' => [
                'Laravel 13 project structure & routing',
                'MySQL schema for users, sessions, interviews & questions',
                'Modern dark studio glassmorphism UI & typography',
            ],
        ],
        [
            'number' => 2,
            'title' => 'Candidate Authentication & Google OAuth',
            'summary' => 'Complete registration, login, Google OAuth 2.0 sign-in, and session-protected workspace.',
            'feature_key' => 'authentication',
            'deliverables' => [
                'Candidate registration & email/password login flows',
                'Google OAuth 2.0 (Continue with Google) login',
                'Bcrypt password hashing, CSRF & session security',
            ],
        ],
        [
            'number' => 3,
            'title' => 'Mission Control Dashboard & History',
            'summary' => 'Personalized dashboard displaying interview metrics, ATS scores, skills, and session history.',
            'feature_key' => 'dashboard',
            'deliverables' => [
                'Total sessions, average score & peak score KPIs',
                'Dynamic detected skills & ATS status overview',
                'Interview history list with resume & delete controls',
            ],
        ],
        [
            'number' => 4,
            'title' => 'Smart PDF Resume Parser & ATS Analyzer',
            'summary' => 'Automated resume text extraction, skill detection, ATS compatibility scoring, and fix checklist.',
            'feature_key' => 'resume_parser',
            'deliverables' => [
                'PDF upload validation & text extraction (smalot/pdfparser)',
                'ATS friendliness audit (0–100 score + section & keyword checks)',
                'Actionable ATS resume fix suggestions per target role',
            ],
        ],
        [
            'number' => 5,
            'title' => 'Topic-Tagged Text Interview & Weak Topic Engine',
            'summary' => 'Structured 5-question interview tagged by topic with heuristic grading and weak-topic detection.',
            'feature_key' => 'text_interview',
            'deliverables' => [
                'Role & difficulty tailored topic-tagged question generator',
                'Sticky session HUD with timer, progress bar & word count',
                'Weak-topic identification with targeted focus recommendations',
            ],
        ],
        [
            'number' => 6,
            'title' => 'Intelligence Report, Job Roadmap & Email Delivery',
            'summary' => 'Complete post-interview report with Target Job Achievement Roadmap and automated email dispatch.',
            'feature_key' => 'core_report',
            'deliverables' => [
                'Circular score gauge, ATS audit & weak-topic focus matrix',
                'Personalized Target Job Achievement Roadmap & skill-gap plan',
                'Automated HTML interview report delivery to candidate email',
            ],
        ],
        [
            'number' => 7,
            'title' => 'Live Video & Voice Proctoring Mode',
            'summary' => 'Real-time webcam interview room with motion proctoring, AI TTS questions, and live STT.',
            'feature_key' => 'live_mode',
            'deliverables' => [
                'Live webcam stream & client-side movement alerts',
                'AI spoken questions via Web Speech Synthesis (TTS)',
                'Real-time voice answer capture in live room',
            ],
        ],
        [
            'number' => 8,
            'title' => 'Technical Coding Round & DSA Challenges',
            'summary' => 'Hands-on coding challenges tailored to target job role with in-browser code editor & test cases.',
            'feature_key' => 'coding_round',
            'deliverables' => [
                'Target-role coding problems (DSA, SQL queries & debugging)',
                'In-browser code editor with syntax highlighting & timer',
                'Automated test-case evaluation & time/space complexity review',
            ],
        ],
        [
            'number' => 9,
            'title' => 'Cloud LLM Integration (Gemini & Groq) & Voice STT',
            'summary' => 'External AI provider integration for deep contextual questioning, JSON grading, and voice dictation.',
            'feature_key' => 'cloud_llm',
            'feature_keys' => ['cloud_llm', 'voice_dictation'],
            'deliverables' => [
                'Google Gemini 2.0 API question & evaluation pipeline',
                'Groq Llama 3.3 automatic failover support',
                'Per-question microphone voice dictation (STT) in text mode',
            ],
        ],
        [
            'number' => 10,
            'title' => 'Printable PDF Report Export & Production Polish',
            'summary' => 'One-click print/PDF export layouts and final production deployment readiness.',
            'feature_key' => 'pdf_export',
            'deliverables' => [
                'Dedicated print stylesheet & Save-to-PDF export action',
                'End-to-end automated regression test coverage',
                'Production caching & deployment hardening',
            ],
        ],
    ],

];
