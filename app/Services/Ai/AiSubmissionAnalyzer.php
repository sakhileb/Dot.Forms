<?php

namespace App\Services\Ai;

use App\Models\Form;
use Illuminate\Support\Collection;

class AiSubmissionAnalyzer
{
    public function analyze(Form $form, Collection $submissions): array
    {
        $sample = $submissions->take(100)->values();

        $total = $sample->count();

        if ($total === 0) {
            return [
                'summary' => 'No submissions yet. Once data starts coming in, AI analytics will summarize patterns.',
                'sentiment' => [
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                ],
                'recommendations' => [
                    'Collect at least 20 submissions to unlock meaningful trend analysis.',
                ],
            ];
        }

        $optionStats = $this->topOptionStats($form, $sample);
        $sentiment = $this->sentimentScores($sample);

        $summary = 'Analyzed '.$total.' submissions. ';

        if ($optionStats !== null) {
            $summary .= $optionStats;
        } else {
            $summary .= 'Most answers are evenly distributed across fields.';
        }

        return [
            'summary' => $summary,
            'sentiment' => $sentiment,
            'recommendations' => $this->recommendations($form, $sample),
        ];
    }

    protected function topOptionStats(Form $form, Collection $submissions): ?string
    {
        foreach ($form->fields as $field) {
            $choices = $field->options['choices'] ?? [];

            if (! is_array($choices) || $choices === []) {
                continue;
            }

            $counts = [];

            foreach ($submissions as $submission) {
                $value = $submission->data[$field->id] ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                $counts[$value] = ($counts[$value] ?? 0) + 1;
            }

            if ($counts === []) {
                continue;
            }

            arsort($counts);
            $topOption = array_key_first($counts);
            $topCount = $counts[$topOption] ?? 0;
            $pct = (int) round(($topCount / max($submissions->count(), 1)) * 100);

            return 'Top response for "'.$field->label.'" is "'.$topOption.'" at '.$pct.'%.';
        }

        return null;
    }

    protected function sentimentScores(Collection $submissions): array
    {
        $positiveWords = ['good', 'great', 'excellent', 'love', 'helpful', 'happy'];
        $negativeWords = ['bad', 'poor', 'hate', 'difficult', 'frustrating', 'slow'];

        $scores = [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
        ];

        foreach ($submissions as $submission) {
            $text = strtolower(json_encode($submission->data) ?: '');

            $posHits = 0;
            $negHits = 0;

            foreach ($positiveWords as $word) {
                if (str_contains($text, $word)) {
                    $posHits++;
                }
            }

            foreach ($negativeWords as $word) {
                if (str_contains($text, $word)) {
                    $negHits++;
                }
            }

            if ($posHits > $negHits) {
                $scores['positive']++;
            } elseif ($negHits > $posHits) {
                $scores['negative']++;
            } else {
                $scores['neutral']++;
            }
        }

        return $scores;
    }

    protected function recommendations(Form $form, Collection $submissions): array
    {
        $recommendations = [];

        foreach ($form->fields as $field) {
            $answered = 0;

            foreach ($submissions as $submission) {
                $value = $submission->data[$field->id] ?? null;

                if ($value !== null && $value !== '' && $value !== []) {
                    $answered++;
                }
            }

            $completionRate = (int) round(($answered / max($submissions->count(), 1)) * 100);

            if ($completionRate < 60) {
                $recommendations[] = 'Field "'.$field->label.'" has low completion ('.$completionRate.'%). Consider clearer wording or making it optional.';
            }
        }

        if ($recommendations === []) {
            $recommendations[] = 'Completion rates are healthy. Consider adding one optional open-text field for richer qualitative insights.';
        }

        return $recommendations;
    }
}
