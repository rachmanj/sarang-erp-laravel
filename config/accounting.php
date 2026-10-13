<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accounting period start date
    |--------------------------------------------------------------------------
    |
    | Documents dated before this day are rejected unless they are marked as
    | opening balance (or manual opening-balance journals).
    |
    */

    'start_date' => env('ACCOUNTING_START_DATE', '2026-01-01'),

];
