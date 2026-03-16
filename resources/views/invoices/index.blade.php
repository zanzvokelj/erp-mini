<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold">Invoices</h1>

            <<button
                onclick="window.location='/invoices/create'"
                class="px-4 py-2 bg-black text-white rounded-lg text-sm">
                New Invoice
            </button>
        </div>

        <div class="flex items-center gap-4">

            <input
                id="invoiceSearch"
                type="text"
                placeholder="Search invoice or customer..."
                class="border rounded-lg px-3 py-2 text-sm w-64"
            >

            <select id="statusFilter" class="border rounded-lg  text-sm">
                <option value="">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="draft">Draft</option>
            </select>

        </div>

        <div class="bg-white rounded-xl shadow border overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 border-b">
                <tr class="text-left text-gray-500">
                    <th class="p-4">Invoice</th>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Total</th>
                    <th class="p-4">Due</th>
                    <th class="p-4">Action</th>
                </tr>
                </thead>

                <tbody id="invoiceTable"></tbody>

            </table>

        </div>

    </div>


    <script>

        let invoices = [];
        let searchTimeout = null;

        async function loadInvoices() {

            const search = document.getElementById('invoiceSearch').value;
            const status = document.getElementById('statusFilter').value;

            const params = new URLSearchParams({
                search: search,
                status: status
            });

            const res = await fetch(`/api/v1/invoices?${params}`);
            const data = await res.json();

            invoices = data.data;

            renderInvoices(invoices);
        }


        function renderInvoices(list) {

            const table = document.getElementById('invoiceTable');

            table.innerHTML = list.map(invoice => {

                let statusColor = {
                    paid: 'bg-green-100 text-green-700',
                    partial: 'bg-yellow-100 text-yellow-700',
                    draft: 'bg-gray-100 text-gray-600'
                }[invoice.status] ?? 'bg-gray-100';

                const paid = invoice.payments
                    ? invoice.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0)
                    : 0;

                const remaining = parseFloat(invoice.total) - paid;

                return `
<tr class="border-b hover:bg-gray-50 cursor-pointer"
    onclick="window.location='/invoices/${invoice.id}'">

    <td class="p-4 font-medium">
        ${invoice.invoice_number}
    </td>

    <td class="p-4">
        ${invoice.customer.name}
    </td>

    <td class="p-4">
        <span class="px-2 py-1 text-xs rounded-full ${statusColor}">
            ${invoice.status}
        </span>
    </td>

    <td class="p-4">

        <div>€${parseFloat(invoice.total).toFixed(2)}</div>

        ${paid > 0 ? `
            <div class="text-xs text-gray-500">
                Paid: €${paid.toFixed(2)}
            </div>

            <div class="text-xs text-red-600">
                Remaining: €${remaining.toFixed(2)}
            </div>
        ` : ''}

    </td>

    <td class="p-4">
        ${invoice.due_date ?? '-'}
    </td>

    <td class="p-4">

        ${invoice.status !== 'paid' ? `
            <button
                class="text-sm bg-blue-600 text-white px-3 py-1 rounded"
                onclick="event.stopPropagation(); quickPay(${invoice.id})">
                Pay
            </button>
        ` : ''}

    </td>

</tr>
`;

            }).join('');
        }


        async function quickPay(invoiceId) {

            const amount = prompt("Enter payment amount:");

            if (!amount) return;

            await fetch(`/api/v1/invoices/${invoiceId}/payments`, {

                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },

                body: JSON.stringify({
                    amount: amount,
                    payment_method: "manual"
                })

            });

            loadInvoices();
        }


        /* SEARCH (debounce) */

        document.getElementById('invoiceSearch')
            .addEventListener('input', () => {

                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(loadInvoices, 400);

            });


        /* STATUS FILTER */

        document.getElementById('statusFilter')
            .addEventListener('change', loadInvoices);


        /* INITIAL LOAD */

        loadInvoices();

    </script>


</x-app-layout>
