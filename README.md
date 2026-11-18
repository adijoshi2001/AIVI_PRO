# AIVI PRO — AI Virtual Interviewer Platform (Laravel 13)

> **Project Status:** **60% Complete (6 / 10 Phases Live)** • **4 Upcoming Phases Locked via Feature Flags**
>
> A full-stack **Laravel 13** web application that parses candidate PDF resumes, audits ATS friendliness with actionable fixes, conducts topic-tagged technical & behavioral interviews, identifies weak topics with tailored study plans, generates a personalized **Target Job Achievement Roadmap**, and automatically emails the complete intelligence report to the candidate.

---

## 1. Tech Stack & Architecture

| Layer | Technology / Package | Purpose |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 13 (`^13.17`) on PHP `8.5.12` | MVC architecture, routing, Auth, Eloquent ORM, Mailables |
| **Database** | MySQL (`aivi`) + SQLite (in-memory tests) | Persistent storage for users, sessions, interviews & questions |
| **OAuth 2.0 Auth** | `laravel/socialite` (`^5.33`) | Secure **"Continue with Google"** sign-in + auto account linking |
| **Resume PDF Parsing** | `smalot/pdfparser` (`^2.13`) | Extracts selectable text from uploaded PDF resumes (max 5 MB) |
| **AI & Evaluation Layer** | `App\Services\AiService` | Smart Local Heuristic Engine (active) + Gemini 2.0 / Groq Llama 3.3 (Phase 9) |
| **Phase Feature Control** | `config/roadmap.php` + `RoadmapService` | Controls 10-phase milestone rollout via `AIVI_COMPLETED_PHASES` |
| **Frontend UI** | Blade + Custom Glassmorphic CSS (`public/css/aivi.css`) | Dark studio theme, Plus Jakarta Sans / Inter / JetBrains Mono, SVG gauges |
| **Automated Testing** | PHPUnit 12 (`tests/Feature/AiviInterviewFlowTest.php`) | 7 feature tests / 77 assertions verifying full end-to-end workflows |

---

## 2. 10-Phase Project Roadmap (60% Completed)

| Phase | Module / Deliverable | Feature Key | Status |
| :---: | :--- | :--- | :--- |
| **01** | Core Laravel 13 Architecture, MySQL Schema & Dark Studio UI | `core_setup` | **Completed** |
| **02** | Candidate Authentication (Email/Password + Google OAuth 2.0) | `authentication` | **Completed** |
| **03** | Mission Control Dashboard, KPIs & Interview History | `dashboard` | **Completed** |
| **04** | Smart PDF Resume Parser & 6-Point ATS Analyzer + Fix Checklist | `resume_parser` | **Completed** |
| **05** | Topic-Tagged Text Interview Workspace & Weak Topic Engine | `text_interview` | **Completed** |
| **06** | Intelligence Report, Target Job Roadmap & Auto Email Delivery | `core_report` | **Completed** |
| **07** | Live Video & Voice Proctoring Mode (Webcam, Motion Alerts, TTS/STT) | `live_mode` | **Upcoming (Locked)** |
| **08** | Technical Coding Round & DSA Challenges (Code Editor + Test Cases) | `coding_round` | **Upcoming (Locked)** |
| **09** | Cloud LLM Integration (Gemini 2.0 + Groq Llama 3.3) & Voice Dictation | `cloud_llm`, `voice_dictation` | **Upcoming (Locked)** |
| **10** | Printable PDF Report Export & Final Production Polish | `pdf_export` | **Upcoming (Locked)** |

---

## 3. What Is Currently Built & Working (Phases 1–6)

### A. Candidate Authentication & Security (`AuthController`)
- Register, Login, and Logout with **Bcrypt password hashing**, CSRF protection, and session fixation protection (`session()->regenerate()`).
- **Google OAuth 2.0 ("Continue with Google")** via `laravel/socialite` (`/auth/google/redirect`, `/auth/google/callback`):
  - Automatically creates a verified account (`google_id`, `avatar`, `email_verified_at`) or safely links an existing email account without duplicates.
  - Displays a clean error alert if Google credentials are not yet configured in `.env`.

### B. Mission Control Dashboard (`GET /dashboard`)
- **Project Milestone Banner:** Visual 10-segment progress bar (`60% Complete • 6 / 10 Phases`) linking to `/roadmap`.
- **KPI Cards:** Total Sessions, Average Score, Peak Score, and Top Detected Skills.
- **Interview History:** Displays mode, difficulty, ATS score pill, Email Sent indicator, overall score, and **Resume / View Report / Delete** actions.

### C. Smart PDF Resume Parser & 6-Point ATS Audit (`ResumeParserService`)
- Accepts PDF resume upload (stored privately in `storage/app/private/resumes`) or pasted resume summary.
- Auto-detects **25+ technical skills** (PHP, Laravel, Python, JavaScript, TypeScript, React, SQL, Docker, AWS, ML, REST API, etc.).
- Runs a **6-point ATS Friendliness Analysis (0–100%)**:
  1. Machine-Readable Text Layer (flags scanned/image-only PDFs)
  2. Contact & Profile Metadata (email, phone, GitHub/LinkedIn)
  3. Standard ATS Section Headings (Experience/Projects, Skills, Education)
  4. Technical Keyword Density vs. Target Role
  5. Quantified Impact Metrics (`%`, scale, latency, user counts)
  6. Engineering Action Verbs & Optimal Word Count
- Outputs **ATS Friendly / Needs Optimization verdict**, pass/fail checklist, and **numbered actionable ATS Fixes** (`FIX 1`, `FIX 2`, ...).

### D. Topic-Tagged Interview & Weak Topic Detection (`AiService`)
- Generates **5 tailored questions** tagged with explicit topics (`Laravel`, `PHP`, `SQL`, `REST API`, `System Architecture`, `Debugging & Root Cause`, `Testing & QA`, etc.).
- **Sticky Session HUD:** Live elapsed timer (`00:00`), answer completion progress bar (`X / 5 answered`), detected skill pills, and per-question live word counter (turns green at `30+` words).
- **Weak Topics Identification:** Any topic where answers score `< 65%` is flagged under **Weak Topics & What to Focus On** with priority (`Critical`, `High`, `Moderate`) and a concrete **Focus Study Plan**.

### E. Post-Interview Intelligence Report, Target Job Roadmap & Email Delivery
- **Report Page (`GET /interviews/{interview}/report`):**
  - Sticky quick-jump nav (`Executive Summary`, `Weak Topics`, `ATS Resume Audit`, `Target Job Roadmap`, `Q&A Breakdown`).
  - Circular SVG overall score gauge + verdict badge.
  - **Target Job Achievement Roadmap:** Computes readiness level, estimated timeline, **missing role-critical skills**, and a **4-stage weekly execution roadmap** (Week 1–2 ATS & Weak Topics → Week 3–4 Missing Skills → Week 5–6 Coding Prep & Flagship Project → Week 7–8 Mock Rounds & Applications).
  - Topic-tagged Q&A Breakdown with per-question score bar and feedback.
- **Automated Email Delivery (`InterviewReportMail`):**
  - Automatically dispatches a responsive HTML report email to the user's email address upon interview submission and stores `report_emailed_at`.
  - Includes a **Resend Report to Email** button (`POST /interviews/{interview}/email`).

---

## 4. Database Schema

| Table | Key Columns |
| :--- | :--- |
| `users` | `id`, `name`, `email` (unique), `google_id` (nullable unique), `avatar`, `password`, `email_verified_at` |
| `interviews` | `id`, `user_id` (FK), `mode`, `difficulty`, `target_role`, `resume_filename`, `skills` (JSON), `ats_score`, `ats_analysis` (JSON), `score`, `feedback`, `strengths` (JSON), `improvements` (JSON), `weak_topics` (JSON), `career_roadmap` (JSON), `evaluation_provider`, `movement_alerts`, `duration_seconds`, `status`, `completed_at`, `report_emailed_at` |
| `interview_questions` | `id`, `interview_id` (FK), `order_index`, `topic`, `question_text`, `answer_text`, `question_score`, `question_feedback` |
| `sessions`, `cache`, `jobs` | Standard Laravel 13 database-backed session, cache, and queue tables |

---

## 5. Application Routes (17 Routes)

| Method | URI | Name | Controller / Purpose |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | `home` | Landing page (redirects logged-in users to `/dashboard`) |
| `GET` | `/roadmap` | `roadmap` | Public 10-Phase Project Roadmap & progress tracker |
| `GET/POST` | `/register`, `/login` | `register`, `login` | Email/password candidate registration & login |
| `GET` | `/auth/google/redirect` | `auth.google.redirect` | Redirects user to Google OAuth 2.0 consent screen |
| `GET` | `/auth/google/callback` | `auth.google.callback` | Handles Google OAuth callback & logs in / creates user |
| `POST` | `/logout` | `logout` | Logs out & invalidates session |
| `GET` | `/dashboard` | `dashboard` | Mission Control analytics & history |
| `GET` | `/interviews/new` | `interviews.create` | New interview setup (Mode, Role, Difficulty, Resume) |
| `POST` | `/interviews` | `interviews.store` | Parses resume, runs ATS audit, generates 5 topic-tagged Qs |
| `GET` | `/interviews/{interview}/session` | `interviews.session` | Active interview workspace |
| `POST` | `/interviews/{interview}/submit` | `interviews.submit` | Evaluates answers, builds weak topics + job roadmap, sends email |
| `GET` | `/interviews/{interview}/report` | `interviews.report` | Full Intelligence Report page |
| `POST` | `/interviews/{interview}/email` | `interviews.email` | Resends HTML report email to authenticated user |
| `DELETE` | `/interviews/{interview}` | `interviews.destroy` | Deletes interview & uploaded PDF from private storage |

---

## 6. Setup, Configuration & Next Steps

### Quick Start
```bash
cd laravelproject
php artisan migrate
php artisan serve
```
Open **http://127.0.0.1:8000** (Landing), **http://127.0.0.1:8000/roadmap** (10-Phase Roadmap), or run `php artisan test` to verify all 7 automated feature tests.

### Activate Optional External Integrations in `.env`
1. **Google OAuth Login (Phase 2 — Active):**
   Create a Web OAuth Client in Google Cloud Console with redirect URI `http://127.0.0.1:8000/auth/google/callback`:
   ```env
   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
   ```
2. **Real Inbox Email Delivery (Phase 6 — Active):**
   Replace `MAIL_MAILER=log` with SMTP (e.g., Gmail App Password):
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your_email@gmail.com
   MAIL_PASSWORD=your_16_char_app_password
   MAIL_FROM_ADDRESS=your_email@gmail.com
   ```
3. **Unlock Upcoming Phases (Phases 7–10):**
   - Set `AIVI_COMPLETED_PHASES=7` → unlocks **Live Video & Voice Proctoring** (already built in `interviews/live.blade.php`).
   - Build **Phase 8 (Technical Coding Round & DSA Challenges)** → interactive code editor + target-role coding/SQL problems + automated test runner.
   - Set `AIVI_COMPLETED_PHASES=9` + add `GEMINI_API_KEY` / `GROQ_API_KEY` → unlocks **Cloud LLM Evaluation & Voice Dictation**.
   - Set `AIVI_COMPLETED_PHASES=10` → unlocks **Printable PDF Report Export** (`100% Complete`).
