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
                    <th class="p-4">
                        <button
                            type="button"
                            id="sort-created"
                            class="inline-flex items-center gap-1 font-medium hover:text-gray-700"
                            onclick="setSort('created')"
                        >
                            Created
                        </button>
                    </th>
                    <th class="p-4">
                        <button
                            type="button"
                            id="sort-due"
                            class="inline-flex items-center gap-1 font-medium hover:text-gray-700"
                            onclick="setSort('due')"
                        >
                            Due
                        </button>
                    </th>
                    <th class="p-4">Action</th>
                </tr>
                </thead>

                <tbody id="invoiceTable"></tbody>

            </table>

        </div>

        <div
            id="invoicePagination"
            class="flex items-center justify-between gap-4 text-sm text-gray-600"
        ></div>

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
        let invoicePagination = null;
        let currentPage = 1;
        let currentSort = 'created';

        async function loadInvoices(page = 1) {

            currentPage = page;

            const search = document.getElementById('invoiceSearch').value;
            const status = document.getElementById('statusFilter').value;

            const params = new URLSearchParams({
                search: search,
                status: status,
                page: page,
                sort_by: currentSort
            });

            const res = await apiFetch(`/api/v1/invoices?${params}`);
            const data = await res.json();

            invoices = data.data;
            invoicePagination = {
                currentPage: data.current_page,
                lastPage: data.last_page,
                perPage: data.per_page,
                total: data.total,
                from: data.from,
                to: data.to
            };

            renderInvoices(invoices);
            updateSortButtons();
            renderPagination();
        }

        function renderInvoices(list) {

            const table = document.getElementById('invoiceTable');

            if (list.length === 0) {
                table.innerHTML = `
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-500">
                            No invoices found.
                        </td>
                    </tr>
                `;

                return;
            }

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
        ${invoice.created_at ? new Date(invoice.created_at).toLocaleString() : '-'}
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

        function setSort(sort) {
            currentSort = sort;
            loadInvoices(1);
        }

        function updateSortButtons() {
            const createdButton = document.getElementById('sort-created');
            const dueButton = document.getElementById('sort-due');

            createdButton.className = `inline-flex items-center gap-1 font-medium ${currentSort === 'created' ? 'text-gray-900' : 'hover:text-gray-700'}`;
            dueButton.className = `inline-flex items-center gap-1 font-medium ${currentSort === 'due' ? 'text-gray-900' : 'hover:text-gray-700'}`;

            createdButton.innerHTML = `Created${currentSort === 'created' ? ' <span>&darr;</span>' : ''}`;
            dueButton.innerHTML = `Due${currentSort === 'due' ? ' <span>&darr;</span>' : ''}`;
        }

        function renderPagination() {

            const pagination = document.getElementById('invoicePagination');

            if (!invoicePagination) {
                pagination.innerHTML = '';
                return;
            }

            if (invoicePagination.lastPage <= 1) {
                pagination.innerHTML = invoicePagination.total
                    ? `<div>Showing ${invoicePagination.from}-${invoicePagination.to} of ${invoicePagination.total} invoices</div>`
                    : '';
                return;
            }

            pagination.innerHTML = `
                <div>
                    Showing ${invoicePagination.from}-${invoicePagination.to} of ${invoicePagination.total} invoices
                </div>

                <div class="flex items-center gap-2">
                    <button
                        class="px-3 py-2 border rounded-lg ${invoicePagination.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white'}"
                        onclick="changePage(${invoicePagination.currentPage - 1})"
                        ${invoicePagination.currentPage === 1 ? 'disabled' : ''}
                    >
                        Previous
                    </button>

                    <span class="px-3 py-2">
                        Page ${invoicePagination.currentPage} of ${invoicePagination.lastPage}
                    </span>

                    <button
                        class="px-3 py-2 border rounded-lg ${invoicePagination.currentPage === invoicePagination.lastPage ? 'opacity-50 cursor-not-allowed' : 'hover:bg-white'}"
                        onclick="changePage(${invoicePagination.currentPage + 1})"
                        ${invoicePagination.currentPage === invoicePagination.lastPage ? 'disabled' : ''}
                    >
                        Next
                    </button>
                </div>
            `;
        }

        function changePage(page) {

            if (!invoicePagination) {
                return;
            }

            if (page < 1 || page > invoicePagination.lastPage) {
                return;
            }

            loadInvoices(page);
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
            loadInvoices(currentPage);
        }

        document.getElementById('invoiceSearch')
            .addEventListener('input', () => {

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadInvoices(1), 400);

            });

        document.getElementById('statusFilter')
            .addEventListener('change', () => loadInvoices(1));

        loadInvoices();

    </script>

</x-app-layout>
