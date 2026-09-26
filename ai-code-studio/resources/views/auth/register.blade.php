@extends('auth.layout')
@section('title', 'Create your account')
@section('screen')
@if ($closed)
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Sign-ups are closed</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">This platform is invite-only right now. Ask an admin for an account.</p></div>
  <a href="{{ route('login') }}" class="btn lg">← Back to sign in</a>
@else
<form method="POST" action="{{ route('register') }}" style="display:flex;flex-direction:column;gap:16px" x-data="{ pw: '' }">
  @csrf
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Create your account</h1><p style="margin:6px 0 0;color:#8A8A94">Free to start. No credit card needed.</p></div>
  @if (session('pending_idea'))
    <div style="border:1px solid #2A2540;background:#110F1A;border-radius:9px;padding:10px 12px;font-size:13px;color:#C9C9D1;line-height:1.5"><span style="color:#A99BFF">✦</span> We’ll plan “{{ \Illuminate\Support\Str::limit(session('pending_idea'), 90) }}” right after you sign up.</div>
  @endif
  <label class="lbl"><span>Full name</span><input class="inp" name="name" value="{{ old('name') }}" placeholder="Ada Okafor" required autofocus>@error('name')<span class="err">{{ $message }}</span>@enderror</label>
  <label class="lbl"><span>Work email</span><input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
  <label class="lbl"><span>Password</span><input class="inp" type="password" name="password" x-model="pw" placeholder="At least 8 characters" required>
    @php($cols = "['#E5695E', '#E5695E', '#E8B66B', '#58C98A', '#58C98A']")
    <div x-data="{ get score() { return (pw.length >= 8) + /[A-Z]/.test(pw) + /\d/.test(pw) + /[^A-Za-z0-9]/.test(pw) } }" style="display:flex;flex-direction:column;gap:6px">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px">
        <template x-for="i in 4"><span style="height:3px;border-radius:2px" :style="{ background: i <= score ? {{ $cols }}[score] : '#1F1F26' }"></span></template>
      </div>
      <span style="font-size:12px;color:#6E6E79" x-text="['Too short', 'Weak', 'Okay', 'Strong', 'Very strong'][score]"></span>
    </div>
    @error('password')<span class="err">{{ $message }}</span>@enderror
  </label>
  <button class="btn primary lg">Create account</button>
  <div style="text-align:center;color:#8A8A94;font-size:13.5px">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
</form>
@endif
@endsection
