<?php

namespace Tests\Feature;

use Tests\TestCase;

class AssetModulePhase3Test extends TestCase
{
    public function test_critical_asset_route_names_are_registered(): void
    {
        $names = [
            'assets.index',
            'assets.data',
            'assets.create',
            'assets.store',
            'assets.show',
            'assets.edit',
            'assets.update',
            'assets.destroy',
            'assets.categories',
            'assets.projects',
            'assets.departments',
            'assets.vendors',
            'assets.schedule',
            'assets.depreciation.index',
            'assets.depreciation.store',
            'assets.depreciation.data',
            'assets.depreciation.create',
            'assets.depreciation.show',
            'assets.depreciation.calculate',
            'assets.depreciation.createEntries',
            'assets.depreciation.post',
            'assets.depreciation.reverse',
            'assets.depreciation.entries',
            'assets.disposals.index',
            'assets.disposals.store',
            'assets.disposals.data',
            'assets.disposals.create',
            'assets.disposals.show',
            'assets.disposals.edit',
            'assets.disposals.update',
            'assets.disposals.destroy',
            'assets.disposals.post',
            'assets.disposals.reverse',
            'assets.movements.index',
            'assets.movements.store',
            'assets.movements.data',
            'assets.movements.create',
            'assets.movements.show',
            'assets.movements.edit',
            'assets.movements.update',
            'assets.movements.destroy',
            'assets.movements.approve',
            'assets.movements.complete',
            'assets.movements.cancel',
            'assets.movements.history',
            'assets.import.index',
            'assets.import.template',
            'assets.import.validate',
            'assets.import.import',
            'assets.import.reference-data',
            'assets.import.bulk-update',
            'assets.data-quality.index',
            'assets.data-quality.duplicates',
            'assets.data-quality.incomplete',
            'assets.data-quality.consistency',
            'assets.data-quality.orphaned',
            'assets.data-quality.export',
            'assets.data-quality.score',
            'assets.data-quality.duplicate-details',
            'assets.data-quality.assets-by-issue',
            'assets.bulk-operations.index',
            'assets.bulk-update.data',
            'assets.bulk-update.preview',
            'assets.bulk-update',
        ];

        foreach ($names as $name) {
            $this->assertTrue(app('router')->has($name), "Missing route name: {$name}");
        }
    }

    public function test_import_data_quality_and_bulk_views_use_main_layout(): void
    {
        foreach ([
            'resources/views/assets/import/index.blade.php',
            'resources/views/assets/data-quality/index.blade.php',
            'resources/views/assets/bulk-operations/index.blade.php',
            'resources/views/assets/data-quality/duplicates.blade.php',
            'resources/views/assets/data-quality/incomplete.blade.php',
            'resources/views/assets/data-quality/consistency.blade.php',
            'resources/views/assets/data-quality/orphaned.blade.php',
            'resources/views/assets/movements/asset-history.blade.php',
        ] as $path) {
            $contents = file_get_contents(base_path($path));
            $this->assertStringContainsString("@extends('layouts.main')", $contents, $path);
            $this->assertStringNotContainsString("@extends('layouts.app')", $contents, $path);
            $this->assertStringNotContainsString('<div class="content-wrapper">', $contents, $path);
        }
    }

    public function test_views_use_permission_strings_not_policy_ability(): void
    {
        $disposals = file_get_contents(base_path('resources/views/assets/disposals/index.blade.php'));
        $movements = file_get_contents(base_path('resources/views/assets/movements/index.blade.php'));

        $this->assertStringContainsString("@can('assets.disposal.create')", $disposals);
        $this->assertStringNotContainsString("@can('create', App\\Models\\AssetDisposal::class)", $disposals);

        $this->assertStringContainsString("@can('assets.movement.create')", $movements);
        $this->assertStringNotContainsString("@can('create', App\\Models\\AssetMovement::class)", $movements);
    }

    public function test_data_quality_service_references_business_partner_id(): void
    {
        $source = file_get_contents(base_path('app/Services/DataQuality/AssetDataQualityService.php'));

        $this->assertStringContainsString('business_partner_id', $source);
        $this->assertStringContainsString('business_partners', $source);
        $this->assertStringNotContainsString("whereNull('vendor_id')", $source);
        $this->assertStringNotContainsString("from('vendors')", $source);
    }

    public function test_import_validate_route_points_to_validate_import_method(): void
    {
        $route = collect(app('router')->getRoutes())->first(function ($route) {
            return $route->getName() === 'assets.import.validate';
        });

        $this->assertNotNull($route);
        $this->assertSame('validateImport', $route->getActionMethod());
    }
}
