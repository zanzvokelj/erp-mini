<x-app-layout>

    <div class="p-8 space-y-8">

        <div class="flex justify-between items-center">

            <div>
                <h1 class="text-2xl font-semibold">
                    Invoice <span id="invoiceNumber"></span>
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    Customer: <span id="invoiceCustomer"></span>
                </p>

                <p class="text-sm text-gray-500">
                    Order: <span id="invoiceOrder"></span>
                </p>
            </div>

            <div class="flex gap-2">

                <button
                    onclick="downloadPDF()"
                    class="px-4 py-2 text-blue-500 text-md">
                    Download PDF
                </button>

                <button
                    onclick="quickPay()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                    Add Payment
                </button>

            </div>

        </div>


        <!-- SUMMARY -->
        <div class="bg-white rounded-xl shadow border p-6">

            <div class="grid grid-cols-3 gap-6">

                <div>
                    <p class="text-gray-500 text-sm">Total</p>
                    <p id="invoiceTotal" class="text-xl font-semibold"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-sm">Paid</p>
                    <p id="invoicePaid" class="text-xl font-semibold text-green-600"></p>
                </div>

                <div>
                    <p class="text-gray-500 text-sm">Remaining</p>
                    <p id="invoiceRemaining" class="text-xl font-semibold text-red-600"></p>
                </div>

            </div>

            <div class="w-full bg-gray-200 rounded h-2 mt-4">
                <div id="paymentProgress"
                     class="bg-green-500 h-2 rounded"
                     style="width:0%">
                </div>
            </div>

        </div>


        <!-- ITEMS -->
        <div class="bg-white rounded-xl shadow border">

            <div class="p-4 border-b font-medium">
                Invoice Items
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50">
                <tr class="text-left text-gray-500">
                    <th class="p-4">Product</th>
                    <th class="p-4">Qty</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Subtotal</th>
                </tr>
                </thead>

                <tbody id="invoiceItems"></tbody>

            </table>

        </div>


        <!-- PAYMENTS -->
        <div class="bg-white rounded-xl shadow border">

            <div class="p-4 border-b font-medium">
                Payment History
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50">
                <tr class="text-left text-gray-500">
                    <th class="p-4">Date</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Method</th>
                </tr>
                </thead>

                <tbody id="paymentTable"></tbody>

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
                class="w-full border rounded-lg px-3 py-2"
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

        const invoiceId = {{ $invoiceId }};
        let invoice = null;

        async function loadInvoice() {

            const res = await apiFetch(`/api/v1/invoices/${invoiceId}`);
            invoice = await res.json();

            renderInvoice();
        }

        function renderInvoice() {

            document.getElementById('invoiceNumber').innerText = invoice.invoice_number;
            document.getElementById('invoiceOrder').innerText = invoice.order?.order_number ?? '-';
            document.getElementById('invoiceCustomer').innerText = invoice.customer.name;

            const paid = invoice.payments
                ? invoice.payments.reduce((sum,p)=>sum+parseFloat(p.amount),0)
                : 0;

            const remaining = parseFloat(invoice.total) - paid;

            document.getElementById('invoiceTotal').innerText = "€"+parseFloat(invoice.total).toFixed(2);
            document.getElementById('invoicePaid').innerText = "€"+paid.toFixed(2);
            document.getElementById('invoiceRemaining').innerText = "€"+remaining.toFixed(2);

            const progress = (paid / invoice.total) * 100;
            document.getElementById('paymentProgress').style.width = progress + "%";

            document.getElementById('invoiceItems').innerHTML =
                invoice.items.map(item => `
<tr class="border-b">
<td class="p-4">${item.product.name}</td>
<td class="p-4">${item.quantity}</td>
<td class="p-4">€${parseFloat(item.price).toFixed(2)}</td>
<td class="p-4">€${parseFloat(item.subtotal).toFixed(2)}</td>
</tr>
`).join('');

            document.getElementById('paymentTable').innerHTML =
                invoice.payments.map(payment => `
<tr class="border-b">
<td class="p-4">${payment.paid_at}</td>
<td class="p-4">€${parseFloat(payment.amount).toFixed(2)}</td>
<td class="p-4">${payment.payment_method ?? '-'}</td>
</tr>
`).join('');
        }


        // 🔥 MODAL LOGIC
        function quickPay(){

            const paid = invoice.payments
                ? invoice.payments.reduce((sum,p)=>sum+parseFloat(p.amount),0)
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

            if(parseFloat(amount) > parseFloat(document.getElementById('paymentAmount').max)){
                alert("Amount exceeds remaining balance");
                return;
            }

            const res = await apiFetch(`/api/v1/invoices/${invoiceId}/payments`,{
                method:"POST",
                headers:{
                    "Content-Type":"application/json"
                },
                body:JSON.stringify({
                    amount:amount,
                    payment_method:method
                })
            });

            if(!res.ok){
                alert("Payment failed");
                return;
            }

            closePaymentModal();
            loadInvoice();
        }


        function downloadPDF() {
            window.open(`/api/v1/invoices/${invoiceId}/pdf`, '_blank');
        }

        loadInvoice();

    </script>

</x-app-layout>
