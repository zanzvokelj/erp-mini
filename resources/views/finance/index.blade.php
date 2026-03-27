<x-app-layout>

    <div class="p-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Finance Dashboard</h1>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('finance.vat-summary.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    VAT Summary
                </a>

                <a
                    href="{{ route('finance.periods.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Periods
                </a>

                <a
                    href="{{ route('finance.accounts.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Accounts
                </a>

                <a
                    href="{{ route('finance.balance-sheet.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Balance Sheet
                </a>

                <a
                    href="{{ route('finance.profit-and-loss.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Profit &amp; Loss
                </a>

                <a
                    href="{{ route('finance.trial-balance.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    Trial Balance
                </a>

                <a
                    href="{{ route('finance.journal-entries.index') }}"
                    class="px-4 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-50"
                >
                    View Journal
                </a>
            </div>
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

            <div class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
                <p id="overduePaginationSummary" class="text-sm text-gray-500">
                    Showing 0-0 of 0 overdue invoices
                </p>

                <div class="flex items-center gap-2">
                    <button
                        id="overduePrev"
                        type="button"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Previous
                    </button>

                    <span id="overduePageInfo" class="text-sm text-gray-600">Page 1 of 1</span>

                    <button
                        id="overdueNext"
                        type="button"
                        class="px-3 py-2 text-sm border border-gray-200 rounded-lg bg-white hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Next
                    </button>
                </div>
            </div>

        </div>

    </div>

    <script>
        const currencyFormatter = new Intl.NumberFormat('sl-SI', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        function formatMoney(value) {
            return currencyFormatter.format(Number(value ?? 0));
        }

        let overduePage = 1;
        const overduePerPage = 10;

        async function loadFinance(page = 1){
            overduePage = page;

            const res = await fetch(`/api/v1/finance/overview?page=${page}&per_page=${overduePerPage}`);
            const data = await res.json();

            // KPI
            document.getElementById('revenue').innerText =
                formatMoney(data.revenue);

            document.getElementById('outstanding').innerText =
                formatMoney(data.outstanding);

            document.getElementById('overdue').innerText =
                formatMoney(data.overdue);

            document.getElementById('thisMonth').innerText =
                formatMoney(data.this_month);

            // 🔥 TABLE RENDER
            const table = document.getElementById('overdueTable');
            const pagination = data.overdue_pagination ?? {};
            const summary = document.getElementById('overduePaginationSummary');
            const pageInfo = document.getElementById('overduePageInfo');
            const prevButton = document.getElementById('overduePrev');
            const nextButton = document.getElementById('overdueNext');

            summary.innerText = `Showing ${pagination.from ?? 0}-${pagination.to ?? 0} of ${pagination.total ?? 0} overdue invoices`;
            pageInfo.innerText = `Page ${pagination.current_page ?? 1} of ${pagination.last_page ?? 1}`;
            prevButton.disabled = (pagination.current_page ?? 1) <= 1;
            nextButton.disabled = !Boolean(pagination.has_more_pages);

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
<td class="p-4 text-red-600">${formatMoney(i.open_amount ?? i.total)}</td>
<td class="p-4">${i.due_date ?? '-'}</td>

</tr>
`).join('');
        }

        document.getElementById('overduePrev').addEventListener('click', () => {
            if (overduePage > 1) {
                loadFinance(overduePage - 1);
            }
        });

        document.getElementById('overdueNext').addEventListener('click', () => {
            loadFinance(overduePage + 1);
        });

        loadFinance();

    </script>

</x-app-layout>
