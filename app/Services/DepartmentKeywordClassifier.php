<?php

namespace App\Services;

/**
 * Şikâyet metninde güçlü anahtar kelime eşleşmesi (BERT düşük güven / belirsizken).
 */
class DepartmentKeywordClassifier
{
    /**
     * @return array{slug: string, score: int, confidence: float}|null
     */
    public function classify(string $text): ?array
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return null;
        }

        $rules = config('department_classifier_keywords', []);
        $bestSlug = null;
        $bestScore = 0;

        foreach ($rules as $slug => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $keyword = $this->normalize((string) $keyword);
                if ($keyword !== '' && str_contains($normalized, $keyword)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestSlug = $slug;
            }
        }

        if ($bestSlug === null || $bestScore < 1) {
            return null;
        }

        return [
            'slug' => $bestSlug,
            'score' => $bestScore,
            'confidence' => min(0.95, 0.78 + ($bestScore * 0.06)),
        ];
    }

    /**
     * @param  array{
     *     department_slug: string,
     *     department_name: string|null,
     *     confidence: float,
     *     needs_review: bool,
     *     department_id: int|null,
     *     top_predictions: array<int, array<string, mixed>>
     * }  $ml
     * @return array{
     *     department_slug: string,
     *     department_name: string|null,
     *     confidence: float,
     *     needs_review: bool,
     *     department_id: int|null,
     *     top_predictions: array<int, array<string, mixed>>,
     *     classification_method?: string
     * }
     */
    public function refine(string $text, array $ml, MudurlukClassifierService $resolver): array
    {
        $keyword = $this->classify($text);
        if ($keyword === null) {
            $ml['classification_method'] = $ml['classification_method'] ?? 'ml';

            return $ml;
        }

        if (! $this->shouldOverride($ml)) {
            $ml['classification_method'] = $ml['classification_method'] ?? 'ml';

            return $ml;
        }

        $slug = $keyword['slug'];
        $departmentId = $resolver->resolveDepartmentId($slug);
        $slugToName = $this->slugToDisplayNames();

        return [
            'department_slug' => $slug,
            'department_name' => $slugToName[$slug] ?? $ml['department_name'],
            'confidence' => $keyword['confidence'],
            'needs_review' => $keyword['confidence'] < (float) config('services.mudurluk_classifier.confidence_threshold', 0.55),
            'department_id' => $departmentId,
            'top_predictions' => $ml['top_predictions'],
            'classification_method' => 'keyword',
        ];
    }

    /**
     * @param  array{confidence: float, top_predictions?: array<int, array<string, mixed>>}  $ml
     */
    protected function shouldOverride(array $ml): bool
    {
        $confidence = (float) ($ml['confidence'] ?? 0);
        $threshold = (float) config('services.mudurluk_classifier.confidence_threshold', 0.55);

        if ($confidence < $threshold) {
            return true;
        }

        $tops = $ml['top_predictions'] ?? [];
        if (count($tops) >= 2) {
            $first = (float) ($tops[0]['confidence'] ?? 0);
            $second = (float) ($tops[1]['confidence'] ?? 0);
            if (abs($first - $second) < 0.12) {
                return true;
            }
        }

        return false;
    }

    protected function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /**
     * @return array<string, string>
     */
    protected function slugToDisplayNames(): array
    {
        $labels = json_decode(
            (string) file_get_contents(base_path('ml/mudurluk-siniflandirici/labels.json')),
            true,
        );

        $map = [];
        foreach ($labels['labels'] ?? [] as $row) {
            $map[$row['slug']] = $row['name'];
        }

        return $map;
    }
}
