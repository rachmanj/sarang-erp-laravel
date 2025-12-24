# Business Partner Module Architecture

## Overview

The Business Partner module provides a unified approach to managing both customers and suppliers in the Sarang ERP system. This consolidation simplifies data management, improves consistency, and enables better relationship management with entities that serve as both customers and suppliers.

## Database Schema

### Core Tables

1. **business_partners**

    - Primary table for all business relationships
    - Contains shared fields like code, name, status, registration_number
    - Uses partner_type field to distinguish between 'customer', 'supplier', and 'both'
    - Includes `default_currency_id` field (foreign key to currencies table) - automatically set to base currency (IDR) if not provided during creation

2. **business_partner_contacts**

    - Stores contact persons for each business partner
    - Supports multiple contacts with different roles (primary, billing, technical, etc.)
    - Includes contact details like name, position, email, phone

3. **business_partner_addresses**

    - Manages multiple addresses per business partner
    - Supports different address types (billing, shipping, registered office, etc.)
    - Includes complete address fields with proper formatting

4. **business_partner_details**
    - Flexible attribute storage using EAV (Entity-Attribute-Value) pattern
    - Organized by section_type (taxation, terms, banking, etc.)
    - Supports different field types (text, number, date, boolean, json)

## Model Structure

1. **BusinessPartner**

    - Main model with relationships to contacts, addresses, and details
    - Includes scopes for filtering by type (customers(), suppliers())
    - Provides helper methods for accessing specific details

2. **BusinessPartnerContact**

    - Manages contact information with proper validation
    - Includes scopes for filtering by type and primary status

3. **BusinessPartnerAddress**

    - Handles address management with proper validation
    - Supports different address types with formatting helpers

4. **BusinessPartnerDetail**
    - Implements flexible attribute storage
    - Provides type casting for different field types

## Service Layer

**BusinessPartnerService**

-   Handles complex business logic
-   Manages CRUD operations with transaction safety
-   Provides specialized methods for searching and filtering
-   Implements soft delete for partners with transactions
-   Automatically assigns base currency (IDR) as default when `default_currency_id` is not provided during creation or update
-   Conditionally loads relationships (purchaseOrders, salesOrders, purchaseInvoices, salesInvoices) only if corresponding database tables exist, preventing errors during schema evolution

## Controller Structure

**BusinessPartnerController**

-   Implements RESTful actions (index, create, store, show, edit, update, destroy)
-   Includes specialized endpoints for searching and filtering
-   Provides proper validation and error handling
-   Implements permission-based access control

## View Structure

The Business Partner module uses a tabbed interface for better organization:

1. **General Information Tab**

    - Basic partner information (code, name, type, status)
    - Website and notes
    - Default currency (automatically set to base currency if not specified)

2. **Contact Details Tab**

    - Multiple contact persons with different roles
    - Complete contact information (name, position, email, phone)

3. **Addresses Tab**

    - Multiple addresses with different types
    - Complete address fields with proper formatting

4. **Taxation & Terms Tab**

    - Tax registration information (NPWP)
    - Payment terms and credit limits
    - Discount structures

5. **Banking Tab**

    - Bank account details
    - Payment methods

6. **Transactions Tab** (in detail view)
    - Recent purchase/sales orders (conditionally displayed if tables exist)
    - Recent invoices and payments (conditionally displayed if tables exist)
    - Views check both table existence and relationship loading status before displaying data

## Integration Points

The Business Partner module integrates with several other modules:

1. **Purchase Module**

    - PurchaseOrder model uses businessPartner() relationship
    - Maintains backward compatibility with vendor() method

2. **Sales Module**

    - SalesOrder model uses businessPartner() relationship
    - Maintains backward compatibility with customer() method

3. **Delivery Module**

    - DeliveryOrder model uses businessPartner() relationship
    - Maintains backward compatibility with customer() method

4. **Accounting Module**

    - Invoice and payment models use businessPartner() relationship
    - Credit limit and payment term management

5. **Fixed Assets Module**
    - Asset acquisition tracking with businessPartner() relationship

## Data Migration

The migration from separate vendors and customers tables to the unified business_partners table is handled by:

1. **Database Schema Changes**

    - Modified original migration files to create business_partners table
    - Added supporting tables for contacts, addresses, and details
    - Updated foreign key references in dependent tables

2. **BusinessPartnerMigrationSeeder**

    - Migrates existing vendors to business partners (type: supplier)
    - Migrates existing customers to business partners (type: customer)
    - Detects and merges duplicate entries (same entity as both customer and supplier)

3. **BusinessPartnerSampleSeeder**
    - Creates sample business partners for testing
    - Includes examples of all partner types (customer, supplier, both)

## Security and Permissions

The Business Partner module uses the following permissions:

-   `business_partners.view` - For viewing business partner information
-   `business_partners.manage` - For creating, updating, and deleting business partners

## Future Enhancements

Potential future enhancements for the Business Partner module:

1. **Advanced Classification**

    - Industry sector classification
    - Size classification (small, medium, large)
    - Geographic region grouping

2. **Relationship Management**

    - Interaction history tracking
    - Communication log
    - Document management

3. **Analytics Integration**

    - Performance metrics
    - Relationship strength indicators
    - Risk assessment

4. **Portal Access**
    - Self-service portal for business partners
    - Document sharing and collaboration
    - Order and invoice tracking
