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
            description: 'Protected API for Mini ERP. Authenticate via the login panel or paste a bearer token into Authorize.'
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
            }
        },
        security: [
            { bearerAuth: [] }
        ],
        paths: {
            '/api/v1/login': {
                post: {
                    summary: 'Login and receive Sanctum token',
                    tags: ['Auth'],
                    security: [],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: {
                                    type: 'object',
                                    required: ['email', 'password'],
                                    properties: {
                                        email: { type: 'string', format: 'email', example: 'admin@admin.com' },
                                        password: { type: 'string', format: 'password' }
                                    }
                                }
                            }
                        }
                    },
                    responses: {
                        '200': { description: 'Authenticated successfully' },
                        '401': { description: 'Invalid credentials' },
                        '422': { description: 'User not allowed to access the API' }
                    }
                }
            },
            '/api/v1/logout': {
                post: {
                    summary: 'Logout current token',
                    tags: ['Auth'],
                    responses: {
                        '200': { description: 'Logged out' }
                    }
                }
            },
            '/api/v1/products': {
                get: {
                    summary: 'List products',
                    tags: ['Products'],
                    responses: {
                        '200': { description: 'Product list' },
                        '401': { description: 'Unauthenticated' },
                        '403': { description: 'Forbidden' }
                    }
                }
            },
            '/api/v1/orders': {
                get: {
                    summary: 'List orders',
                    tags: ['Orders'],
                    responses: {
                        '200': { description: 'Order list' }
                    }
                }
            },
            '/api/v1/inventory': {
                get: {
                    summary: 'Inventory overview',
                    tags: ['Inventory'],
                    responses: {
                        '200': { description: 'Inventory summary' }
                    }
                }
            },
            '/api/v1/customers': {
                get: {
                    summary: 'List customers',
                    tags: ['Customers'],
                    responses: {
                        '200': { description: 'Customer list' }
                    }
                }
            },
            '/api/v1/suppliers': {
                get: {
                    summary: 'List suppliers',
                    tags: ['Suppliers'],
                    responses: {
                        '200': { description: 'Supplier list' }
                    }
                }
            },
            '/api/v1/invoices': {
                get: {
                    summary: 'List invoices',
                    tags: ['Invoices'],
                    responses: {
                        '200': { description: 'Invoice list' }
                    }
                }
            },
            '/api/v1/finance/overview': {
                get: {
                    summary: 'Finance overview',
                    tags: ['Finance'],
                    responses: {
                        '200': { description: 'Finance overview' }
                    }
                }
            }
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
