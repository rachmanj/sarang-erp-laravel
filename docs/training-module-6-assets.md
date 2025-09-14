# Training Module 6: Fixed Asset Management

## Complete Asset Lifecycle Management

**Duration**: 3 hours  
**Target Audience**: Asset managers, accounting staff, maintenance team  
**Prerequisites**: Module 4 (Financial Management), basic understanding of asset management

---

## Learning Objectives

By the end of this module, participants will be able to:

1. **Navigate the fixed asset management interface** and understand asset categories
2. **Create and manage fixed assets** with proper specifications and depreciation
3. **Process asset acquisitions** and capitalizations
4. **Handle depreciation calculations** using different methods
5. **Manage asset movements** and transfers between departments
6. **Process asset disposals** with gain/loss calculations
7. **Generate asset reports** and register documentation

---

## Module Overview

### Key Features Covered

-   **Asset Master Data**: Asset setup, categorization, specifications
-   **Asset Acquisition**: Purchase processing, capitalization, commissioning
-   **Depreciation Management**: Automatic calculation, different methods, adjustments
-   **Asset Movement**: Transfers, relocations, department changes
-   **Asset Disposal**: Sale, scrapping, loss processing with gain/loss calculation
-   **Asset Reporting**: Comprehensive asset register and depreciation reports
-   **Data Quality**: Duplicate detection, consistency validation, audit trails

---

## Story-Based Training Scenarios

### Scenario 1: New Asset Setup and Acquisition

**Business Context**: You're an asset manager at PT Sarange Trading. The company has purchased new office equipment that needs to be set up as fixed assets in the system.

**Story**: "PT Sarange Trading has purchased new office equipment: 10 desktop computers (Rp 15,000,000 each), 5 office chairs (Rp 2,500,000 each), and 1 server (Rp 25,000,000). These assets need to be set up in the system with proper categorization, depreciation methods, and specifications."

#### Step-by-Step Exploration

**Step 1: Access Asset Management**

-   Navigate to: `Assets > Asset Management`
-   Observe the asset dashboard showing current assets and their status
-   Notice the different asset categories and depreciation methods

**Step 2: Create New Asset Category**

-   Click "Asset Categories"
-   Add new category: "IT Equipment"
-   **Category Code**: IT-001
-   **Category Name**: IT Equipment
-   **Depreciation Method**: Straight Line
-   **Useful Life**: 3 years
-   **Residual Value**: 10%

**Step 3: Set Up Desktop Computer Asset**

-   Click "Add New Asset"
-   **Asset Code**: PC-001
-   **Asset Name**: Desktop Computer - Model A
-   **Category**: IT Equipment
-   **Acquisition Date**: Today's date
-   **Acquisition Cost**: Rp 15,000,000
-   **Supplier**: Tech Solutions Indonesia
-   **Location**: Jakarta Office
-   **Department**: IT Department

**Step 4: Configure Depreciation**

-   **Depreciation Method**: Straight Line
-   **Useful Life**: 3 years (36 months)
-   **Annual Depreciation**: Rp 4,500,000
-   **Monthly Depreciation**: Rp 375,000
-   **Residual Value**: Rp 1,500,000
-   **Depreciation Start**: Next month

**Step 5: Set Up Asset Specifications**

-   **Brand**: Dell
-   **Model**: OptiPlex 7090
-   **Serial Number**: DL001234567
-   **Specifications**: Intel i7, 16GB RAM, 512GB SSD
-   **Warranty**: 3 years
-   **Maintenance Schedule**: Quarterly

#### Discussion Points

-   Why is proper asset categorization important?
-   How do you determine useful life and depreciation method?
-   What information is needed for asset setup?

#### Hands-On Exercise

Create the remaining assets (4 more computers, 5 office chairs, 1 server) with different specifications and depreciation methods. Practice with different asset categories.

---

### Scenario 2: Asset Acquisition and Capitalization

**Business Context**: The purchased assets have been delivered and installed. You need to process the asset acquisition and capitalize them in the system.

**Story**: "The IT equipment has been delivered, installed, and tested. All assets are now ready for use. You need to process the asset acquisition, update the asset status to 'In Use', and begin depreciation calculations."

#### Step-by-Step Exploration

**Step 1: Access Asset Acquisition**

-   Navigate to: `Assets > Asset Acquisition`
-   Click "New Acquisition"
-   Select asset: PC-001 (Desktop Computer)

**Step 2: Process Asset Acquisition**

-   **Acquisition Date**: Today's date
-   **Commissioning Date**: Today's date
-   **Status**: In Use
-   **Location**: Jakarta Office - IT Room
-   **Responsible Person**: Bapak Ahmad (IT Manager)
-   **Purchase Order**: PO-2024-001
-   **Invoice**: INV-2024-001

**Step 3: Update Asset Status**

-   **Previous Status**: Under Construction
-   **New Status**: In Use
-   **Status Date**: Today's date
-   **Reason**: Commissioning completed
-   **Approval**: IT Manager approval

**Step 4: Begin Depreciation**

-   **Depreciation Start**: Next month
-   **First Depreciation**: Rp 375,000
-   **Accumulated Depreciation**: Rp 0
-   **Book Value**: Rp 15,000,000
-   **Depreciation Status**: Active

**Step 5: Generate Acquisition Documents**

-   **Asset Acquisition Report**: Detailed acquisition information
-   **Asset Tag**: Physical tag for asset identification
-   **Asset Certificate**: Official asset documentation
-   **Depreciation Schedule**: Monthly depreciation plan

#### Discussion Points

-   What is the difference between acquisition and capitalization?
-   When should depreciation begin?
-   What documentation is required for asset acquisition?

#### Hands-On Exercise

Process acquisitions for all purchased assets, practice with different status changes, and understand the capitalization process.

---

### Scenario 3: Depreciation Calculation and Management

**Business Context**: The month has ended, and you need to run the monthly depreciation calculation for all active assets.

**Story**: "It's the end of the month, and you need to run the monthly depreciation calculation for all active assets. The system should automatically calculate depreciation for each asset based on their depreciation method and useful life."

#### Step-by-Step Exploration

**Step 1: Access Depreciation Management**

-   Navigate to: `Assets > Depreciation`
-   Click "Monthly Depreciation Run"
-   Select period: Current month
-   Review assets to be depreciated

**Step 2: Review Depreciation Calculation**

-   **PC-001**: Rp 375,000 (Straight Line, 3 years)
-   **PC-002**: Rp 375,000 (Straight Line, 3 years)
-   **PC-003**: Rp 375,000 (Straight Line, 3 years)
-   **CHR-001**: Rp 208,333 (Straight Line, 5 years)
-   **SRV-001**: Rp 694,444 (Straight Line, 3 years)
-   **Total Depreciation**: Rp 2,041,777

**Step 3: Run Depreciation Calculation**

-   **Run Date**: Today's date
-   **Period**: January 2024
-   **Assets Processed**: 10 assets
-   **Total Depreciation**: Rp 2,041,777
-   **Status**: Calculated

**Step 4: Review Depreciation Entries**

-   **Journal Entry**: Automatic journal entry creation
-   **Debit**: Depreciation Expense - Rp 2,041,777
-   **Credit**: Accumulated Depreciation - Rp 2,041,777
-   **Posting**: Automatic posting to general ledger

**Step 5: Generate Depreciation Report**

-   **Monthly Depreciation Report**: Detailed depreciation by asset
-   **Depreciation Schedule**: Future depreciation projections
-   **Asset Register**: Updated asset values
-   **Financial Impact**: Depreciation expense for the month

#### Discussion Points

-   How does the system calculate depreciation automatically?
-   What happens when depreciation is posted?
-   How do you handle depreciation adjustments?

#### Hands-On Exercise

Practice running depreciation calculations, review different depreciation methods, and understand the impact on financial statements.

---

### Scenario 4: Asset Movement and Transfer

**Business Context**: An asset needs to be transferred from one department to another due to organizational changes.

**Story**: "Due to organizational restructuring, the IT department is moving to a new office location. Desktop computer PC-001 needs to be transferred from the current IT room to the new IT office. You need to process this asset movement and update the asset records."

#### Step-by-Step Exploration

**Step 1: Access Asset Movement**

-   Navigate to: `Assets > Asset Movement`
-   Click "New Movement"
-   Select asset: PC-001 (Desktop Computer)

**Step 2: Process Asset Transfer**

-   **Movement Date**: Today's date
-   **Movement Type**: Department Transfer
-   **From Location**: Jakarta Office - IT Room
-   **To Location**: Jakarta Office - New IT Office
-   **From Department**: IT Department
-   **To Department**: IT Department
-   **Responsible Person**: Bapak Ahmad (IT Manager)

**Step 3: Update Asset Location**

-   **Previous Location**: Jakarta Office - IT Room
-   **New Location**: Jakarta Office - New IT Office
-   **Location Code**: JKT-IT-002
-   **Building**: Main Office Building
-   **Floor**: 3rd Floor
-   **Room**: IT Office

**Step 4: Record Movement Details**

-   **Movement Reason**: Organizational restructuring
-   **Approval**: IT Manager approval
-   **Movement Cost**: Rp 0 (internal transfer)
-   **Status**: Completed
-   **Reference**: MOV-2024-001

**Step 5: Generate Movement Documents**

-   **Asset Movement Report**: Detailed movement information
-   **Location Update**: Updated asset location records
-   **Movement Certificate**: Official movement documentation
-   **Inventory Update**: Updated asset inventory

#### Discussion Points

-   What information is needed for asset movements?
-   How do you track asset location changes?
-   What approvals are required for asset transfers?

#### Hands-On Exercise

Practice different types of asset movements: department transfers, location changes, and responsibility transfers. Learn to handle various movement scenarios.

---

### Scenario 5: Asset Disposal and Gain/Loss Calculation

**Business Context**: An asset has reached the end of its useful life and needs to be disposed of. You need to process the disposal and calculate any gain or loss.

**Story**: "Desktop computer PC-001 has reached the end of its 3-year useful life and is being disposed of. The asset has a book value of Rp 1,500,000 (residual value) but is being sold for Rp 2,000,000. You need to process the disposal and calculate the gain."

#### Step-by-Step Exploration

**Step 1: Access Asset Disposal**

-   Navigate to: `Assets > Asset Disposal`
-   Click "New Disposal"
-   Select asset: PC-001 (Desktop Computer)

**Step 2: Process Asset Disposal**

-   **Disposal Date**: Today's date
-   **Disposal Type**: Sale
-   **Disposal Method**: External Sale
-   **Buyer**: PT Computer Solutions
-   **Disposal Reason**: End of useful life
-   **Approval**: IT Manager approval

**Step 3: Calculate Disposal Values**

-   **Original Cost**: Rp 15,000,000
-   **Accumulated Depreciation**: Rp 13,500,000
-   **Book Value**: Rp 1,500,000
-   **Sale Price**: Rp 2,000,000
-   **Gain on Disposal**: Rp 500,000

**Step 4: Record Disposal Transaction**

-   **Disposal Number**: DISP-2024-001
-   **Asset Status**: Disposed
-   **Disposal Cost**: Rp 0
-   **Proceeds**: Rp 2,000,000
-   **Gain/Loss**: Rp 500,000 (Gain)

**Step 5: Generate Disposal Documents**

-   **Asset Disposal Report**: Detailed disposal information
-   **Gain/Loss Calculation**: Disposal financial impact
-   **Disposal Certificate**: Official disposal documentation
-   **Financial Impact**: Gain recorded in income statement

#### Discussion Points

-   How is gain or loss calculated on asset disposal?
-   What happens to the asset after disposal?
-   How do you handle different disposal methods?

#### Hands-On Exercise

Practice different disposal scenarios: sales with gains/losses, scrapping, and insurance claims. Learn to handle various disposal methods and calculations.

---

## Advanced Features Exploration

### Depreciation Methods

**Straight Line Depreciation**

-   **Scenario**: Calculate straight line depreciation
-   **Exercise**: Set up asset with 5-year useful life
-   **Question**: When is straight line depreciation most appropriate?

**Declining Balance Depreciation**

-   **Scenario**: Calculate declining balance depreciation
-   **Exercise**: Set up asset with 20% declining balance rate
-   **Question**: What are the advantages of declining balance?

**Sum of Years Digits**

-   **Scenario**: Calculate sum of years digits depreciation
-   **Exercise**: Set up asset with 5-year useful life
-   **Question**: How does sum of years digits work?

### Asset Reporting and Analytics

**Asset Register Report**

-   **Purpose**: Comprehensive asset listing
-   **Exercise**: Generate asset register report
-   **Analysis**: Review asset portfolio and values

**Depreciation Schedule**

-   **Purpose**: Future depreciation projections
-   **Exercise**: Generate depreciation schedule
-   **Analysis**: Plan for future depreciation expenses

**Asset Performance Analysis**

-   **Purpose**: Analyze asset utilization and performance
-   **Exercise**: Generate performance analysis report
-   **Analysis**: Identify underutilized or overutilized assets

---

## Assessment Questions

### Knowledge Check

1. **What information is required to set up a new fixed asset?**
2. **How does the system calculate depreciation automatically?**
3. **What happens during asset acquisition and capitalization?**
4. **How are asset movements processed and tracked?**
5. **How is gain or loss calculated on asset disposal?**

### Practical Exercises

1. **Set up new assets** with proper categorization and depreciation
2. **Process asset acquisitions** and capitalizations
3. **Run depreciation calculations** for multiple assets
4. **Handle asset movements** and transfers
5. **Process asset disposals** with gain/loss calculations

### Scenario-Based Questions

1. **An asset's useful life needs to be extended. How do you handle this?**
2. **An asset is damaged and needs repair. What steps do you take?**
3. **An asset's value needs to be written down. How do you process this?**
4. **Multiple assets need to be disposed of together. What's the procedure?**
5. **An asset's location is unknown. How do you handle this?**

---

## Troubleshooting Common Issues

### Issue 1: Depreciation Calculation Errors

**Symptoms**: Incorrect depreciation amounts calculated
**Causes**:

-   Wrong depreciation method selected
-   Incorrect useful life or residual value
-   System calculation errors

**Solutions**:

1. Verify depreciation method and parameters
2. Check useful life and residual value settings
3. Review calculation logic
4. Process depreciation adjustment if needed

### Issue 2: Asset Movement Processing Issues

**Symptoms**: Asset movement not updating location
**Causes**:

-   Incomplete movement information
-   System integration issues
-   Approval workflow problems

**Solutions**:

1. Complete all movement fields
2. Check approval workflow
3. Verify system integration
4. Process manual update if needed

### Issue 3: Disposal Calculation Errors

**Symptoms**: Incorrect gain/loss calculation
**Causes**:

-   Wrong disposal values entered
-   Incorrect book value calculation
-   System calculation errors

**Solutions**:

1. Verify disposal values
2. Check book value calculation
3. Review calculation logic
4. Process correction entry if needed

---

## Best Practices

### Asset Setup

-   **Maintain accurate asset data** and update regularly
-   **Use consistent naming conventions** for asset codes
-   **Set appropriate depreciation methods** based on asset type
-   **Document all asset specifications** thoroughly

### Asset Management

-   **Process acquisitions promptly** to begin depreciation
-   **Track asset movements** accurately
-   **Monitor asset performance** regularly
-   **Maintain asset documentation** for audit purposes

### Depreciation Management

-   **Run depreciation calculations** monthly
-   **Review depreciation schedules** regularly
-   **Handle depreciation adjustments** promptly
-   **Monitor depreciation trends** for planning

### Disposal Processing

-   **Process disposals promptly** to maintain accuracy
-   **Calculate gain/loss accurately** for financial reporting
-   **Maintain disposal documentation** for audit purposes
-   **Update asset records** immediately after disposal

---

## Module Completion Checklist

-   [ ] Successfully set up new assets with proper categorization
-   [ ] Processed asset acquisitions and capitalizations
-   [ ] Ran depreciation calculations for multiple assets
-   [ ] Handled asset movements and transfers
-   [ ] Processed asset disposals with gain/loss calculations
-   [ ] Generated asset reports and registers
-   [ ] Understood different depreciation methods
-   [ ] Completed all hands-on exercises
-   [ ] Passed assessment questions

---

## Next Steps

After completing this module, participants should:

1. **Practice daily asset management operations** in the system
2. **Review module materials** for reference
3. **Prepare for Module 7**: Analytics & Business Intelligence
4. **Complete assessment** to verify understanding
5. **Ask questions** about any unclear concepts

---

_This module provides comprehensive training on fixed asset management in Sarange ERP. Participants should feel confident in their ability to manage the complete asset lifecycle from acquisition to disposal._
