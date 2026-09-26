@extends('admin.layout', ['page' => 'license'])

@section('content')
@php($code = $settings['license_code'])
<div data-screen-label="License & Updates" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,400px),1fr));gap:16px;align-items:start">
  <form method="POST" action="{{ route('admin.license.save') }}" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    @csrf
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600">License</span><span style="font-size:12px;padding:3px 10px;border-radius:10px;background:{{ $code ? '#15261C' : '#2A2214' }};color:{{ $code ? '#58C98A' : '#E8B66B' }}">● {{ $code ? 'Saved' : 'Not added' }}</span></div>
    @foreach ([['Licensed to', $settings['company_name'], 'Geist'], ['Installation ID', 'inst_'.substr(md5(config('app.key')), 0, 4).'••••••'.substr(md5(config('app.key')), -4), 'Geist Mono'], ['Installed', isset($meta['installed_at']) ? \Illuminate\Support\Carbon::parse($meta['installed_at'])->format('M j, Y') : '—', 'Geist'], ['Last saved', $settings['license_verified_at'] ? \Illuminate\Support\Carbon::parse($settings['license_verified_at'])->diffForHumans() : '—', 'Geist']] as [$k, $v, $f])
      <div style="display:flex;justify-content:space-between;gap:12px;padding:10px 18px;border-bottom:1px solid #16161B"><span style="color:#8A8A94">{{ $k }}</span><span style="font-family:{{ $f }},ui-monospace,sans-serif;font-size:13px;text-align:right">{{ $v }}</span></div>
    @endforeach
    <div style="padding:14px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <label class="lbl"><span>Purchase code</span><input class="inp sm mono" name="license_code" value="{{ $code }}" placeholder="xxxxxxxx-xxxx-…"></label>
      <label class="lbl"><span>Domain</span><input class="inp sm mono" name="license_domain" value="{{ $settings['license_domain'] ?: request()->getHost() }}"></label>
    </div>
    <div style="padding:0 18px 14px;display:flex;gap:8px;flex-wrap:wrap"><button class="btn sm" style="height:30px">Save license</button></div>
  </form>
  <form method="POST" action="{{ route('admin.license.save') }}" x-data="{ backup: {{ $settings['backup_before_update'] ? 'true' : 'false' }}, busy: false }" @submit="busy = true" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    @csrf <input type="hidden" name="action" value="update"><input type="hidden" name="backup" :value="backup ? 1 : 0">
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;font-weight:600">Software updates</div>
    <div style="padding:18px;display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;gap:24px;flex-wrap:wrap"><div><div style="font-size:12px;color:#6E6E79">You have</div><div class="mono" style="font-size:20px;margin-top:3px">v{{ $meta['version'] ?? config('studio.version') }}</div></div><div style="font-size:20px;color:#3A3A44;align-self:flex-end">→</div><div><div style="font-size:12px;color:#6E6E79">Uploaded files</div><div class="mono" style="font-size:20px;margin-top:3px;color:#C7BDFF">v{{ config('studio.version') }}</div></div></div>
      <div style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:#B4B4BE;line-height:1.5">
        @if ($pending)
          <div style="color:#E8B66B">{{ count($pending) }} database update{{ count($pending) > 1 ? 's' : '' }} waiting to be applied:</div>
          @foreach (array_slice($pending, 0, 5) as $m)<div class="mono" style="font-size:12px">+ {{ $m }}</div>@endforeach
        @else
          <div>✓ Your database is up to date with the uploaded files.</div>
          <div style="color:#8A8A94">To update: upload the new release over this folder (keep <span class="mono">.env</span> and <span class="mono">storage/</span>), then come back here and click Update now.</div>
        @endif
      </div>
      <label @click.prevent="backup = !backup" style="display:flex;gap:10px;align-items:center;cursor:pointer;padding:10px 12px;border:1px solid #1F1F26;border-radius:8px"><span :style="{ background: backup ? 'var(--accent)' : 'transparent', borderColor: backup ? 'var(--accent)' : '#3A3A44' }" style="width:16px;height:16px;border-radius:4px;display:grid;place-items:center;font-size:11px;color:#fff;border:1px solid" x-text="backup ? '✓' : ''"></span><span style="flex:1">Back up everything first <span style="color:#58C98A;font-size:12px">(recommended)</span></span></label>
      <div style="display:flex;gap:8px"><button class="btn primary" style="height:36px" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" x-text="busy ? 'Updating…' : '{{ $pending ? 'Update now' : 'Re-run update' }}'">Update now</button></div>
      <div style="font-size:12px;color:#6E6E79;line-height:1.5">Backups are saved to <span class="mono">storage/app/backups</span>. Takes a few seconds; users won’t notice.</div>
    </div>
  </form>
</div>
@endsection
