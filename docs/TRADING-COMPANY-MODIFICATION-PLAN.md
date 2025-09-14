# Sarange ERP Modification Plan for Trading Company (Perusahaan Dagang)

## Indonesian Tax Compliance & PSAK Compliance

**Purpose**: Comprehensive modification plan to adapt Sarange ERP for trading company operations while ensuring compliance with Indonesian tax regulations and PSAK (Pernyataan Standar Akuntansi Keuangan)

**Date**: 2025-01-15
**Target**: Trading Company (Perusahaan Dagang) Operations

---

## Executive Summary

Sarange ERP requires significant modifications to support trading company operations effectively. The current system is designed for general business operations but lacks specific features required for trading companies such as inventory management, cost of goods sold tracking, and comprehensive tax compliance features.

### Key Modifications Required:

1. **Chart of Accounts Restructuring** - PSAK-compliant structure for trading companies
2. **Inventory Management Enhancement** - Real-time stock tracking and valuation
3. **Tax Compliance Features** - PPN, PPh, and withholding tax automation
4. **Cost of Goods Sold (COGS) Tracking** - Accurate HPP calculation
5. **Trading-Specific Reporting** - Margin analysis, inventory turnover, etc.

---

## 1. Chart of Accounts (CoA) Restructuring

### Current Issues:

-   Current CoA is designed for non-profit/training organizations
-   Missing trading-specific accounts (inventory, COGS, trading margins)
-   Insufficient detail for trading operations

### Recommended PSAK-Compliant Chart of Accounts:

```php
// Assets (1)
'1' => 'Aset',
'1.1' => 'Aset Lancar',
'1.1.1' => 'Kas dan Setara Kas',
'1.1.1.01' => 'Kas di Tangan',
'1.1.1.02' => 'Kas di Bank - Operasional',
'1.1.1.03' => 'Kas di Bank - Investasi',
'1.1.2' => 'Piutang Usaha',
'1.1.2.01' => 'Piutang Dagang',
'1.1.2.02' => 'Piutang Lain-lain',
'1.1.2.03' => 'Cadangan Kerugian Piutang',
'1.1.3' => 'Persediaan',
'1.1.3.01' => 'Persediaan Barang Dagangan',
'1.1.3.02' => 'Persediaan dalam Perjalanan',
'1.1.3.03' => 'Persediaan Konsinyasi',
'1.1.4' => 'Pajak Dibayar Dimuka',
'1.1.4.01' => 'PPN Masukan',
'1.1.4.02' => 'PPh Pasal 22 Dibayar Dimuka',
'1.1.4.03' => 'PPh Pasal 23 Dibayar Dimuka',
'1.1.5' => 'Biaya Dibayar Dimuka',
'1.1.5.01' => 'Sewa Dibayar Dimuka',
'1.1.5.02' => 'Asuransi Dibayar Dimuka',
'1.1.5.03' => 'Biaya Lain Dibayar Dimuka',

'1.2' => 'Aset Tidak Lancar',
'1.2.1' => 'Aset Tetap',
'1.2.1.01' => 'Tanah',
'1.2.1.02' => 'Bangunan',
'1.2.1.03' => 'Akumulasi Penyusutan Bangunan',
'1.2.1.04' => 'Kendaraan',
'1.2.1.05' => 'Akumulasi Penyusutan Kendaraan',
'1.2.1.06' => 'Peralatan Kantor',
'1.2.1.07' => 'Akumulasi Penyusutan Peralatan Kantor',
'1.2.2' => 'Aset Tidak Berwujud',
'1.2.2.01' => 'Goodwill',
'1.2.2.02' => 'Merek Dagang',
'1.2.2.03' => 'Lisensi',

// Liabilities (2)
'2' => 'Kewajiban',
'2.1' => 'Kewajiban Lancar',
'2.1.1' => 'Utang Usaha',
'2.1.1.01' => 'Utang Dagang',
'2.1.1.02' => 'Utang Lain-lain',
'2.1.2' => 'Utang Pajak',
'2.1.2.01' => 'PPN Keluaran',
'2.1.2.02' => 'PPh Pasal 21',
'2.1.2.03' => 'PPh Pasal 22',
'2.1.2.04' => 'PPh Pasal 23',
'2.1.2.05' => 'PPh Pasal 25',
'2.1.3' => 'Utang Jangka Pendek',
'2.1.3.01' => 'Utang Bank Jangka Pendek',
'2.1.3.02' => 'Utang Sewa Jangka Pendek',
'2.1.4' => 'Biaya yang Masih Harus Dibayar',
'2.1.4.01' => 'Gaji yang Masih Harus Dibayar',
'2.1.4.02' => 'Bunga yang Masih Harus Dibayar',

'2.2' => 'Kewajiban Tidak Lancar',
'2.2.1' => 'Utang Jangka Panjang',
'2.2.1.01' => 'Utang Bank Jangka Panjang',
'2.2.1.02' => 'Obligasi',

// Equity (3)
'3' => 'Ekuitas',
'3.1' => 'Modal Saham',
'3.1.1' => 'Modal Saham Biasa',
'3.1.2' => 'Modal Saham Preferen',
'3.2' => 'Agio/Disagio Saham',
'3.2.1' => 'Agio Saham',
'3.2.2' => 'Disagio Saham',
'3.3' => 'Laba Ditahan',
'3.3.1' => 'Saldo Awal Laba Ditahan',
'3.3.2' => 'Laba Tahun Berjalan',

// Revenue (4)
'4' => 'Pendapatan',
'4.1' => 'Pendapatan Usaha',
'4.1.1' => 'Penjualan Barang Dagangan',
'4.1.1.01' => 'Penjualan Tunai',
'4.1.1.02' => 'Penjualan Kredit',
'4.1.2' => 'Retur Penjualan',
'4.1.3' => 'Diskon Penjualan',
'4.1.4' => 'Potongan Penjualan',
'4.2' => 'Pendapatan Lain-lain',
'4.2.1' => 'Pendapatan Sewa',
'4.2.2' => 'Pendapatan Bunga',
'4.2.3' => 'Pendapatan Kurs Selisih',

// Cost of Goods Sold (5)
'5' => 'Harga Pokok Penjualan',
'5.1' => 'Pembelian Barang Dagangan',
'5.1.1' => 'Pembelian Tunai',
'5.1.2' => 'Pembelian Kredit',
'5.2' => 'Retur Pembelian',
'5.3' => 'Diskon Pembelian',
'5.4' => 'Potongan Pembelian',
'5.5' => 'Biaya Pengiriman Masuk',
'5.6' => 'Biaya Asuransi Masuk',
'5.7' => 'Penyesuaian Persediaan',

// Operating Expenses (6)
'6' => 'Beban Operasional',
'6.1' => 'Beban Penjualan',
'6.1.1' => 'Gaji Karyawan Penjualan',
'6.1.2' => 'Komisi Penjualan',
'6.1.3' => 'Biaya Iklan dan Promosi',
'6.1.4' => 'Biaya Pengiriman Keluar',
'6.1.5' => 'Biaya Asuransi Keluar',
'6.1.6' => 'Biaya Pameran',
'6.2' => 'Beban Administrasi dan Umum',
'6.2.1' => 'Gaji Karyawan Administrasi',
'6.2.2' => 'Biaya Sewa Kantor',
'6.2.3' => 'Biaya Listrik dan Air',
'6.2.4' => 'Biaya Telepon dan Internet',
'6.2.5' => 'Biaya Perjalanan Dinas',
'6.2.6' => 'Biaya Konsultan',
'6.2.7' => 'Biaya Legal dan Notaris',
'6.2.8' => 'Biaya Audit',
'6.2.9' => 'Biaya Penyusutan',
'6.2.10' => 'Biaya Asuransi',
'6.2.11' => 'Biaya Pemeliharaan',
'6.2.12' => 'Biaya Lain-lain',

// Other Income/Expenses (7)
'7' => 'Pendapatan dan Beban Lain-lain',
'7.1' => 'Pendapatan Lain-lain',
'7.1.1' => 'Pendapatan Sewa',
'7.1.2' => 'Pendapatan Bunga',
'7.1.3' => 'Keuntungan Selisih Kurs',
'7.2' => 'Beban Lain-lain',
'7.2.1' => 'Kerugian Selisih Kurs',
'7.2.2' => 'Beban Bunga',
'7.2.3' => 'Kerugian Penjualan Aset',
```

---

## 2. Areas for Improvement

### 2.1 Inventory Management System

**Current Gap**: Limited inventory tracking capabilities
**Required Enhancements**:

-   Real-time inventory tracking
-   Multiple inventory valuation methods (FIFO, LIFO, Weighted Average)
-   Inventory aging and turnover analysis
-   Automated reorder point management
-   Batch/lot tracking for perishable goods
-   Inventory adjustment and cycle counting

### 2.2 Tax Compliance Features

**Current Gap**: Basic tax handling, insufficient for Indonesian compliance
**Required Enhancements**:

-   **PPN (VAT) Management**:
    -   Automatic PPN calculation (11% as of 2024)
    -   PPN Masukan and PPN Keluaran tracking
    -   Monthly PPN reporting (SPT Masa PPN)
    -   E-Faktur integration capability
-   **PPh Management**:
    -   PPh Pasal 21 (Employee tax)
    -   PPh Pasal 22 (Import tax)
    -   PPh Pasal 23 (Withholding tax on services)
    -   PPh Pasal 25 (Monthly tax installment)
-   **Tax Reporting**:
    -   SPT Tahunan (Annual tax return)
    -   Real-time tax liability tracking
    -   Tax reconciliation reports

### 2.3 Cost of Goods Sold (COGS) Tracking

**Current Gap**: No dedicated COGS module
**Required Enhancements**:

-   Automatic COGS calculation on sales
-   Purchase cost tracking
-   Freight and handling cost allocation
-   Inventory valuation methods integration
-   Margin analysis and reporting

### 2.4 Trading-Specific Features

**Current Gap**: Generic business features
**Required Enhancements**:

-   **Purchase Order Management**:
    -   Multi-supplier comparison
    -   Purchase approval workflow
    -   Supplier performance tracking
-   **Sales Order Management**:
    -   Customer credit limit management
    -   Sales commission tracking
    -   Customer pricing tiers
-   **Margin Analysis**:
    -   Product-wise margin tracking
    -   Customer profitability analysis
    -   Supplier cost analysis

### 2.5 Reporting Enhancements

**Current Gap**: Basic financial reports
**Required Enhancements**:

-   **Trading-Specific Reports**:
    -   Gross Profit Analysis
    -   Inventory Turnover Report
    -   Supplier Performance Report
    -   Customer Profitability Analysis
    -   Margin Analysis by Product/Customer
-   **Compliance Reports**:
    -   Indonesian financial statements format
    -   Tax reconciliation reports
    -   PSAK-compliant reporting

---

## 3. Action Plan

### Phase 1: Foundation Setup (Weeks 1-2)

**Priority**: P0 - Critical

#### 1.1 Database Schema Modifications

-   [ ] Create new migration for trading company CoA
-   [ ] Modify existing accounts table to support trading operations
-   [ ] Add inventory-related tables:
    -   `inventory_items` (product master data)
    -   `inventory_transactions` (stock movements)
    -   `inventory_valuations` (cost tracking)
-   [ ] Add tax-related tables:
    -   `tax_codes` (PPN, PPh rates)
    -   `tax_transactions` (tax calculations)
    -   `tax_reports` (compliance tracking)

#### 1.2 Chart of Accounts Implementation

-   [ ] Create `TradingCoASeeder.php` with PSAK-compliant structure
-   [ ] Implement account hierarchy validation
-   [ ] Add account type validations for trading operations
-   [ ] Create account mapping for existing transactions

### Phase 2: Core Trading Features (Weeks 3-6)

**Priority**: P1 - Important

#### 2.1 Inventory Management Module

-   [ ] Create `InventoryController` with CRUD operations
-   [ ] Implement inventory transaction tracking
-   [ ] Add inventory valuation methods (FIFO, LIFO, Weighted Average)
-   [ ] Create inventory adjustment functionality
-   [ ] Implement stock level monitoring and alerts

#### 2.2 Enhanced Purchase Management

-   [ ] Modify `PurchaseOrderController` for trading operations
-   [ ] Add supplier comparison features
-   [ ] Implement purchase approval workflow
-   [ ] Add freight and handling cost tracking
-   [ ] Create supplier performance tracking

#### 2.3 Enhanced Sales Management

-   [ ] Modify `SalesOrderController` for trading operations
-   [ ] Add customer credit limit management
-   [ ] Implement sales commission tracking
-   [ ] Add customer pricing tiers
-   [ ] Create customer profitability tracking

### Phase 3: Tax Compliance (Weeks 7-10)

**Priority**: P1 - Important

#### 3.1 PPN (VAT) Management

-   [ ] Create `PPNController` for VAT management
-   [ ] Implement automatic PPN calculation (11%)
-   [ ] Add PPN Masukan and Keluaran tracking
-   [ ] Create monthly PPN reporting
-   [ ] Implement E-Faktur integration preparation

#### 3.2 PPh Management

-   [ ] Create `PPhController` for income tax management
-   [ ] Implement PPh Pasal 21 (employee tax)
-   [ ] Add PPh Pasal 22 (import tax)
-   [ ] Implement PPh Pasal 23 (withholding tax)
-   [ ] Add PPh Pasal 25 (monthly installment)

#### 3.3 Tax Reporting System

-   [ ] Create tax reconciliation reports
-   [ ] Implement SPT Tahunan preparation
-   [ ] Add real-time tax liability tracking
-   [ ] Create tax compliance dashboard

### Phase 4: COGS and Margin Analysis (Weeks 11-14)

**Priority**: P1 - Important

#### 4.1 Cost of Goods Sold Module

-   [ ] Create `COGSController` for cost tracking
-   [ ] Implement automatic COGS calculation
-   [ ] Add purchase cost allocation
-   [ ] Create freight and handling cost distribution
-   [ ] Implement inventory valuation integration

#### 4.2 Margin Analysis System

-   [ ] Create margin analysis reports
-   [ ] Implement product-wise margin tracking
-   [ ] Add customer profitability analysis
-   [ ] Create supplier cost analysis
-   [ ] Implement gross profit analysis

### Phase 5: Reporting and Analytics (Weeks 15-18)

**Priority**: P2 - Nice to have

#### 5.1 Trading-Specific Reports

-   [ ] Create gross profit analysis reports
-   [ ] Implement inventory turnover analysis
-   [ ] Add supplier performance reports
-   [ ] Create customer profitability reports
-   [ ] Implement margin analysis by product/customer

#### 5.2 PSAK-Compliant Financial Statements

-   [ ] Create Indonesian format financial statements
-   [ ] Implement PSAK-compliant reporting
-   [ ] Add comparative period reporting
-   [ ] Create management dashboard
-   [ ] Implement export to Excel/PDF

### Phase 6: Testing and Deployment (Weeks 19-20)

**Priority**: P0 - Critical

#### 6.1 Testing Phase

-   [ ] Unit testing for all new modules
-   [ ] Integration testing with existing system
-   [ ] User acceptance testing
-   [ ] Performance testing
-   [ ] Security testing

#### 6.2 Deployment Preparation

-   [ ] Create deployment documentation
-   [ ] Prepare user training materials
-   [ ] Create system administration guide
-   [ ] Prepare data migration scripts
-   [ ] Create rollback procedures

---

## 4. Technical Implementation Details

### 4.1 Database Modifications Required

```sql
-- New tables for trading operations
CREATE TABLE inventory_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id BIGINT,
    unit_of_measure VARCHAR(50),
    purchase_price DECIMAL(15,2),
    selling_price DECIMAL(15,2),
    min_stock_level INT DEFAULT 0,
    max_stock_level INT DEFAULT 0,
    reorder_point INT DEFAULT 0,
    valuation_method ENUM('fifo', 'lifo', 'weighted_average') DEFAULT 'fifo',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE inventory_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    item_id BIGINT NOT NULL,
    transaction_type ENUM('purchase', 'sale', 'adjustment', 'transfer') NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    reference_type VARCHAR(50), -- 'purchase_order', 'sales_order', etc.
    reference_id BIGINT,
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE tax_codes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('ppn', 'pph_21', 'pph_22', 'pph_23', 'pph_25') NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    effective_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 4.2 Controller Modifications Required

```php
// New controllers needed
- InventoryController
- COGSController
- PPNController
- PPhController
- MarginAnalysisController
- TradingReportsController

// Existing controllers to modify
- PurchaseOrderController (add trading features)
- SalesOrderController (add trading features)
- ReportsController (add trading reports)
```

### 4.3 Service Layer Additions

```php
// New services needed
- InventoryService
- COGSService
- TaxCalculationService
- MarginAnalysisService
- TradingReportService
```

---

## 5. Compliance Requirements

### 5.1 PSAK Compliance

-   **PSAK 1**: Presentation of Financial Statements
-   **PSAK 2**: Inventories (for trading companies)
-   **PSAK 15**: Revenue Recognition
-   **PSAK 16**: Property, Plant and Equipment
-   **PSAK 19**: Employee Benefits
-   **PSAK 23**: Revenue from Contracts with Customers

### 5.2 Indonesian Tax Compliance

-   **PPN (VAT)**: 11% rate, monthly reporting
-   **PPh**: Various rates based on transaction type
-   **SPT Tahunan**: Annual tax return format
-   **E-Faktur**: Electronic invoice system integration
-   **Tax Reconciliation**: Monthly tax liability tracking

---

## 6. Risk Assessment

### 6.1 High Risk Items

-   **Data Migration**: Existing transaction data compatibility
-   **Tax Compliance**: Accuracy of tax calculations
-   **Inventory Valuation**: Method consistency and accuracy
-   **User Training**: Adoption of new features

### 6.2 Mitigation Strategies

-   **Phased Implementation**: Gradual rollout to minimize disruption
-   **Comprehensive Testing**: Thorough testing before production
-   **Backup Procedures**: Complete data backup before migration
-   **Training Program**: Extensive user training and documentation

---

## 7. Success Metrics

### 7.1 Functional Metrics

-   [ ] 100% PSAK-compliant financial reporting
-   [ ] 100% Indonesian tax compliance
-   [ ] Real-time inventory tracking accuracy
-   [ ] Automated COGS calculation accuracy
-   [ ] Tax calculation accuracy (99.9%)

### 7.2 Performance Metrics

-   [ ] System response time < 2 seconds
-   [ ] Report generation time < 30 seconds
-   [ ] 99.9% system uptime
-   [ ] User satisfaction score > 4.5/5

---

## 8. Budget Estimation

### 8.1 Development Costs

-   **Phase 1-2**: 6 weeks × 2 developers = 12 developer-weeks
-   **Phase 3-4**: 8 weeks × 2 developers = 16 developer-weeks
-   **Phase 5-6**: 4 weeks × 2 developers = 8 developer-weeks
-   **Total**: 36 developer-weeks

### 8.2 Additional Costs

-   **Testing**: 2 weeks × 1 tester = 2 tester-weeks
-   **Documentation**: 1 week × 1 technical writer = 1 writer-week
-   **Training**: 1 week × 1 trainer = 1 trainer-week

---

## Conclusion

This comprehensive modification plan will transform Sarange ERP into a fully compliant trading company management system. The phased approach ensures minimal disruption to existing operations while systematically implementing all required features for Indonesian tax compliance and PSAK adherence.

The estimated timeline of 20 weeks provides adequate time for thorough development, testing, and deployment while ensuring quality and compliance standards are met.
