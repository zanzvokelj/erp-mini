Mini ERP – Inventory & Order Management System
A transaction-safe Inventory and Order Management System built with Laravel.
This project demonstrates how a real-world backend system can be designed beyond simple CRUD
applications by focusing on:
- transactional safety
- inventory traceability
- domain-driven architecture
- SQL-driven analytics
- scalable system design
- multi-warehouse inventory management
  The application simulates a Mini ERP backend used by small to medium businesses to manage inventory,
  suppliers, customers, warehouses, and order workflows.
  Problem
  Small businesses often manage inventory using spreadsheets or simple tools that lack:
- reliable stock tracking
- warehouse visibility
- order traceability
- concurrency safety
- inventory history
- analytics
  This project demonstrates how these problems can be solved using a ledger-based inventory model,
  transaction-safe order processing, and warehouse-aware inventory architecture.
  Key Features
  Inventory Ledger System
  Instead of storing stock as a simple integer column, the system uses a ledger model.
  All inventory changes are stored as Stock Movements.
  Stock = SUM(in) - SUM(out)
  Example:
  IN +200 Purchase Order
  OUT -5 Order #1042
  OUT -2 Order #1043
  Running balance example:
  +200 → 200
  -5 → 195
  -2 → 193
  +100 → 293
  Benefits:
- full inventory audit trail
- traceable stock history
- safe concurrent updates
- historical reconstruction of inventory
  This pattern is commonly used in ERP systems such as SAP, Odoo, and NetSuite.
  Multi-Warehouse Inventory
  The system supports multiple warehouses.
  Each stock movement belongs to a specific warehouse.
  Structure:
  stock_movements
  product_id
  warehouse_id
  type (in/out)
  quantity
  Example inventory:
  Warehouse Product Stock
  MAIN Monitor 120
  EU Monitor 45
  US Monitor 30
  Inventory pages allow filtering by warehouse.
  Each product page displays:
- total stock
- stock per warehouse
- stock movement history
  Stock Reservations
  To prevent overselling during order creation, the system supports temporary stock reservations.
  StockReservation structure:
  product_id
  order_id
  quantity
  expires_at
  Available stock formula:
  available_stock = current_stock - reserved_stock
  Reservations automatically expire using a TTL mechanism.
  Order Workflow
  Orders follow a controlled lifecycle:
  DRAFT → CONFIRMED
  CONFIRMED → SHIPPED
  SHIPPED → COMPLETED
  DRAFT → CANCELLED
  CONFIRMED → CANCELLED
  COMPLETED → RETURNED
  Illegal transitions are prevented at the controller and service layer.
  Transaction-Safe Order Processing
  Order confirmation runs inside a database transaction.
  Products are locked using:
  SELECT ... FOR UPDATE
  Process:
  confirmOrder()
  DB::transaction
  lock products
  validate stock availability
  finalize reservations
  update order status
  Guarantees:
- atomic order processing
- concurrency safety
- accurate stock deductions
  Order Activity Timeline
  Each order maintains a full activity log.
  Example:
  Order created
  Product added
  Item quantity updated
  Order confirmed
  Order shipped
  Order completed
  Activities are stored in the order_activities table.
  Purchase Orders
  Inventory can be replenished using Purchase Orders.
  Workflow:
  DRAFT → ORDERED → RECEIVED
  Receiving a purchase order creates stock movements:
  IN → purchase_order
  Inventory Forecasting
  The system includes a forecasting engine that predicts when inventory will run out based on historical
  sales data.
  Example:
  Inventory will run out in 12 days
  Product Search API
  Endpoint:
  GET /api/products/search
  Used for:
- product autocomplete
- order item selection
  Queue System
  Background jobs include:
  LowStockAlertJob
  Triggered when inventory falls below minimum threshold.
  MonthlyReportJob
  Aggregates revenue analytics.
  Dashboard Analytics
  The dashboard provides:
- total revenue
- order count
- average order value
- top selling products
- low stock alerts
- revenue growth
  Architecture
  Service Layer Architecture:
  Controllers
  ↓
  Services
  ↓
  Models
  ↓
  Database
  Core services:
  OrderService
  ProductService
  InventoryService
  PurchaseOrderService
  AnalyticsService
  Purpose
  This project demonstrates how to design a realistic backend system beyond CRUD applications with
  strong domain modeling and transactional safety.
  Author
  Backend portfolio project demonstrating modern Laravel architecture and ERP-style inventory systems.
