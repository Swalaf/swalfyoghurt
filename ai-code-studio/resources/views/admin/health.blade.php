@extends('admin.layout', ['page' => 'health'])

@section('cta')
<form method="POST" action="{{ route('admin.health.clear') }}" class="inline">@csrf<button class="btn">⟳ Clear caches</button></form>
@endsection

@section('content')
<div data-screen-label="System Health" style="display:flex;flex-direction:column;gap:16px">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px">
    @foreach ($gauges as [$n, $v, $c, $d])
      <div style="border:1px solid #1C1C22;border-radius:10px;background:#0E0E11;padding:14px 16px;display:flex;flex-direction:column;gap:8px"><div style="display:flex;justify-content:space-between"><span style="color:#8A8A94">{{ $n }}</span><span class="mono">{{ $v }}%</span></div><div style="height:6px;border-radius:3px;background:#1C1C22"><div style="height:100%;border-radius:3px;width:{{ min(100, $v) }}%;background:{{ $c }}"></div></div><span style="font-size:12px;color:#6E6E79">{{ $d }}</span></div>
    @endforeach
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    @foreach ($services as [$n, $d, $v, $c, $fix])
      <div style="display:flex;align-items:center;gap:14px;padding:12px 18px;border-bottom:1px solid #16161B;flex-wrap:wrap"><span style="width:9px;height:9px;border-radius:50%;flex:none;background:{{ $c }}"></span><div style="flex:1;min-width:200px"><div style="font-weight:500">{{ $n }}</div><div style="font-size:12.5px;color:#6E6E79;margin-top:2px">{{ $d }}</div></div><span style="font-size:12.5px;color:{{ $c }}">{{ $v }}</span>
        @if ($fix)
          @if (str_contains($fix[1], 'clear-cache'))<form method="POST" action="{{ $fix[1] }}" class="inline">@csrf<button style="height:28px;padding:0 12px;border-radius:6px;border:none;background:#221D3D;color:#C7BDFF;font-size:12.5px;cursor:pointer">{{ $fix[0] }}</button></form>
          @else<a href="{{ $fix[1] }}" style="height:28px;padding:0 12px;border-radius:6px;background:#221D3D;color:#C7BDFF;font-size:12.5px;display:flex;align-items:center">{{ $fix[0] }}</a>@endif
        @endif
      </div>
    @endforeach
  </div>
</div>
@endsection
