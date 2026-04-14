<?php

namespace Tests\Unit;

use Illuminate\Pagination\AbstractPaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaginationUsesBootstrapTest extends TestCase
{
    #[Test]
    public function default_pagination_views_use_bootstrap_four(): void
    {
        $this->assertSame('pagination::bootstrap-4', AbstractPaginator::$defaultView);
        $this->assertSame('pagination::simple-bootstrap-4', AbstractPaginator::$defaultSimpleView);
    }
}
