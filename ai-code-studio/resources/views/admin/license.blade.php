@extends('admin.layout', ['page' => 'license'])

@section('content')
@php
  $code = $settings['license_code'];
  $st = $settings['license_status'] ?? null;
  $stMap = ['active' => ['Active', '#58C98A', '#15261C'], 'unverified' => ['Saved (not verified)', '#E8B66B', '#2A2214'], 'unreachable' => ['Server unreachable', '#E8B66B', '#2A2214'], 'revoked' => ['Revoked', '#E5695E', '#2A1616'], 'in_use' => ['Used on another domain', '#E5695E', '#2A1616'], 'invalid' => ['Invalid', '#E5695E', '#2A1616'], 'inactive' => ['Deactivated', '#8A8A94', '#1C1C22']];
  [$stLabel, $stC, $stB] = $code ? ($stMap[$st] ?? ['Not verified', '#E8B66B', '#2A2214']) : ['Not added', '#E8B66B', '#2A2214'];
  $update = session('update');
  $client = app(\App\Services\Licensing\LicenseClient::class);
@endphp
<div data-screen-label="License & Updates" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,400px),1fr));gap:16px;align-items:start">
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600">License</span><span style="font-size:12px;padding:3px 10px;border-radius:10px;background:{{ $stB }};color:{{ $stC }}">● {{ $stLabel }}</span></div>
    @foreach ([['Type', $settings['license_type'] ? ucfirst($settings['license_type']).' licence' : '—', 'Geist'], ['Licence key', $code ? substr($code, 0, 8).'••••'.substr($code, -4) : '—', 'Geist Mono'], ['Domain', $settings['license_domain'] ?: \App\Services\Licensing\LicenseClient::domain(), 'Geist Mono'], ['Installation ID', \App\Services\Licensing\LicenseClient::instanceId(), 'Geist Mono'], ['Support until', $settings['license_supported_until'] ? \Illuminate\Support\Carbon::parse($settings['license_supported_until'])->toFormattedDateString() : '—', 'Geist'], ['Last verified', $settings['license_verified_at'] ? \Illuminate\Support\Carbon::parse($settings['license_verified_at'])->diffForHumans() : '—', 'Geist']] as [$k, $v, $f])
      <div style="display:flex;justify-content:space-between;gap:12px;padding:10px 18px;border-bottom:1px solid #16161B"><span style="color:#8A8A94">{{ $k }}</span><span style="font-family:{{ $f }},ui-monospace,sans-serif;font-size:13px;text-align:right">{{ $v }}</span></div>
    @endforeach
    <form method="POST" action="{{ route('admin.license.save') }}" style="padding:14px 18px;display:grid;grid-template-columns:2fr 1fr;gap:10px;align-items:end">@csrf
      <label class="lbl"><span>{{ $code ? 'Replace licence key' : 'Licence key or purchase code' }}</span><input class="inp sm mono" name="license_code" placeholder="ACS-XXXX-XXXX-XXXX-XXXX" required></label>
      <button class="btn sm primary" style="height:34px">Activate</button>
    </form>
    @if ($code)
    <div style="padding:0 18px 14px;display:flex;gap:8px;flex-wrap:wrap">
      <form method="POST" action="{{ route('admin.license.save') }}" class="inline">@csrf<input type="hidden" name="action" value="verify"><button class="btn sm">Verify now</button></form>
      <form method="POST" action="{{ route('admin.license.save') }}" class="inline" onsubmit="return confirm('Deactivate this licence here so you can activate it on another domain?')">@csrf<input type="hidden" name="action" value="deactivate"><button class="btn sm">Move to another domain</button></form>
    </div>
    @endif
    @unless ($client->configured())
      <div style="padding:0 18px 14px;font-size:12px;color:#6E6E79;line-height:1.5">No licence server is configured (STUDIO_LICENSE_URL), so keys are only format-checked. A revoked or invalid licence only shows this warning — your platform keeps working.</div>
    @endunless
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600">Software updates</span>
      <form method="POST" action="{{ route('admin.license.save') }}" class="inline">@csrf<input type="hidden" name="action" value="updates"><button class="btn xs">Check for updates</button></form>
    </div>
    <div style="padding:18px;display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;gap:24px;flex-wrap:wrap"><div><div style="font-size:12px;color:#6E6E79">You have</div><div class="mono" style="font-size:20px;margin-top:3px">v{{ $meta['version'] ?? config('studio.version') }}</div></div>@if ($update && ! empty($update['latest']))<div style="font-size:20px;color:#3A3A44;align-self:flex-end">→</div><div><div style="font-size:12px;color:#6E6E79">Latest</div><div class="mono" style="font-size:20px;margin-top:3px;color:#C7BDFF">v{{ $update['latest'] }}</div></div>@endif</div>
      @if ($update && ! empty($update['newer']))
        <div style="border:1px solid #2A2540;background:#110F1A;border-radius:9px;padding:12px 14px;display:flex;flex-direction:column;gap:8px">
          <div style="white-space:pre-wrap;color:#C9C9D1;font-size:13px;line-height:1.5">{{ $update['notes'] ?: 'New version available.' }}</div>
          @if ($update['download'])<a href="{{ $update['download'] }}" class="btn primary sm" style="align-self:flex-start">Download v{{ $update['latest'] }}</a>@else<div class="err">Your support period has ended — renew to download updates.</div>@endif
          <div style="font-size:12px;color:#8A8A94;line-height:1.5">Then upload the files over this folder (keep <span class="mono">.env</span>, <span class="mono">storage/</span> and <span class="mono">public/uploads/</span>) and click “Finish update” below. See docs/UPGRADING.md.</div>
        </div>
      @endif
      <form method="POST" action="{{ route('admin.license.save') }}" x-data="{ backup: {{ $settings['backup_before_update'] ? 'true' : 'false' }}, busy: false }" @submit="busy = true" style="display:flex;flex-direction:column;gap:12px">
        @csrf <input type="hidden" name="action" value="update"><input type="hidden" name="backup" :value="backup ? 1 : 0">
        <div style="font-size:13px;color:#B4B4BE;line-height:1.5">
          @if ($pending)
            <div style="color:#E8B66B">{{ count($pending) }} database update{{ count($pending) > 1 ? 's' : '' }} waiting — uploaded files are v{{ config('studio.version') }}.</div>
          @elseif (($meta['version'] ?? null) && version_compare($meta['version'], config('studio.version'), '<'))
            <div style="color:#E8B66B">Files are v{{ config('studio.version') }} — click Finish update to complete the upgrade.</div>
          @else
            <div>✓ Your database is up to date with the uploaded files.</div>
          @endif
        </div>
        <label @click.prevent="backup = !backup" style="display:flex;gap:10px;align-items:center;cursor:pointer;padding:10px 12px;border:1px solid #1F1F26;border-radius:8px"><span :style="{ background: backup ? 'var(--accent)' : 'transparent', borderColor: backup ? 'var(--accent)' : '#3A3A44' }" style="width:16px;height:16px;border-radius:4px;display:grid;place-items:center;font-size:11px;color:#fff;border:1px solid" x-text="backup ? '✓' : ''"></span><span style="flex:1">Back up everything first <span style="color:#58C98A;font-size:12px">(recommended)</span></span></label>
        <div><button class="btn primary" style="height:36px" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" x-text="busy ? 'Updating…' : 'Finish update'">Finish update</button></div>
        <div style="font-size:12px;color:#6E6E79;line-height:1.5">Backups are saved to <span class="mono">storage/app/backups</span>.</div>
      </form>
    </div>
  </div>
</div>
@endsection
