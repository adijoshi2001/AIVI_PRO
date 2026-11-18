<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIVI PRO Interview Performance Report</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #0b1120; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #e2e8f0; line-height: 1.6;">
    @php
        $score = (int) ($interview->score ?? 0);
        $scoreColor = $score >= 75 ? '#34d399' : ($score >= 45 ? '#fbbf24' : '#f87171');
        $atsScore = (int) ($interview->ats_score ?? ($ats['score'] ?? 0));
        $atsColor = $atsScore >= 70 ? '#34d399' : ($atsScore >= 45 ? '#fbbf24' : '#f87171');
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 640px; margin: 0 auto; background-color: #111827; border: 1px solid #1f2937; border-radius: 16px; overflow: hidden;">
        <!-- Header -->
        <tr>
            <td style="padding: 28px 30px; background: linear-gradient(135deg, #312e81, #4c1d95); border-bottom: 1px solid #3730a3;">
                <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #c7d2fe;">AIVI PRO • INTERVIEW INTELLIGENCE REPORT</div>
                <h1 style="margin: 8px 0 4px; font-size: 24px; color: #ffffff;">Hi {{ $user->name ?? 'Candidate' }}, here is your report!</h1>
                <div style="font-size: 14px; color: #e0e7ff;">
                    Role: <strong>{{ $interview->target_role }}</strong> • {{ ucfirst($interview->difficulty) }} Level
                </div>
            </td>
        </tr>

        <!-- Score Summary -->
        <tr>
            <td style="padding: 24px 30px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 20px;">
                    <tr>
                        <td width="50%" style="padding: 16px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; text-align: center;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 700;">Interview Score</div>
                            <div style="font-size: 32px; font-weight: 800; color: {{ $scoreColor }}; margin-top: 4px;">{{ $score }}%</div>
                        </td>
                        <td width="12"></td>
                        <td width="50%" style="padding: 16px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; text-align: center;">
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; font-weight: 700;">Resume ATS Score</div>
                            <div style="font-size: 32px; font-weight: 800; color: {{ $atsColor }}; margin-top: 4px;">{{ $atsScore }}%</div>
                        </td>
                    </tr>
                </table>

                <h2 style="margin: 0 0 8px; font-size: 16px; color: #ffffff;">Executive Summary</h2>
                <p style="margin: 0 0 22px; font-size: 14px; color: #cbd5e1;">{{ $interview->feedback }}</p>

                <!-- Weak Topics -->
                <div style="padding: 18px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; margin-bottom: 18px;">
                    <h3 style="margin: 0 0 10px; font-size: 15px; color: #fbbf24;">Weak Topics to Focus On</h3>
                    @if ($weakTopics !== [])
                        @foreach ($weakTopics as $wt)
                            <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #1e293b;">
                                <div style="font-size: 14px; font-weight: 700; color: #f8fafc;">
                                    {{ $wt['topic'] }} — <span style="color: #f87171;">{{ $wt['avg_score'] }}% ({{ $wt['priority'] }} Priority)</span>
                                </div>
                                <div style="font-size: 13px; color: #94a3b8; margin-top: 3px;">{{ $wt['focus_plan'] }}</div>
                            </div>
                        @endforeach
                    @else
                        <p style="margin: 0; font-size: 13px; color: #6ee7b7;">Great job! No critical weak topics detected across this session.</p>
                    @endif
                </div>

                <!-- ATS Resume Audit -->
                @if ($ats !== [])
                    <div style="padding: 18px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; margin-bottom: 18px;">
                        <h3 style="margin: 0 0 6px; font-size: 15px; color: #a5b4fc;">ATS Resume Check: {{ $ats['verdict'] ?? 'Evaluated' }}</h3>
                        <ul style="margin: 8px 0 0; padding-left: 18px; font-size: 13px; color: #cbd5e1;">
                            @foreach ((array) ($ats['fixes'] ?? []) as $fix)
                                <li style="margin-bottom: 6px;">{{ $fix }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Career Roadmap -->
                @if ($careerRoadmap !== [])
                    <div style="padding: 18px; background-color: #0f172a; border: 1px solid #1e293b; border-radius: 12px; margin-bottom: 22px;">
                        <h3 style="margin: 0 0 6px; font-size: 15px; color: #38bdf8;">Target Job Roadmap: {{ $careerRoadmap['target_role'] ?? $interview->target_role }}</h3>
                        <div style="font-size: 13px; color: #94a3b8; margin-bottom: 10px;">
                            Status: <strong style="color: #e2e8f0;">{{ $careerRoadmap['readiness_level'] ?? '' }}</strong>
                            ({{ $careerRoadmap['estimated_timeline'] ?? '' }})
                        </div>
                        @if (! empty($careerRoadmap['missing_skills']))
                            <div style="font-size: 13px; color: #fbbf24; margin-bottom: 10px;">
                                <strong>Skills to Add:</strong> {{ implode(', ', (array) $careerRoadmap['missing_skills']) }}
                            </div>
                        @endif
                        @foreach ((array) ($careerRoadmap['steps'] ?? []) as $step)
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 13px; font-weight: 700; color: #e0e7ff;">
                                    {{ $step['stage'] }} ({{ $step['timeline'] }}) — {{ $step['title'] }}
                                </div>
                                <ul style="margin: 4px 0 0; padding-left: 18px; font-size: 12.5px; color: #cbd5e1;">
                                    @foreach ((array) ($step['tasks'] ?? []) as $task)
                                        <li style="margin-bottom: 3px;">{{ $task }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="text-align: center; margin-top: 24px;">
                    <a href="{{ route('interviews.report', $interview) }}" style="display: inline-block; padding: 12px 26px; background: #6366f1; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 10px;">
                        Open Full Interactive Report →
                    </a>
                </div>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="padding: 16px 30px; background-color: #0b1120; border-top: 1px solid #1f2937; text-align: center; font-size: 12px; color: #64748b;">
                Sent automatically by AIVI PRO • AI Virtual Interviewer Platform
            </td>
        </tr>
    </table>
</body>
</html>

