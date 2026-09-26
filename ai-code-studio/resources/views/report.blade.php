@extends('auth.layout')
@section('title', __('Report an app'))
@section('screen')
@if (session('reported'))
  <div style="display:flex;flex-direction:column;gap:14px">
    <div style="width:44px;height:44px;border-radius:10px;background:#15261C;color:#58C98A;display:grid;place-items:center;font-size:20px">✓</div>
    <div><h1 style="margin:0;font-size:24px;font-weight:600">{{ __('Thanks — we’ll take a look') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('Our team reviews every report. If the app breaks our rules it will be removed.') }}</p></div>
  </div>
@else
<form method="POST" action="{{ route('report.store', $subdomain) }}" style="display:flex;flex-direction:column;gap:16px">
  @csrf
  <div><h1 style="margin:0;font-size:24px;font-weight:600;letter-spacing:-0.02em">{{ __('Report an app') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('Tell us what’s wrong with') }} <span class="mono" style="color:#E4E4E9">{{ \App\Jobs\DeployProject::url($subdomain) }}</span></p></div>
  <label class="lbl"><span>{{ __('What’s the problem?') }}</span>
    <select class="inp" name="reason" required>@foreach (\App\Models\AbuseReport::REASONS as $k => $v)<option value="{{ $k }}" @selected(old('reason') === $k)>{{ __($v) }}</option>@endforeach</select>
  </label>
  <label class="lbl"><span>{{ __('Details (optional)') }}</span><textarea class="inp" name="details" style="height:auto;min-height:90px;padding:10px 12px" maxlength="2000">{{ old('details') }}</textarea></label>
  <label class="lbl"><span>{{ __('Your email (optional)') }}</span><input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com"></label>
  <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
  @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
  <button class="btn primary lg">{{ __('Send report') }}</button>
</form>
@endif
@endsection
