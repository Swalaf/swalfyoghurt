@extends('auth.layout')
@section('title', 'Create your account')
@section('screen')
@if ($closed)
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">{{ __('Sign-ups are closed') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('This platform is invite-only right now. Ask an admin for an account.') }}</p></div>
  <a href="{{ route('login') }}" class="btn lg">{{ __('← Back to sign in') }}</a>
@else
<form method="POST" action="{{ route('register') }}" style="display:flex;flex-direction:column;gap:16px" x-data="{ pw: '' }">
  @csrf
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">{{ __('Create your account') }}</h1><p style="margin:6px 0 0;color:#8A8A94">{{ __('Free to start. No credit card needed.') }}</p></div>
  @if (session('pending_idea'))
    <div style="border:1px solid #2A2540;background:#110F1A;border-radius:9px;padding:10px 12px;font-size:13px;color:#C9C9D1;line-height:1.5"><span style="color:#A99BFF">✦</span> We’ll plan “{{ \Illuminate\Support\Str::limit(session('pending_idea'), 90) }}” right after you sign up.</div>
  @endif
  <label class="lbl"><span>{{ __('Full name') }}</span><input class="inp" name="name" value="{{ old('name') }}" placeholder="{{ __('Ada Okafor') }}" required autofocus>@error('name')<span class="err">{{ $message }}</span>@enderror</label>
  <label class="lbl"><span>{{ __('Work email') }}</span><input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="you@company.com" required>@error('email')<span class="err">{{ $message }}</span>@enderror</label>
  <label class="lbl"><span>{{ __('Password') }}</span><input class="inp" type="password" name="password" x-model="pw" placeholder="{{ __('At least 8 characters') }}" required>
    @php($cols = "['#E5695E', '#E5695E', '#E8B66B', '#58C98A', '#58C98A']")
    <div x-data="{ get score() { return (pw.length >= 8) + /[A-Z]/.test(pw) + /\d/.test(pw) + /[^A-Za-z0-9]/.test(pw) } }" style="display:flex;flex-direction:column;gap:6px">
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px">
        <template x-for="i in 4"><span style="height:3px;border-radius:2px" :style="{ background: i <= score ? {{ $cols }}[score] : '#1F1F26' }"></span></template>
      </div>
      <span style="font-size:12px;color:#6E6E79" x-text="['Too short', 'Weak', 'Okay', 'Strong', 'Very strong'][score]"></span>
    </div>
    @error('password')<span class="err">{{ $message }}</span>@enderror
  </label>
  <label style="display:flex;gap:8px;align-items:flex-start;color:#9A9AA5;font-size:13px;line-height:1.45"><input type="checkbox" name="terms" value="1" @checked(old('terms')) style="accent-color:var(--accent);margin-top:3px" required><span>{!! __('I agree to the :terms and :privacy.', ['terms' => '<a href="'.route('terms').'" target="_blank">'.e(__('Terms of Service')).'</a>', 'privacy' => '<a href="'.route('privacy').'" target="_blank">'.e(__('Privacy Policy')).'</a>']) !!}</span></label>
  @error('terms')<span class="err">{{ $message }}</span>@enderror
  <button class="btn primary lg">{{ __('Create account') }}</button>
  <div style="text-align:center;color:#8A8A94;font-size:13.5px">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></div>
</form>
@endif
@endsection
