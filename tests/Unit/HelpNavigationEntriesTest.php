<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpNavigationEntriesTest extends TestCase
{
    public function test_help_navigation_includes_june_2026_feature_entries(): void
    {
        $path = dirname(__DIR__, 2).'/docs/manuals/help-navigation.json';
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $ids = collect($data['entries'])->pluck('id')->all();

        foreach ([
            'bank-reconciliation',
            'bank-accounts',
            'tax-compliance',
            'document-workflow-features',
            'accounting-reports-enhanced',
            'periods-year-end',
        ] as $id) {
            $this->assertContains($id, $ids, "Missing help-navigation entry: {$id}");
        }
    }
}
