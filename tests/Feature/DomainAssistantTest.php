<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Unauthenticated access does not require a test database.
 * Full RBAC + conversation isolation tests need `sarang_erp_test` (see phpunit.xml) and RefreshDatabase.
 */
class DomainAssistantTest extends TestCase
{
    public function test_guest_is_redirected_from_assistant(): void
    {
        $this->get(route('assistant.index'))
            ->assertRedirect(route('login'));
    }
}
