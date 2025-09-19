<?php

namespace App\Services;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerContact;
use App\Models\BusinessPartnerAddress;
use App\Models\BusinessPartnerDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BusinessPartnerService
{
    public function createBusinessPartner($data)
    {
        return DB::transaction(function () use ($data) {
            // Create main business partner record
            $businessPartner = BusinessPartner::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'partner_type' => $data['partner_type'],
                'status' => $data['status'] ?? 'active',
                'registration_number' => $data['registration_number'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'website' => $data['website'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create contacts if provided
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                $this->createContacts($businessPartner, $data['contacts']);
            }

            // Create addresses if provided
            if (isset($data['addresses']) && is_array($data['addresses'])) {
                $this->createAddresses($businessPartner, $data['addresses']);
            }

            // Create details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->createDetails($businessPartner, $data['details']);
            }

            return $businessPartner->load(['contacts', 'addresses', 'details']);
        });
    }

    public function updateBusinessPartner(BusinessPartner $businessPartner, $data)
    {
        return DB::transaction(function () use ($businessPartner, $data) {
            // Update main business partner record
            $businessPartner->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'partner_type' => $data['partner_type'],
                'status' => $data['status'] ?? $businessPartner->status,
                'registration_number' => $data['registration_number'] ?? $businessPartner->registration_number,
                'tax_id' => $data['tax_id'] ?? $businessPartner->tax_id,
                'website' => $data['website'] ?? $businessPartner->website,
                'notes' => $data['notes'] ?? $businessPartner->notes,
            ]);

            // Update contacts if provided
            if (isset($data['contacts']) && is_array($data['contacts'])) {
                $this->updateContacts($businessPartner, $data['contacts']);
            }

            // Update addresses if provided
            if (isset($data['addresses']) && is_array($data['addresses'])) {
                $this->updateAddresses($businessPartner, $data['addresses']);
            }

            // Update details if provided
            if (isset($data['details']) && is_array($data['details'])) {
                $this->updateDetails($businessPartner, $data['details']);
            }

            return $businessPartner->load(['contacts', 'addresses', 'details']);
        });
    }

    public function deleteBusinessPartner(BusinessPartner $businessPartner)
    {
        return DB::transaction(function () use ($businessPartner) {
            // Check if business partner has any transactions
            $hasTransactions = $this->hasTransactions($businessPartner);

            if ($hasTransactions) {
                // Soft delete by changing status to inactive
                $businessPartner->update(['status' => 'inactive']);
                return false; // Indicates soft delete
            } else {
                // Hard delete
                $businessPartner->delete();
                return true; // Indicates hard delete
            }
        });
    }

    public function getBusinessPartnersByType($type, $filters = [])
    {
        $query = BusinessPartner::query();

        // Filter by type
        if ($type === 'customer') {
            $query->customers();
        } elseif ($type === 'supplier') {
            $query->suppliers();
        } elseif ($type === 'both') {
            $query->where('partner_type', 'both');
        }

        // Apply additional filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        return $query->with(['primaryContact', 'primaryAddress'])->get();
    }

    public function getBusinessPartnerWithDetails($id)
    {
        return BusinessPartner::with([
            'contacts',
            'addresses',
            'details',
            'purchaseOrders' => function ($query) {
                $query->latest()->limit(5);
            },
            'salesOrders' => function ($query) {
                $query->latest()->limit(5);
            }
        ])->findOrFail($id);
    }

    protected function createContacts(BusinessPartner $businessPartner, $contacts)
    {
        foreach ($contacts as $contactData) {
            BusinessPartnerContact::create([
                'business_partner_id' => $businessPartner->id,
                'contact_type' => $contactData['contact_type'],
                'name' => $contactData['name'],
                'position' => $contactData['position'] ?? null,
                'email' => $contactData['email'] ?? null,
                'phone' => $contactData['phone'] ?? null,
                'mobile' => $contactData['mobile'] ?? null,
                'is_primary' => $contactData['is_primary'] ?? false,
                'notes' => $contactData['notes'] ?? null,
            ]);
        }
    }

    protected function createAddresses(BusinessPartner $businessPartner, $addresses)
    {
        foreach ($addresses as $addressData) {
            BusinessPartnerAddress::create([
                'business_partner_id' => $businessPartner->id,
                'address_type' => $addressData['address_type'],
                'address_line_1' => $addressData['address_line_1'],
                'address_line_2' => $addressData['address_line_2'] ?? null,
                'city' => $addressData['city'],
                'state_province' => $addressData['state_province'] ?? null,
                'postal_code' => $addressData['postal_code'] ?? null,
                'country' => $addressData['country'] ?? 'Indonesia',
                'is_primary' => $addressData['is_primary'] ?? false,
                'notes' => $addressData['notes'] ?? null,
            ]);
        }
    }

    protected function createDetails(BusinessPartner $businessPartner, $details)
    {
        foreach ($details as $detailData) {
            BusinessPartnerDetail::create([
                'business_partner_id' => $businessPartner->id,
                'section_type' => $detailData['section_type'],
                'field_name' => $detailData['field_name'],
                'field_value' => $detailData['field_value'],
                'field_type' => $detailData['field_type'] ?? 'text',
                'is_required' => $detailData['is_required'] ?? false,
                'sort_order' => $detailData['sort_order'] ?? 0,
            ]);
        }
    }

    protected function updateContacts(BusinessPartner $businessPartner, $contacts)
    {
        // Delete existing contacts
        $businessPartner->contacts()->delete();

        // Create new contacts
        $this->createContacts($businessPartner, $contacts);
    }

    protected function updateAddresses(BusinessPartner $businessPartner, $addresses)
    {
        // Delete existing addresses
        $businessPartner->addresses()->delete();

        // Create new addresses
        $this->createAddresses($businessPartner, $addresses);
    }

    protected function updateDetails(BusinessPartner $businessPartner, $details)
    {
        // Delete existing details
        $businessPartner->details()->delete();

        // Create new details
        $this->createDetails($businessPartner, $details);
    }

    protected function hasTransactions(BusinessPartner $businessPartner)
    {
        $count = 0;
        $count += $businessPartner->purchaseOrders()->count();
        $count += $businessPartner->salesOrders()->count();
        $count += $businessPartner->deliveryOrders()->count();
        $count += $businessPartner->purchaseInvoices()->count();
        $count += $businessPartner->salesInvoices()->count();
        $count += $businessPartner->purchasePayments()->count();
        $count += $businessPartner->salesReceipts()->count();
        $count += $businessPartner->goodsReceipts()->count();
        $count += $businessPartner->assets()->count();

        return $count > 0;
    }

    public function getBusinessPartnerStatistics()
    {
        return [
            'total' => BusinessPartner::count(),
            'customers' => BusinessPartner::customers()->count(),
            'suppliers' => BusinessPartner::suppliers()->count(),
            'both' => BusinessPartner::where('partner_type', 'both')->count(),
            'active' => BusinessPartner::active()->count(),
            'inactive' => BusinessPartner::where('status', 'inactive')->count(),
        ];
    }

    public function searchBusinessPartners($search, $type = null)
    {
        $query = BusinessPartner::query();

        if ($type) {
            if ($type === 'customer') {
                $query->customers();
            } elseif ($type === 'supplier') {
                $query->suppliers();
            } else {
                $query->where('partner_type', $type);
            }
        }

        $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('registration_number', 'like', "%{$search}%");
        });

        return $query->active()->limit(20)->get(['id', 'code', 'name', 'partner_type']);
    }
}
