<p>Hi {{ $prediction->user->name }},</p>

<p>We've predicted a harvest for your tree <strong>{{ $prediction->tree_code }}</strong>.</p>

<p>
    📅 Expected Harvest Date: <strong>{{ \Carbon\Carbon::parse($prediction->predicted_date)->format('F j, Y') }}</strong><br>
    🌾 Estimated Quantity: <strong>{{ $prediction->predicted_quantity }} kg</strong>
</p>

<p>We'll send you reminders as the date approaches. Stay tuned!</p>

<p>— Tamarind App Team</p>