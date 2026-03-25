<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold">Invoices</h1>

            <button
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

            <select id="statusFilter" class="border rounded-lg text-sm">
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

    <!-- 🔥 PAYMENT MODAL -->
    <div id="paymentModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-4">

            <h2 class="text-lg font-semibold">Add Payment</h2>

            <input
                id="paymentAmount"
                type="number"
                step="0.01"
                placeholder="Enter amount"
                class="w-full border rounded-lg px-3 py-2"
                autofocus
            >

            <select id="paymentMethod" class="w-full border rounded-lg px-3 py-2">
                <option value="manual">Manual</option>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank">Bank Transfer</option>
            </select>

            <div class="flex justify-end gap-2 pt-2">

                <button
                    onclick="closePaymentModal()"
                    class="px-4 py-2 text-sm text-gray-500">
                    Cancel
                </button>

                <button
                    onclick="submitPayment()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                    Confirm
                </button>

            </div>

        </div>

    </div>

    <script>

        let invoices = [];
        let searchTimeout = null;
        let currentInvoiceId = null;

        async function loadInvoices() {

            const search = document.getElementById('invoiceSearch').value;
            const status = document.getElementById('statusFilter').value;

            const params = new URLSearchParams({
                search: search,
                status: status
            });

            const res = await apiFetch(`/api/v1/invoices?${params}`);
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

        function quickPay(invoiceId){

            const invoice = invoices.find(i => i.id === invoiceId);

            currentInvoiceId = invoiceId;

            const paid = invoice.payments
                ? invoice.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0)
                : 0;

            const remaining = parseFloat(invoice.total) - paid;

            const input = document.getElementById('paymentAmount');

            input.value = remaining.toFixed(2);
            input.max = remaining;

            document.getElementById('paymentModal').classList.remove('hidden');
            document.getElementById('paymentModal').classList.add('flex');
        }

        function closePaymentModal(){
            document.getElementById('paymentModal').classList.add('hidden');
        }

        async function submitPayment(){

            const amount = document.getElementById('paymentAmount').value;
            const method = document.getElementById('paymentMethod').value;

            if(!amount){
                alert("Enter amount");
                return;
            }

            const res = await apiFetch(`/api/v1/invoices/${currentInvoiceId}/payments`,{
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                body: JSON.stringify({
                    amount: amount,
                    payment_method: method
                })
            });

            if(!res.ok){
                alert("Payment failed");
                return;
            }

            closePaymentModal();
            loadInvoices();
        }

        document.getElementById('invoiceSearch')
            .addEventListener('input', () => {

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(loadInvoices, 400);

            });

        document.getElementById('statusFilter')
            .addEventListener('change', loadInvoices);

        loadInvoices();

    </script>

</x-app-layout>
