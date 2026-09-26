@extends('auth.layout')
@section('title', 'Two-step verification')
@section('screen')
<form method="POST" action="{{ route('two-factor') }}" style="display:flex;flex-direction:column;gap:18px">
  @csrf
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Two-step verification</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">Enter the 6-digit code from your authenticator app.</p></div>
  @include('auth.partials.otp', ['button' => 'Verify'])
  <div style="text-align:center;color:#8A8A94;font-size:13.5px">Lost your device? @if ($s = \App\Support\Settings::get('support_email'))<a href="mailto:{{ $s }}">Contact support</a>@else Contact your admin.@endif</div>
</form>
@endsection
