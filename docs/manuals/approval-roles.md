# Approval Roles Documentation

## Overview

The ERP system uses **approval roles** to determine who can approve documents (Sales Orders, Purchase Orders, etc.) based on amount thresholds. These are separate from **permission roles** (managed in `/admin/roles`) which control system access and permissions.

## Approval Roles

There are three approval roles in the system:

### 1. **Officer**

- **Purpose**: Can approve orders up to 5,000,000
- **Usage**: First level approval for small orders
- **Threshold**: 0 - 5,000,000

### 2. **Supervisor**

- **Purpose**: Can approve orders between 5,000,000 - 15,000,000
- **Usage**: Second level approval for medium orders
- **Threshold**: 5,000,000 - 15,000,000

### 3. **Manager**

- **Purpose**: Can approve orders above 15,000,000
- **Usage**: Final level approval for large orders
- **Threshold**: 15,000,000+

## Approval Workflow

### Sales Orders

Based on the order amount, the system requires:

- **0 - 5,000,000**: Requires **Officer** approval only
- **5,000,000 - 15,000,000**: Requires **Officer** + **Supervisor** approval (sequential)
- **15,000,000+**: Requires **Officer** + **Supervisor** + **Manager** approval (sequential)

### Purchase Orders

Same thresholds apply:

- **0 - 5,000,000**: Requires **Officer** approval only
- **5,000,000 - 15,000,000**: Requires **Officer** + **Supervisor** approval
- **15,000,000+**: Requires **Officer** + **Supervisor** + **Manager** approval

## How to Assign Approval Roles

### Via UI (Recommended)

1. Navigate to `/admin/users`
2. Click **Edit** on the user you want to assign approval roles to
3. Scroll down to the **Approval Roles** section
4. Check the appropriate roles:
    - ☑ **Officer** - Can approve orders up to 5M
    - ☑ **Supervisor** - Can approve orders 5M-15M
    - ☑ **Manager** - Can approve orders above 15M
5. Click **Update User**

## Important Notes

1. **Approval roles are different from permission roles**:
    - Permission roles (admin, officer, etc.) control system access
    - Approval roles (officer, supervisor, manager) control document approval workflow

2. **A user can have multiple approval roles**:
    - A user can be both Officer and Supervisor
    - This allows them to approve orders in multiple threshold ranges

3. **Approval workflow is sequential**:
    - For orders requiring multiple approvals, they must be approved in order
    - Officer → Supervisor → Manager

4. **If no users exist with a required role**:
    - The approval workflow will not create approval records for that role
    - This can cause orders to be stuck in "pending" status
    - Always ensure at least one user has each required approval role
