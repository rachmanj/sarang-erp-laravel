<?php

namespace Tests\Unit;

use App\Services\Help\HelpVector;
use PHPUnit\Framework\TestCase;

class HelpVectorTest extends TestCase
{
    public function test_cosine_of_identical_vectors_is_one(): void
    {
        $v = [1.0, 0.0, 0.0];
        $this->assertEqualsWithDelta(1.0, HelpVector::cosineSimilarity($v, $v), 0.0001);
    }

    public function test_cosine_of_orthogonal_vectors_is_zero(): void
    {
        $a = [1.0, 0.0];
        $b = [0.0, 1.0];
        $this->assertEqualsWithDelta(0.0, HelpVector::cosineSimilarity($a, $b), 0.0001);
    }
}
