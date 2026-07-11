<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Yerel BERTurk sınıflandırıcı servisine (FastAPI) istek atar.
 */
class MudurlukClassifierService
{
    public function __construct(
        protected DepartmentKeywordClassifier $keywordClassifier,
    ) {}
    /**
     * @return array{
     *     department_slug: string,
     *     department_name: string|null,
     *     confidence: float,
     *     needs_review: bool,
     *     department_id: int|null,
     *     top_predictions: array<int, array<string, mixed>>
     * }|null
     */
    public function predict(string $text): ?array
    {
        $baseUrl = rtrim((string) config('services.mudurluk_classifier.url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        $request = Http::timeout((int) config('services.mudurluk_classifier.timeout', 5))
            ->acceptJson();

        $apiKey = config('services.mudurluk_classifier.api_key');
        if (filled($apiKey)) {
            $request = $request->withHeader('X-Api-Key', $apiKey);
        }

        try {
            $response = $request->post("{$baseUrl}/predict", [
                'text' => $text,
            ]);
        } catch (ConnectionException $exception) {
            report($exception);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();
        $slug = (string) ($payload['department_slug'] ?? '');

        $result = [
            'department_slug' => $slug,
            'department_name' => $payload['department_name'] ?? null,
            'confidence' => (float) ($payload['confidence'] ?? 0),
            'needs_review' => (bool) ($payload['needs_review'] ?? true),
            'department_id' => $this->resolveDepartmentId($slug),
            'top_predictions' => $payload['top_predictions'] ?? [],
            'classification_method' => (string) ($payload['classification_method'] ?? 'ml'),
        ];

        return $this->keywordClassifier->refine($text, $result, $this);
    }

    public function resolveDepartmentId(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }

        $department = Department::query()
            ->get(['id', 'name'])
            ->first(function (Department $dept) use ($slug): bool {
                return $this->slugify($dept->name) === $slug;
            });

        return $department?->id;
    }

    public function slugify(string $name): string
    {
        $ascii = Str::ascii(mb_strtoupper(trim($name), 'UTF-8'));
        $slug = preg_replace('/[^A-Z0-9]+/', '_', $ascii) ?? '';
        $slug = trim($slug, '_');
        foreach (['_MUDURLUGU', '_MUDURLUK'] as $suffix) {
            if (str_ends_with($slug, $suffix)) {
                $slug = substr($slug, 0, -strlen($suffix));
            }
        }

        return $slug;
    }
}
