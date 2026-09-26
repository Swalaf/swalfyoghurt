@extends('auth.layout')
@section('title', __('Thank you'))
@section('screen')
<div style="display:flex;flex-direction:column;gap:16px">
  @if ($license)
    <div style="width:44px;height:44px;border-radius:10px;background:#15261C;color:#58C98A;display:grid;place-items:center;font-size:20px">✓</div>
    <div><h1 style="margin:0;font-size:26px;font-weight:600">{{ __('Your licence key') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('We also emailed it to :email.', ['email' => $license->email]) }}</p></div>
    <div x-data="{ c: false }" style="display:flex;gap:8px;align-items:center;border:1px solid #26262E;border-radius:9px;background:#111115;padding:0 6px 0 14px;height:48px"><span class="mono" style="flex:1;font-size:15px;letter-spacing:.04em">{{ $license->key }}</span><span @click="navigator.clipboard.writeText(@js($license->key)); c = true" class="btn sm" x-text="c ? '{{ __('Copied') }}' : '{{ __('Copy') }}'"></span></div>
    @if ($download)<a href="{{ $download }}" class="btn primary lg">{{ __('Download v:v', ['v' => $release->version]) }}</a>@endif
    <div style="color:#9A9AA5;line-height:1.6;font-size:13.5px">{{ __('Next: download the latest release, upload it to your server and open /install. Enter this key in the License step.') }}</div>
  @else
    <div><h1 style="margin:0;font-size:24px;font-weight:600">{{ __('Waiting for payment confirmation…') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('This usually takes a few seconds. Your key will also arrive by email.') }}</p></div>
    <script>setTimeout(() => location.reload(), 4000)</script>
  @endif
</div>
@endsection
