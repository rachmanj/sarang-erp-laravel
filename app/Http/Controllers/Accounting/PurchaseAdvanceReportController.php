<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PurchaseAdvanceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:ap.payments.view']);
    }

    public function report()
    {
        $today = now()->toDateString();

        $advances = DB::table('purchase_advances as pa')
            ->join('business_partners as bp', 'bp.id', '=', 'pa.business_partner_id')
            ->leftJoin('purchase_advance_applications as paa', 'paa.advance_id', '=', 'pa.id')
            ->where('pa.status', 'posted')
            ->groupBy(
                'pa.id',
                'pa.advance_no',
                'pa.date',
                'pa.business_partner_id',
                'bp.name',
                'pa.total_amount'
            )
            ->select(
                'pa.id',
                'pa.advance_no',
                'pa.date',
                'pa.business_partner_id',
                'bp.name as vendor_name',
                'pa.total_amount',
                DB::raw('COALESCE(SUM(paa.amount), 0) as used_amount')
            )
            ->orderBy('bp.name')
            ->orderBy('pa.date')
            ->get();

        $supplierSummary = [];
        foreach ($advances as $row) {
            $vendorId = (int) $row->business_partner_id;
            if (! isset($supplierSummary[$vendorId])) {
                $supplierSummary[$vendorId] = [
                    'vendor_name' => $row->vendor_name,
                    'total_posted' => 0.0,
                    'total_used' => 0.0,
                    'remaining' => 0.0,
                    'aging' => [
                        '0_30' => 0.0,
                        '31_60' => 0.0,
                        '61_90' => 0.0,
                        'over_90' => 0.0,
                    ],
                ];
            }

            $remaining = max(0, (float) $row->total_amount - (float) $row->used_amount);
            $supplierSummary[$vendorId]['total_posted'] += (float) $row->total_amount;
            $supplierSummary[$vendorId]['total_used'] += (float) $row->used_amount;
            $supplierSummary[$vendorId]['remaining'] += $remaining;

            if ($remaining > 0.009) {
                $days = (int) \Carbon\Carbon::parse($row->date)->diffInDays(\Carbon\Carbon::parse($today));
                $bucket = match (true) {
                    $days <= 30 => '0_30',
                    $days <= 60 => '31_60',
                    $days <= 90 => '61_90',
                    default => 'over_90',
                };
                $supplierSummary[$vendorId]['aging'][$bucket] += $remaining;
            }
        }

        $applications = DB::table('purchase_advance_applications as paa')
            ->join('purchase_advances as pa', 'pa.id', '=', 'paa.advance_id')
            ->join('purchase_invoices as pi', 'pi.id', '=', 'paa.invoice_id')
            ->join('business_partners as bp', 'bp.id', '=', 'pa.business_partner_id')
            ->leftJoin('purchase_payments as pp', 'pp.id', '=', 'paa.payment_id')
            ->select(
                'bp.name as vendor_name',
                'pa.advance_no',
                'pi.invoice_no',
                'paa.amount',
                DB::raw('COALESCE(pp.date, paa.created_at) as application_date')
            )
            ->orderByDesc('application_date')
            ->orderBy('pa.advance_no')
            ->get();

        return view('purchase_advances.report', compact('supplierSummary', 'applications'));
    }
}
