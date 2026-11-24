@php
    $entity = $entity ?? \App\Models\CompanyEntity::where('name', 'PT Cahaya Sarange Jaya')->first();
    $companyName = $entity?->name ?? 'PT Cahaya Sarange Jaya';
    $kodePosBalikpapan = '76114';
    $kodePosJakarta = '12560';
    $csjHeadOfficeLine = 'Jl. M.T. Haryono Ruko Haryono Palace No.25, Gn. Bahagia, Gn. Samarinda, Kecamatan Balikpapan Selatan, Kota Balikpapan, Kalimantan Timur';
    $csjBranchOfficeLine = 'Jl. Raya Cilandak KKO, Komplek Vico Kav. 12, Ps. Minggu, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta';
    $csjHeadOfficeDisplay = $csjHeadOfficeLine.' '.$kodePosBalikpapan;
    $csjBranchOfficeDisplay = $csjBranchOfficeLine.' '.$kodePosJakarta;
    $csjEmail = 'info@cahayasarangejaya.co.id';
    $companyPhone = $entity?->phone ?? '';
    $letterheadVariant = $letterheadVariant ?? 'standard';
    $companyTaxNumber = $entity?->tax_number ?? '';
@endphp

@if ($letterheadVariant === 'purchase_order')
    <div class="company-header">
        <div class="company-left">
            @if (file_exists(public_path('logo_pt_csj_transparan.jpeg')))
                <div class="company-logo">
                    <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="{{ $companyName }}">
                </div>
            @endif
            <div>
                <div class="company-legal">{{ $companyName }}</div>
            </div>
        </div>
        <div class="company-offices">
            <strong>Head Office:</strong>
            {{ $csjHeadOfficeDisplay }}<br>
            <strong>Branch Office:</strong>
            {{ $csjBranchOfficeDisplay }}<br>
            <strong>Email:</strong> {{ $csjEmail }}
            @if ($companyPhone)
                <br><strong>Telp:</strong> {{ $companyPhone }}
            @endif
        </div>
    </div>
@elseif ($letterheadVariant === 'dotmatrix')
    <div class="company-header">
        @if (file_exists(public_path('logo_pt_csj_transparan.jpeg')))
            <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ $companyName }}</div>
        <div class="company-details">
            <strong>Head Office:</strong> {{ $csjHeadOfficeDisplay }}<br>
            <strong>Branch Office:</strong> {{ $csjBranchOfficeDisplay }}<br>
            {{ $csjEmail }}@if ($companyPhone) · Telp: {{ $companyPhone }}@endif
        </div>
    </div>
@else
    <div class="company-header">
        @if (file_exists(public_path('logo_pt_csj_transparan.jpeg')))
            <div class="company-logo">
                <img src="{{ asset('logo_pt_csj_transparan.jpeg') }}" alt="{{ $companyName }}">
            </div>
        @endif
        <div class="company-info">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-details">
                <strong>Head Office:</strong> {{ $csjHeadOfficeDisplay }}<br>
                <strong>Branch Office:</strong> {{ $csjBranchOfficeDisplay }}<br>
                Email: {{ $csjEmail }}@if ($companyPhone) · Telp: {{ $companyPhone }}@endif
                @if ($companyTaxNumber)
                    <br>Tax ID: {{ $companyTaxNumber }}
                @endif
            </div>
        </div>
    </div>
@endif