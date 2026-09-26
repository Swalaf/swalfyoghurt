@extends('admin.layout', ['page' => 'reports'])

@section('content')
<div style="display:flex;flex-direction:column;gap:14px">
  <div style="display:flex;gap:4px">
    @foreach (['open' => 'Open', 'actioned' => 'Taken down', 'dismissed' => 'Dismissed'] as $k => $v)
      <a href="{{ route('admin.reports', ['status' => $k]) }}" style="padding:7px 12px;border-radius:7px;font-size:12.5px;border:1px solid {{ $status === $k ? 'var(--accent-line)' : '#1F1F26' }};background:{{ $status === $k ? '#17142A' : '#0E0E11' }};color:{{ $status === $k ? '#E4E4E9' : '#9A9AA5' }}">{{ $v }} <span class="mono" style="color:#6E6E79">{{ $counts[$k] ?? 0 }}</span></a>
    @endforeach
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    @forelse ($reports as $r)
      @php($owner = $r->deployment?->project?->user)
      <div x-data="{ open: false }" style="padding:14px 18px;border-bottom:1px solid #16161B;display:flex;flex-direction:column;gap:8px">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <span style="font-size:11px;padding:2px 8px;border-radius:10px;background:#2A1616;color:#E5695E">{{ \App\Models\AbuseReport::REASONS[$r->reason] ?? $r->reason }}</span>
          <a href="{{ url('/p/'.$r->subdomain) }}" target="_blank" rel="noopener" class="mono" style="font-size:12.5px">/p/{{ $r->subdomain }} ↗</a>
          <span style="color:#6E6E79;font-size:12px">{{ $r->created_at->diffForHumans() }}{{ $r->reporter_email ? ' · '.$r->reporter_email : '' }}</span>
          <span style="flex:1"></span>
          @if ($owner)<a href="{{ route('admin.users', ['user' => $owner->id]) }}" style="font-size:12px">{{ $owner->email }}</a>@endif
        </div>
        @if ($r->details)<div style="color:#C9C9D1;line-height:1.5;white-space:pre-wrap">{{ $r->details }}</div>@endif
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          @if ($r->status === 'open')
            <button @click="open = !open" class="btn sm danger" style="border-color:#3A1D1D">Take down…</button>
            <form method="POST" action="{{ route('admin.reports.dismiss', $r) }}" class="inline">@csrf<button class="btn sm">Dismiss</button></form>
          @elseif ($r->status === 'actioned')
            <form method="POST" action="{{ route('admin.reports.restore', $r) }}" class="inline">@csrf<button class="btn sm">Restore app</button></form>
          @endif
        </div>
        <form x-show="open" x-cloak method="POST" action="{{ route('admin.reports.takedown', $r) }}" class="d-flex" style="gap:8px;flex-wrap:wrap;align-items:center">@csrf
          <input class="inp sm" name="reason" value="{{ \App\Models\AbuseReport::REASONS[$r->reason] ?? '' }}" style="max-width:320px">
          <label style="display:flex;gap:6px;align-items:center;font-size:12.5px;color:#9A9AA5"><input type="checkbox" name="suspend" value="1">Also suspend the owner</label>
          <button class="btn sm primary" style="background:#E5695E">Take down</button>
        </form>
      </div>
    @empty
      <div style="padding:40px;text-align:center;color:#8A8A94">No {{ $status === 'open' ? 'open ' : '' }}reports.</div>
    @endforelse
  </div>
</div>
@endsection
