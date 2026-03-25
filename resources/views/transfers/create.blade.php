<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-lg text-gray-800">
            Create Transfer
        </h2>
    </x-slot>

    <div class="max-w-xl space-y-6">

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <form method="POST" action="{{ route('transfers.store') }}" class="space-y-4">
                @csrf

                <!-- PRODUCT -->
                <div>
                    <label class="text-sm text-gray-600">Product</label>

                    <select id="product-select" name="product_id"
                            class="w-full border rounded px-3 py-2 text-sm">
                    </select>
                </div>

                <!-- FROM -->
                <div>
                    <label class="text-sm text-gray-600">From Warehouse</label>

                    <select name="from_warehouse" id="from-warehouse"
                            class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Select source warehouse</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- TO -->
                <div>
                    <label class="text-sm text-gray-600">To Warehouse</label>

                    <select name="to_warehouse" id="to-warehouse"
                            class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Select destination warehouse</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- QTY -->
                <div>
                    <label class="text-sm text-gray-600">Quantity</label>

                    <input type="number" name="quantity" min="1" value="1"
                           id="qty-input"
                           class="w-full border rounded px-3 py-2 text-sm">

                    <div class="text-xs text-gray-500 mt-1" id="stock-info"></div>
                    <div class="text-xs text-red-600 hidden" id="stock-warning">
                        Not enough stock
                    </div>
                </div>

                <button
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                    Transfer
                </button>

            </form>
        </div>
    </div>

    <!-- TomSelect -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            let currentAvailable = 0;

            const qtyInput = document.getElementById("qty-input");
            const stockInfo = document.getElementById("stock-info");
            const stockWarning = document.getElementById("stock-warning");
            const warehouseSelect = document.getElementById("from-warehouse");
            const toWarehouseSelect = document.getElementById("to-warehouse");

            function validateQty(){
                if(qtyInput.value > currentAvailable){
                    stockWarning.classList.remove("hidden");
                } else {
                    stockWarning.classList.add("hidden");
                }
            }

            qtyInput.addEventListener("input", validateQty);
            warehouseSelect.addEventListener("change", () => {
                const option = select.options[select.getValue()];

                if (!option) {
                    currentAvailable = 0;
                    stockInfo.innerText = '';
                    validateQty();
                    return;
                }

                currentAvailable = option.available || 0;

                stockInfo.innerText =
                    `Stock: ${option.stock} | Reserved: ${option.reserved} | Available: ${option.available}`;

                validateQty();
            });

            const select = new TomSelect("#product-select",{

                valueField: "id",
                labelField: "name",
                searchField: ["name","sku"],
                placeholder: "Search by SKU or product name",

                load: function(query, callback) {

                    const warehouse = warehouseSelect.value;

                    apiFetch(`/api/products/search?q=${encodeURIComponent(query)}&warehouse_id=${warehouse}`)
                        .then(res => res.json())
                        .then(data => {

                            const results = data.map(p => ({
                                id: p.id,
                                name: warehouse
                                    ? `${p.name} (${p.sku}) — Available: ${p.available}`
                                    : `${p.name} (${p.sku}) — ${p.source_warehouse_name ?? 'No warehouse'}${p.source_available !== null ? ` — Available: ${p.source_available}` : ''}`,
                                sku: p.sku,
                                stock: warehouse ? p.stock : p.source_stock,
                                reserved: warehouse ? p.reserved : p.source_reserved,
                                available: warehouse ? p.available : p.source_available,
                                sourceWarehouseId: p.source_warehouse_id,
                                sourceWarehouseName: p.source_warehouse_name
                            }));

                            callback(results);
                        })
                        .catch(() => callback());
                },

                onChange: function(value){

                    const option = this.options[value];

                    if (!option) {
                        currentAvailable = 0;
                        stockInfo.innerText = '';
                        validateQty();
                        return;
                    }

                    if (!warehouseSelect.value && option.sourceWarehouseId) {
                        warehouseSelect.value = option.sourceWarehouseId;
                    }

                    if (
                        warehouseSelect.value &&
                        toWarehouseSelect.value &&
                        warehouseSelect.value === toWarehouseSelect.value
                    ) {
                        toWarehouseSelect.value = '';
                    }

                    currentAvailable = option.available || 0;

                    stockInfo.innerText =
                        `Stock: ${option.stock} | Reserved: ${option.reserved} | Available: ${option.available}`;

                    validateQty();
                }
            });

        });
    </script>

</x-app-layout>
