<p>Hi {{ $userName }},</p>

<p>Here are the upcoming harvest predictions:</p>

<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>Tree Code</th>
            <th>Predicted Harvest Date</th>
            <th>Predicted Quantity</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($predictions as $prediction)
            <tr>
                <td>{{ $prediction->code }}</td>
                <td>{{ \Carbon\Carbon::parse($prediction->predicted_date)->format('F j, Y') }}</td>
                <td>{{ $prediction->predicted_quantity }} kg</td>
            </tr>
        @endforeach
    </tbody>
</table>