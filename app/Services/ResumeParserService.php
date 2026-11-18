<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Throwable;

class ResumeParserService
{
    /**
     * Curated map of display skill names to regex patterns.
     */
    private const SKILL_PATTERNS = [
        'PHP' => '/\bphp\b/i',
        'Laravel' => '/\blaravel\b/i',
        'Python' => '/\bpython\b/i',
        'Java' => '/\bjava\b(?!script)/i',
        'JavaScript' => '/\b(?:javascript|js)\b/i',
        'TypeScript' => '/\b(?:typescript|ts)\b/i',
        'React' => '/\breact(?:\.js|js)?\b/i',
        'Vue.js' => '/\bvue(?:\.js|js)?\b/i',
        'Node.js' => '/\bnode(?:\.js|js)?\b/i',
        'HTML & CSS' => '/\b(?:html5?|css3?|tailwind|bootstrap)\b/i',
        'SQL' => '/\b(?:sql|mysql|postgresql|postgres|sqlite)\b/i',
        'MongoDB' => '/\bmongodb\b/i',
        'Machine Learning' => '/\b(?:machine\s+learning|scikit-learn|sklearn|ml)\b/i',
        'Deep Learning' => '/\b(?:deep\s+learning|tensorflow|pytorch|keras)\b/i',
        'Data Science' => '/\b(?:data\s+science|pandas|numpy|data\s+analysis)\b/i',
        'NLP' => '/\b(?:nlp|natural\s+language\s+processing|llm|generative\s+ai)\b/i',
        'Flask' => '/\bflask\b/i',
        'Django' => '/\bdjango\b/i',
        'C++' => '/(?:\bc\+\+\b|\bcpp\b)/i',
        'C#' => '/(?:\bc#|\bcsharp\b|\.net\b)/i',
        'Docker' => '/\bdocker\b/i',
        'Kubernetes' => '/\b(?:kubernetes|k8s)\b/i',
        'AWS' => '/\b(?:aws|amazon\s+web\s+services)\b/i',
        'Git' => '/\b(?:git|github|gitlab)\b/i',
        'REST API' => '/\b(?:rest\s*api|restful|graphql|microservices)\b/i',
        'Linux' => '/\blinux\b/i',
    ];

    public function extractTextFromPdf(string $absolutePath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();

            return trim(preg_replace('/\s+/', ' ', $text) ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * @return array<int, string>
     */
    public function extractSkills(string $text): array
    {
        $found = [];

        foreach (self::SKILL_PATTERNS as $skill => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $found[] = $skill;
            }
        }

        return $found !== [] ? array_slice($found, 0, 10) : ['Software Engineering', 'Problem Solving'];
    }

    /**
     * Analyze resume text for ATS compatibility and generate concrete fixes.
     *
     * @param  array<int, string>  $skills
     * @return array{
     *     score: int,
     *     is_ats_friendly: bool,
     *     verdict: string,
     *     word_count: int,
     *     checks: array<int, array{label: string, passed: bool, detail: string}>,
     *     fixes: array<int, string>
     * }
     */
    public function analyzeAts(
        string $resumeText,
        array $skills,
        ?string $targetRole = null,
        bool $pdfUploaded = false
    ): array {
        $cleanText = trim($resumeText);
        $words = $cleanText !== '' ? preg_split('/\s+/', $cleanText) : [];
        $wordCount = is_array($words) ? count(array_filter($words)) : 0;

        $score = 0;
        $checks = [];
        $fixes = [];

        // 1. Machine-Readable Text Extraction (20 pts)
        $isReadable = mb_strlen($cleanText) >= 40;
        if ($isReadable) {
            $score += 20;
            $checks[] = [
                'label' => 'Machine-Readable Text Layer',
                'passed' => true,
                'detail' => $pdfUploaded
                    ? 'PDF text was cleanly extracted without image-only barriers.'
                    : 'Plain text resume content is cleanly parseable by ATS scanners.',
            ];
        } else {
            $checks[] = [
                'label' => 'Machine-Readable Text Layer',
                'passed' => false,
                'detail' => $pdfUploaded
                    ? 'Very little selectable text was found in your PDF (likely scanned/Canva image export).'
                    : 'Resume summary is too short for ATS parsers to index.',
            ];
            $fixes[] = $pdfUploaded
                ? 'Export your resume as a standard text-based PDF (from Google Docs, Word, or LaTeX) instead of a scanned image or graphic-heavy Canva layout.'
                : 'Include your full resume text (experience, projects, skills, education) so ATS parsers can evaluate your profile properly.';
        }

        // 2. Contact Information (15 pts)
        $hasEmail = preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $cleanText) === 1;
        $hasPhone = preg_match('/(?:\+?\d{1,3}[\s\-]?)?(?:\(?\d{3}\)?[\s\-]?)?\d{3}[\s\-]?\d{4}/', $cleanText) === 1;
        $hasLinks = preg_match('/\b(?:github\.com|linkedin\.com|portfolio|https?:\/\/)/i', $cleanText) === 1;

        if ($hasEmail && ($hasPhone || $hasLinks)) {
            $score += 15;
            $checks[] = [
                'label' => 'Contact & Profile Metadata',
                'passed' => true,
                'detail' => 'Detected valid contact details (email and phone/profile links) in plain text.',
            ];
        } elseif ($hasEmail || $hasPhone || $hasLinks) {
            $score += 8;
            $checks[] = [
                'label' => 'Contact & Profile Metadata',
                'passed' => false,
                'detail' => 'Partial contact metadata detected—missing either email, phone, or GitHub/LinkedIn link.',
            ];
            $fixes[] = 'Place a clean top header with plain-text Email, Phone Number, GitHub, and LinkedIn URLs (avoid putting contact details inside PDF headers/footers or icons).';
        } else {
            $checks[] = [
                'label' => 'Contact & Profile Metadata',
                'passed' => false,
                'detail' => 'No email address, phone number, or GitHub/LinkedIn profile detected.',
            ];
            $fixes[] = 'Add a standard plain-text contact block at the top of your resume containing Email, Phone, LinkedIn, and GitHub links.';
        }

        // 3. Standard ATS Section Headings (15 pts)
        $hasExperienceOrProjects = preg_match('/\b(?:experience|work history|employment|internship|projects?)\b/i', $cleanText) === 1;
        $hasSkillsSection = preg_match('/\b(?:skills?|technologies|tech stack|technical expertise)\b/i', $cleanText) === 1;
        $hasEducation = preg_match('/\b(?:education|university|college|b\.?tech|bachelor|master|degree|bca|mca|b\.?sc)\b/i', $cleanText) === 1;
        $sectionHits = (int) $hasExperienceOrProjects + (int) $hasSkillsSection + (int) $hasEducation;

        if ($sectionHits >= 3) {
            $score += 15;
            $checks[] = [
                'label' => 'Standard ATS Section Headings',
                'passed' => true,
                'detail' => 'Contains recognizable Experience/Projects, Technical Skills, and Education sections.',
            ];
        } else {
            $score += $sectionHits * 4;
            $missingSections = [];
            if (! $hasExperienceOrProjects) {
                $missingSections[] = 'Experience / Projects';
            }
            if (! $hasSkillsSection) {
                $missingSections[] = 'Technical Skills';
            }
            if (! $hasEducation) {
                $missingSections[] = 'Education';
            }
            $checks[] = [
                'label' => 'Standard ATS Section Headings',
                'passed' => false,
                'detail' => 'Missing standard ATS section labels: ' . implode(', ', $missingSections) . '.',
            ];
            $fixes[] = 'Use clear, conventional section headings (' . implode(', ', $missingSections) . ') in a single-column layout so ATS parsers categorize your data accurately.';
        }

        // 4. Target Role & Technical Keyword Match (20 pts)
        $realSkills = array_values(array_diff($skills, ['Software Engineering', 'Problem Solving']));
        $skillCount = count($realSkills);
        $roleMatched = $targetRole !== null
            && trim($targetRole) !== ''
            && preg_match('/' . preg_quote(explode(' ', trim($targetRole))[0], '/') . '/i', $cleanText) === 1;

        if ($skillCount >= 4) {
            $score += 20;
            $checks[] = [
                'label' => 'Technical Keyword Density',
                'passed' => true,
                'detail' => "Strong keyword match with {$skillCount} technical skills indexed (" . implode(', ', array_slice($realSkills, 0, 5)) . ').',
            ];
        } elseif ($skillCount >= 2) {
            $score += 12;
            $checks[] = [
                'label' => 'Technical Keyword Density',
                'passed' => false,
                'detail' => "Only {$skillCount} core technical keywords detected—below the ideal ATS threshold of 4+.",
            ];
            $fixes[] = 'Add a dedicated "Technical Skills" section explicitly listing frameworks, databases, tools, and exact keywords from "' . ($targetRole ?: 'your target role') . '" job descriptions.';
        } else {
            $score += 4;
            $checks[] = [
                'label' => 'Technical Keyword Density',
                'passed' => false,
                'detail' => 'Insufficient role-specific technical keywords detected for ATS shortlisting.',
            ];
            $fixes[] = 'Mirror exact technical keywords (languages, frameworks, databases, cloud/DevOps tools) required for ' . ($targetRole ?: 'your target job role') . ' naturally inside both Skills and Project bullets.';
        }

        if (! $roleMatched && $targetRole !== null && trim($targetRole) !== '') {
            $fixes[] = 'Include a concise 2-line Professional Summary at the top explicitly mentioning your target title ("' . trim($targetRole) . '") to boost ATS title-matching score.';
        }

        // 5. Quantified Achievements & Metrics (15 pts)
        $hasMetrics = preg_match('/(?:\b\d+\s*%|\b\d+\+\s*(?:users|clients|projects|apis|endpoints|requests|hours|months|years)|\b\d+x\b|\b\d+\s*(?:ms|sec|seconds|k\+?)\b)/i', $cleanText) === 1;
        if ($hasMetrics) {
            $score += 15;
            $checks[] = [
                'label' => 'Quantified Impact Metrics',
                'passed' => true,
                'detail' => 'Resume includes measurable results (percentages, scale, latency, or user metrics).',
            ];
        } else {
            $checks[] = [
                'label' => 'Quantified Impact Metrics',
                'passed' => false,
                'detail' => 'No measurable numbers or impact metrics found in project/work bullets.',
            ];
            $fixes[] = 'Quantify your achievements using numbers (e.g., "Reduced API response time by 40%", "Built 12+ REST endpoints serving 500+ users", or "Increased test coverage to 85%").';
        }

        // 6. Action Verbs & Optimal Length (15 pts)
        preg_match_all('/\b(?:built|developed|designed|implemented|architected|optimized|deployed|led|created|automated|integrated|reduced|improved|engineered|maintained|launched)\b/i', $cleanText, $verbMatches);
        $verbCount = count(array_unique(array_map('strtolower', $verbMatches[0] ?? [])));
        $goodLength = $wordCount >= 120 && $wordCount <= 1050;

        if ($verbCount >= 3 && $goodLength) {
            $score += 15;
            $checks[] = [
                'label' => 'Action Verbs & Optimal Length',
                'passed' => true,
                'detail' => "Well-balanced resume length ({$wordCount} words) with strong engineering action verbs.",
            ];
        } else {
            $partial = ($verbCount >= 2 ? 6 : 2) + ($goodLength ? 5 : 0);
            $score += $partial;
            $checks[] = [
                'label' => 'Action Verbs & Optimal Length',
                'passed' => false,
                'detail' => "Word count is {$wordCount} words with {$verbCount} distinct action verb(s) (ideal: 150–750 words, 4+ action verbs).",
            ];
            if ($wordCount < 120) {
                $fixes[] = 'Expand your resume with 2–3 structured project bullet points per project (aim for 250–600 words for a strong 1-page ATS resume).';
            } elseif ($wordCount > 1050) {
                $fixes[] = 'Trim verbose paragraphs into concise 1–2 line bullet points so your resume fits cleanly within 1–2 pages.';
            }
            if ($verbCount < 3) {
                $fixes[] = 'Start every project/experience bullet with strong action verbs like "Architected", "Implemented", "Optimized", or "Deployed" instead of "Worked on" or "Responsible for".';
            }
        }

        $score = max(0, min(100, $score));
        $isAtsFriendly = $score >= 70;
        $verdict = match (true) {
            $score >= 80 => 'ATS Friendly (High Pass Probability)',
            $score >= 70 => 'ATS Friendly (Minor Tweaks Suggested)',
            $score >= 45 => 'Needs ATS Optimization',
            default => 'Not ATS Friendly — Action Required',
        };

        if ($fixes === []) {
            $fixes[] = 'Your resume structure is ATS-ready! Tailor your top 3 skills and summary slightly for each specific job posting before applying.';
        }

        return [
            'score' => $score,
            'is_ats_friendly' => $isAtsFriendly,
            'verdict' => $verdict,
            'word_count' => $wordCount,
            'checks' => $checks,
            'fixes' => array_values(array_unique($fixes)),
        ];
    }
}
