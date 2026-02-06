<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use App\Services\BusinessPartnerService;
use App\Services\BusinessPartnerJournalService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BusinessPartnerController extends Controller
{
    protected $businessPartnerService;

    public function __construct(BusinessPartnerService $businessPartnerService)
    {
        $this->middleware(['auth'])->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);

        $this->businessPartnerService = $businessPartnerService;
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $statistics = $this->businessPartnerService->getBusinessPartnerStatistics();

        return view('business_partners.index', compact('type', 'statistics'));
    }

    public function create()
    {
        return view('business_partners.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:business_partners,code'],
            'name' => ['required', 'string', 'max:150'],
            'partner_type' => ['required', 'in:customer,supplier'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'registration_number' => ['nullable', 'string', 'max:30'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],

            // Contacts validation
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_type' => ['required_with:contacts', 'in:primary,billing,shipping,technical,sales,support'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:150'],
            'contacts.*.position' => ['nullable', 'string', 'max:100'],
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:50'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],

            // Addresses validation
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required_with:addresses', 'in:billing,shipping,registered,warehouse,office'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.notes' => ['nullable', 'string'],

            // Details validation
            'details' => ['nullable', 'array'],
            'details.*.section_type' => ['required_with:details', 'in:taxation,terms,banking,financial,preferences,custom'],
            'details.*.field_name' => ['required_with:details', 'string', 'max:100'],
            'details.*.field_value' => ['nullable', 'string'],
            'details.*.field_type' => ['nullable', 'in:text,number,date,boolean,json'],
            'details.*.is_required' => ['nullable', 'boolean'],
            'details.*.sort_order' => ['nullable', 'integer'],
        ]);

        try {
            $businessPartner = $this->businessPartnerService->createBusinessPartner($data);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Business Partner created successfully',
                    'data' => $businessPartner
                ]);
            }

            return redirect()->route('business_partners.show', $businessPartner)
                ->with('success', 'Business Partner created successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating Business Partner: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error creating Business Partner: ' . $e->getMessage());
        }
    }

    public function show(BusinessPartner $businessPartner)
    {
        $businessPartner = $this->businessPartnerService->getBusinessPartnerWithDetails($businessPartner->id);

        return view('business_partners.show', compact('businessPartner'));
    }

    public function edit(BusinessPartner $businessPartner)
    {
        $businessPartner = $this->businessPartnerService->getBusinessPartnerWithDetails($businessPartner->id);

        return view('business_partners.edit', compact('businessPartner'));
    }

    public function update(Request $request, BusinessPartner $businessPartner)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:business_partners,code,' . $businessPartner->id],
            'name' => ['required', 'string', 'max:150'],
            'partner_type' => ['required', 'in:customer,supplier'],
            'status' => ['nullable', 'in:active,inactive,suspended'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'registration_number' => ['nullable', 'string', 'max:30'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string'],

            // Contacts validation
            'contacts' => ['nullable', 'array'],
            'contacts.*.contact_type' => ['required_with:contacts', 'in:primary,billing,shipping,technical,sales,support'],
            'contacts.*.name' => ['required_with:contacts', 'string', 'max:150'],
            'contacts.*.position' => ['nullable', 'string', 'max:100'],
            'contacts.*.email' => ['nullable', 'email', 'max:150'],
            'contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:50'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.notes' => ['nullable', 'string'],

            // Addresses validation
            'addresses' => ['nullable', 'array'],
            'addresses.*.address_type' => ['required_with:addresses', 'in:billing,shipping,registered,warehouse,office'],
            'addresses.*.address_line_1' => ['required_with:addresses', 'string', 'max:255'],
            'addresses.*.address_line_2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['required_with:addresses', 'string', 'max:100'],
            'addresses.*.state_province' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
            'addresses.*.is_primary' => ['nullable', 'boolean'],
            'addresses.*.notes' => ['nullable', 'string'],

            // Details validation
            'details' => ['nullable', 'array'],
            'details.*.section_type' => ['required_with:details', 'in:taxation,terms,banking,financial,preferences,custom'],
            'details.*.field_name' => ['required_with:details', 'string', 'max:100'],
            'details.*.field_value' => ['nullable', 'string'],
            'details.*.field_type' => ['nullable', 'in:text,number,date,boolean,json'],
            'details.*.is_required' => ['nullable', 'boolean'],
            'details.*.sort_order' => ['nullable', 'integer'],
        ]);

        try {
            $businessPartner = $this->businessPartnerService->updateBusinessPartner($businessPartner, $data);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Business Partner updated successfully',
                    'data' => $businessPartner
                ]);
            }

            return redirect()->route('business_partners.show', $businessPartner)
                ->with('success', 'Business Partner updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating Business Partner: ' . $e->getMessage()
                ], 500);
            }

            return back()->withInput()
                ->with('error', 'Error updating Business Partner: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, BusinessPartner $businessPartner)
    {
        try {
            $hardDelete = $this->businessPartnerService->deleteBusinessPartner($businessPartner);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $hardDelete ? 'Business Partner deleted successfully' : 'Business Partner deactivated successfully',
                    'hard_delete' => $hardDelete
                ]);
            }

            $message = $hardDelete ? 'Business Partner deleted successfully' : 'Business Partner deactivated successfully';
            return redirect()->route('business_partners.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting Business Partner: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Error deleting Business Partner: ' . $e->getMessage());
        }
    }

    public function data(Request $request)
    {
        $type = $request->get('type', 'all');

        $query = BusinessPartner::query();

        if ($type !== 'all') {
            if ($type === 'customer') {
                $query->customers();
            } elseif ($type === 'supplier') {
                $query->suppliers();
            } else {
                $query->where('partner_type', $type);
            }
        }

        return DataTables::of($query)
            ->addColumn('partner_type_badge', function ($businessPartner) {
                $badges = [
                    'customer' => '<span class="badge badge-info">Customer</span>',
                    'supplier' => '<span class="badge badge-warning">Supplier</span>',
                ];
                return $badges[$businessPartner->partner_type] ?? '';
            })
            ->addColumn('status_badge', function ($businessPartner) {
                $badges = [
                    'active' => '<span class="badge badge-success">Active</span>',
                    'inactive' => '<span class="badge badge-secondary">Inactive</span>',
                    'suspended' => '<span class="badge badge-danger">Suspended</span>',
                ];
                return $badges[$businessPartner->status] ?? '';
            })
            ->addColumn('primary_contact', function ($businessPartner) {
                $contact = $businessPartner->primaryContact;
                return $contact ? $contact->full_contact : '-';
            })
            ->addColumn('primary_address', function ($businessPartner) {
                $address = $businessPartner->primaryAddress;
                return $address ? $address->short_address : '-';
            })
            ->addColumn('actions', function ($businessPartner) {
                return view('business_partners.partials.actions', compact('businessPartner'))->render();
            })
            ->rawColumns(['partner_type_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function search(Request $request)
    {
        $search = $request->get('q', '');
        $type = $request->get('type');

        $businessPartners = $this->businessPartnerService->searchBusinessPartners($search, $type);

        return response()->json($businessPartners);
    }

    public function getByType(Request $request)
    {
        $type = $request->get('type', 'customer');
        $businessPartners = $this->businessPartnerService->getBusinessPartnersByType($type);

        return response()->json($businessPartners);
    }

    public function journalHistory(Request $request, BusinessPartner $businessPartner)
    {
        $this->middleware('can:business_partners.journal_history');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 25);

        $journalService = new BusinessPartnerJournalService($businessPartner);
        $journalData = $journalService->getJournalHistory($startDate, $endDate, $page, $perPage);

        if ($request->wantsJson()) {
            return response()->json($journalData);
        }

        return view('business_partners.journal_history', compact('businessPartner', 'journalData'));
    }

    public function getPaymentTerms(BusinessPartner $businessPartner)
    {
        $paymentTermsDays = $businessPartner->getPaymentTermsDays();
        
        return response()->json([
            'success' => true,
            'payment_terms_days' => $paymentTermsDays,
        ]);
    }
}
