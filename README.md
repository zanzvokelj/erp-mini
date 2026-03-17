# 🧾 Mini ERP – Inventory & Order Management System

A transaction-safe Inventory and Order Management System built with Laravel.

This project demonstrates how a real-world backend system can be designed beyond simple CRUD applications by focusing on:

- transactional safety
- inventory traceability
- domain-driven architecture
- SQL-driven analytics
- scalable system design
- multi-warehouse inventory management

---

## 🚀 Highlights

- Transaction-safe order processing (DB transactions + row locking)
- Reservation-based inventory system (prevents overselling)
- Multi-warehouse support
- Ledger-based inventory tracking (audit-ready)
- Concurrency-safe architecture
- Performance tested (1000 orders scenario)
- Full automated test coverage

---

## 📌 Problem

Small businesses often manage inventory using spreadsheets or simple tools that lack:

- reliable stock tracking
- warehouse visibility
- order traceability
- concurrency safety
- inventory history
- analytics

This project demonstrates how these problems can be solved using a ledger-based inventory model, transaction-safe order processing, and warehouse-aware inventory architecture.

---

## 🧩 Key Features

### 📊 Inventory Ledger System

Instead of storing stock as a simple integer column, the system uses a **ledger model**.

All inventory changes are stored as **Stock Movements**:

```
Stock = SUM(in) - SUM(out)
```

#### Example:

```
IN  +200  Purchase Order
OUT -5    Order #1042
OUT -2    Order #1043
```

#### Running balance:

```
+200 → 200
-5   → 195
-2   → 193
+100 → 293
```

#### Benefits:

- full inventory audit trail
- traceable stock history
- safe concurrent updates
- historical reconstruction of inventory

This pattern is commonly used in ERP systems such as **SAP, Odoo, and NetSuite**.

---

### 🏭 Multi-Warehouse Inventory

The system supports multiple warehouses.

Each stock movement belongs to a specific warehouse.

#### Structure:

```
stock_movements
- product_id
- warehouse_id
- type (in/out)
- quantity
```

#### Example:

| Warehouse | Product | Stock |
|----------|--------|------|
| MAIN     | Monitor | 120 |
| EU       | Monitor | 45  |
| US       | Monitor | 30  |

Features:

- stock per warehouse
- total stock overview
- full movement history

---

### 🛑 Stock Reservations

To prevent overselling during order creation, the system uses **temporary reservations**.

#### Structure:

```
product_id
order_id
quantity
expires_at
```

#### Formula:

```
available_stock = current_stock - reserved_stock
```

- reservations expire automatically (TTL)
- ensures safe concurrent ordering

---

### 🔄 Order Workflow

Orders follow a strict lifecycle:

```
DRAFT → CONFIRMED → SHIPPED → COMPLETED
DRAFT → CANCELLED
CONFIRMED → CANCELLED
COMPLETED → RETURNED
```

- illegal transitions are prevented
- enforced in controller + service layer

---

### 🔒 Transaction-Safe Order Processing

Order confirmation runs inside a database transaction.

#### Process:

```
confirmOrder()
  → DB::transaction
    → lock products (SELECT ... FOR UPDATE)
    → validate stock
    → finalize reservations
    → update order status
```

#### Guarantees:

- atomic order processing
- concurrency safety
- accurate stock deductions

---

### 📜 Order Activity Timeline

Each order maintains a full activity log.

Example:

```
Order created
Product added
Item quantity updated
Order confirmed
Order shipped
Order completed
```

Stored in:

```
order_activities
```

---

### 📦 Purchase Orders

Inventory can be replenished using purchase orders.

#### Workflow:

```
DRAFT → ORDERED → RECEIVED
```

Receiving creates stock movements:

```
IN → purchase_order
```

---

### 📈 Inventory Forecasting

Predicts when stock will run out based on historical data.

Example:

```
Inventory will run out in 12 days
```

---

### 🔍 Product Search API

```
GET /api/products/search
```

Used for:

- autocomplete
- order item selection

---

### ⚙️ Queue System

Background jobs:

- LowStockAlertJob → triggers alerts
- MonthlyReportJob → revenue analytics

---

### 📊 Dashboard Analytics

Provides:

- total revenue
- order count
- average order value
- top selling products
- low stock alerts
- revenue growth

---

## 🏗️ Architecture

### Service Layer Architecture

```
Controllers
   ↓
Services
   ↓
Models
   ↓
Database
```

### Core Services

- OrderService
- ProductService
- InventoryService
- PurchaseOrderService
- AnalyticsService

---

## 💳 Payment & Invoice System

### 🧾 Invoice Generation

Invoices are generated from shipped orders.

Includes:

- customer details
- order items
- totals and taxes
- issue date

---

### 📄 PDF Export

Invoices can be exported as PDF.

Use cases:

- sending invoices
- accounting
- reporting

---

### 💰 Payment Tracking

- record payments per invoice
- track payment status
- link payments to orders

---

## 🧪 Testing

The system includes a comprehensive automated test suite.

### Core Coverage

- inventory per warehouse
- stock reservations
- order lifecycle
- purchase orders

### Advanced Coverage

- concurrency protection
- performance test (1000 orders)
- edge cases
- reservation expiration
- multi-item validation

### Example Scenarios

- concurrent orders reserving same stock
- insufficient stock
- reservation expiration
- stock deduction after shipping

---

### ▶ Run Tests

```bash
php artisan test
```

---

## 🎯 Purpose

This project demonstrates how to design a realistic backend system beyond CRUD applications with:

- strong domain modeling
- transactional safety
- scalable architecture

---

## 👨‍💻 Author

Backend portfolio project demonstrating modern Laravel architecture and ERP-style inventory systems.
