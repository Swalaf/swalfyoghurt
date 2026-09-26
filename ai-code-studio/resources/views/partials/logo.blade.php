@php($logo = \App\Support\Settings::get('brand_logo'))
@if ($logo)
<img src="{{ $logo }}" alt="" style="width:{{ $size ?? 24 }}px;height:{{ $size ?? 24 }}px;border-radius:6px;object-fit:contain;flex:none">
@else
<span style="width:{{ $size ?? 24 }}px;height:{{ $size ?? 24 }}px;border-radius:{{ ($size ?? 24) > 22 ? 6 : 5 }}px;background:var(--accent);display:grid;place-items:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;font-weight:600;color:#fff;flex:none">{/}</span>
@endif
