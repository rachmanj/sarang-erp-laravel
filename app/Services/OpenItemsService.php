<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\GoodsReceiptPO;
use App\Models\Accounting\PurchaseInvoice;
use App\Models\Accounting\PurchasePayment;
use App\Models\SalesOrder;
use App\Models\DeliveryOrder;
use App\Models\Accounting\SalesInvoice;
use App\Models\Accounting\SalesReceipt;
use App\Models\ErpParameter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OpenItemsService
{
    /**
     * Get all open documents grouped by type
     */
    public function getAllOpenItems(array $filters = [])
    {
        $overdueThresholds = $this->getOverdueThresholds();

        $openItems = [
            'purchase_orders' => $this->getOpenPurchaseOrders($filters, $overdueThresholds),
            'goods_receipts' => $this->getOpenGoodsReceipts($filters, $overdueThresholds),
            'purchase_invoices' => $this->getOpenPurchaseInvoices($filters, $overdueThresholds),
            'sales_orders' => $this->getOpenSalesOrders($filters, $overdueThresholds),
            'delivery_orders' => $this->getOpenDeliveryOrders($filters, $overdueThresholds),
            'sales_invoices' => $this->getOpenSalesInvoices($filters, $overdueThresholds),
        ];

        return $openItems;
    }

    /**
     * Get open Purchase Orders
     */
    public function getOpenPurchaseOrders(array $filters = [], array $overdueThresholds = [])
    {
        $query = PurchaseOrder::with(['businessPartner', 'createdBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        $pos = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['po_overdue_days'] ?? 30;
        foreach ($pos as $po) {
            $po->is_overdue = $po->days_open > $overdueDays;
            $po->overdue_days = max(0, $po->days_open - $overdueDays);
        }

        return $pos;
    }

    /**
     * Get open Goods Receipts
     */
    public function getOpenGoodsReceipts(array $filters = [], array $overdueThresholds = [])
    {
        $query = GoodsReceiptPO::with(['businessPartner', 'journalPostedBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        $grpos = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['grpo_overdue_days'] ?? 15;
        foreach ($grpos as $grpo) {
            $grpo->is_overdue = $grpo->days_open > $overdueDays;
            $grpo->overdue_days = max(0, $grpo->days_open - $overdueDays);
        }

        return $grpos;
    }

    /**
     * Get open Purchase Invoices
     */
    public function getOpenPurchaseInvoices(array $filters = [], array $overdueThresholds = [])
    {
        $query = PurchaseInvoice::with(['supplier', 'createdBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where('business_partner_id', $filters['supplier_id']);
        }

        $pis = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['pi_overdue_days'] ?? 7;
        foreach ($pis as $pi) {
            $pi->is_overdue = $pi->days_open > $overdueDays;
            $pi->overdue_days = max(0, $pi->days_open - $overdueDays);
        }

        return $pis;
    }

    /**
     * Get open Sales Orders
     */
    public function getOpenSalesOrders(array $filters = [], array $overdueThresholds = [])
    {
        $query = SalesOrder::with(['customer', 'createdBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        $sos = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['so_overdue_days'] ?? 30;
        foreach ($sos as $so) {
            $so->is_overdue = $so->days_open > $overdueDays;
            $so->overdue_days = max(0, $so->days_open - $overdueDays);
        }

        return $sos;
    }

    /**
     * Get open Delivery Orders
     */
    public function getOpenDeliveryOrders(array $filters = [], array $overdueThresholds = [])
    {
        $query = DeliveryOrder::with(['customer', 'createdBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        $dos = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['do_overdue_days'] ?? 15;
        foreach ($dos as $do) {
            $do->is_overdue = $do->days_open > $overdueDays;
            $do->overdue_days = max(0, $do->days_open - $overdueDays);
        }

        return $dos;
    }

    /**
     * Get open Sales Invoices
     */
    public function getOpenSalesInvoices(array $filters = [], array $overdueThresholds = [])
    {
        $query = SalesInvoice::with(['customer', 'createdBy'])
            ->where('closure_status', 'open')
            ->select('*', DB::raw('DATEDIFF(NOW(), created_at) as days_open'));

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['customer_id'])) {
            $query->where('business_partner_id', $filters['customer_id']);
        }

        $sis = $query->orderBy('created_at', 'desc')->get();

        // Add overdue status
        $overdueDays = $overdueThresholds['si_overdue_days'] ?? 7;
        foreach ($sis as $si) {
            $si->is_overdue = $si->days_open > $overdueDays;
            $si->overdue_days = max(0, $si->days_open - $overdueDays);
        }

        return $sis;
    }

    /**
     * Get overdue thresholds from ERP parameters
     */
    public function getOverdueThresholds()
    {
        $parameters = ErpParameter::where('category', 'document_closure')
            ->where('parameter_key', 'like', '%_overdue_days')
            ->pluck('parameter_value', 'parameter_key')
            ->toArray();

        return [
            'po_overdue_days' => (int) ($parameters['po_overdue_days'] ?? 30),
            'grpo_overdue_days' => (int) ($parameters['grpo_overdue_days'] ?? 15),
            'pi_overdue_days' => (int) ($parameters['pi_overdue_days'] ?? 7),
            'so_overdue_days' => (int) ($parameters['so_overdue_days'] ?? 30),
            'do_overdue_days' => (int) ($parameters['do_overdue_days'] ?? 15),
            'si_overdue_days' => (int) ($parameters['si_overdue_days'] ?? 7),
        ];
    }

    /**
     * Get summary statistics for open items
     */
    public function getOpenItemsSummary(array $filters = [])
    {
        $overdueThresholds = $this->getOverdueThresholds();

        $summary = [
            'total_open_documents' => 0,
            'total_overdue_documents' => 0,
            'total_open_amount' => 0,
            'total_overdue_amount' => 0,
            'by_type' => []
        ];

        $documentTypes = [
            'purchase_orders' => 'Purchase Orders',
            'goods_receipts' => 'Goods Receipts',
            'purchase_invoices' => 'Purchase Invoices',
            'sales_orders' => 'Sales Orders',
            'delivery_orders' => 'Delivery Orders',
            'sales_invoices' => 'Sales Invoices',
        ];

        foreach ($documentTypes as $type => $label) {
            $method = 'getOpen' . str_replace('_', '', ucwords($type, '_'));
            $documents = $this->$method($filters, $overdueThresholds);

            $openCount = $documents->count();
            $overdueCount = $documents->where('is_overdue', true)->count();
            $openAmount = $documents->sum('total_amount');
            $overdueAmount = $documents->where('is_overdue', true)->sum('total_amount');

            $summary['by_type'][$type] = [
                'label' => $label,
                'open_count' => $openCount,
                'overdue_count' => $overdueCount,
                'open_amount' => $openAmount,
                'overdue_amount' => $overdueAmount,
            ];

            $summary['total_open_documents'] += $openCount;
            $summary['total_overdue_documents'] += $overdueCount;
            $summary['total_open_amount'] += $openAmount;
            $summary['total_overdue_amount'] += $overdueAmount;
        }

        return $summary;
    }

    /**
     * Export open items to Excel
     */
    public function exportToExcel(array $filters = [])
    {
        $openItems = $this->getAllOpenItems($filters);
        $summary = $this->getOpenItemsSummary($filters);

        // This would integrate with Laravel Excel package
        // For now, return data structure for manual Excel generation
        return [
            'summary' => $summary,
            'details' => $openItems,
            'exported_at' => now(),
            'filters_applied' => $filters,
        ];
    }
}
