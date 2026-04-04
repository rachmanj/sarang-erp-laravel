<?php

return [
    'manuals_path' => base_path('docs/manuals'),
    'navigation_json' => base_path('docs/manuals/help-navigation.json'),
    'similarity_threshold' => (float) env('HELP_SIMILARITY_THRESHOLD', 0.22),
    'top_k' => (int) env('HELP_TOP_K', 6),
    'reindex_batch_size' => (int) env('HELP_REINDEX_BATCH_SIZE', 6),
];
