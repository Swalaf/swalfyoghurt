@extends('admin.layout', ['page' => 'providers'])

@section('cta')
<form method="POST" action="{{ route('admin.providers.store') }}" class="inline">@csrf<input type="hidden" name="driver" value="custom"><button class="btn">+ Add custom API</button></form>
@endsection

@section('content')
@php($sc = ['connected' => '#58C98A', 'slow' => '#E8B66B', 'error' => '#E5695E'])
<div data-screen-label="Admin AI Providers" style="display:flex;flex-direction:column;gap:20px">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px">
    @foreach ($providers as $p)
      @php($on = $p->status !== 'not_configured')
      <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px;display:flex;flex-direction:column;gap:12px">
        <div style="display:flex;align-items:center;gap:10px"><span style="width:34px;height:34px;border-radius:8px;background:#17171C;display:grid;place-items:center;font-weight:600">{{ $p->meta('ini') }}</span><div style="flex:1;min-width:0"><div style="font-weight:600">{{ $p->name }}</div><div style="font-size:12px;color:#6E6E79">{{ $p->meta('kind') }}</div></div><span style="font-size:12px;display:flex;align-items:center;gap:6px;color:{{ $sc[$p->status] ?? '#6E6E79' }}"><span style="width:7px;height:7px;border-radius:50%;background:{{ $sc[$p->status] ?? '#6E6E79' }}"></span>{{ $p->statusLabel() }}</span></div>
        <div style="font-size:12.5px;color:#8A8A94;line-height:1.5;min-height:38px">{{ $p->meta('about') }}@if ($on)<br><span class="mono" style="font-size:11.5px;color:#6E6E79">{{ $p->maskedKey() }} · {{ $p->model() }}@if ($p->latency_ms) · {{ number_format($p->latency_ms) }}ms @endif</span>@endif</div>
        <div style="display:flex;gap:6px">
          <a href="{{ route('admin.providers', ['connect' => $p->id]) }}" class="btn sm {{ $on ? '' : 'primary' }}" style="flex:1;height:30px">{{ $on ? 'Settings' : 'Connect' }}</a>
          @if ($on)<form method="POST" action="{{ route('admin.providers.test', $p) }}" class="inline">@csrf<button class="btn sm" style="height:30px">Test</button></form>@endif
        </div>
      </div>
    @endforeach
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:18px;display:flex;flex-direction:column;gap:12px">
    <div><div style="font-weight:600">How should the platform choose an AI model?</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">If one provider goes down, requests automatically move to the next.</div></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px">
      @foreach (\App\Http\Controllers\Admin\ProviderController::ROUTES as [$t, $d, $tag])
        @php($on = $routing === $t)
        <form method="POST" action="{{ route('admin.routing') }}" class="inline">@csrf<input type="hidden" name="routing" value="{{ $t }}">
          <button style="width:100%;text-align:left;border:1px solid {{ $on ? 'var(--accent-line)' : '#1F1F26' }};background:{{ $on ? '#110F1A' : 'transparent' }};border-radius:9px;padding:12px 14px;cursor:pointer;color:inherit;font-size:13.5px"><div style="font-weight:500;display:flex;justify-content:space-between;gap:8px">{{ $t }}<span style="font-size:11px;color:#C7BDFF">{{ $tag }}</span></div><div style="font-size:12.5px;color:#8A8A94;margin-top:4px;line-height:1.45">{{ $d }}</div></button>
        </form>
      @endforeach
    </div>
  </div>
</div>
@endsection

@section('overlays')
@if ($conn)
<div style="position:fixed;inset:0;z-index:40;display:grid;place-items:center;padding:20px">
  <a href="{{ route('admin.providers') }}" style="position:absolute;inset:0;background:rgba(5,5,7,0.6)"></a>
  <form method="POST" action="{{ route('admin.providers.update', $conn) }}" x-data="{ busy: false }" @submit="busy = true" style="position:relative;width:min(480px,100%);max-height:92vh;overflow:auto;background:#0E0E11;border:1px solid #26262E;border-radius:12px">
    @csrf @method('PUT')
    <div style="padding:16px 20px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><div style="font-weight:600;font-size:15px">Connect {{ $conn->name }}</div><a href="{{ route('admin.providers') }}" style="color:#8A8A94;font-size:18px">×</a></div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
      @foreach (['Create a free account on the '.$conn->name.' website.', 'Open “API keys” in your account and click “Create key”.', 'Copy the key and paste it below.'] as $i => $t)
        <div style="display:flex;gap:12px"><span style="width:22px;height:22px;border-radius:50%;background:var(--accent-soft);color:#C7BDFF;display:grid;place-items:center;font-size:11.5px;flex:none">{{ $i + 1 }}</span><div style="line-height:1.5;padding-top:1px">{{ $t }}</div></div>
      @endforeach
      <input class="inp mono" name="api_key" placeholder="{{ $conn->api_key ? $conn->maskedKey().' (saved — paste to replace)' : 'Paste your API key here' }}" autocomplete="off" style="font-size:13.5px">
      <div style="font-size:12px;color:#6E6E79">🔒 Keys are encrypted and never shown in full again.</div>
      <details @if ($conn->driver === 'custom') open @endif style="border-top:1px solid #1C1C22;padding-top:12px">
        <summary style="cursor:pointer;color:#9A9AA5;font-size:12.5px">Advanced</summary>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px">
          <label class="lbl" style="grid-column:1/-1"><span>API base URL</span><input class="inp sm mono" name="base_url" value="{{ $conn->base_url }}" placeholder="{{ $conn->meta('base') ?: 'https://your-server/v1' }}"></label>
          <label class="lbl"><span>Default model</span><input class="inp sm mono" name="default_model" value="{{ $conn->default_model }}" placeholder="{{ $conn->meta('model') }}"></label>
          <label class="lbl"><span>Priority (1 = first)</span><input class="inp sm" type="number" name="priority" value="{{ $conn->priority }}"></label>
          <label class="lbl"><span>Cost per 1M tokens ($)</span><input class="inp sm" name="cost_per_million" value="{{ $conn->cost_per_million }}"></label>
        </div>
      </details>
      @if (session('conn_error'))<div style="padding:10px 12px;border-radius:8px;background:#1A0F0F;color:#F2B8B2;font-size:13px">✕ {{ session('conn_error') }}</div>@endif
    </div>
    <div style="padding:14px 20px;border-top:1px solid #1C1C22;display:flex;justify-content:space-between;gap:8px">
      <span>@if ($conn->status !== 'not_configured')<button name="disconnect" value="1" class="btn danger">Disconnect</button>@endif</span>
      <span style="display:flex;gap:8px"><a href="{{ route('admin.providers') }}" class="btn">Close</a><button class="btn primary" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" x-text="busy ? 'Testing…' : 'Test & connect'">Test & connect</button></span>
    </div>
  </form>
</div>
@endif
@endsection
