<x-app-layout>

    <div class="p-8 max-w-xl space-y-6">

        <h1 class="text-2xl font-semibold">Create Invoice</h1>

        <div class="bg-white rounded-2xl shadow border p-6 space-y-4">

            <div class="relative">

                <label class="text-sm text-gray-500 mb-1 block">
                    Order
                </label>

                <!-- INPUT -->
                <input
                    id="orderInput"
                    type="text"
                    placeholder="Search order..."
                    class="w-full border rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

                <!-- DROPDOWN -->
                <div
                    id="orderDropdown"
                    class="absolute w-full bg-white border rounded-xl shadow-lg mt-2 hidden max-h-60 overflow-y-auto z-50"
                ></div>

            </div>

            <button
                onclick="createInvoice()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                Create Invoice
            </button>

        </div>

    </div>


    <script>

        let selectedOrderId = null;
        let timeout = null;

        const input = document.getElementById('orderInput');
        const dropdown = document.getElementById('orderDropdown');


        // 🔥 LOAD ON FOCUS (top 20)
        input.addEventListener('focus', () => {
            searchOrders();
        });


        // 🔍 SEARCH (debounce)
        input.addEventListener('input', () => {

            clearTimeout(timeout);

            timeout = setTimeout(searchOrders, 300);
        });


        async function searchOrders(){

            const query = input.value;

            const params = new URLSearchParams();

            if(query.length > 0){
                params.append('search', query);
            }

            const res = await apiFetch(`/api/v1/orders/invoicable?${params}`);
            const orders = await res.json(); // backend že vrne clean data

            if(!orders.length){
                dropdown.innerHTML = `<div class="p-3 text-sm text-gray-500">No results</div>`;
                dropdown.classList.remove('hidden');
                return;
            }

            dropdown.innerHTML = orders.map(o => `
<div
    onclick="selectOrder(${o.id}, '${o.label.replace(/'/g, "\\'")}')"
    class="p-3 hover:bg-gray-50 cursor-pointer text-sm"
>
    ${o.label}
</div>
`).join('');

            dropdown.classList.remove('hidden');
        }


        function selectOrder(id, label){

            selectedOrderId = id;

            input.value = label;

            dropdown.classList.add('hidden');
        }


        async function createInvoice(){

            if(!selectedOrderId){
                alert("Select order");
                return;
            }

            try {

                const res = await apiFetch(`/api/v1/orders/${selectedOrderId}/invoice`,{
                    method:"POST",
                });

                const data = await res.json();

                if(!res.ok){
                    console.error(data);
                    alert(data.error || data.message || "Something went wrong");
                    return;
                }

                window.location = "/invoices";

            } catch (e){
                console.error(e);
                alert("Network error");
            }
        }


        // 👉 CLOSE ON OUTSIDE CLICK
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

    </script>

</x-app-layout>
