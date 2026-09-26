@extends('auth.layout')
@section('title', 'Reset your password')
@section('screen')
<form method="POST" action="{{ route('password.email') }}" style="display:flex;flex-direction:column;gap:18px">
  @csrf
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Reset your password</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">Enter your email and we’ll send you a link to choose a new password.</p></div>
  <label class="lbl"><span>Email</span><input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
  <button class="btn primary lg">Send reset link</button>
  <a href="{{ route('login') }}" style="text-align:center;color:#8A8A94">← Back to sign in</a>
</form>
@endsection
