<?php

namespace App\Observers;

use App\Services\Accounting\AccountingStartDateGuard;
use Illuminate\Database\Eloquent\Model;

class AccountingStartDateObserver
{
    public function saving(Model $model): void
    {
        AccountingStartDateGuard::validateModel($model);
    }
}
