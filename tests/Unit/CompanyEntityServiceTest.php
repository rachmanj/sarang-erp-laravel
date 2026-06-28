<?php

namespace Tests\Unit;

use App\Models\CompanyEntity;
use App\Services\CompanyEntityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyEntityServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function default_entity_is_pt_cahaya_sarange_jaya(): void
    {
        $this->seed();

        $defaultEntity = app(CompanyEntityService::class)->getDefaultEntity();

        $this->assertSame(CompanyEntity::DEFAULT_CODE, $defaultEntity->code);
        $this->assertSame('PT Cahaya Sarange Jaya', $defaultEntity->name);
    }

    #[Test]
    public function get_entity_without_id_returns_default_entity(): void
    {
        $this->seed();

        $entity = app(CompanyEntityService::class)->getEntity();

        $this->assertSame(CompanyEntity::DEFAULT_CODE, $entity->code);
    }
}
