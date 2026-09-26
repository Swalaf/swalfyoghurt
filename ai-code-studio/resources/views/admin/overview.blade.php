@extends('admin.layout', ['page' => 'overview'])

@section('content')
@php($doneCount = collect($checklist)->where(2, true)->count())
<div data-screen-label="Admin Overview" style="display:flex;flex-direction:column;gap:20px">
  @if ($doneCount < 6)
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;overflow:hidden">
    <div style="padding:16px 18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;border-bottom:1px solid #1C1C22">
      <div><div style="font-weight:600;font-size:15px">Finish setting up your platform</div><div style="color:#8A8A94;margin-top:3px">{{ $doneCount }} of 6 done · about {{ (6 - $doneCount) * 2 }} minutes left</div></div>
      <div style="width:220px;max-width:100%;height:6px;border-radius:3px;background:#1C1C22"><div style="height:100%;border-radius:3px;background:var(--accent);width:{{ round($doneCount / 6 * 100) }}%"></div></div>
    </div>
    @foreach ($checklist as [$t, $d, $done, $to])
      <div style="display:flex;align-items:center;gap:14px;padding:12px 18px;border-bottom:1px solid #16161B">
        <span style="width:22px;height:22px;border-radius:50%;flex:none;display:grid;place-items:center;font-size:11px;border:1px solid {{ $done ? 'var(--accent)' : '#3A3A44' }};background:{{ $done ? 'var(--accent)' : 'transparent' }};color:#fff">{{ $done ? '✓' : '' }}</span>
        <div style="flex:1;min-width:0"><div style="font-weight:500;color:{{ $done ? '#8A8A94' : '#F2F2F5' }}">{{ $t }}</div><div style="font-size:12.5px;color:#6E6E79;margin-top:2px">{{ $d }}</div></div>
        <a href="{{ route($to, $to === 'admin.settings' ? ['section' => 'Email'] : []) }}" class="btn sm {{ $done ? 'ghost' : 'primary' }}" style="{{ $done ? 'color:#A5A5AF' : '' }}">{{ $done ? 'Review' : 'Set up' }}</a>
      </div>
    @endforeach
  </div>
  @endif
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
    @foreach ($kpis as [$label, $value, $sub, $c, $help])
      <div style="border:1px solid #1C1C22;border-radius:10px;background:#0E0E11;padding:14px 16px;display:flex;flex-direction:column;gap:6px">
        <span style="font-size:12.5px;color:#8A8A94">{{ $label }}</span>
        <span style="font-size:24px;font-weight:600;letter-spacing:-0.02em">{{ $value }}</span>
        <span style="font-size:12px;color:{{ $c }}">{{ $sub }}</span>
        @if (session('admin_tips', true))<span style="font-size:11.5px;color:#6E6E79;line-height:1.4">{{ $help }}</span>@endif
      </div>
    @endforeach
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));gap:16px;align-items:start">
    <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px"><span style="font-weight:600">Money in vs AI cost · 14 days</span><span style="display:flex;gap:14px;font-size:12px;color:#8A8A94"><span style="display:flex;gap:6px;align-items:center"><span style="width:8px;height:8px;border-radius:2px;background:var(--accent)"></span>Revenue</span><span style="display:flex;gap:6px;align-items:center"><span style="width:8px;height:8px;border-radius:2px;background:#3A3A44"></span>AI cost</span></span></div>
      <div style="height:160px;display:flex;align-items:flex-end;gap:6px;border-bottom:1px solid #1C1C22">
        @foreach ($chart as $b)<div title="{{ $b['day'] }}" style="flex:1;display:flex;gap:2px;align-items:flex-end;height:100%"><div style="flex:1;border-radius:3px 3px 0 0;background:var(--accent);height:{{ max($b['r'], 1) }}%"></div><div style="flex:1;border-radius:3px 3px 0 0;background:#3A3A44;height:{{ max($b['c'], 1) }}%"></div></div>@endforeach
      </div>
      <div style="margin-top:12px;font-size:12.5px;color:#8A8A94">@if ($margin !== null)You keep <span style="color:#58C98A;font-weight:500">{{ $margin }}%</span> of revenue after AI costs this month.@else No revenue yet — connect a payment gateway under Plans & pricing.@endif</div>
    </div>
    <div style="display:flex;flex-direction:column;gap:16px">
      <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
        <div style="padding:12px 16px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><span style="font-weight:600">Is everything working?</span><a href="{{ route('admin.health') }}" style="font-size:12.5px">Details</a></div>
        @foreach ($statusMini as [$n, $v, $c])<div style="display:flex;justify-content:space-between;padding:8px 16px;font-size:13px"><span style="color:#B4B4BE">{{ $n }}</span><span style="display:flex;gap:6px;align-items:center;color:{{ $c }}"><span style="width:7px;height:7px;border-radius:50%;background:{{ $c }}"></span>{{ $v }}</span></div>@endforeach
      </div>
      <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
        <div style="padding:12px 16px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><span style="font-weight:600">New signups</span><a href="{{ route('admin.users') }}" style="font-size:12.5px">All users</a></div>
        @forelse ($recentUsers as $u)<a href="{{ route('admin.users', ['user' => $u->id]) }}" style="display:flex;align-items:center;gap:10px;padding:8px 16px;color:inherit"><span style="width:26px;height:26px;border-radius:50%;background:#1F1F26;display:grid;place-items:center;font-size:10.5px;font-weight:600">{{ $u->initials() }}</span><div style="flex:1;min-width:0"><div style="font-size:13px">{{ $u->name }}</div><div style="font-size:11.5px;color:#6E6E79">{{ $u->email }}</div></div><span style="font-size:11.5px;color:#8A8A94">{{ $u->is_admin ? 'Admin' : $u->plan?->name }}</span></a>@empty<div style="padding:14px 16px;color:#6E6E79">No sign-ups yet.</div>@endforelse
      </div>
    </div>
  </div>
</div>
@endsection
