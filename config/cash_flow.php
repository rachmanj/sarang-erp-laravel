<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Account code prefixes — indirect cash flow (Trading-style default)
    |--------------------------------------------------------------------------
    |
    | Adjust for your chart of accounts. Prefix match: exact code or "prefix.*".
    | Nonprofit CoASeeder uses different numbering (e.g. AR under 1.1.4); override here.
    |
    */
    'account_prefixes' => [
        'cash_and_bank' => ['1.1.1'],
        'receivables' => ['1.1.2'],
        'inventory' => ['1.1.3'],
        'prepaid' => ['1.1.5', '1.1.7'],
        'payables' => ['2.1.1'],
        'accrued_liabilities' => ['2.1.4'],
        'tax_payables' => ['2.1.2'],
        'input_vat_prepaid_assets' => ['1.1.4'],
        'non_current_assets' => ['1.2'],
        'short_term_borrowings' => ['2.1.3'],
        'long_term_liabilities' => ['2.2'],
        'equity_financing_prefixes' => ['3.1', '3.2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefix notes (TradingCoASeeder)
    |--------------------------------------------------------------------------
    |
    | - tax_payables: Utang Pajak (e.g. PPN Keluaran, PPh) — WC effect like trade AP.
    | - input_vat_prepaid_assets: Pajak dibayar dimuka / PPN Masukan — WC effect like other current assets.
    | If your COA uses 1.1.4 for something else (e.g. some nonprofits), set input_vat_prepaid_assets to [] or
    | the correct codes.
    |
    | - short_term_borrowings: Utang Jangka Pendek (e.g. bank overdraft ST) — financing delta like LT debt.
    | - equity_financing_prefixes: Modal / agio (e.g. 3.1, 3.2). Excludes 3.3 laba ditahan so net income in
    |   operating is not double-counted. Add 3.3.x here only if you use a different closing model and accept
    |   overlap risk.
    |
    */

];
