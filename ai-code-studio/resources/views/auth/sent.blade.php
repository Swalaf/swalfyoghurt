@extends('auth.layout')
@section('title', 'Check your inbox')
@section('screen')
<div style="display:flex;flex-direction:column;gap:18px">
  <div style="width:44px;height:44px;border-radius:10px;background:var(--accent-soft);color:#C7BDFF;display:grid;place-items:center;font-size:20px">✉</div>
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">{{ __('Check your inbox') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('If an account exists for') }} <span style="color:#E4E4E9">{{ $email ?: 'that address' }}</span>{{ __(', we sent it a reset link. It expires in 60 minutes.') }}</p></div>
  <form method="POST" action="{{ route('password.email') }}" x-data="{ left: 45 }" x-init="let t = setInterval(() => { if (--left <= 0) clearInterval(t) }, 1000)" style="text-align:center;color:#8A8A94;font-size:13.5px">
    @csrf <input type="hidden" name="email" value="{{ $email }}">
    {{ __('Didn’t get it?') }} <button :disabled="left > 0" style="background:none;border:none;color:#C7BDFF;cursor:pointer;font-size:13.5px;padding:0" x-text="left > 0 ? 'Resend in 0:' + String(left).padStart(2, '0') : 'Resend'"></button>
  </form>
  <a href="{{ route('login') }}" class="btn lg">{{ __('← Back to sign in') }}</a>
</div>
@endsection
