<?php

namespace App\Models\Accounting;

use App\Models\Project;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountStatementLine extends Model
{
    protected $fillable = [
        'account_statement_id',
        'transaction_date',
        'reference_type',
        'reference_id',
        'reference_no',
        'description',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'project_id',
        'dept_id',
        'memo',
        'sort_order',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function accountStatement(): BelongsTo
    {
        return $this->belongsTo(AccountStatement::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopeByDateRange($query, $fromDate, $toDate)
    {
        return $query->whereBetween('transaction_date', [$fromDate, $toDate]);
    }

    public function scopeByReference($query, $referenceType, $referenceId)
    {
        return $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByDepartment($query, $deptId)
    {
        return $query->where('dept_id', $deptId);
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->debit_amount - $this->credit_amount;
    }

    public function getIsDebitAttribute()
    {
        return $this->debit_amount > 0;
    }

    public function getIsCreditAttribute()
    {
        return $this->credit_amount > 0;
    }

    public function getReferenceDisplayAttribute()
    {
        if ($this->reference_no) {
            return $this->reference_no;
        }
        return "{$this->reference_type}#{$this->reference_id}";
    }

    // Helper methods
    public function getReferenceModel()
    {
        $modelClass = $this->getReferenceModelClass();
        if ($modelClass && class_exists($modelClass)) {
            return $modelClass::find($this->reference_id);
        }
        return null;
    }

    protected function getReferenceModelClass()
    {
        $modelMap = [
            'journal' => \App\Models\Accounting\Journal::class,
            'sales_invoice' => \App\Models\Accounting\SalesInvoice::class,
            'purchase_invoice' => \App\Models\Accounting\PurchaseInvoice::class,
            'sales_receipt' => \App\Models\Accounting\SalesReceipt::class,
            'purchase_payment' => \App\Models\Accounting\PurchasePayment::class,
            'cash_expense' => \App\Models\Accounting\CashExpense::class,
            'asset_disposal' => \App\Models\AssetDisposal::class,
            'delivery_order' => \App\Models\DeliveryOrder::class,
        ];

        return $modelMap[$this->reference_type] ?? null;
    }

    public function getReferenceUrl()
    {
        $routeMap = [
            'journal' => 'journals.show',
            'sales_invoice' => 'sales-invoices.show',
            'purchase_invoice' => 'purchase-invoices.show',
            'sales_receipt' => 'sales-receipts.show',
            'purchase_payment' => 'purchase-payments.show',
            'cash_expense' => 'cash-expenses.show',
            'asset_disposal' => 'asset-disposals.show',
            'delivery_order' => 'delivery-orders.show',
        ];

        $route = $routeMap[$this->reference_type] ?? null;
        if ($route) {
            return route($route, $this->reference_id);
        }
        return null;
    }
}
