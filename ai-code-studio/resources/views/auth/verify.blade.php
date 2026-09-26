@extends('auth.layout')
@section('title', 'Verify your email')
@section('screen')
<div style="display:flex;flex-direction:column;gap:18px">
  <div style="width:44px;height:44px;border-radius:10px;background:var(--accent-soft);color:#C7BDFF;display:grid;place-items:center;font-size:20px">✉</div>
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Verify your email</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">We sent a 6-digit code to <span style="color:#E4E4E9">{{ auth()->user()->email }}</span>.</p></div>
  <form method="POST" action="{{ route('verification.verify') }}">@csrf @include('auth.partials.otp', ['button' => 'Continue'])</form>
  <form method="POST" action="{{ route('verification.resend') }}" style="text-align:center;color:#8A8A94;font-size:13.5px">@csrf Didn’t get it? <button style="background:none;border:none;color:#C7BDFF;cursor:pointer;font-size:13.5px;padding:0">Send a new code</button></form>
</div>
@endsection
