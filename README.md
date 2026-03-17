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

### ⚡ Event-Driven Architecture

The system uses an event-driven approach to decouple core business logic from side effects.

### Events

- OrderConfirmed
- OrderShipped

### Example Flow

Order confirmation triggers:

OrderConfirmed → LowStockAlertJob

Order shipping triggers:

OrderShipped →
- GenerateInvoiceFromOrder
- SendShippingNotification

### Benefits

- decoupled architecture
- easier scalability
- async processing with queues
- clean separation of concerns

---

### 🧵 Queue Jobs

The system uses background jobs for asynchronous processing:

- LowStockAlertJob → checks inventory thresholds
- MonthlyReportJob → generates revenue analytics
- ReleaseExpiredReservationsJob → cleans expired reservations

Jobs are processed using Laravel queues.

---

### 🔁 Real-World Analogy

This architecture follows patterns used in modern systems:

- event-driven systems (e.g. Kafka-based systems)
- microservice communication patterns
- asynchronous processing pipelines

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

### 🧮 Warehouse-Aware Stock Calculation

Stock is dynamically calculated per warehouse using filtered stock movements.

Example:

SELECT SUM(
CASE
WHEN type = 'in' THEN quantity
WHEN type = 'out' THEN -quantity
END
)
FROM stock_movements
WHERE product_id = ? AND warehouse_id = ?

This allows:

- accurate per-warehouse stock tracking
- flexible filtering in UI
- scalable inventory design

---

### ⚠️ No Stored Stock Field

The system does NOT store stock directly in the products table.

Instead, stock is calculated dynamically from stock movements.

Benefits:

- eliminates data inconsistency
- avoids race conditions
- ensures single source of truth


---

### 🧠 SQL-Driven Filtering

Advanced filtering is performed at the database level using GROUP BY and HAVING clauses.

Examples:

- low stock filtering
- out of stock detection

This ensures:

- high performance
- reduced application-level processing


---
### 🔁 Double-Entry Inventory Transfers

Warehouse transfers follow a double-entry pattern:

- OUT movement from source warehouse
- IN movement to destination warehouse

This ensures:

- consistency
- full traceability
- accounting-style correctness

---

### 🔗 Movement References

Each stock movement includes a reference field linking it to its origin:

- orders
- transfers
- purchase orders

This enables:

- traceability
- auditability
- debugging

---

### 📈 Running Balance Calculation

Stock history includes a running balance for each movement.

This allows:

- visual tracking of stock changes over time
- easier debugging
- audit-friendly reporting

---

### 🔐 Concurrency-Safe Inventory Updates

Critical operations use row-level locking:

SELECT ... FOR UPDATE

This ensures:

- no race conditions
- accurate stock under concurrent requests

---

### ⏳ Reservation Expiration

Expired reservations are automatically cleaned up via a background job.

This prevents:

- stale reservations
- locked inventory

---


### 🧱 Domain Separation

Business logic is split into specialized services:

- ProductService → stock calculations
- InventoryService → reservations and availability
- OrderService → order lifecycle

This improves:

- maintainability
- testability
- scalability


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

# 🔐 API Authentication & 📘 API Documentation

## 🔐 API Authentication

The system uses **token-based authentication with Laravel Sanctum** to secure all API endpoints.

### Flow:

1. User logs in via API
2. Backend returns a personal access token
3. Token is sent with every request

Authorization: Bearer {token}

### Example:

POST /api/v1/login

Response:

{
"token": "your-access-token",
"user": {
"id": 1,
"email": "user@example.com"
}
}

### Protected Routes

All core endpoints are protected using:

auth:sanctum

This ensures:

- only authenticated users can access data
- secure order and inventory operations
- safe API usage for frontend and mobile apps

### Logout

POST /api/v1/logout

Invalidates the current token.

---

## 📘 API Documentation (OpenAPI / Swagger)

The project includes a fully documented API using the **OpenAPI 3.0 specification**.

Interactive documentation is available via Swagger UI:

http://127.0.0.1:8000/api/docs

### Features:

- interactive endpoint testing
- request/response schemas
- authentication support (Bearer token)
- grouped endpoints by domain (Products, Orders, Inventory, etc.)

### Authentication in Swagger

1. Call /login endpoint
2. Copy the returned token
3. Click Authorize
4. Enter:

Bearer {token}

Swagger will automatically attach the token to all protected requests.

---

## 🧠 Developer Experience

To improve development workflow, Swagger authorization can be persisted:

'persist_authorization' => true

This keeps the user authenticated even after page refresh (development only).


## 🎯 Purpose

This project demonstrates how to design a realistic backend system beyond CRUD applications with:

- strong domain modeling
- transactional safety
- scalable architecture

---

## 🚀 New Features

### 🔄 Warehouse Transfers
- Transfer stock between warehouses
- Automatically creates:
    - OUT movement from source warehouse
    - IN movement to destination warehouse
- Ensures stock consistency with database transactions
- Prevents transfer if insufficient stock

### 📦 Warehouse-based Inventory
- Stock is tracked per warehouse using stock movements
- Products do not belong to a single warehouse
- Real-time stock calculation:
    - Global stock
    - Per-warehouse stock

### 📊 Stock Movement Ledger
- Full audit trail of all stock changes
- Includes:
    - Transfers
    - Orders
    - Manual adjustments
- Each movement includes:
    - Type (IN / OUT)
    - Quantity
    - Warehouse
    - Reference

### 🔍 Advanced Product Search
- Multi-term search (e.g. "monitor arm")
- Search by:
    - Name
    - SKU

### 🧠 Inventory Forecast
- Predicts when stock will run out
- Based on historical consumption

### 📦 SKU Support
- Each product has a unique SKU
- Used for precise identification


## 👨‍💻 Author

Backend portfolio project demonstrating modern Laravel architecture and ERP-style inventory systems.
