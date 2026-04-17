<!DOCTYPE html>
<html>
<head>
    <title>Products PDF</title>
    <style>
        body { font-family: Arial; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid black; }
        th { background: #f2f2f2; }
        th, td { padding: 8px; text-align: center; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Products Export</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>SKU</th>
        <th>Price</th>
        <th>Quantity</th>
        <th>Date</th>
    </tr>

    @foreach($products as $p)
    <tr>
        <td>{{ $p->id }}</td>
        <td>{{ $p->name }}</td>
        <td>{{ $p->sku }}</td>
        <td>{{ $p->price }}</td>
        <td>{{ $p->quantity }}</td>
        <td>{{ $p->created_at }}</td>
    </tr>
    @endforeach

</table>

</body>
</html>