<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiService
{
    public function __construct(
        private readonly RoadmapService $roadmap = new RoadmapService(),
    ) {}

    /**
     * Generate 5 topic-tagged interview questions using Gemini -> Groq -> Smart Heuristic fallback.
     *
     * @param  array<int, string>  $skills
     * @return array{questions: array<int, array{topic: string, question: string}>, provider: string}
     */
    public function generateQuestions(
        string $resumeText,
        array $skills,
        string $difficulty = 'intermediate',
        ?string $targetRole = null
    ): array {
        $roleLabel = $targetRole ?: 'Software Developer';
        $skillsCsv = implode(', ', $skills);
        $excerpt = mb_substr(trim($resumeText), 0, 3000);

        $systemPrompt = <<<PROMPT
You are AIVI PRO, an expert technical and behavioral interviewer.
Generate exactly 5 crisp, realistic interview questions for a {$difficulty}-level {$roleLabel} candidate.
- Base every question directly on the candidate's resume and skills ({$skillsCsv}).
- Include 4 technical/project questions and 1 behavioral/problem-solving question.
- Format EVERY line strictly as: TopicName | Question text
- Return ONLY the 5 lines with NO numbering, NO bullets, and NO intro or outro text.
PROMPT;

        $userPrompt = "Candidate Skills: {$skillsCsv}\nTarget Role: {$roleLabel}\nDifficulty: {$difficulty}\n\nResume Content:\n{$excerpt}";

        $result = $this->callAi($systemPrompt, $userPrompt);

        if ($result !== null) {
            $lines = preg_split('/\r\n|\r|\n/', $result['text']) ?: [];
            $questions = [];
            foreach ($lines as $line) {
                $clean = trim(preg_replace('/^\s*(?:\d+[\.\)\-:]|\-|\*|\•)\s*/', '', $line) ?? '');
                if (mb_strlen($clean) < 12) {
                    continue;
                }

                if (str_contains($clean, '|')) {
                    [$rawTopic, $rawQuestion] = array_map('trim', explode('|', $clean, 2));
                    if (mb_strlen($rawQuestion) >= 12) {
                        $questions[] = [
                            'topic' => $rawTopic !== '' ? mb_substr($rawTopic, 0, 80) : $this->inferTopicFromQuestion($rawQuestion, $skills),
                            'question' => $rawQuestion,
                        ];

                        continue;
                    }
                }

                $questions[] = [
                    'topic' => $this->inferTopicFromQuestion($clean, $skills),
                    'question' => $clean,
                ];
            }

            if (count($questions) >= 3) {
                return [
                    'questions' => array_slice($questions, 0, 5),
                    'provider' => $result['provider'],
                ];
            }
        }

        return [
            'questions' => $this->generateLocalQuestions($skills, $difficulty, $roleLabel),
            'provider' => 'local-heuristic',
        ];
    }

    /**
     * Evaluate Q&A pairs and generate weak-topic focus plan + target job career roadmap.
     *
     * @param  array<int, array{topic?: string|null, question: string, answer: string}>  $qaPairs
     * @param  array<int, string>  $skills
     * @param  array<string, mixed>|null  $atsAnalysis
     * @return array{
     *     score: int,
     *     feedback: string,
     *     strengths: array<int, string>,
     *     improvements: array<int, string>,
     *     weak_topics: array<int, array{topic: string, avg_score: int, missed_count: int, priority: string, focus_plan: string}>,
     *     career_roadmap: array{
     *         target_role: string,
     *         readiness_level: string,
     *         readiness_summary: string,
     *         estimated_timeline: string,
     *         missing_skills: array<int, string>,
     *         steps: array<int, array{stage: string, timeline: string, title: string, tasks: array<int, string>}>
     *     },
     *     per_question: array<int, array{score: int, feedback: string}>,
     *     provider: string
     * }
     */
    public function evaluateInterview(
        array $qaPairs,
        array $skills,
        string $difficulty = 'intermediate',
        int $movementAlerts = 0,
        ?string $targetRole = null,
        ?array $atsAnalysis = null
    ): array {
        $roleLabel = $targetRole ?: 'Software Developer';
        $transcript = '';
        foreach ($qaPairs as $i => $pair) {
            $num = $i + 1;
            $topic = $pair['topic'] ?? 'General';
            $ans = trim($pair['answer']) !== '' ? trim($pair['answer']) : '(No answer provided)';
            $transcript .= "Q{$num} [{$topic}]: {$pair['question']}\nA{$num}: {$ans}\n\n";
        }

        $skillsCsv = implode(', ', $skills);
        $systemPrompt = <<<PROMPT
You are a strict Lead Engineer evaluating a {$difficulty}-level {$roleLabel} candidate on skills: {$skillsCsv}.
Rules:
- Blank, "I don't know", gibberish, or irrelevant answers score 0-10.
- Vague/generic answers score 20-45.
- Solid answers with clear examples and technical accuracy score 70-100.
- Return STRICT JSON ONLY (no markdown fences, no commentary) matching this exact schema:
{
  "score": <int 0-100>,
  "feedback": "<3-5 sentence comprehensive summary>",
  "strengths": ["<strength 1>", "<strength 2>"],
  "improvements": ["<improvement 1>", "<improvement 2>"],
  "per_question": [
    {"score": <int 0-100>, "feedback": "<1-2 sentence question-specific critique>"}
  ]
}
PROMPT;

        $userPrompt = "Proctoring movement alerts during session: {$movementAlerts}\n\nInterview Transcript:\n{$transcript}";

        $result = $this->callAi($systemPrompt, $userPrompt);

        if ($result !== null) {
            $parsed = $this->extractJson($result['text']);
            if ($parsed !== null && isset($parsed['score'])) {
                $perQuestion = [];
                foreach ($qaPairs as $i => $pair) {
                    $item = $parsed['per_question'][$i] ?? null;
                    $perQuestion[] = [
                        'score' => max(0, min(100, (int) ($item['score'] ?? $parsed['score']))),
                        'feedback' => (string) ($item['feedback'] ?? 'Evaluated as part of overall performance.'),
                    ];
                }

                $overall = max(0, min(100, (int) $parsed['score']));
                $weakTopics = $this->buildWeakTopics($qaPairs, $perQuestion);
                $careerRoadmap = $this->generateCareerRoadmap($roleLabel, $skills, $weakTopics, $atsAnalysis, $overall, $difficulty);

                return [
                    'score' => $overall,
                    'feedback' => (string) ($parsed['feedback'] ?? 'Evaluation completed.'),
                    'strengths' => array_values(array_filter((array) ($parsed['strengths'] ?? []))),
                    'improvements' => array_values(array_filter((array) ($parsed['improvements'] ?? []))),
                    'weak_topics' => $weakTopics,
                    'career_roadmap' => $careerRoadmap,
                    'per_question' => $perQuestion,
                    'provider' => $result['provider'],
                ];
            }
        }

        return $this->evaluateLocally($qaPairs, $skills, $movementAlerts, $roleLabel, $atsAnalysis, $difficulty);
    }

    /**
     * Try Gemini first, then Groq. Returns null if Phase 9 is locked or both unavailable/failed.
     *
     * @return array{text: string, provider: string}|null
     */
    private function callAi(string $systemPrompt, string $userPrompt): ?array
    {
        if (! $this->roadmap->isFeatureEnabled('cloud_llm')) {
            return null;
        }

        $geminiKey = trim((string) config('services.gemini.key'));
        $geminiModel = (string) config('services.gemini.model', 'gemini-2.0-flash');

        if ($geminiKey !== '' && ! str_starts_with($geminiKey, 'your_')) {
            try {
                $response = Http::timeout(25)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}",
                    [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [['text' => $userPrompt]],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.4,
                        ],
                    ]
                );

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return ['text' => trim($text), 'provider' => 'gemini'];
                    }
                }
            } catch (Throwable $e) {
                Log::warning('AIVI Gemini request failed: ' . $e->getMessage());
            }
        }

        $groqKey = trim((string) config('services.groq.key'));
        $groqModel = (string) config('services.groq.model', 'llama-3.3-70b-versatile');

        if ($groqKey !== '' && ! str_starts_with($groqKey, 'your_')) {
            try {
                $response = Http::timeout(25)
                    ->withToken($groqKey)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $groqModel,
                        'temperature' => 0.4,
                        'messages' => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $userPrompt],
                        ],
                    ]);

                if ($response->successful()) {
                    $text = data_get($response->json(), 'choices.0.message.content');
                    if (is_string($text) && trim($text) !== '') {
                        return ['text' => trim($text), 'provider' => 'groq'];
                    }
                }
            } catch (Throwable $e) {
                Log::warning('AIVI Groq request failed: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractJson(string $raw): ?array
    {
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw)) ?? trim($raw);
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $raw, $matches) === 1) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $skills
     */
    private function inferTopicFromQuestion(string $question, array $skills): string
    {
        $lower = mb_strtolower($question);
        foreach ($skills as $skill) {
            if (str_contains($lower, mb_strtolower($skill))) {
                return $skill;
            }
        }

        return match (true) {
            str_contains($lower, 'sql') || str_contains($lower, 'database') || str_contains($lower, 'index') => 'Database & SQL',
            str_contains($lower, 'api') || str_contains($lower, 'http') || str_contains($lower, 'rest') => 'REST API Design',
            str_contains($lower, 'test') || str_contains($lower, 'qa') => 'Testing & QA',
            str_contains($lower, 'bug') || str_contains($lower, 'debug') => 'Debugging & Problem Solving',
            str_contains($lower, 'architecture') || str_contains($lower, 'scale') => 'System Architecture',
            default => 'Core Engineering',
        };
    }

    /**
     * Skill-aware local question bank with explicit topic tags.
     *
     * @param  array<int, string>  $skills
     * @return array<int, array{topic: string, question: string}>
     */
    private function generateLocalQuestions(array $skills, string $difficulty, string $roleLabel): array
    {
        $bank = [
            'PHP' => [
                'beginner' => 'Explain the difference between == and === in PHP, and why strict comparison matters in web applications.',
                'intermediate' => 'How does PHP handle dependency injection, namespaces, and Composer autoloading (PSR-4) in modern projects?',
                'advanced' => 'How would you profile and optimize a high-traffic PHP application experiencing memory leaks and slow database queries?',
            ],
            'Laravel' => [
                'beginner' => 'Explain the MVC request lifecycle in Laravel from routes to controllers, models, and Blade views.',
                'intermediate' => 'What is the N+1 query problem in Laravel Eloquent, and how do you solve it using eager loading?',
                'advanced' => 'How would you design a scalable Laravel architecture using Queues, Redis caching, Events, and Service Providers?',
            ],
            'Python' => [
                'beginner' => 'What are the key differences between Python lists, tuples, sets, and dictionaries, and when do you use each?',
                'intermediate' => 'Explain how Python decorators, generators, and virtual environments help build maintainable applications.',
                'advanced' => 'How does Python’s GIL impact concurrency, and when would you choose asyncio vs multiprocessing?',
            ],
            'JavaScript' => [
                'beginner' => 'Explain the difference between var, let, and const in JavaScript and how closures work.',
                'intermediate' => 'How does the JavaScript event loop handle Promises, async/await, microtasks, and macrotasks?',
                'advanced' => 'How do you prevent memory leaks and optimize rendering performance in large JavaScript web apps?',
            ],
            'TypeScript' => [
                'beginner' => 'What are the main benefits of TypeScript over plain JavaScript, and how do interfaces differ from types?',
                'intermediate' => 'How do TypeScript generics, union types, and strict null checks prevent production bugs?',
                'advanced' => 'How would you structure shared API contract types across a large full-stack TypeScript monorepo?',
            ],
            'React' => [
                'beginner' => 'What is the difference between state and props in React, and how does the Virtual DOM work?',
                'intermediate' => 'Explain how useEffect, useMemo, and useCallback work, and common pitfalls with dependency arrays.',
                'advanced' => 'How would you architect state management and code-splitting for a complex multi-page React application?',
            ],
            'Node.js' => [
                'beginner' => 'How does Node.js handle asynchronous I/O on a single-threaded event loop?',
                'intermediate' => 'Explain middleware chaining, error handling, and JWT authentication best practices in Express/Node.js.',
                'advanced' => 'How do you scale a CPU-intensive Node.js service using Worker Threads, clustering, and message queues?',
            ],
            'SQL' => [
                'beginner' => 'Explain the differences between INNER JOIN, LEFT JOIN, and RIGHT JOIN with a practical example.',
                'intermediate' => 'How do database indexes improve query speed, and what trade-offs should you consider before indexing every column?',
                'advanced' => 'How do you diagnose slow SQL queries using EXPLAIN plans and design normalized vs denormalized schemas?',
            ],
            'Machine Learning' => [
                'beginner' => 'What is the difference between supervised and unsupervised learning? Give two real-world examples.',
                'intermediate' => 'Explain overfitting and underfitting, and describe practical techniques like cross-validation and regularization to fix them.',
                'advanced' => 'How would you monitor data drift and evaluate precision-recall trade-offs for an imbalanced ML model in production?',
            ],
            'Docker' => [
                'beginner' => 'What is the difference between a Docker image and a Docker container, and why do teams use containers?',
                'intermediate' => 'How do multi-stage Dockerfiles reduce image size and improve security in CI/CD pipelines?',
                'advanced' => 'How would you troubleshoot networking, persistent volumes, and container resource limits in a multi-service Docker stack?',
            ],
            'AWS' => [
                'beginner' => 'Explain the differences between EC2, S3, and RDS, and when you would use each in a web application.',
                'intermediate' => 'How do IAM roles, security groups, and VPC subnets work together to secure cloud deployments?',
                'advanced' => 'How would you design a fault-tolerant, auto-scaling architecture on AWS with zero-downtime deployments?',
            ],
            'Git' => [
                'beginner' => 'Explain the difference between git merge and git rebase, and how you resolve merge conflicts.',
                'intermediate' => 'What Git branching strategy do you follow for feature development, hotfixes, and pull request reviews?',
                'advanced' => 'How do you safely recover from a bad production commit or rebase history without disrupting teammates?',
            ],
            'REST API' => [
                'beginner' => 'Explain HTTP methods (GET, POST, PUT, PATCH, DELETE) and common status codes like 200, 201, 400, 401, and 404.',
                'intermediate' => 'How do you design secure REST APIs with authentication, rate limiting, pagination, and consistent error responses?',
                'advanced' => 'How would you handle API versioning, idempotency keys, and backward compatibility in a distributed system?',
            ],
            'Java' => [
                'beginner' => 'Explain OOP pillars (encapsulation, inheritance, polymorphism, abstraction) with a Java example.',
                'intermediate' => 'How does Java garbage collection work, and what is the difference between ArrayList, LinkedList, and HashMap?',
                'advanced' => 'How would you diagnose thread deadlocks and tune JVM performance in a high-throughput Spring Boot service?',
            ],
        ];

        $level = in_array($difficulty, ['beginner', 'intermediate', 'advanced'], true) ? $difficulty : 'intermediate';
        $questions = [];

        foreach ($skills as $skill) {
            if (isset($bank[$skill][$level])) {
                $questions[] = [
                    'topic' => $skill,
                    'question' => $bank[$skill][$level],
                ];
            }
            if (count($questions) >= 4) {
                break;
            }
        }

        $generic = [
            [
                'topic' => 'System Architecture',
                'question' => "Walk me through the most challenging {$roleLabel} project on your resume and the architectural decisions you made.",
            ],
            [
                'topic' => 'Debugging & Root Cause',
                'question' => 'Describe a difficult bug you encountered recently—how did you isolate the root cause and verify the fix?',
            ],
            [
                'topic' => 'Code Quality & Security',
                'question' => 'How do you ensure code quality, security, and maintainability when collaborating with a team under tight deadlines?',
            ],
            [
                'topic' => 'Testing & QA',
                'question' => 'Explain how you test your applications (unit, integration, or end-to-end) before deploying to production.',
            ],
            [
                'topic' => 'Behavioral & Adaptability',
                'question' => 'Tell me about a time you had to learn a new technology quickly to deliver a feature. What was your approach?',
            ],
        ];

        foreach ($generic as $item) {
            if (count($questions) >= 5) {
                break;
            }
            $questions[] = $item;
        }

        return array_slice($questions, 0, 5);
    }

    /**
     * Structured local evaluator when live AI API keys are not active.
     *
     * @param  array<int, array{topic?: string|null, question: string, answer: string}>  $qaPairs
     * @param  array<int, string>  $skills
     * @param  array<string, mixed>|null  $atsAnalysis
     * @return array{
     *     score: int,
     *     feedback: string,
     *     strengths: array<int, string>,
     *     improvements: array<int, string>,
     *     weak_topics: array<int, array{topic: string, avg_score: int, missed_count: int, priority: string, focus_plan: string}>,
     *     career_roadmap: array{
     *         target_role: string,
     *         readiness_level: string,
     *         readiness_summary: string,
     *         estimated_timeline: string,
     *         missing_skills: array<int, string>,
     *         steps: array<int, array{stage: string, timeline: string, title: string, tasks: array<int, string>}>
     *     },
     *     per_question: array<int, array{score: int, feedback: string}>,
     *     provider: string
     * }
     */
    private function evaluateLocally(
        array $qaPairs,
        array $skills,
        int $movementAlerts,
        string $roleLabel,
        ?array $atsAnalysis,
        string $difficulty
    ): array {
        $weakPhrases = ['idk', "i don't know", 'dont know', 'no idea', 'skip', 'none', 'na', 'n/a', 'asdf', 'test'];
        $techKeywords = array_merge(
            array_map('strtolower', $skills),
            ['api', 'database', 'query', 'cache', 'index', 'model', 'controller', 'async', 'test', 'security', 'performance', 'architecture', 'component', 'algorithm', 'example', 'project', 'production', 'scalability', 'validation', 'error', 'eloquent', 'migration', 'docker', 'queue']
        );

        $perQuestion = [];
        $totalScore = 0;
        $answeredCount = 0;
        $strongCount = 0;

        foreach ($qaPairs as $pair) {
            $answer = trim($pair['answer']);
            $lower = mb_strtolower($answer);
            $words = $answer !== '' ? preg_split('/\s+/', $answer) : [];
            $wordCount = is_array($words) ? count(array_filter($words)) : 0;

            if ($answer === '' || in_array($lower, $weakPhrases, true) || $wordCount < 3) {
                $qScore = 0;
                $qFeedback = 'No substantial answer provided. Revise the core concept of ' . ($pair['topic'] ?? 'this topic') . ' and prepare a real project example.';
            } else {
                $answeredCount++;
                $depthScore = min(55, (int) round($wordCount * 1.4));

                $matched = 0;
                foreach (array_unique($techKeywords) as $kw) {
                    if ($kw !== '' && str_contains($lower, $kw)) {
                        $matched++;
                    }
                }
                $techScore = min(35, $matched * 8);

                $structureScore = 0;
                if (preg_match('/\b(because|for example|such as|therefore|first|however|using|solved)\b/i', $answer) === 1) {
                    $structureScore += 10;
                }

                $qScore = max(15, min(95, $depthScore + $techScore + $structureScore));
                if ($qScore >= 70) {
                    $strongCount++;
                    $qFeedback = 'Good depth and clear technical terminology. Including measurable outcomes would make it even stronger.';
                } elseif ($qScore >= 45) {
                    $qFeedback = 'Decent foundation, but expand with concrete implementation details, trade-offs, and edge cases.';
                } else {
                    $qFeedback = 'Too brief or unclear. Study ' . ($pair['topic'] ?? 'this topic') . ' fundamentals and walk through a step-by-step example.';
                }
            }

            $totalScore += $qScore;
            $perQuestion[] = [
                'score' => $qScore,
                'feedback' => $qFeedback,
            ];
        }

        $count = max(1, count($qaPairs));
        $overall = (int) round($totalScore / $count);
        $weakTopics = $this->buildWeakTopics($qaPairs, $perQuestion);
        $careerRoadmap = $this->generateCareerRoadmap($roleLabel, $skills, $weakTopics, $atsAnalysis, $overall, $difficulty);

        $strengths = [];
        if ($answeredCount === count($qaPairs)) {
            $strengths[] = 'Attempted all interview questions without skipping.';
        }
        if ($strongCount > 0) {
            $strengths[] = "Demonstrated solid technical clarity on {$strongCount} of {$count} question(s).";
        }
        if ($atsAnalysis !== null && ($atsAnalysis['is_ats_friendly'] ?? false) === true) {
            $strengths[] = 'Resume passed ATS readiness checks (' . ($atsAnalysis['score'] ?? 0) . '% ATS score).';
        }
        if ($movementAlerts <= 3) {
            $strengths[] = 'Maintained steady focus and minimal proctoring alerts during the session.';
        }
        if ($strengths === []) {
            $strengths[] = 'Completed the interview workflow and established a baseline score to build upon.';
        }

        $improvements = [];
        if ($answeredCount < count($qaPairs)) {
            $improvements[] = 'Avoid leaving questions blank—walk through your thought process even if unsure.';
        }
        if ($weakTopics !== []) {
            $topicNames = implode(', ', array_map(fn (array $t): string => $t['topic'], array_slice($weakTopics, 0, 3)));
            $improvements[] = "Prioritize revising your weak interview topics: {$topicNames}.";
        }
        if ($overall < 75) {
            $improvements[] = 'Structure answers using STAR (Situation, Task, Action, Result) and cite concrete technical trade-offs.';
        }
        if ($movementAlerts > 5) {
            $improvements[] = "High proctoring movement alerts ({$movementAlerts}) detected—maintain steady camera framing in live interviews.";
        }
        if ($this->roadmap->isFeatureEnabled('cloud_llm')) {
            $improvements[] = 'Add your GEMINI_API_KEY or GROQ_API_KEY in .env to unlock deep LLM-powered technical critique.';
        } else {
            $improvements[] = 'Deep LLM-powered critique via Gemini & Groq APIs is scheduled for Phase 9 (currently running Phase 5 Local Heuristic Mode).';
        }

        $summary = match (true) {
            $overall >= 80 => "Strong performance ({$overall}%) for {$roleLabel}. Your answers show solid technical depth and structured communication across the assessed topics.",
            $overall >= 55 => "Moderate performance ({$overall}%) for {$roleLabel}. You demonstrated foundational knowledge, but several topics need deeper architectural detail and concrete examples.",
            default => "Needs improvement ({$overall}%) for {$roleLabel}. Focus on the highlighted weak topics below, strengthen your resume ATS score, and back answers with hands-on project examples.",
        };

        return [
            'score' => $overall,
            'feedback' => $summary,
            'strengths' => $strengths,
            'improvements' => $improvements,
            'weak_topics' => $weakTopics,
            'career_roadmap' => $careerRoadmap,
            'per_question' => $perQuestion,
            'provider' => 'local-heuristic',
        ];
    }

    /**
     * Identify topics where the candidate struggled (< 65%) and build targeted study focus plans.
     *
     * @param  array<int, array{topic?: string|null, question: string, answer: string}>  $qaPairs
     * @param  array<int, array{score: int, feedback: string}>  $perQuestion
     * @return array<int, array{topic: string, avg_score: int, missed_count: int, priority: string, focus_plan: string}>
     */
    private function buildWeakTopics(array $qaPairs, array $perQuestion): array
    {
        $grouped = [];

        foreach ($qaPairs as $idx => $pair) {
            $qScore = (int) ($perQuestion[$idx]['score'] ?? 0);
            if ($qScore >= 65) {
                continue;
            }

            $topic = trim((string) ($pair['topic'] ?? '')) ?: 'Core Engineering';
            if (! isset($grouped[$topic])) {
                $grouped[$topic] = ['scores' => [], 'count' => 0];
            }
            $grouped[$topic]['scores'][] = $qScore;
            $grouped[$topic]['count']++;
        }

        $weakTopics = [];
        foreach ($grouped as $topic => $data) {
            $avg = (int) round(array_sum($data['scores']) / max(1, count($data['scores'])));
            $priority = match (true) {
                $avg < 35 => 'Critical',
                $avg < 55 => 'High',
                default => 'Moderate',
            };

            $weakTopics[] = [
                'topic' => $topic,
                'avg_score' => $avg,
                'missed_count' => $data['count'],
                'priority' => $priority,
                'focus_plan' => $this->focusAdviceForTopic($topic),
            ];
        }

        usort($weakTopics, fn (array $a, array $b): int => $a['avg_score'] <=> $b['avg_score']);

        return $weakTopics;
    }

    private function focusAdviceForTopic(string $topic): string
    {
        $guides = [
            'Laravel' => 'Revisit Eloquent relationships, N+1 eager loading, middleware lifecycle, queues, and service container binding by building a mini CRUD + API module.',
            'PHP' => 'Practice strict typing, OOP interfaces/traits, PSR-4 autoloading, exception handling, and memory/query profiling in PHP 8+.',
            'SQL' => 'Practice complex JOINs, GROUP BY aggregations, composite indexing trade-offs, transactions, and reading EXPLAIN query plans.',
            'Database & SQL' => 'Practice SQL JOINs, indexing strategies, normalization vs denormalization, and analyzing slow queries with EXPLAIN.',
            'JavaScript' => 'Master closures, prototypes, the Event Loop (microtasks vs macrotasks), Promises/async-await, and error handling.',
            'TypeScript' => 'Practice generics, discriminated unions, utility types, and strict type-safe API schemas.',
            'React' => 'Review component lifecycle, hooks (useEffect/useMemo/useCallback), state architecture, and preventing unnecessary re-renders.',
            'Node.js' => 'Practice async event loop patterns, Express middleware design, JWT auth, streams, and graceful error handling.',
            'Python' => 'Revise data structures complexity, decorators, generators, virtual environments, and asyncio vs multiprocessing.',
            'Machine Learning' => 'Review bias-variance trade-off, cross-validation, precision/recall/F1 metrics, regularization, and feature engineering.',
            'Docker' => 'Practice writing multi-stage Dockerfiles, docker-compose networking, volume persistence, and container debugging.',
            'AWS' => 'Revise EC2, S3, RDS, IAM least-privilege policies, VPC security groups, and CI/CD deployment flows.',
            'REST API' => 'Master HTTP semantics, status codes, pagination, rate limiting, idempotency, and clean validation error schemas.',
            'Git' => 'Practice branching workflows, interactive rebase, cherry-pick, bisect, and clean pull request conflict resolution.',
            'System Architecture' => 'Prepare a crisp 2-minute breakdown of your flagship project covering architecture diagram, database design, bottlenecks, and trade-offs.',
            'Debugging & Root Cause' => 'Practice explaining bugs systematically: symptom → logs/reproduction → root cause → fix → regression test added.',
            'Code Quality & Security' => 'Study OWASP Top 10 (SQLi, XSS, CSRF), code review checklists, SOLID principles, and static analysis tools.',
            'Testing & QA' => 'Write PHPUnit/Jest feature & unit tests covering happy paths, edge cases, authentication boundaries, and database assertions.',
            'Behavioral & Adaptability' => 'Prepare 3 concise STAR stories (Situation, Task, Action, Result) highlighting ownership, fast learning, and measurable impact.',
        ];

        return $guides[$topic]
            ?? "Dedicate focused study sessions to {$topic} fundamentals, build a small hands-on demo, and practice explaining real-world trade-offs aloud.";
    }

    /**
     * Build a personalized post-interview career roadmap to achieve the target job role.
     *
     * @param  array<int, string>  $skills
     * @param  array<int, array{topic: string, avg_score: int, missed_count: int, priority: string, focus_plan: string}>  $weakTopics
     * @param  array<string, mixed>|null  $atsAnalysis
     * @return array{
     *     target_role: string,
     *     readiness_level: string,
     *     readiness_summary: string,
     *     estimated_timeline: string,
     *     missing_skills: array<int, string>,
     *     steps: array<int, array{stage: string, timeline: string, title: string, tasks: array<int, string>}>
     * }
     */
    private function generateCareerRoadmap(
        string $targetRole,
        array $skills,
        array $weakTopics,
        ?array $atsAnalysis,
        int $overallScore,
        string $difficulty
    ): array {
        $roleLower = mb_strtolower($targetRole);
        $expectedSkills = match (true) {
            str_contains($roleLower, 'laravel') || str_contains($roleLower, 'php') => ['PHP', 'Laravel', 'SQL', 'REST API', 'Git', 'Docker', 'Testing & CI/CD', 'Redis / Queues'],
            str_contains($roleLower, 'frontend') || str_contains($roleLower, 'react') || str_contains($roleLower, 'ui') => ['JavaScript', 'TypeScript', 'React', 'HTML & CSS', 'REST API', 'Git', 'Testing & Performance'],
            str_contains($roleLower, 'data') || str_contains($roleLower, 'ml') || str_contains($roleLower, 'ai') => ['Python', 'SQL', 'Machine Learning', 'Data Science', 'Deep Learning', 'Docker', 'Git'],
            str_contains($roleLower, 'node') || str_contains($roleLower, 'mern') || str_contains($roleLower, 'full') => ['JavaScript', 'TypeScript', 'React', 'Node.js', 'SQL', 'REST API', 'Docker', 'Git'],
            str_contains($roleLower, 'devops') || str_contains($roleLower, 'cloud') => ['Linux', 'Docker', 'Kubernetes', 'AWS', 'Git', 'CI/CD Pipelines', 'Monitoring'],
            default => ['SQL', 'REST API', 'Git', 'Docker', 'System Design', 'Automated Testing', 'DSA & Coding'],
        };

        $normalizedHave = array_map('strtolower', $skills);
        $missingSkills = [];
        foreach ($expectedSkills as $req) {
            $reqLower = mb_strtolower($req);
            $hasIt = false;
            foreach ($normalizedHave as $have) {
                if ($have === $reqLower || str_contains($reqLower, $have) || str_contains($have, $reqLower)) {
                    $hasIt = true;
                    break;
                }
            }
            if (! $hasIt) {
                $missingSkills[] = $req;
            }
        }
        $missingSkills = array_slice($missingSkills, 0, 5);

        $atsScore = (int) ($atsAnalysis['score'] ?? 60);
        $readinessLevel = match (true) {
            $overallScore >= 75 && $atsScore >= 70 && count($weakTopics) <= 1 => 'Interview Ready — Target Applications Now',
            $overallScore >= 50 => 'Almost Ready — Close Focused Gaps',
            default => 'Foundation Building Phase',
        };

        $estimatedTimeline = match (true) {
            $overallScore >= 75 && $atsScore >= 70 => '2–3 Weeks to Offer Readiness',
            $overallScore >= 50 => '4–6 Weeks Structured Prep',
            default => '6–8 Weeks Intensive Roadmap',
        };

        $readinessSummary = sprintf(
            'Based on your %d%% interview score, %d%% ATS resume health, and %d weak topic(s), follow this step-by-step plan to land a %s (%s) role.',
            $overallScore,
            $atsScore,
            count($weakTopics),
            $targetRole,
            ucfirst($difficulty)
        );

        $weakTopicNames = array_map(fn (array $w): string => $w['topic'], array_slice($weakTopics, 0, 3));
        $stage1Tasks = [];
        if ($atsScore < 75) {
            $stage1Tasks[] = "Fix your resume's ATS issues first (currently {$atsScore}%) using the ATS Fix Checklist above so recruiters shortlist your profile.";
        } else {
            $stage1Tasks[] = "Maintain your strong {$atsScore}% ATS resume score and tailor keywords for each {$targetRole} job description.";
        }
        if ($weakTopicNames !== []) {
            $stage1Tasks[] = 'Deep-dive into your missed interview topics: ' . implode(', ', $weakTopicNames) . '.';
        } else {
            $stage1Tasks[] = 'Refine your existing answers with sharper architecture trade-offs and measurable production metrics.';
        }
        $stage1Tasks[] = 'Write crisp 1-minute STAR explanations for your top 2 resume projects.';

        $stage2Tasks = $missingSkills !== []
            ? [
                'Learn and add missing role-critical skills to your stack: ' . implode(', ', $missingSkills) . '.',
                "Build small hands-on mini-labs demonstrating {$missingSkills[0]} in a realistic {$targetRole} scenario.",
                'Document your learnings and commit clean code to GitHub with clear READMEs.',
            ]
            : [
                "Deepen production mastery of your core stack (" . implode(', ', array_slice($skills, 0, 4)) . ').',
                'Study caching, security hardening, logging, and automated testing patterns for ' . $targetRole . '.',
                'Review real-world system design case studies matching your target seniority.',
            ];

        $stage3Tasks = [
            "Ship 1 flagship deployed {$targetRole} portfolio project with live URL, clean Git history, and automated tests.",
            'Practice 20–30 role-specific coding & debugging challenges (DSA, SQL queries, API debugging — upcoming interactive Technical Coding Round in Phase 8!).',
            'Perform a self-code-review focusing on edge cases, time/space complexity, and clean architecture.',
        ];

        $stage4Tasks = [
            'Retake 2–3 timed mock interviews on AIVI PRO targeting 80%+ across all topics.',
            "Apply strategically to 15–20 {$targetRole} openings per week with your ATS-optimized resume + GitHub portfolio.",
            'Reach out to hiring managers/engineers on LinkedIn with a concise note linking your deployed project.',
        ];

        return [
            'target_role' => $targetRole,
            'readiness_level' => $readinessLevel,
            'readiness_summary' => $readinessSummary,
            'estimated_timeline' => $estimatedTimeline,
            'missing_skills' => $missingSkills,
            'steps' => [
                [
                    'stage' => 'Stage 01',
                    'timeline' => 'Week 1–2',
                    'title' => 'Fix Resume ATS & Close Weak Interview Topics',
                    'tasks' => $stage1Tasks,
                ],
                [
                    'stage' => 'Stage 02',
                    'timeline' => 'Week 3–4',
                    'title' => 'Master Missing Core Skills for ' . $targetRole,
                    'tasks' => $stage2Tasks,
                ],
                [
                    'stage' => 'Stage 03',
                    'timeline' => 'Week 5–6',
                    'title' => 'Technical Coding Prep & Flagship Project Proof',
                    'tasks' => $stage3Tasks,
                ],
                [
                    'stage' => 'Stage 04',
                    'timeline' => 'Week 7–8',
                    'title' => 'Mock Rounds, Networking & Job Applications',
                    'tasks' => $stage4Tasks,
                ],
            ],
        ];
    }
}
