@extends('auth.layout')
@section('title', 'Sign in')
@section('screen')
<form method="POST" action="{{ route('login') }}" style="display:flex;flex-direction:column;gap:18px">
  @csrf
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Welcome back</h1><p style="margin:6px 0 0;color:#8A8A94">Sign in to continue building.</p></div>
  @if (session('status'))<div style="padding:10px 12px;border-radius:8px;background:#12211A;color:#A8E0B8;font-size:13px">{{ session('status') }}</div>@endif
  <label class="lbl"><span>Email</span><input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus></label>
  <label class="lbl"><span style="display:flex;justify-content:space-between">Password<a href="{{ route('password.request') }}" style="color:#C7BDFF">Forgot password?</a></span><input class="inp" type="password" name="password" required></label>
  @error('email')<div class="err">{{ $message }}</div>@enderror
  <label style="display:flex;align-items:center;gap:8px;color:#9A9AA5;font-size:13px"><input type="checkbox" name="remember" value="1" checked style="accent-color:var(--accent)">Keep me signed in</label>
  <button class="btn primary lg">Sign in</button>
  @if (\App\Support\Settings::get('allow_signups'))
  <div style="text-align:center;color:#8A8A94;font-size:13.5px">New here? <a href="{{ route('register') }}">Create an account</a></div>
  @endif
</form>
@endsection
