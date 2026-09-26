@extends('studio.layout')
@section('title', 'AI Providers')

@section('main')
@php($sc = ['connected' => '#58C98A', 'slow' => '#E8B66B', 'error' => '#E5695E'])
<div data-screen-label="AI Providers" style="flex:1;overflow:auto;padding:22px 28px 40px">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    <div><div style="font-size:11px;color:#A99BFF;letter-spacing:0.08em;margin-bottom:5px">BRING YOUR OWN AI</div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">AI Providers</h1><div style="color:#8A8A94;margin-top:5px">You control your AI costs. Route every request through the providers you connect.</div></div>
    <form method="POST" action="{{ route('admin.providers.store') }}" class="inline">@csrf<input type="hidden" name="driver" value="custom"><button class="btn primary sm" style="height:32px">+ Add provider</button></form>
  </div>
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px">
    <span style="font-size:12px;color:#8A8A94">Routing</span>
    <div style="display:flex;border:1px solid #1F1F26;border-radius:6px;overflow:hidden;flex-wrap:wrap">
      @foreach (['Best quality', 'Cheapest', 'Fastest', 'Free-first', 'Manual', 'Automatic fallback'] as $name)
        <form method="POST" action="{{ route('admin.routing') }}" class="inline">@csrf<input type="hidden" name="routing" value="{{ $name }}"><button style="padding:6px 11px;font-size:12px;cursor:pointer;border:none;border-right:1px solid #1F1F26;background:{{ $routing === $name ? 'var(--accent-soft)' : 'transparent' }};color:{{ $routing === $name ? '#C7BDFF' : '#9A9AA5' }}">{{ $name }}</button></form>
      @endforeach
    </div>
    <span style="font-size:12px;color:#6E6E79">Fallback chain: {{ app(\App\Services\Ai\AiClient::class)->candidates()->pluck('name')->implode(' → ') ?: 'connect a provider to start' }}</span>
  </div>
  <div style="border:1px solid #1C1C22;border-radius:9px;overflow:auto;background:#0E0E11">
    <div style="min-width:860px">
      @php($grid = 'grid-template-columns:1.4fr 1fr 1.6fr 0.8fr 0.9fr 0.6fr 1fr')
      <div style="display:grid;{{ $grid }};gap:12px;padding:9px 14px;border-bottom:1px solid #1C1C22;font-size:11px;color:#6E6E79;letter-spacing:0.04em">
        <span>PROVIDER</span><span>STATUS</span><span>MODEL</span><span>LATENCY</span><span>COST / 1M</span><span>PRIORITY</span><span style="text-align:right">ACTIONS</span>
      </div>
      @foreach ($providers as $pv)
        @php($c = $sc[$pv->status] ?? '#6E6E79')
        <div style="display:grid;{{ $grid }};gap:12px;padding:11px 14px;border-bottom:1px solid #16161B;align-items:center">
          <div style="min-width:0"><div style="font-weight:500">{{ $pv->name }}</div><div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79;margin-top:2px;overflow:hidden;text-overflow:ellipsis">{{ $pv->maskedKey() }}</div></div>
          <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:{{ $c }}"><span style="width:7px;height:7px;border-radius:50%;background:{{ $c }}"></span>{{ $pv->statusLabel() }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#B4B4BE;overflow:hidden;text-overflow:ellipsis">{{ $pv->model() ?: '—' }}{{ $pv->models ? ' · '.$pv->models : '' }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px">{{ $pv->latency_ms ? ($pv->latency_ms >= 1000 ? round($pv->latency_ms / 1000, 1).'s' : $pv->latency_ms.'ms') : '—' }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px">{{ $pv->cost_per_million === null ? '—' : ($pv->cost_per_million == 0 ? 'Free' : '$'.number_format($pv->cost_per_million, 2)) }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#8A8A94">{{ $pv->priority }}</span>
          <div style="display:flex;gap:6px;justify-content:flex-end">
            @if ($pv->status !== 'not_configured')<form method="POST" action="{{ route('admin.providers.test', $pv) }}" class="inline">@csrf<button class="btn xs">Test</button></form>@endif
            <a href="{{ route('admin.providers', ['connect' => $pv->id]) }}" class="btn xs">{{ $pv->status === 'not_configured' ? 'Set up' : 'Configure' }}</a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
