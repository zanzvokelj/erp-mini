<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans; }
        table { width:100%; border-collapse: collapse; }
        td,th { border:1px solid #ccc; padding:8px; }
    </style>
</head>
<body>

<h2>Invoice {{ $invoice->invoice_number }}</h2>

<p>
    Customer: {{ $invoice->customer->name }} <br>
    Issued: {{ $invoice->issued_at }}
</p>

<table>
    <thead>
    <tr>
        <th>Product</th>
        <th>Qty</th>
        <th>Price</th>
        <th>Total</th>
    </tr>
    </thead>

    <tbody>

    @foreach($invoice->items as $item)

        <tr>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->price }}</td>
            <td>{{ $item->subtotal }}</td>
        </tr>

    @endforeach

    </tbody>
</table>

<h3>Total: {{ $invoice->total }}</h3>

</body>
</html>
