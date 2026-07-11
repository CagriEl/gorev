<?php

namespace Tests\Unit;

use App\Services\DepartmentKeywordClassifier;
use App\Services\MudurlukClassifierService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DepartmentKeywordClassifierTest extends TestCase
{
    #[Test]
    public function it_classifies_full_garbage_bins_as_temizlik(): void
    {
        $result = app(DepartmentKeywordClassifier::class)->classify('Çöpler çok dolu');

        $this->assertNotNull($result);
        $this->assertSame('TEMIZLIK_ISLERI', $result['slug']);
        $this->assertGreaterThanOrEqual(0.85, $result['confidence']);
    }

    #[Test]
    public function it_overrides_ambiguous_ml_predictions(): void
    {
        $resolver = $this->createMock(MudurlukClassifierService::class);
        $resolver->method('resolveDepartmentId')->willReturn(1);

        $ml = [
            'department_slug' => 'FEN_ISLERI',
            'department_name' => 'Fen',
            'confidence' => 0.261,
            'needs_review' => true,
            'department_id' => null,
            'top_predictions' => [
                ['department_slug' => 'TEMIZLIK_ISLERI', 'confidence' => 0.261],
                ['department_slug' => 'PARK_BAHCE', 'confidence' => 0.259],
            ],
        ];

        $refined = app(DepartmentKeywordClassifier::class)->refine('Çöpler çok dolu', $ml, $resolver);

        $this->assertSame('TEMIZLIK_ISLERI', $refined['department_slug']);
        $this->assertSame('keyword', $refined['classification_method']);
        $this->assertGreaterThanOrEqual(0.85, $refined['confidence']);
        $this->assertFalse($refined['needs_review']);
    }
}
