# Mini ERP – Inventory & Order Management System

A transaction-safe **Inventory and Order Management System** built with **Laravel**.

This project demonstrates how a real-world backend system can be designed beyond simple CRUD applications by focusing on:

- transactional safety
- inventory traceability
- domain-driven architecture
- SQL-based analytics
- scalable system design

The application simulates a **Mini ERP backend used by small to medium businesses** to manage inventory, suppliers, customers, and order workflows.

---

# Problem

Small businesses often manage inventory using spreadsheets or basic tools that lack:

- reliable stock tracking
- order traceability
- concurrency safety
- inventory history
- analytics

This project demonstrates how these problems can be solved using a **ledger-based inventory model** combined with **transaction-safe order processing**.

---

# Key Features

## Inventory Ledger System

Instead of storing stock as a simple integer column, the system uses a **ledger model**.

All stock changes are stored as **Stock Movements**.

```
Stock = SUM(in) - SUM(out)
```

Example:

| Type | Quantity | Reference |
|-----|-----|-----|
| IN | +200 | Restock |
| OUT | -5 | Order #1042 |
| OUT | -2 | Order #1043 |

Each product page displays a **running stock balance**:

```
+200 → 200
-5 → 195
-2 → 193
+100 → 293
```

Benefits of the ledger approach:

- full inventory audit trail
- traceable stock history
- safe concurrent updates
- accurate historical reconstruction

This approach is commonly used in **ERP systems such as SAP or Odoo**.

---

# Order Workflow

Orders follow a controlled lifecycle.

Allowed transitions:

```
DRAFT → CONFIRMED
CONFIRMED → SHIPPED
SHIPPED → COMPLETED

CONFIRMED → CANCELLED
```

Illegal transitions are prevented at the controller and service layer.

---

# Preventing Overselling

Order confirmation runs inside a **database transaction**.

Products are locked using:

```
SELECT ... FOR UPDATE
```

Processing flow:

```
confirmOrder()

DB::transaction:
    lock products
    validate stock availability
    create stock movements
    update order status
```

This guarantees:

- atomic order processing
- concurrency safety
- accurate stock deductions

---

# Order Activity Timeline

Each order maintains a full **activity log**.

Example timeline:

```
Order created
Product added (qty 3)
Order confirmed
Order shipped
Order completed
```

Activities are stored in the `order_activities` table and displayed in the order page timeline.

---

# Order Editing

Draft orders support:

- adding items
- updating item quantities
- removing items

Once confirmed, orders become immutable to protect inventory integrity.

---

# Order Cancellation & Stock Rollback

If a confirmed order is cancelled:

- deducted inventory is restored
- a compensating stock movement is created

Example:

```
OUT → order
IN → order_cancel
```

This ensures the ledger remains consistent.

---

# Product Stock History

Each product page contains a full **stock movement ledger**.

Example:

| Type | Qty | Balance | Reference |
|-----|-----|-----|-----|
| IN | +200 | 200 | Restock |
| OUT | -5 | 195 | Order #1042 |
| OUT | -2 | 193 | Order #1043 |
| IN | +100 | 293 | Restock |

This provides a complete audit of inventory changes.

---

# Dashboard Analytics

The dashboard provides operational insights such as:

- total revenue
- order count
- average order value
- low stock products
- top selling products
- stock turnover rate
- revenue growth

Revenue visualization is generated using **Chart.js**.

All analytics queries are executed at the **database level using SQL aggregates**.

---

# Architecture

The system follows a **Service Layer architecture**.

```
Controllers
   ↓
Services
   ↓
Models
   ↓
Database
```

Controllers handle:

- request validation
- routing
- response formatting

Business logic resides in service classes:

```
OrderService
ProductService
AnalyticsService
```

This separation improves:

- maintainability
- domain clarity
- testability

---

# Domain Model

Core entities:

```
Product
Supplier
Customer
Order
OrderItem
StockMovement
OrderActivity
User
```

Relationships:

```
Supplier → hasMany Products
Product → hasMany StockMovements
Customer → hasMany Orders
Order → hasMany OrderItems
Order → hasMany Activities
OrderItem → belongsTo Product
StockMovement → belongsTo Product
```

---

# Queue System

The system includes asynchronous background jobs.

### LowStockAlertJob

Triggered when product inventory drops below the minimum threshold.

### MonthlyReportJob

Aggregates monthly revenue and logs analytics reports.

Queue workers can run continuously in production environments.

---

# Realistic Data Simulation

The database is seeded with realistic data:

```
Products:     1000
Suppliers:    20
Customers:    200
Orders:       5000
StockMoves:   10000+
```

This allows testing:

- analytics queries
- pagination
- performance under larger datasets

---

# Performance Considerations

The system avoids common performance pitfalls.

Strategies used:

- SQL aggregation instead of PHP loops
- indexed columns
- eager loading relationships
- pagination for large datasets
- minimal column selection

Key indexes:

```
sku
order_number
customer_id
product_id
created_at
```

---

# Security & Validation

The application includes:

- request validation
- CSRF protection
- guarded mass assignment
- status transition guards
- transactional data operations

---

# Technology Stack

Backend:

- Laravel
- PHP
- MySQL / PostgreSQL

Frontend:

- Blade
- TailwindCSS
- Alpine.js
- Chart.js

Infrastructure:

- Laravel queues
- database transactions
- background workers

---

# Scaling Strategy

In larger systems, the architecture could evolve into independent services:

```
Inventory Service
Order Service
Analytics Service
```

Event-driven communication and message queues could be introduced for large-scale deployments.

---

# Possible Future Improvements

Potential extensions include:

- multi-warehouse inventory
- purchase order workflow
- supplier restocking automation
- REST API
- role-based access control
- Redis caching layer
- automated reporting
- automated tests

---

# Purpose of This Project

This project demonstrates how to design a **realistic backend system** that goes beyond simple CRUD applications.

The focus is on:

- correct domain modeling
- transactional safety
- inventory traceability
- scalable backend architecture

---

# Author

Backend portfolio project demonstrating modern Laravel architecture and ERP-style inventory management.
