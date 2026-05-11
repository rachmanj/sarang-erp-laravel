<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\InvoiceCollection;
use App\Http\Resources\Api\Customer\InvoiceResource;
use App\Models\Accounting\SalesInvoice;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request): InvoiceCollection
    {
        /** @var BusinessPartner $partner */
        $partner = $request->attributes->get('api_business_partner');

        $request->validate([
            'status' => ['sometimes', 'string', 'max:50'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        if ($request->filled('date_from') && $request->filled('date_to')) {
            if ($request->date('date_to')->lt($request->date('date_from'))) {
                throw ValidationException::withMessages([
                    'date_to' => ['The date to must be after or equal to date from.'],
                ]);
            }
        }

        $query = SalesInvoice::query()
            ->where('business_partner_id', $partner->id)
            ->with(['currency'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to')->toDateString());
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return new InvoiceCollection($query->paginate($perPage));
    }

    public function show(Request $request, string $invoice_no): InvoiceResource
    {
        /** @var BusinessPartner $partner */
        $partner = $request->attributes->get('api_business_partner');

        $invoice = SalesInvoice::query()
            ->where('business_partner_id', $partner->id)
            ->where('invoice_no', $invoice_no)
            ->with(['lines', 'currency'])
            ->firstOrFail();

        return new InvoiceResource($invoice);
    }
}
