<?php

namespace App\Services;

class RoadmapService
{
    /**
     * @return array<int, array{
     *     number: int,
     *     title: string,
     *     summary: string,
     *     feature_key: string,
     *     deliverables: array<int, string>,
     *     completed: bool,
     *     status: string
     * }>
     */
    public function phases(): array
    {
        $completedLimit = $this->completedCount();
        $configured = (array) config('roadmap.phases', []);
        $result = [];

        foreach ($configured as $phase) {
            $number = (int) ($phase['number'] ?? 0);
            $isCompleted = $number > 0 && $number <= $completedLimit;

            $result[] = [
                'number' => $number,
                'title' => (string) ($phase['title'] ?? ''),
                'summary' => (string) ($phase['summary'] ?? ''),
                'feature_key' => (string) ($phase['feature_key'] ?? ''),
                'deliverables' => array_values((array) ($phase['deliverables'] ?? [])),
                'completed' => $isCompleted,
                'status' => $isCompleted ? 'completed' : 'upcoming',
            ];
        }

        return $result;
    }

    public function totalCount(): int
    {
        return count((array) config('roadmap.phases', []));
    }

    public function completedCount(): int
    {
        $total = $this->totalCount();
        $configured = (int) config('roadmap.completed_phases', 6);

        return max(0, min($total, $configured));
    }

    public function remainingCount(): int
    {
        return max(0, $this->totalCount() - $this->completedCount());
    }

    public function completionPercentage(): int
    {
        $total = $this->totalCount();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completedCount() / $total) * 100);
    }

    public function isPhaseEnabled(int $phaseNumber): bool
    {
        return $phaseNumber > 0 && $phaseNumber <= $this->completedCount();
    }

    public function isFeatureEnabled(string $featureKey): bool
    {
        $phaseNumber = $this->featurePhaseNumber($featureKey);

        if ($phaseNumber === null) {
            return true;
        }

        return $this->isPhaseEnabled($phaseNumber);
    }

    public function featurePhaseNumber(string $featureKey): ?int
    {
        foreach ((array) config('roadmap.phases', []) as $phase) {
            $primaryMatch = ($phase['feature_key'] ?? null) === $featureKey;
            $multiMatch = in_array($featureKey, (array) ($phase['feature_keys'] ?? []), true);

            if ($primaryMatch || $multiMatch) {
                return (int) ($phase['number'] ?? 0);
            }
        }

        return null;
    }
}
