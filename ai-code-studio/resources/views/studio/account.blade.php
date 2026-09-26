@extends('studio.layout')
@section('title', 'Account & security')

@section('main')
<div style="flex:1;overflow:auto;padding:28px 28px 60px">
  <div style="max-width:720px;margin:0 auto;display:flex;flex-direction:column;gap:18px">
    <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">{{ __('Account & security') }}</h1><div style="color:#8A8A94;margin-top:5px">{{ $user->name }} · {{ $user->email }}</div></div>

    <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
      <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;font-weight:600">{{ __('Plan & credits') }}</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1px;background:#1C1C22">
        @foreach ([['Plan', $user->is_admin ? 'Admin' : ($user->plan?->name ?? '—').($user->plan_expires_at ? ' · until '.$user->plan_expires_at->toFormattedDateString() : '')], ['Credits left', $user->is_admin ? 'Unlimited' : number_format($user->credits)], ['Projects', $user->projects()->count().($user->plan?->max_projects ? ' of '.$user->plan->max_projects : '')], ['Workspace', $user->isDeveloper() ? 'Developer' : 'Simple']] as [$k, $v])
          <div style="background:#0E0E11;padding:12px 18px"><div style="font-size:12px;color:#6E6E79">{{ $k }}</div><div style="margin-top:3px">{{ $v }}</div></div>
        @endforeach
      </div>
    </div>

    <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
      <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;align-items:center"><div><div style="font-weight:600">{{ __('Two-step verification') }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ __('Ask for a code from your phone when you sign in.') }}</div></div><span style="font-size:12px;padding:3px 10px;border-radius:10px;background:{{ $user->hasTwoFactor() ? '#15261C' : '#1C1C22' }};color:{{ $user->hasTwoFactor() ? '#58C98A' : '#8A8A94' }}">{{ $user->hasTwoFactor() ? '● On' : 'Off' }}</span></div>
      <div style="padding:16px 18px;display:flex;flex-direction:column;gap:12px">
        @if ($user->hasTwoFactor())
          <form method="POST" action="{{ route('studio.account.2fa.disable') }}">@csrf @method('DELETE')<button class="btn danger">{{ __('Turn off two-step verification') }}</button></form>
        @elseif ($pendingSecret)
          <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-start">
            <div id="qr" style="background:#fff;padding:10px;border-radius:8px;width:152px;height:152px"></div>
            <div style="flex:1;min-width:220px;display:flex;flex-direction:column;gap:10px;line-height:1.5">
              <div>{{ __('1. Scan this with Google Authenticator, 1Password or Authy.') }}</div>
              <div style="font-size:12.5px;color:#8A8A94">{{ __('Can’t scan? Enter this key:') }} <span class="mono" style="color:#E4E4E9;word-break:break-all">{{ chunk_split($pendingSecret, 4, ' ') }}</span></div>
              <div>{{ __('2. Type the 6-digit code it shows:') }}</div>
              <form method="POST" action="{{ route('studio.account.2fa.confirm') }}" style="display:flex;gap:8px">@csrf<input name="code" inputmode="numeric" maxlength="6" class="inp mono" style="width:140px;letter-spacing:.2em" placeholder="123456" autofocus><button class="btn primary" style="height:40px">{{ __('Turn on') }}</button></form>
              @error('code')<div class="err">{{ $message }}</div>@enderror
            </div>
          </div>
          @push('scripts')
          <script src="{{ asset('js/qrcode.min.js') }}"></script>
          <script>new QRCode(document.getElementById('qr'), { text: @json($otpUri), width: 132, height: 132 });</script>
          @endpush
        @else
          <form method="POST" action="{{ route('studio.account.2fa') }}">@csrf<button class="btn primary">{{ __('Set up two-step verification') }}</button></form>
        @endif
      </div>
    </div>

    <form method="POST" action="{{ route('studio.account.password') }}" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
      @csrf
      <div style="padding:14px 18px;border-bottom:1px solid #1C1C22"><div style="font-weight:600">{{ __('Change password') }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ __('Other devices stay signed in until their session ends.') }}</div></div>
      <div style="padding:16px 18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <label class="lbl"><span>{{ __('Current password') }}</span><input class="inp" type="password" name="current_password" required>@error('current_password')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="lbl"><span>{{ __('New password') }}</span><input class="inp" type="password" name="password" required>@error('password')<span class="err">{{ $message }}</span>@enderror</label>
        <label class="lbl"><span>{{ __('Confirm new password') }}</span><input class="inp" type="password" name="password_confirmation" required></label>
      </div>
      <div style="padding:0 18px 16px"><button class="btn primary">{{ __('Update password') }}</button></div>
    </form>
    <form method="POST" action="{{ route('studio.account.locale') }}" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">@csrf
      <div><div style="font-weight:600">{{ __('Language') }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ __('Used for the interface and emails.') }}</div></div>
      <select name="locale" class="inp sm" style="width:auto" onchange="this.form.submit()">@foreach (config('studio.locales') as $code => $label)<option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>@endforeach</select>
    </form>

    <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <div><div style="font-weight:600">{{ __('Download your data') }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ __('A zip with your profile, projects, files, chats and payments.') }}</div></div>
      <a href="{{ route('studio.account.export') }}" class="btn">{{ __('Download') }}</a>
    </div>

    <form method="POST" action="{{ route('studio.account.destroy') }}" x-data="{ open: false }" style="border:1px solid #3A1D1D;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;flex-direction:column;gap:12px">@csrf @method('DELETE')
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div><div style="font-weight:600;color:#F2B8B2">{{ __('Delete account') }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ __('Permanently deletes your account, projects and published apps. This can’t be undone.') }}</div></div>
        <span @click="open = !open" class="btn danger" style="border-color:#3A1D1D">{{ __('Delete…') }}</span>
      </div>
      <div x-show="open" x-cloak class="d-flex" style="gap:8px;flex-wrap:wrap;align-items:center"><input class="inp" type="password" name="password" placeholder="{{ __('Confirm with your password') }}" style="max-width:280px" required><button class="btn" style="background:#E5695E;border:none;color:#fff">{{ __('Delete my account') }}</button></div>
      @error('password')<div class="err">{{ $message }}</div>@enderror
    </form>
  </div>
</div>
@endsection
