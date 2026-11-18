@extends('layouts.app')

@section('title', 'Project Roadmap (10 Phases) — AIVI PRO')

@section('content')
@php
    $allPhases = $roadmap->phases();
    $completedPhases = array_values(array_filter($allPhases, fn (array $p): bool => $p['completed']));
    $upcomingPhases = array_values(array_filter($allPhases, fn (array $p): bool => ! $p['completed']));
@endphp

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.88rem;">
                ← Back to {{ auth()->check() ? 'Mission Control' : 'Home' }}
            </a>
            <h1 class="page-title gradient-text" style="margin-top: 0.5rem;">10-Phase Project Roadmap</h1>
            <p class="subtitle" style="margin-top: 0.35rem;">
                Live delivery milestone tracker for AIVI PRO — completed modules and upcoming phases.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
            <span class="pill pill-green">{{ $roadmap->completedCount() }} Completed</span>
            <span class="pill pill-amber">{{ $roadmap->remainingCount() }} Upcoming</span>
            <span class="pill pill-cyan mono">{{ $roadmap->completionPercentage() }}% Overall</span>
        </div>
    </div>

    <!-- Overall Milestone Progress -->
    <div class="glass preview-window roadmap-banner">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <div class="mono" style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); font-weight: 700;">
                    Current Milestone Status
                </div>
                <div class="section-heading" style="font-size: 1.45rem; margin-top: 0.15rem;">
                    Phase {{ $roadmap->completedCount() }} of {{ $roadmap->totalCount() }} Completed
                </div>
            </div>
            <div style="font-family: var(--font-display); font-size: 2.35rem; font-weight: 800; color: var(--accent);">
                {{ $roadmap->completionPercentage() }}%
            </div>
        </div>

        <div class="phase-segments" style="margin-top: 1rem;">
            @foreach ($allPhases as $phase)
                <div class="phase-segment {{ $phase['completed'] ? 'done' : '' }}" title="Phase {{ $phase['number'] }}: {{ $phase['title'] }}"></div>
            @endforeach
        </div>

        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.85rem; font-size: 0.84rem; color: var(--text-muted);">
            <span>Phases 1–{{ $roadmap->completedCount() }} are active &amp; testable right now.</span>
            @if ($roadmap->remainingCount() > 0)
                <span>Phases {{ $roadmap->completedCount() + 1 }}–{{ $roadmap->totalCount() }} are locked for upcoming milestones.</span>
            @else
                <span>All 10 phases are unlocked and active.</span>
            @endif
        </div>
    </div>

    <!-- Completed Phases -->
    @if ($completedPhases !== [])
        <div style="margin-bottom: 2.5rem;">
            <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 1.15rem;">
                <h2 class="section-heading">Completed Phases</h2>
                <span class="pill pill-green mono">{{ count($completedPhases) }} Live</span>
            </div>

            <div class="phase-grid">
                @foreach ($completedPhases as $phase)
                    <div class="glass phase-card phase-card-completed">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <span class="pill pill-green mono">Phase {{ sprintf('%02d', $phase['number']) }}</span>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--success);">✓ Completed</span>
                        </div>

                        <h3 style="font-family: var(--font-display); font-size: 1.08rem; font-weight: 800; line-height: 1.35;">
                            {{ $phase['title'] }}
                        </h3>

                        <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.55;">
                            {{ $phase['summary'] }}
                        </p>

                        <ul class="phase-deliverables">
                            @foreach ($phase['deliverables'] as $item)
                                <li>
                                    <span style="color: var(--success);">✓</span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Upcoming Phases -->
    @if ($upcomingPhases !== [])
        <div>
            <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 1.15rem;">
                <h2 class="section-heading">Upcoming Phases</h2>
                <span class="pill pill-amber mono">{{ count($upcomingPhases) }} Scheduled</span>
            </div>

            <div class="phase-grid">
                @foreach ($upcomingPhases as $phase)
                    <div class="glass phase-card phase-card-locked">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                            <span class="pill pill-amber mono">Phase {{ sprintf('%02d', $phase['number']) }}</span>
                            <span style="font-size: 0.8rem; font-weight: 700; color: var(--warning);">🔒 Upcoming</span>
                        </div>

                        <h3 style="font-family: var(--font-display); font-size: 1.08rem; font-weight: 800; line-height: 1.35;">
                            {{ $phase['title'] }}
                        </h3>

                        <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.55;">
                            {{ $phase['summary'] }}
                        </p>

                        <ul class="phase-deliverables">
                            @foreach ($phase['deliverables'] as $item)
                                <li>
                                    <span style="color: var(--text-muted);">○</span>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
