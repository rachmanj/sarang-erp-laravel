<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feedback persistence is covered by migration + controller; full flow needs MySQL test DB (see phpunit.xml).
 * This test only asserts unauthenticated JSON requests are rejected without touching the database.
 */
class HelpFeedbackTest extends TestCase
{
    public function test_guest_cannot_submit_feedback(): void
    {
        $this->postJson(route('help.feedback'), [
            'type' => 'bug',
            'title' => 'Test',
            'body' => 'Body',
        ])->assertUnauthorized();
    }
}
