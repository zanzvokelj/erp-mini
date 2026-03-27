<?php

namespace App\Accounting;

class AccountingEntryTypes
{
    public const INVOICE_ISSUED = 'invoice_issued';
    public const PAYMENT_RECEIVED = 'payment_received';
    public const PURCHASE_ORDER_RECEIVED = 'purchase_order_received';
    public const SUPPLIER_PAYMENT = 'supplier_payment';
    public const COST_OF_GOODS_SOLD = 'cost_of_goods_sold';
    public const MANUAL_REVERSAL = 'manual_reversal';
}
