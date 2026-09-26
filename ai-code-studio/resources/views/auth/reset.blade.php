@extends('auth.layout')
@section('title', 'Choose a new password')
@section('screen')
<form method="POST" action="{{ route('password.update') }}" style="display:flex;flex-direction:column;gap:18px">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Choose a new password</h1><p style="margin:6px 0 0;color:#8A8A94">You’ll be signed out on all other devices afterwards.</p></div>
  <label class="lbl"><span>Email</span><input class="inp" type="email" name="email" value="{{ old('email', $email) }}" required></label>
  <label class="lbl"><span>New password</span><input class="inp" type="password" name="password" required autofocus></label>
  <label class="lbl"><span>Confirm password</span><input class="inp" type="password" name="password_confirmation" required></label>
  @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
  <button class="btn primary lg">Update password</button>
</form>
@endsection
