<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ERP API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
    <style>
        :root {
            --bg: #f5f1e8;
            --panel: #fffdf7;
            --ink: #1d2a24;
            --muted: #66756d;
            --accent: #0f766e;
            --accent-strong: #115e59;
            --border: #d8d2c4;
            --danger: #b42318;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.08), transparent 28%),
                radial-gradient(circle at top right, rgba(180, 35, 24, 0.07), transparent 20%),
                var(--bg);
            color: var(--ink);
            font-family: Georgia, "Times New Roman", serif;
        }

        .docs-shell {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 20px 48px;
        }

        .docs-header {
            display: grid;
            gap: 16px;
            margin-bottom: 20px;
        }

        .docs-title {
            margin: 0;
            font-size: 34px;
            line-height: 1.05;
            letter-spacing: -0.03em;
        }

        .docs-subtitle {
            margin: 0;
            max-width: 840px;
            color: var(--muted);
            font-size: 15px;
        }

        .auth-card {
            display: grid;
            gap: 14px;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 16px;
            background: rgba(255, 253, 247, 0.94);
            box-shadow: 0 14px 40px rgba(24, 39, 34, 0.08);
        }

        .auth-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .auth-field {
            display: grid;
            gap: 6px;
        }

        .auth-field label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .auth-field input {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 12px;
            background: #fff;
            color: var(--ink);
            font-size: 14px;
        }

        .auth-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .auth-actions button {
            border: 0;
            border-radius: 999px;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--accent-strong);
        }

        .btn-secondary {
            background: #e7ece8;
            color: var(--ink);
        }

        .auth-status {
            min-height: 20px;
            font-size: 14px;
            color: var(--muted);
        }

        .auth-status.error {
            color: var(--danger);
        }

        .auth-status.success {
            color: var(--accent-strong);
        }

        #swagger-ui {
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(24, 39, 34, 0.10);
        }
    </style>
</head>
<body>
<div class="docs-shell">
    <div class="docs-header">
        <div>
            <h1 class="docs-title">Mini ERP API Docs</h1>
            <p class="docs-subtitle">
                Swagger UI is connected to the protected API. Use the admin credentials below to obtain a Sanctum token,
                auto-apply it to Swagger's Authorize state, and keep it persisted across page refreshes.
            </p>
        </div>

        <div class="auth-card">
            <div class="auth-grid">
                <div class="auth-field">
                    <label for="api-email">Email</label>
                    <input id="api-email" type="email" autocomplete="username" placeholder="admin@admin.com">
                </div>
                <div class="auth-field">
                    <label for="api-password">Password</label>
                    <input id="api-password" type="password" autocomplete="current-password" placeholder="Password">
                </div>
            </div>

            <div class="auth-actions">
                <button id="api-login" class="btn-primary" type="button">Login To API</button>
                <button id="api-logout" class="btn-secondary" type="button">Logout</button>
            </div>

            <div id="auth-status" class="auth-status"></div>
        </div>
    </div>

    <div id="swagger-ui"></div>
</div>

<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-standalone-preset.js"></script>
<script>
    const TOKEN_KEY = 'mini_erp_api_token';
    const USER_KEY = 'mini_erp_api_user';

    const statusEl = document.getElementById('auth-status');
    const emailEl = document.getElementById('api-email');
    const passwordEl = document.getElementById('api-password');
    const loginBtn = document.getElementById('api-login');
    const logoutBtn = document.getElementById('api-logout');

    const openApiSpec = {
        openapi: '3.0.3',
        info: {
            title: 'Mini ERP API',
            version: '1.0.0',
            description: 'Protected ERP API for products, orders, inventory, invoicing, payments, customers, suppliers, and finance analytics. Authenticate via the login panel above or paste a bearer token into Authorize.'
        },
        servers: [
            { url: window.location.origin }
        ],
        components: {
            securitySchemes: {
                bearerAuth: {
                    type: 'http',
                    scheme: 'bearer',
                    bearerFormat: 'Token'
                }
            },
            schemas: {
                LoginRequest: {
                    type: 'object',
                    required: ['email', 'password'],
                    properties: {
                        email: { type: 'string', format: 'email', example: 'admin@admin.com' },
                        password: { type: 'string', format: 'password', example: 'secret123' }
                    }
                },
                LoginResponse: {
                    type: 'object',
                    properties: {
                        token: { type: 'string', example: '1|sanctum-token-example' },
                        user: { $ref: '#/components/schemas/User' }
                    }
                },
                User: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 1 },
                        name: { type: 'string', example: 'Admin' },
                        email: { type: 'string', format: 'email', example: 'admin@admin.com' },
                        role: { type: 'string', example: 'admin' }
                    }
                },
                Supplier: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 3 },
                        name: { type: 'string', example: 'Acme Supplies' }
                    }
                },
                Customer: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 8 },
                        name: { type: 'string', example: 'Northwind Retail' },
                        email: { type: 'string', nullable: true, example: 'buyer@northwind.test' }
                    }
                },
                Product: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 12 },
                        sku: { type: 'string', example: 'MON-ARM-001' },
                        name: { type: 'string', example: 'Monitor Arm' },
                        description: { type: 'string', nullable: true, example: 'Dual monitor articulated arm' },
                        price: { type: 'number', format: 'float', example: 149.9 },
                        cost_price: { type: 'number', format: 'float', example: 72.5 },
                        min_stock: { type: 'integer', example: 15 },
                        supplier: { $ref: '#/components/schemas/Supplier' }
                    }
                },
                OrderItem: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 440 },
                        product_id: { type: 'integer', example: 12 },
                        quantity: { type: 'integer', example: 3 },
                        price_at_time: { type: 'number', format: 'float', example: 149.9 },
                        cost_at_time: { type: 'number', format: 'float', example: 72.5 },
                        product: { $ref: '#/components/schemas/Product' }
                    }
                },
                Order: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 5038 },
                        order_number: { type: 'string', example: 'ORD-005038' },
                        status: { type: 'string', example: 'confirmed' },
                        customer_id: { type: 'integer', example: 8 },
                        warehouse_id: { type: 'integer', example: 1 },
                        subtotal: { type: 'number', format: 'float', example: 449.7 },
                        discount_total: { type: 'number', format: 'float', example: 0 },
                        total: { type: 'number', format: 'float', example: 449.7 },
                        confirmed_at: { type: 'string', format: 'date-time', nullable: true },
                        customer: { $ref: '#/components/schemas/Customer' },
                        items: {
                            type: 'array',
                            items: { $ref: '#/components/schemas/OrderItem' }
                        }
                    }
                },
                InvoiceItem: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 88 },
                        product_id: { type: 'integer', example: 12 },
                        quantity: { type: 'integer', example: 3 },
                        price: { type: 'number', format: 'float', example: 149.9 },
                        subtotal: { type: 'number', format: 'float', example: 449.7 },
                        product: { $ref: '#/components/schemas/Product' }
                    }
                },
                Payment: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 21 },
                        invoice_id: { type: 'integer', example: 54 },
                        amount: { type: 'number', format: 'float', example: 449.7 },
                        payment_method: { type: 'string', nullable: true, example: 'bank_transfer' },
                        paid_at: { type: 'string', format: 'date-time', nullable: true }
                    }
                },
                Invoice: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 54 },
                        invoice_number: { type: 'string', example: 'INV-ABC123XYZ987' },
                        order_id: { type: 'integer', example: 5038 },
                        customer_id: { type: 'integer', example: 8 },
                        status: { type: 'string', example: 'partial' },
                        subtotal: { type: 'number', format: 'float', example: 449.7 },
                        tax: { type: 'number', format: 'float', example: 0 },
                        total: { type: 'number', format: 'float', example: 449.7 },
                        due_date: { type: 'string', format: 'date', nullable: true },
                        issued_at: { type: 'string', format: 'date-time', nullable: true },
                        paid_at: { type: 'string', format: 'date-time', nullable: true },
                        customer: { $ref: '#/components/schemas/Customer' },
                        order: { $ref: '#/components/schemas/Order' },
                        items: {
                            type: 'array',
                            items: { $ref: '#/components/schemas/InvoiceItem' }
                        },
                        payments: {
                            type: 'array',
                            items: { $ref: '#/components/schemas/Payment' }
                        }
                    }
                },
                StockMovement: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 301 },
                        product_id: { type: 'integer', example: 12 },
                        warehouse_id: { type: 'integer', nullable: true, example: 1 },
                        type: { type: 'string', example: 'in' },
                        quantity: { type: 'integer', example: 50 },
                        reference_type: { type: 'string', nullable: true, example: 'purchase_order' },
                        reference_id: { type: 'integer', nullable: true, example: 19 },
                        created_at: { type: 'string', format: 'date-time' }
                    }
                },
                InventoryRow: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 12 },
                        sku: { type: 'string', example: 'MON-ARM-001' },
                        name: { type: 'string', example: 'Monitor Arm' },
                        min_stock: { type: 'integer', example: 15 },
                        stock: { type: 'integer', example: 122 }
                    }
                },
                InvoicableOrder: {
                    type: 'object',
                    properties: {
                        id: { type: 'integer', example: 5038 },
                        label: { type: 'string', example: 'ORD-005038 — Northwind Retail — €449.70' }
                    }
                },
                PaginatedMeta: {
                    type: 'object',
                    properties: {
                        current_page: { type: 'integer', example: 1 },
                        per_page: { type: 'integer', example: 20 },
                        total: { type: 'integer', example: 250 }
                    }
                },
                ErrorResponse: {
                    type: 'object',
                    properties: {
                        message: { type: 'string', example: 'The given data was invalid.' },
                        error: { type: 'string', example: 'Only confirmed orders can be shipped' },
                        errors: {
                            type: 'object',
                            additionalProperties: {
                                type: 'array',
                                items: { type: 'string' }
                            }
                        }
                    }
                }
            }
        },
        security: [
            { bearerAuth: [] }
        ],
        tags: [
            { name: 'Auth', description: 'Token login and logout for protected API access.' },
            { name: 'Products', description: 'Product catalog and stock history.' },
            { name: 'Orders', description: 'Order lifecycle, item management, confirmation, shipping, and invoicing.' },
            { name: 'Customers', description: 'Customer directory.' },
            { name: 'Suppliers', description: 'Supplier directory and related products.' },
            { name: 'Inventory', description: 'Inventory overview, movements, and manual stock adjustments.' },
            { name: 'Invoices', description: 'Invoice listing, details, PDF export, and overdue monitoring.' },
            { name: 'Payments', description: 'Invoice payment recording.' },
            { name: 'Finance', description: 'Finance dashboard aggregates.' }
        ],
        paths: {
            '/api/v1/login': {
                post: {
                    tags: ['Auth'],
                    operationId: 'loginApiUser',
                    summary: 'Login and receive Sanctum token',
                    description: 'Authenticates an allowlisted admin user and returns a bearer token for subsequent API calls.',
                    security: [],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: { $ref: '#/components/schemas/LoginRequest' }
                            }
                        }
                    },
                    responses: {
                        '200': {
                            description: 'Authenticated successfully',
                            content: {
                                'application/json': {
                                    schema: { $ref: '#/components/schemas/LoginResponse' }
                                }
                            }
                        },
                        '401': { description: 'Invalid credentials' },
                        '422': { description: 'User is not allowed to access the API' }
                    }
                }
            },
            '/api/v1/logout': {
                post: {
                    tags: ['Auth'],
                    operationId: 'logoutApiUser',
                    summary: 'Logout current token',
                    responses: {
                        '200': { description: 'Token invalidated' },
                        '401': { description: 'Unauthenticated' }
                    }
                }
            },
            '/api/v1/products': {
                get: {
                    tags: ['Products'],
                    operationId: 'listProducts',
                    summary: 'List products',
                    parameters: [
                        { name: 'search', in: 'query', schema: { type: 'string' }, description: 'Search by product name or SKU.' },
                        { name: 'supplier', in: 'query', schema: { type: 'integer' }, description: 'Filter by supplier ID.' },
                        { name: 'per_page', in: 'query', schema: { type: 'integer', default: 20 } }
                    ],
                    responses: {
                        '200': {
                            description: 'Paginated product list',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/Product' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        },
                        '401': { description: 'Unauthenticated' },
                        '403': { description: 'Forbidden' }
                    }
                }
            },
            '/api/v1/products/{product}': {
                get: {
                    tags: ['Products'],
                    operationId: 'showProduct',
                    summary: 'Get product detail',
                    parameters: [
                        { name: 'product', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Product detail', content: { 'application/json': { schema: { $ref: '#/components/schemas/Product' } } } },
                        '404': { description: 'Product not found' }
                    }
                }
            },
            '/api/v1/products/{product}/stock-history': {
                get: {
                    tags: ['Products'],
                    operationId: 'productStockHistory',
                    summary: 'Get stock movement history for one product',
                    parameters: [
                        { name: 'product', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': {
                            description: 'Product with latest stock movements',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            product: { $ref: '#/components/schemas/Product' },
                                            movements: {
                                                type: 'array',
                                                items: { $ref: '#/components/schemas/StockMovement' }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/orders': {
                get: {
                    tags: ['Orders'],
                    operationId: 'listOrders',
                    summary: 'List orders',
                    responses: {
                        '200': {
                            description: 'Paginated order list',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/Order' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                },
                post: {
                    tags: ['Orders'],
                    operationId: 'createDraftOrder',
                    summary: 'Create draft order',
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['customer_id', 'warehouse_id'],
                                    properties: {
                                        customer_id: { type: 'integer', example: 8 },
                                        warehouse_id: { type: 'integer', example: 1 }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '201': { description: 'Draft order created', content: { 'application/json': { schema: { $ref: '#/components/schemas/Order' } } } },
                        '422': { description: 'Validation failed', content: { 'application/json': { schema: { $ref: '#/components/schemas/ErrorResponse' } } } }
                    }
                }
            },
            '/api/v1/orders/invoicable': {
                get: {
                    tags: ['Orders'],
                    operationId: 'listInvoicableOrders',
                    summary: 'List shipped orders that do not yet have invoices',
                    parameters: [
                        { name: 'search', in: 'query', schema: { type: 'string' }, description: 'Search by order number or customer.' }
                    ],
                    responses: {
                        '200': {
                            description: 'Lightweight order selector list',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'array',
                                        items: { $ref: '#/components/schemas/InvoicableOrder' }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/orders/{order}': {
                get: {
                    tags: ['Orders'],
                    operationId: 'showOrder',
                    summary: 'Get order detail',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Order detail', content: { 'application/json': { schema: { $ref: '#/components/schemas/Order' } } } },
                        '404': { description: 'Order not found' }
                    }
                }
            },
            '/api/v1/orders/{order}/items': {
                post: {
                    tags: ['Orders'],
                    operationId: 'addOrderItem',
                    summary: 'Add item to order',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['product_id', 'quantity'],
                                    properties: {
                                        product_id: { type: 'integer', example: 12 },
                                        quantity: { type: 'integer', example: 3 }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '200': { description: 'Item added' },
                        '422': { description: 'Validation or stock check failed' }
                    }
                }
            },
            '/api/v1/orders/items/{item}': {
                patch: {
                    tags: ['Orders'],
                    operationId: 'updateOrderItem',
                    summary: 'Update order item quantity',
                    parameters: [
                        { name: 'item', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['quantity'],
                                    properties: {
                                        quantity: { type: 'integer', example: 5 }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '200': { description: 'Item updated' },
                        '422': { description: 'Validation failed' }
                    }
                },
                delete: {
                    tags: ['Orders'],
                    operationId: 'removeOrderItem',
                    summary: 'Remove item from order',
                    parameters: [
                        { name: 'item', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Item removed' }
                    }
                }
            },
            '/api/v1/orders/{order}/confirm': {
                post: {
                    tags: ['Orders'],
                    operationId: 'confirmOrder',
                    summary: 'Confirm draft order',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Order confirmed' },
                        '422': { description: 'Order cannot be confirmed' }
                    }
                }
            },
            '/api/v1/orders/{order}/ship': {
                post: {
                    tags: ['Orders'],
                    operationId: 'shipOrder',
                    summary: 'Ship confirmed order',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Order shipped' },
                        '422': { description: 'Only confirmed orders can be shipped' }
                    }
                }
            },
            '/api/v1/orders/{order}/complete': {
                post: {
                    tags: ['Orders'],
                    operationId: 'completeOrder',
                    summary: 'Mark order as completed',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Order completed' }
                    }
                }
            },
            '/api/v1/orders/{order}/cancel': {
                post: {
                    tags: ['Orders'],
                    operationId: 'cancelOrder',
                    summary: 'Cancel order',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Order cancelled' }
                    }
                }
            },
            '/api/v1/orders/{order}/invoice': {
                post: {
                    tags: ['Orders'],
                    operationId: 'createInvoiceFromOrder',
                    summary: 'Create invoice from shipped order',
                    parameters: [
                        { name: 'order', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': {
                            description: 'Invoice created',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            message: { type: 'string', example: 'Invoice created' },
                                            invoice_id: { type: 'integer', example: 54 }
                                        }
                                    }
                                }
                            }
                        },
                        '400': { description: 'Invoice already exists' },
                        '422': { description: 'Order must be shipped before invoicing' }
                    }
                }
            },
            '/api/v1/customers': {
                get: {
                    tags: ['Customers'],
                    operationId: 'listCustomers',
                    summary: 'List customers',
                    parameters: [
                        { name: 'search', in: 'query', schema: { type: 'string' } },
                        { name: 'per_page', in: 'query', schema: { type: 'integer', default: 20 } }
                    ],
                    responses: {
                        '200': {
                            description: 'Paginated customers',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/Customer' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/customers/{customer}': {
                get: {
                    tags: ['Customers'],
                    operationId: 'showCustomer',
                    summary: 'Get customer detail',
                    parameters: [
                        { name: 'customer', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Customer detail', content: { 'application/json': { schema: { $ref: '#/components/schemas/Customer' } } } }
                    }
                }
            },
            '/api/v1/suppliers': {
                get: {
                    tags: ['Suppliers'],
                    operationId: 'listSuppliers',
                    summary: 'List suppliers',
                    parameters: [
                        { name: 'search', in: 'query', schema: { type: 'string' } },
                        { name: 'per_page', in: 'query', schema: { type: 'integer', default: 20 } }
                    ],
                    responses: {
                        '200': {
                            description: 'Paginated suppliers',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/Supplier' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/suppliers/{supplier}': {
                get: {
                    tags: ['Suppliers'],
                    operationId: 'showSupplier',
                    summary: 'Get supplier detail with related products',
                    parameters: [
                        { name: 'supplier', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Supplier detail' }
                    }
                }
            },
            '/api/v1/inventory': {
                get: {
                    tags: ['Inventory'],
                    operationId: 'inventoryOverview',
                    summary: 'List inventory balances',
                    parameters: [
                        { name: 'status', in: 'query', schema: { type: 'string', enum: ['low', 'out'] } },
                        { name: 'per_page', in: 'query', schema: { type: 'integer', default: 20 } }
                    ],
                    responses: {
                        '200': {
                            description: 'Paginated inventory balances',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/InventoryRow' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/inventory/adjust': {
                post: {
                    tags: ['Inventory'],
                    operationId: 'adjustInventory',
                    summary: 'Create manual stock adjustment',
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['product_id', 'type', 'quantity'],
                                    properties: {
                                        product_id: { type: 'integer', example: 12 },
                                        type: { type: 'string', enum: ['in', 'out'], example: 'in' },
                                        quantity: { type: 'integer', example: 25 }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '200': { description: 'Stock adjusted' },
                        '422': { description: 'Validation failed' }
                    }
                }
            },
            '/api/v1/stock-movements': {
                get: {
                    tags: ['Inventory'],
                    operationId: 'listStockMovements',
                    summary: 'List stock movement ledger',
                    responses: {
                        '200': {
                            description: 'Paginated stock movements',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/StockMovement' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/invoices': {
                get: {
                    tags: ['Invoices'],
                    operationId: 'listInvoices',
                    summary: 'List invoices',
                    parameters: [
                        { name: 'search', in: 'query', schema: { type: 'string' }, description: 'Search by invoice number or customer.' },
                        { name: 'status', in: 'query', schema: { type: 'string', enum: ['draft', 'partial', 'paid', 'sent'] } }
                    ],
                    responses: {
                        '200': {
                            description: 'Paginated invoice list',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            data: { type: 'array', items: { $ref: '#/components/schemas/Invoice' } },
                                            meta: { $ref: '#/components/schemas/PaginatedMeta' }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/invoices/overdue': {
                get: {
                    tags: ['Invoices'],
                    operationId: 'listOverdueInvoices',
                    summary: 'List overdue invoices',
                    responses: {
                        '200': { description: 'Paginated overdue invoices' }
                    }
                }
            },
            '/api/v1/invoices/{invoice}': {
                get: {
                    tags: ['Invoices'],
                    operationId: 'showInvoice',
                    summary: 'Get invoice detail',
                    parameters: [
                        { name: 'invoice', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': { description: 'Invoice detail', content: { 'application/json': { schema: { $ref: '#/components/schemas/Invoice' } } } }
                    }
                }
            },
            '/api/v1/invoices/{invoice}/pdf': {
                get: {
                    tags: ['Invoices'],
                    operationId: 'downloadInvoicePdf',
                    summary: 'Download invoice PDF',
                    parameters: [
                        { name: 'invoice', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    responses: {
                        '200': {
                            description: 'PDF binary stream',
                            content: {
                                'application/pdf': {
                                    schema: { type: 'string', format: 'binary' }
                                }
                            }
                        }
                    }
                }
            },
            '/api/v1/invoices/{invoice}/payments': {
                post: {
                    tags: ['Payments'],
                    operationId: 'recordInvoicePayment',
                    summary: 'Record payment against invoice',
                    parameters: [
                        { name: 'invoice', in: 'path', required: true, schema: { type: 'integer' } }
                    ],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['amount'],
                                    properties: {
                                        amount: { type: 'number', format: 'float', example: 150.0 },
                                        payment_method: { type: 'string', nullable: true, example: 'bank_transfer' }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '200': { description: 'Payment recorded', content: { 'application/json': { schema: { $ref: '#/components/schemas/Payment' } } } },
                        '422': { description: 'Payment exceeds invoice total or validation failed' }
                    }
                }
            },
            '/api/v1/finance/overview': {
                get: {
                    tags: ['Finance'],
                    operationId: 'financeOverview',
                    summary: 'Get finance dashboard overview',
                    responses: {
                        '200': {
                            description: 'Finance aggregates and overdue invoice preview',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'object',
                                        properties: {
                                            revenue: { type: 'number', format: 'float', example: 120450.5 },
                                            outstanding: { type: 'number', format: 'float', example: 8200.0 },
                                            overdue: { type: 'number', format: 'float', example: 1450.0 },
                                            this_month: { type: 'number', format: 'float', example: 18300.0 },
                                            overdue_invoices: {
                                                type: 'array',
                                                items: { $ref: '#/components/schemas/Invoice' }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
        }
    };

    const ui = SwaggerUIBundle({
        spec: openApiSpec,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: 'StandaloneLayout',
        persistAuthorization: true,
    });

    function setStatus(message, type = '') {
        statusEl.textContent = message;
        statusEl.className = `auth-status ${type}`.trim();
    }

    function applyToken(token) {
        ui.preauthorizeApiKey('bearerAuth', token);
    }

    function clearSwaggerAuth() {
        ui.authActions.logout('bearerAuth');
    }

    function restorePersistedAuth() {
        const token = localStorage.getItem(TOKEN_KEY);
        const user = localStorage.getItem(USER_KEY);

        if (!token) {
            return;
        }

        applyToken(token);
        setStatus(user ? `Authenticated as ${user}` : 'Authenticated with saved token.', 'success');
    }

    async function loginToApi() {
        const email = emailEl.value.trim();
        const password = passwordEl.value;

        if (!email || !password) {
            setStatus('Enter both email and password.', 'error');
            return;
        }

        setStatus('Authenticating...');

        try {
            const response = await fetch('/api/v1/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload.token) {
                const message = payload.message || payload.errors?.email?.[0] || 'Login failed.';
                setStatus(message, 'error');
                return;
            }

            localStorage.setItem(TOKEN_KEY, payload.token);
            localStorage.setItem(USER_KEY, payload.user?.email || email);
            applyToken(payload.token);
            passwordEl.value = '';
            setStatus(`Authenticated as ${payload.user?.email || email}`, 'success');
        } catch (error) {
            setStatus('Could not reach API login endpoint.', 'error');
        }
    }

    async function logoutFromApi() {
        const token = localStorage.getItem(TOKEN_KEY);

        try {
            if (token) {
                await fetch('/api/v1/logout', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    }
                });
            }
        } finally {
            localStorage.removeItem(TOKEN_KEY);
            localStorage.removeItem(USER_KEY);
            clearSwaggerAuth();
            setStatus('Logged out.', 'success');
        }
    }

    loginBtn.addEventListener('click', loginToApi);
    logoutBtn.addEventListener('click', logoutFromApi);

    restorePersistedAuth();
</script>
</body>
</html>
