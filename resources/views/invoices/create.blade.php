<x-app-layout>

    <div class="p-8 space-y-6 max-w-xl">

        <h1 class="text-2xl font-semibold">
            Create Invoice
        </h1>

        <div class="bg-white rounded-xl shadow border p-6 space-y-4">

            <div>

                <label class="text-sm text-gray-500">
                    Order
                </label>

                <select
                    id="orderSelect"
                    class="border rounded-lg px-3 py-2 w-full">

                    <option value="">Select Order</option>

                </select>

            </div>

            <button
                onclick="createInvoice()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                Create Invoice
            </button>

        </div>

    </div>


    <script>

        async function loadOrders(){

            const res = await fetch('/api/v1/orders/invoicable');

            const data = await res.json();
            const orders = data.data ?? data;

            const select = document.getElementById('orderSelect');

            select.innerHTML = `
<option value="">Select Order</option>
` + orders.map(o=>`
<option value="${o.id}">
${o.order_number} — ${o.customer.name} — €${parseFloat(o.total ?? 0).toFixed(2)}
</option>
`).join('');

        }


        async function createInvoice(){

            const orderId = document.getElementById('orderSelect').value;

            if(!orderId){
                alert("Select order");
                return;
            }

            await fetch(`/api/v1/orders/${orderId}/ship`,{
                method:"POST",
                headers:{
                    "Accept":"application/json"
                }
            });

            window.location="/invoices";

        }

        loadOrders();

    </script>
</x-app-layout>
