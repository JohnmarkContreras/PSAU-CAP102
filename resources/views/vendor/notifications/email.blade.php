@component('mail::message')
{{-- Header --}}
<table align="center" width="100%">
<tr>
    <td align="center">
        <img src="{{ asset('PSAU_Logo.png') }}" alt="PSAU Logo" width="90" height="90" style="margin-bottom: 15px;">
        <h2 style="color:#006400; font-weight: bold;">PSAU Tamarind R&DE</h2>
    </td>
</tr>
</table>

{{-- Body --}}
<p style="font-size: 15px; color: #333;">
You are receiving this email because we received a password reset request for your account.
</p>

@component('mail::button', ['url' => $actionUrl, 'color' => 'success'])
Reset Password
@endcomponent

<p style="font-size: 14px; color: #333;">
This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.<br>
If you did not request a password reset, no further action is required.
</p>

{{-- Footer --}}
<p style="margin-top: 25px; color: #666; font-size: 12px;">
Regards,<br>
<strong>PSAU Tamarind R&DE Team</strong><br>
<em>Pampanga State Agricultural University</em>
</p>

@endcomponent
