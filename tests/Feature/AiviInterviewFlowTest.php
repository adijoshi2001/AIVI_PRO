<?php

namespace Tests\Feature;

use App\Mail\InterviewReportMail;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AiviInterviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_landing_and_register(): void
    {
        $this->get('/')->assertOk()->assertSee('AIVI PRO');

        $response = $this->post('/register', [
            'name' => 'Test Candidate',
            'email' => 'candidate@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'candidate@example.com']);
    }

    public function test_authenticated_user_can_complete_full_interview_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mission Control');

        $this->actingAs($user)
            ->get('/interviews/new')
            ->assertOk()
            ->assertSee('Configure Your AI Interview');

        // Start a new interview using pasted resume text
        $createResponse = $this->actingAs($user)->post('/interviews', [
            'mode' => 'text',
            'difficulty' => 'intermediate',
            'target_role' => 'Laravel Backend Engineer',
            'resume_text' => 'Experienced in PHP, Laravel, MySQL, REST API, Docker, and Git.',
        ]);

        $interview = Interview::firstOrFail();
        $createResponse->assertRedirect(route('interviews.session', $interview));
        $this->assertContains('Laravel', $interview->skills);
        $this->assertContains('PHP', $interview->skills);
        $this->assertCount(5, $interview->questions);

        // View text session
        $this->actingAs($user)
            ->get(route('interviews.session', $interview))
            ->assertOk()
            ->assertSee($interview->questions->first()->question_text);

        // Submit answers
        $answers = [];
        foreach ($interview->questions as $q) {
            $answers[$q->id] = 'In Laravel and PHP, I solve this by using eager loading with Eloquent, proper database indexing, and clean service layer architecture because it improves performance and maintainability in production.';
        }

        $submitResponse = $this->actingAs($user)->post(route('interviews.submit', $interview), [
            'answers' => $answers,
            'duration_seconds' => 145,
            'movement_alerts' => 1,
        ]);

        $submitResponse->assertRedirect(route('interviews.report', $interview));

        $interview->refresh();
        $this->assertSame('completed', $interview->status);
        $this->assertGreaterThan(50, $interview->score);

        // View report
        $this->actingAs($user)
            ->get(route('interviews.report', $interview))
            ->assertOk()
            ->assertSee('Performance Report')
            ->assertSee($interview->score . '%');

        // Delete interview
        $this->actingAs($user)
            ->delete(route('interviews.destroy', $interview))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('interviews', ['id' => $interview->id]);
    }

    public function test_roadmap_displays_60_percent_and_locks_upcoming_phases(): void
    {
        config()->set('roadmap.completed_phases', 6);

        $this->get('/roadmap')
            ->assertOk()
            ->assertSee('10-Phase Project Roadmap')
            ->assertSee('60%')
            ->assertSee('Phase 6 of 10 Completed');

        $user = User::factory()->create();

        // Phase 7 (Live Mode) is locked at 60%
        $this->actingAs($user)
            ->post('/interviews', [
                'mode' => 'live',
                'difficulty' => 'intermediate',
                'target_role' => 'Laravel Developer',
                'resume_text' => 'PHP, Laravel, MySQL, REST API',
            ])
            ->assertSessionHasErrors('mode');

        $this->assertDatabaseCount('interviews', 0);

        // Unlocking to Phase 10 (100%) re-enables Live Mode cleanly
        config()->set('roadmap.completed_phases', 10);

        $liveResponse = $this->actingAs($user)->post('/interviews', [
            'mode' => 'live',
            'difficulty' => 'intermediate',
            'target_role' => 'Laravel Developer',
            'resume_text' => 'PHP, Laravel, MySQL, REST API',
        ]);

        $interview = Interview::firstOrFail();
        $liveResponse->assertRedirect(route('interviews.session', $interview));
        $this->assertSame('live', $interview->mode);
    }

    public function test_google_oauth_redirects_gracefully_when_unconfigured_and_authenticates_user_when_configured(): void
    {
        config()->set('services.google.client_id', '');
        config()->set('services.google.client_secret', '');

        $this->get('/login')
            ->assertOk()
            ->assertSee('Continue with Google');

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-uid-12345');
        $socialiteUser->shouldReceive('getEmail')->andReturn('google.candidate@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Google Candidate');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google.candidate@example.com',
            'google_id' => 'google-uid-12345',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        // Logout and verify existing user links cleanly without duplication
        $this->post('/logout');

        Socialite::shouldReceive('driver->user')->once()->andReturn($socialiteUser);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_interview_generates_ats_audit_weak_topics_career_roadmap_and_emails_report(): void
    {
        Mail::fake();
        config()->set('roadmap.completed_phases', 6);

        $user = User::factory()->create([
            'email' => 'candidate.roadmap@example.com',
        ]);

        // Phase 8 Technical Coding Round is locked at 60%
        $this->actingAs($user)
            ->post('/interviews', [
                'mode' => 'coding',
                'difficulty' => 'intermediate',
                'target_role' => 'Laravel Backend Engineer',
                'resume_text' => 'PHP Laravel developer',
            ])
            ->assertSessionHasErrors('mode');

        // Start a valid text interview with short resume (triggers ATS fixes)
        $this->actingAs($user)->post('/interviews', [
            'mode' => 'text',
            'difficulty' => 'intermediate',
            'target_role' => 'Laravel Backend Engineer',
            'resume_text' => 'Worked on PHP, Laravel, and SQL projects.',
        ])->assertRedirect();

        $interview = Interview::with('questions')->firstOrFail();
        $this->assertNotNull($interview->ats_score);
        $this->assertIsArray($interview->ats_analysis);
        $this->assertNotEmpty($interview->ats_analysis['fixes']);
        $this->assertNotEmpty($interview->questions->first()->topic);

        // Give strong answers to first 2 questions and leave remaining weak/blank to trigger Weak Topics
        $answers = [];
        foreach ($interview->questions as $idx => $q) {
            $answers[$q->id] = $idx < 2
                ? 'In Laravel and PHP, I use eager loading with Eloquent, composite SQL indexes, and service layer architecture because it reduces N+1 queries and improves production performance.'
                : 'idk';
        }

        $this->actingAs($user)
            ->post(route('interviews.submit', $interview), [
                'answers' => $answers,
                'duration_seconds' => 120,
            ])
            ->assertRedirect(route('interviews.report', $interview));

        $interview->refresh();
        $this->assertNotEmpty($interview->weak_topics);
        $this->assertNotEmpty($interview->career_roadmap['steps'] ?? []);
        $this->assertNotNull($interview->report_emailed_at);

        Mail::assertSent(InterviewReportMail::class, function (InterviewReportMail $mail) use ($user): bool {
            return $mail->hasTo($user->email);
        });

        // Verify report page renders all new intelligence sections
        $this->actingAs($user)
            ->get(route('interviews.report', $interview))
            ->assertOk()
            ->assertSee('Weak Topics &amp; What to Focus On', false)
            ->assertSee('Resume ATS Analysis &amp; Recommended Fixes', false)
            ->assertSee('How to Achieve: Laravel Backend Engineer')
            ->assertSee('Technical Coding Round &amp; DSA Prep', false);

        // Resend email endpoint works
        $this->actingAs($user)
            ->post(route('interviews.email', $interview))
            ->assertRedirect(route('interviews.report', $interview))
            ->assertSessionHas('success');

        Mail::assertSentCount(2);
    }
}
