@extends('admin.layout', ['page' => 'settings'])

@section('content')
@php([$desc, $actionLabel, $rows] = $sections[$section])
<div data-screen-label="Settings" style="display:flex;flex-wrap:wrap;gap:18px;align-items:flex-start">
  <div style="display:flex;flex-direction:column;gap:2px;width:220px;flex:none">
    @foreach (array_keys($sections) as $name)
      <a href="{{ route('admin.settings', $name) }}" style="padding:8px 12px;border-radius:6px;background:{{ $section === $name ? '#1A1A20' : 'transparent' }};color:{{ $section === $name ? '#F2F2F5' : '#9A9AA5' }}">{{ $name }}</a>
    @endforeach
  </div>
  <form method="POST" action="{{ route('admin.settings.save', $section) }}" style="flex:1;min-width:min(100%,480px);border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    @csrf
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22"><div style="font-weight:600;font-size:15px">{{ $section }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">{{ $desc }}</div></div>
    @foreach ($rows as [$type, $key, $label, $help])
      <div x-data="{ on: {{ ! empty($settings[$key]) ? 'true' : 'false' }} }" style="display:flex;align-items:center;gap:16px;padding:14px 18px;border-bottom:1px solid #16161B;flex-wrap:wrap">
        <div style="flex:1;min-width:220px"><div style="font-weight:500">{{ $label }}</div><div style="font-size:12.5px;color:#6E6E79;margin-top:3px;line-height:1.45">{{ $help }}</div></div>
        @if ($type === 'toggle')
          <input type="hidden" name="{{ $key }}" :value="on ? 1 : 0">
          <span @click="on = !on" :style="{ background: on ? 'var(--accent)' : '#2A2A32', justifyContent: on ? 'flex-end' : 'flex-start' }" style="width:36px;height:20px;border-radius:10px;padding:2px;display:flex;cursor:pointer"><span style="width:16px;height:16px;border-radius:50%;background:#fff"></span></span>
        @elseif ($type === 'select')
          <select class="inp sm" name="{{ $key }}" style="width:260px;max-width:100%">@foreach (\App\Http\Controllers\LandingController::HEADLINES as $k => $v)<option value="{{ $k }}" @selected($settings[$key] === $k)>{{ $v }}</option>@endforeach</select>
        @elseif ($type === 'select-lang')
          <select class="inp sm" name="{{ $key }}" style="width:260px;max-width:100%">@foreach (config('studio.locales') as $code => $label)<option value="{{ $label }}" @selected($settings[$key] === $label)>{{ $label }}</option>@endforeach</select>
        @elseif ($type === 'info')
          <span class="mono" style="font-size:12.5px;color:#C9C9D1">{{ config('studio.publish_disk') === 's3' ? 'Amazon S3 · '.config('filesystems.disks.s3.bucket') : 'Local disk · storage/app/published' }}</span>
        @elseif ($type === 'textarea')
          <textarea class="inp" name="{{ $key }}" placeholder="{{ __('Leave empty to use the built-in template') }}" style="width:100%;height:auto;min-height:220px;padding:10px 12px;line-height:1.5;font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px">{{ $settings[$key] ?? '' }}</textarea>
        @elseif ($type === 'password')
          <input class="inp sm" type="password" name="{{ $key }}" placeholder="{{ ! empty($settings[$key]) ? '•••••••• (saved)' : '' }}" style="width:260px;max-width:100%" autocomplete="new-password">
        @else
          <input class="inp sm" name="{{ $key }}" value="{{ $settings[$key] ?? '' }}" style="width:260px;max-width:100%">
        @endif
      </div>
    @endforeach
    @error('support_email')<div class="err" style="padding:10px 18px">{{ $message }}</div>@enderror
    <div style="padding:14px 18px;display:flex;justify-content:flex-end;gap:8px">
      <button form="settings-action" class="btn sm" style="height:32px" @if (in_array($section, ['Sign-up & login', 'Maintenance'])) formtarget="_blank" @endif>{{ $actionLabel }}</button>
      <button class="btn primary sm" style="height:32px;padding:0 14px">Save</button>
    </div>
  </form>
  <form id="settings-action" method="POST" action="{{ route('admin.settings.action', $section) }}">@csrf</form>
</div>
@endsection
