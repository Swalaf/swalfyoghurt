@extends('admin.layout', ['page' => 'logs'])

@section('content')
@php($sev = ['ERROR' => ['#E5695E', '#2A1616'], 'WARN' => ['#E8B66B', '#2A2214'], 'INFO' => ['#9A9AA5', '#1C1C22']])
<div data-screen-label="Logs" style="display:flex;flex-direction:column;gap:14px">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="hidden" name="level" value="{{ $filter }}">
    <div style="flex:1;min-width:220px;height:34px;border:1px solid #1F1F26;border-radius:7px;background:#0E0E11;display:flex;align-items:center;gap:8px;padding:0 12px;color:#6E6E79">⌕ <input name="q" value="{{ request('q') }}" placeholder="Search logs…" style="flex:1;background:transparent;border:none;outline:none;color:#E4E4E9;font-size:13px"></div>
    @foreach (['All', 'Errors', 'Warnings'] as $f)
      @php($on = $filter === $f)
      <a href="{{ route('admin.logs', array_filter(['level' => $f, 'q' => request('q'), 'range' => request('range')])) }}" style="height:34px;display:flex;align-items:center;padding:0 12px;border-radius:7px;font-size:12.5px;border:1px solid {{ $on ? 'var(--accent-line)' : '#1F1F26' }};background:{{ $on ? '#17142A' : 'transparent' }};color:{{ $on ? '#E4E4E9' : '#9A9AA5' }}">{{ $f }}</a>
    @endforeach
    <select name="range" onchange="this.form.submit()" class="inp sm" style="width:auto;height:34px;border-color:#1F1F26;color:#C9C9D1;font-size:12.5px;background-color:transparent">
      @foreach (['24h' => 'Last 24 hours', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'all' => 'All time'] as $k => $v)<option value="{{ $k }}" @selected(request('range', '24h') === $k)>{{ $v }}</option>@endforeach
    </select>
    <a href="{{ route('admin.logs.download') }}" style="height:34px;display:flex;align-items:center;padding:0 12px;border-radius:7px;border:1px solid #1F1F26;font-size:12.5px;color:#C9C9D1">Download</a>
  </form>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;overflow:auto">
    <div style="min-width:760px">
      @forelse ($logs as $l)
        <div style="display:grid;grid-template-columns:110px 70px 110px minmax(0,1fr) 180px;gap:12px;padding:10px 16px;border-bottom:1px solid #16161B;align-items:center;font-size:12.5px"><span class="mono" style="color:#6E6E79" title="{{ $l->created_at }}">{{ $l->created_at->isToday() ? $l->created_at->format('H:i') : $l->created_at->format('M j H:i') }}</span><span class="mono" style="justify-self:start;font-size:10.5px;padding:2px 7px;border-radius:4px;background:{{ $sev[$l->level][1] ?? '#1C1C22' }};color:{{ $sev[$l->level][0] ?? '#9A9AA5' }}">{{ $l->level }}</span><span style="color:#8A8A94">{{ $l->category }}</span><span>{{ $l->message }}</span><span style="color:#6E6E79;text-align:right;overflow:hidden;text-overflow:ellipsis">{{ $l->actor }}</span></div>
      @empty
        <div style="padding:40px;text-align:center;color:#8A8A94">Nothing logged for this filter yet.</div>
      @endforelse
    </div>
  </div>
  @if ($logs->hasPages())<div style="display:flex;gap:6px;justify-content:flex-end">@if ($logs->previousPageUrl())<a class="btn sm" href="{{ $logs->previousPageUrl() }}">← Newer</a>@endif @if ($logs->nextPageUrl())<a class="btn sm" href="{{ $logs->nextPageUrl() }}">Older →</a>@endif</div>@endif
</div>
@endsection
