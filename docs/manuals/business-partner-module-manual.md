# Business Partner Module User Manual

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Features Overview](#features-overview)
4. [Creating Business Partners](#creating-business-partners)
5. [Viewing and Searching Partners](#viewing-and-searching-partners)
6. [Editing Business Partners](#editing-business-partners)
7. [Managing Contacts](#managing-contacts)
8. [Managing Addresses](#managing-addresses)
9. [Taxation & Terms](#taxation--terms)
10. [Banking Information](#banking-information)
11. [Journal History](#journal-history)
12. [Common Tasks](#common-tasks)
13. [Troubleshooting](#troubleshooting)
14. [Quick Reference](#quick-reference)
15. [Best Practices](#best-practices)

---

## Introduction

### What is the Business Partner Module?

The Business Partner Module is a unified system that helps you manage all your company's business relationships in one place. It consolidates customers and suppliers into a single, comprehensive management system that tracks:

- **Who you do business with** (customers and suppliers)
- **How to contact them** (multiple contacts and addresses)
- **Business terms** (payment terms, credit limits, tax information)
- **Financial relationships** (account mappings, transaction history)
- **Banking details** (payment methods and bank accounts)

### Who Should Use This Module?

- **Sales Team**: Manage customer information and relationships
- **Purchasing Team**: Manage supplier information and relationships
- **Accounting Team**: Track accounts receivable/payable and financial transactions
- **Customer Service**: Access contact information and transaction history
- **Managers**: Monitor business partner relationships and financial status

### Key Benefits

- **Unified Management**: Single interface for both customers and suppliers
- **Complete Information**: All partner data in one place with tabbed organization
- **Financial Integration**: Automatic account mapping and journal history tracking
- **Flexible Data Storage**: Custom fields for specific business requirements
- **Transaction Visibility**: Complete transaction history with running balances

---

## Getting Started

### Accessing the Business Partner Module

1. Log in to the ERP system
2. From the main menu, click on **"Business Partner"**
3. You will see the Business Partner Management page

### Understanding the Main Screen

When you open the Business Partner module, you'll see:

- **Add Business Partner** button: Create new business partners
- **Filter options**: Filter by type (All, Customer, Supplier) or status
- **Statistics cards**: Total partners, customers, suppliers, active/inactive counts
- **Search box**: Find partners quickly by code, name, or registration number
- **Partner list table**: Shows all your business partners with key information

### Understanding Partner Types

Business partners can be classified as:

- **Customer**: Entities that purchase from your company
- **Supplier**: Entities that supply goods/services to your company

Note: The system supports partners that can be both customers and suppliers, but they are managed as separate records with different partner types.

---

## Features Overview

The Business Partner Module includes these main features:

### 1. **Partner Management**
- Create, edit, and delete business partners
- Classify partners as customers or suppliers
- Set partner status (active, inactive, suspended)
- Track registration numbers and tax IDs

### 2. **Contact Management**
- Multiple contact persons per partner
- Different contact types (primary, billing, shipping, technical, sales, support)
- Complete contact information (name, position, email, phone, mobile)
- Primary contact designation

### 3. **Address Management**
- Multiple addresses per partner
- Different address types (billing, shipping, registered, warehouse, office)
- Complete address fields with proper formatting
- Primary address designation

### 4. **Taxation & Terms**
- Tax registration information (NPWP/Tax ID)
- Payment terms and credit limits
- Account mapping (automatic or manual)
- Default sales price level assignment
- Discount structures

### 5. **Banking Information**
- Bank account details
- Payment methods
- Flexible storage for banking-related information

### 6. **Financial Integration**
- Automatic account mapping (Customer→AR, Supplier→AP)
- Journal history with running balances
- Transaction consolidation from multiple sources
- Multi-dimensional accounting support (projects/departments)

### 7. **Transaction Tracking**
- Recent purchase orders
- Recent sales orders
- Recent invoices and payments
- Complete transaction history

---

## Creating Business Partners

### Step-by-Step Guide

1. **Navigate to Business Partners**
   - Click **"Business Partner"** from the main menu
   - Click the **"Add Business Partner"** button

2. **Fill in General Information**
   - **Code**: Unique partner code (required, max 50 characters)
   - **Name**: Partner name (required, max 150 characters)
   - **Partner Type**: Select Customer or Supplier (required)
   - **Status**: Select Active, Inactive, or Suspended (default: Active)
   - **Registration Number**: Business registration number (optional, max 30 characters)
   - **Tax ID**: Tax identification number/NPWP (optional, max 50 characters)
   - **Website**: Partner website URL (optional)
   - **Notes**: Additional notes about the partner (optional)

3. **Add Contact Information** (Optional)
   - Click the **"Contact Details"** tab
   - Click **"Add Contact"** button
   - Fill in contact information:
     - **Contact Type**: Primary, Billing, Shipping, Technical, Sales, or Support
     - **Name**: Contact person name (required)
     - **Position**: Job title/position (optional)
     - **Email**: Email address (optional)
     - **Phone**: Phone number (optional)
     - **Mobile**: Mobile number (optional)
     - **Is Primary**: Check if this is the primary contact
     - **Notes**: Additional notes (optional)
   - Click **"Add Contact"** to add more contacts

4. **Add Addresses** (Optional)
   - Click the **"Addresses"** tab
   - Click **"Add Address"** button
   - Fill in address information:
     - **Address Type**: Billing, Shipping, Registered, Warehouse, or Office
     - **Address Line 1**: Street address (required)
     - **Address Line 2**: Additional address details (optional)
     - **City**: City name (required)
     - **State/Province**: State or province (optional)
     - **Postal Code**: ZIP/postal code (optional)
     - **Country**: Country name (default: Indonesia)
     - **Is Primary**: Check if this is the primary address
     - **Notes**: Additional notes (optional)
   - Click **"Add Address"** to add more addresses

5. **Configure Taxation & Terms** (Optional)
   - Click the **"Taxation & Terms"** tab
   - **Account**: Select GL account for this partner (optional)
     - Customers default to Accounts Receivable
     - Suppliers default to Accounts Payable
   - **Default Sales Price Level**: Select price level (1, 2, or 3) for customers
   - Add custom fields as needed for payment terms, credit limits, etc.

6. **Add Banking Information** (Optional)
   - Click the **"Banking"** tab
   - Add bank account details and payment methods using custom fields

7. **Save the Partner**
   - Click **"Create Business Partner"** button
   - The system will validate all required fields
   - You'll be redirected to the partner detail page upon success

### Important Notes

- **Code must be unique**: Each partner must have a unique code
- **Partner type cannot be changed**: Once set, partner type cannot be modified
- **Account mapping**: If not specified, system assigns default accounts automatically
- **Primary contacts/addresses**: Only one primary contact and one primary address per partner

---

## Viewing and Searching Partners

### Viewing the Partner List

The Business Partner index page displays:

- **Partner Code**: Unique identifier
- **Partner Name**: Full name
- **Type Badge**: Customer (blue) or Supplier (yellow)
- **Status Badge**: Active (green), Inactive (gray), or Suspended (red)
- **Primary Contact**: Primary contact person information
- **Primary Address**: Primary address information
- **Actions**: View, Edit, Delete buttons

### Filtering Partners

You can filter partners by:

- **Type**: All, Customer, or Supplier
- **Status**: Active, Inactive, or Suspended (via search)

### Searching Partners

Use the search box to find partners by:

- Partner code
- Partner name
- Registration number

The search is case-insensitive and searches across all three fields.

### Viewing Partner Details

1. Click on a partner's **"View"** button or click the partner name
2. The detail page shows all partner information organized in tabs:
   - **General Information**: Basic partner data
   - **Contact Details**: All contact persons
   - **Addresses**: All addresses
   - **Taxation & Terms**: Tax info, account mapping, payment terms
   - **Banking**: Bank account details
   - **Transactions**: Recent orders, invoices, and payments
   - **Journal History**: Complete financial transaction history

---

## Editing Business Partners

### How to Edit a Partner

1. **Navigate to Partner Details**
   - Go to Business Partners list
   - Click **"View"** or partner name
   - Click **"Edit"** button in the header

2. **Modify Information**
   - Update any fields in the General Information tab
   - Modify contacts in the Contact Details tab
   - Update addresses in the Addresses tab
   - Change taxation & terms in the Taxation & Terms tab
   - Update banking information in the Banking tab

3. **Save Changes**
   - Click **"Update Business Partner"** button
   - Changes are saved immediately
   - You'll be redirected to the partner detail page

### Editing Restrictions

- **Partner Code**: Can be changed but must remain unique
- **Partner Type**: Cannot be changed after creation
- **Contacts/Addresses**: Can be added, modified, or removed
- **Details**: Custom fields can be added or modified

### Bulk Updates

Currently, bulk updates are not available. Each partner must be edited individually.

---

## Managing Contacts

### Adding Contacts

1. Open partner detail or edit page
2. Go to **"Contact Details"** tab
3. Click **"Add Contact"** button
4. Fill in contact information
5. Check **"Is Primary"** if this is the main contact
6. Click **"Save"** or **"Add Contact"** to add more

### Contact Types

- **Primary**: Main contact person for the partner
- **Billing**: Contact for billing and invoicing matters
- **Shipping**: Contact for shipping and delivery matters
- **Technical**: Technical support contact
- **Sales**: Sales-related contact
- **Support**: Customer/supplier support contact

### Editing Contacts

1. Go to **"Contact Details"** tab
2. Click **"Edit"** on the contact you want to modify
3. Update the information
4. Click **"Update"** to save changes

### Deleting Contacts

1. Go to **"Contact Details"** tab
2. Click **"Delete"** on the contact you want to remove
3. Confirm deletion in the dialog box

### Primary Contact

- Only one contact can be marked as primary
- The primary contact is displayed in the partner list
- Changing primary contact automatically unmarks the previous primary

---

## Managing Addresses

### Adding Addresses

1. Open partner detail or edit page
2. Go to **"Addresses"** tab
3. Click **"Add Address"** button
4. Fill in address information
5. Check **"Is Primary"** if this is the main address
6. Click **"Save"** or **"Add Address"** to add more

### Address Types

- **Billing**: Address for invoices and billing
- **Shipping**: Address for deliveries and shipments
- **Registered**: Official registered office address
- **Warehouse**: Warehouse or storage location
- **Office**: General office address

### Editing Addresses

1. Go to **"Addresses"** tab
2. Click **"Edit"** on the address you want to modify
3. Update the information
4. Click **"Update"** to save changes

### Deleting Addresses

1. Go to **"Addresses"** tab
2. Click **"Delete"** on the address you want to remove
3. Confirm deletion in the dialog box

### Primary Address

- Only one address can be marked as primary
- The primary address is displayed in the partner list
- Changing primary address automatically unmarks the previous primary

---

## Taxation & Terms

### Account Mapping

Business partners can be assigned to specific GL accounts:

- **Customers**: Default to Accounts Receivable (AR) accounts
- **Suppliers**: Default to Accounts Payable (AP) accounts

**To assign an account:**

1. Go to partner detail or edit page
2. Click **"Taxation & Terms"** tab
3. Find the **"Accounting"** section
4. Select an account from the **"Account"** dropdown
5. Save changes

**Automatic Account Assignment:**

If no account is specified, the system automatically assigns:
- Customers → First available AR account (code starting with 1100%)
- Suppliers → First available AP account (code starting with 2100%)

### Default Sales Price Level

For customers, you can set a default sales price level:

- **Level 1**: Standard pricing
- **Level 2**: Preferred customer pricing
- **Level 3**: VIP customer pricing

This default is used when creating sales orders if no specific price level is selected.

### Payment Terms

Payment terms can be stored as custom fields in the Taxation & Terms section:

- Payment due days (e.g., Net 30, Net 60)
- Early payment discounts
- Late payment penalties

### Credit Limits

Credit limits can be managed through custom fields:

- Maximum credit amount
- Credit period
- Credit approval requirements

### Tax Information

- **Tax ID (NPWP)**: Indonesian tax identification number
- **Registration Number**: Business registration number
- Additional tax-related information can be stored as custom fields

---

## Banking Information

### Adding Banking Details

1. Open partner detail or edit page
2. Go to **"Banking"** tab
3. Add banking information using custom fields:
   - Bank name
   - Account number
   - Account holder name
   - Bank branch
   - SWIFT code
   - Payment methods

### Payment Methods

Common payment methods that can be configured:

- Bank transfer
- Cash
- Check
- Credit card
- Letter of Credit (L/C)
- Other methods

### Multiple Bank Accounts

Partners can have multiple bank accounts. Add each account as a separate entry in the Banking section.

---

## Journal History

### What is Journal History?

Journal History provides a complete financial transaction record for a business partner, showing:

- All transactions affecting the partner's account
- Running balance calculations
- Transaction details (date, type, document number, amount)
- Multi-dimensional accounting information (projects/departments)

### Accessing Journal History

1. Open partner detail page
2. Click **"Journal History"** tab
3. Set date range (optional, defaults to current year)
4. View transactions with running balances

### Understanding Journal History

**Summary Cards:**
- **Opening Balance**: Balance at the start of the date range
- **Total Debits**: Sum of all debit transactions
- **Total Credits**: Sum of all credit transactions
- **Closing Balance**: Balance at the end of the date range

**Transaction Table:**
- **Date**: Transaction posting date
- **Type**: Transaction type (Journal Entry, Sales Invoice, Purchase Invoice, etc.)
- **Document No**: Document number
- **Description**: Transaction description
- **Debit**: Debit amount
- **Credit**: Credit amount
- **Balance**: Running balance after this transaction
- **Project/Dept**: Project and department information
- **Created By**: User who created the transaction

### Transaction Sources

Journal History consolidates transactions from:

1. **Journal Entries**: Direct journal entries affecting the partner's account
2. **Sales Invoices**: For customers
3. **Sales Receipts**: For customers
4. **Purchase Invoices**: For suppliers
5. **Purchase Payments**: For suppliers

### Filtering and Pagination

- **Date Range**: Filter transactions by start and end date
- **Pagination**: View transactions in pages (25 per page by default)
- **Sorting**: Transactions sorted by date and creation time

### Exporting Journal History

Journal history can be exported for reporting purposes. Contact your system administrator for export functionality.

---

## Common Tasks

### Task: Create a New Customer

1. Go to Business Partners → Add Business Partner
2. Enter code (e.g., "CUST001")
3. Enter name (e.g., "PT Maju Bersama")
4. Select "Customer" as partner type
5. Set status to "Active"
6. Add at least one contact (primary contact recommended)
7. Add at least one address (primary address recommended)
8. Add tax ID if available
9. Click "Create Business Partner"

### Task: Create a New Supplier

1. Go to Business Partners → Add Business Partner
2. Enter code (e.g., "SUPP001")
3. Enter name (e.g., "PT Makmur Jaya")
4. Select "Supplier" as partner type
5. Set status to "Active"
6. Add contact and address information
7. Add banking details for payment processing
8. Click "Create Business Partner"

### Task: Update Partner Contact Information

1. Go to Business Partners → Find and open partner
2. Click "Edit" button
3. Go to "Contact Details" tab
4. Click "Edit" on the contact to modify
5. Update phone, email, or other information
6. Click "Update" to save
7. Click "Update Business Partner" to finalize

### Task: Add a New Address

1. Go to Business Partners → Find and open partner
2. Click "Edit" button
3. Go to "Addresses" tab
4. Click "Add Address"
5. Select address type (e.g., "Shipping")
6. Enter address details
7. Click "Save"
8. Click "Update Business Partner" to finalize

### Task: Assign Account to Partner

1. Go to Business Partners → Find and open partner
2. Click "Edit" button
3. Go to "Taxation & Terms" tab
4. Find "Accounting" section
5. Select account from dropdown
6. Click "Update Business Partner"

### Task: View Partner Transaction History

1. Go to Business Partners → Find and open partner
2. Click "Transactions" tab
3. View recent purchase orders, sales orders, invoices, and payments
4. Click "Journal History" tab for complete financial history

### Task: Deactivate a Partner

1. Go to Business Partners → Find and open partner
2. Click "Edit" button
3. Change status from "Active" to "Inactive"
4. Click "Update Business Partner"
5. Partner will no longer appear in active partner lists

### Task: Search for a Partner

1. Go to Business Partners
2. Use search box at the top
3. Type partner code, name, or registration number
4. Results filter automatically as you type

### Task: Filter Partners by Type

1. Go to Business Partners
2. Use filter dropdown at the top
3. Select "Customer" or "Supplier"
4. List updates to show only selected type

---

## Troubleshooting

### Problem: Can't Create Partner - Code Already Exists

**Possible Causes:**
- Partner code is not unique
- Code was used by a deleted partner

**Solutions:**
1. Use a different code
2. Check if partner was previously deleted
3. Contact administrator to check code availability

### Problem: Can't Find a Partner

**Possible Causes:**
- Partner is inactive or suspended
- Wrong search term
- Partner was deleted

**Solutions:**
1. Check filter settings (show inactive partners)
2. Try different search terms
3. Search by registration number
4. Contact administrator if partner should exist

### Problem: Account Not Showing in Dropdown

**Possible Causes:**
- Account doesn't exist
- Account is inactive
- Wrong account type

**Solutions:**
1. Verify account exists in Chart of Accounts
2. Check account is active
3. Ensure account type matches partner type (AR for customers, AP for suppliers)
4. Contact administrator to create account

### Problem: Journal History Not Showing Transactions

**Possible Causes:**
- No transactions exist for the partner
- Date range is incorrect
- Account not assigned to partner

**Solutions:**
1. Check date range covers transaction dates
2. Verify partner has transactions in the system
3. Ensure account is assigned to partner
4. Check transactions are posted/approved

### Problem: Can't Delete a Partner

**Possible Causes:**
- Partner has transaction history
- Partner is referenced in other modules

**Solutions:**
- Partners with transactions cannot be deleted (by design)
- Deactivate the partner instead (change status to Inactive)
- Contact administrator if deletion is absolutely necessary

### Problem: Primary Contact/Address Not Saving

**Possible Causes:**
- Multiple primary contacts/addresses selected
- Form validation error

**Solutions:**
1. Ensure only one primary contact and one primary address
2. Check all required fields are filled
3. Try saving again
4. Contact administrator if issue persists

---

## Quick Reference

### Keyboard Shortcuts

- **Ctrl + F**: Search (in most browsers)
- **Enter**: Submit forms
- **Esc**: Close modals
- **Tab**: Navigate between form fields

### Important Terms

- **Business Partner**: Unified term for customers and suppliers
- **Partner Type**: Classification as Customer or Supplier
- **Primary Contact**: Main contact person for the partner
- **Primary Address**: Main address for the partner
- **Account Mapping**: GL account assignment for financial tracking
- **Journal History**: Complete financial transaction record
- **Running Balance**: Cumulative balance after each transaction

### Partner Status Colors

- 🟢 **Green (Active)**: Partner is active and can be used in transactions
- ⚪ **Gray (Inactive)**: Partner is inactive and hidden from active lists
- 🔴 **Red (Suspended)**: Partner is suspended and cannot be used

### Partner Type Badges

- 🔵 **Blue (Customer)**: Partner is a customer
- 🟡 **Yellow (Supplier)**: Partner is a supplier

### Contact Types

- **Primary**: Main contact
- **Billing**: Billing and invoicing contact
- **Shipping**: Shipping and delivery contact
- **Technical**: Technical support contact
- **Sales**: Sales-related contact
- **Support**: Customer/supplier support contact

### Address Types

- **Billing**: Invoice and billing address
- **Shipping**: Delivery and shipment address
- **Registered**: Official registered office
- **Warehouse**: Warehouse or storage location
- **Office**: General office address

---

## Getting Help

If you need additional assistance:

1. **Check this manual** first for common tasks
2. **Contact your system administrator** for technical issues
3. **Review training materials** if available
4. **Check the audit trail** to see what changed and when
5. **Consult the accounting team** for account mapping questions

---

## Best Practices

### When Creating Partners

- ✅ Use clear, consistent coding conventions (e.g., CUST001, SUPP001)
- ✅ Enter complete contact information from the start
- ✅ Add at least one primary contact and primary address
- ✅ Include tax ID (NPWP) for Indonesian partners
- ✅ Assign appropriate accounts for financial tracking
- ✅ Set correct partner type (Customer vs Supplier)

### When Managing Contacts

- ✅ Always designate a primary contact
- ✅ Keep contact information up to date
- ✅ Add multiple contacts for different purposes (billing, shipping, etc.)
- ✅ Include email and phone for all important contacts
- ✅ Document contact changes in notes

### When Managing Addresses

- ✅ Always designate a primary address
- ✅ Use correct address types (billing, shipping, etc.)
- ✅ Include complete address information
- ✅ Keep addresses current
- ✅ Add multiple addresses when partner has multiple locations

### When Setting Up Financial Information

- ✅ Assign correct GL accounts (AR for customers, AP for suppliers)
- ✅ Set appropriate credit limits for customers
- ✅ Configure payment terms accurately
- ✅ Review account mappings periodically
- ✅ Ensure journal history is accessible

### When Managing Partner Status

- ✅ Keep active partners current
- ✅ Deactivate instead of deleting when possible
- ✅ Use suspended status for temporary restrictions
- ✅ Review inactive partners periodically
- ✅ Document status change reasons

### General Tips

- ✅ Always verify information before saving
- ✅ Use notes to document important changes
- ✅ Review partner information regularly
- ✅ Keep contact and address information current
- ✅ Monitor journal history for financial accuracy
- ✅ Use consistent naming and coding conventions
- ✅ Document special terms or agreements in notes

---

**End of Manual**

*This manual covers the basic features of the Business Partner Module. For advanced features or specific business processes, consult with your system administrator or refer to additional documentation.*

