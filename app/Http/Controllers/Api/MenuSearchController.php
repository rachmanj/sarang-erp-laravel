<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuSearchController extends Controller
{
    protected $menuSearchService;

    public function __construct(MenuSearchService $menuSearchService)
    {
        $this->menuSearchService = $menuSearchService;
    }

    /**
     * Get all searchable menu items for the current user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['items' => []], 401);
        }

        // Cache menu items per user (cache key includes user ID and permissions hash)
        $cacheKey = 'menu_items_user_' . $user->id . '_' . md5(implode(',', $user->getAllPermissions()->pluck('name')->toArray()));

        $items = Cache::remember($cacheKey, 3600, function () {
            return $this->menuSearchService->getSearchableMenuItems();
        });

        // If search query provided, filter items
        $query = $request->get('q', '');
        if (!empty($query)) {
            $items = $this->filterItems($items, $query);
        }

        return response()->json([
            'items' => $items
        ]);
    }

    /**
     * Filter menu items based on search query
     * 
     * @param array $items
     * @param string $query
     * @return array
     */
    private function filterItems(array $items, string $query): array
    {
        $query = strtolower(trim($query));

        if (empty($query)) {
            return $items;
        }

        $filtered = [];
        foreach ($items as $item) {
            // Search in title, breadcrumb, and keywords
            $searchText = $item['searchText'] ?? '';

            if (strpos($searchText, $query) !== false) {
                $filtered[] = $item;
            }
        }

        // Sort by relevance (title matches first, then breadcrumb, then keywords)
        usort($filtered, function ($a, $b) use ($query) {
            $aTitleMatch = stripos($a['title'], $query) !== false ? 0 : 1;
            $bTitleMatch = stripos($b['title'], $query) !== false ? 0 : 1;

            if ($aTitleMatch !== $bTitleMatch) {
                return $aTitleMatch - $bTitleMatch;
            }

            return strcasecmp($a['title'], $b['title']);
        });

        // Limit to top 15 results
        return array_slice($filtered, 0, 15);
    }
}
