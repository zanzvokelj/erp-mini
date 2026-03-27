<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Finance Dashboard</h1>

            <a
                href="{{ route('finance.journal-entries.index') }}"
                class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
            >
                View Journal
            </a>
        </div>

        <!-- KPI -->
        <div class="grid grid-cols-4 gap-6">

            <div class="bg-white p-6 rounded-xl shadow border">
                <p class="text-sm text-gray-500">Revenue</p>
                <p id="revenue" class="text-2xl font-semibold">€0</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow border">
                <p class="text-sm text-gray-500">Outstanding</p>
                <p id="outstanding" class="text-2xl font-semibold text-yellow-600">€0</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow border">
                <p class="text-sm text-gray-500">Overdue</p>
                <p id="overdue" class="text-2xl font-semibold text-red-600">€0</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow border">
                <p class="text-sm text-gray-500">This Month</p>
                <p id="thisMonth" class="text-2xl font-semibold text-green-600">€0</p>
            </div>

        </div>

        <!-- 🔥 OVERDUE TABLE -->
        <div class="bg-white rounded-xl shadow border">

            <div class="p-4 border-b font-medium">
                Overdue Invoices
            </div>

            <table class="w-full text-sm">

                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="p-4 text-left">Invoice</th>
                    <th class="p-4 text-left">Customer</th>
                    <th class="p-4 text-left">Amount</th>
                    <th class="p-4 text-left">Due Date</th>
                </tr>
                </thead>

                <tbody id="overdueTable"></tbody>

            </table>

        </div>

    </div>

    <script>

        async function loadFinance(){

            const res = await fetch('/api/v1/finance/overview');
            const data = await res.json();

            // KPI
            document.getElementById('revenue').innerText =
                "€" + data.revenue.toFixed(2);

            document.getElementById('outstanding').innerText =
                "€" + data.outstanding.toFixed(2);

            document.getElementById('overdue').innerText =
                "€" + data.overdue.toFixed(2);

            document.getElementById('thisMonth').innerText =
                "€" + data.this_month.toFixed(2);

            // 🔥 TABLE RENDER
            const table = document.getElementById('overdueTable');

            if(!data.overdue_invoices.length){
                table.innerHTML = `
<tr>
<td colspan="4" class="p-4 text-center text-gray-500">
No overdue invoices 🎉
</td>
</tr>`;
                return;
            }

            table.innerHTML = data.overdue_invoices.map(i => `
<tr class="border-b hover:bg-gray-50 cursor-pointer"
    onclick="window.location='/invoices/${i.id}'">

<td class="p-4 font-medium">${i.invoice_number}</td>
<td class="p-4">${i.customer?.name ?? '-'}</td>
<td class="p-4 text-red-600">€${parseFloat(i.total).toFixed(2)}</td>
<td class="p-4">${i.due_date ?? '-'}</td>

</tr>
`).join('');
        }

        loadFinance();

    </script>

</x-app-layout>
