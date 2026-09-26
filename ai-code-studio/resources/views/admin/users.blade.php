@extends('admin.layout', ['page' => 'users'])

@php
  $sCol = ['active' => ['#58C98A', '#15261C'], 'trial' => ['#A99BFF', '#1F1B36'], 'suspended' => ['#E5695E', '#2A1616']];
  $grid = 'grid-template-columns:2.2fr 1fr 1fr 0.8fr 1fr 1fr 90px';
@endphp

@section('content')
<div data-screen-label="Users" style="display:flex;flex-direction:column;gap:14px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="hidden" name="filter" value="{{ $filter }}">
    <div style="flex:1;min-width:220px;height:36px;border:1px solid #1F1F26;border-radius:7px;background:#0E0E11;display:flex;align-items:center;gap:8px;padding:0 12px;color:#6E6E79"><span>⌕</span><input name="q" value="{{ request('q') }}" placeholder="Search by name or email" style="flex:1;background:transparent;border:none;outline:none;color:#E4E4E9;font-size:13.5px"></div>
    <div style="display:flex;gap:4px;flex-wrap:wrap">
      @foreach (['All', 'Active', 'Trial', 'Suspended', 'Admins'] as $f)
        @php($on = $filter === $f)
        <a href="{{ route('admin.users', array_filter(['q' => request('q'), 'filter' => $f])) }}" style="padding:7px 12px;border-radius:7px;font-size:12.5px;border:1px solid {{ $on ? 'var(--accent-line)' : '#1F1F26' }};background:{{ $on ? '#17142A' : '#0E0E11' }};color:{{ $on ? '#E4E4E9' : '#9A9AA5' }}">{{ $f }}</a>
      @endforeach
    </div>
    <a href="{{ route('admin.users.export', request()->only('q', 'filter')) }}" class="btn" style="height:36px;font-size:12.5px;background:#0E0E11;border-color:#1F1F26;color:#C9C9D1">Export CSV</a>
  </form>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;overflow:auto">
    <div style="min-width:820px">
      <div style="display:grid;{{ $grid }};gap:12px;padding:10px 16px;border-bottom:1px solid #1C1C22;font-size:11.5px;color:#6E6E79"><span>USER</span><span>PLAN</span><span>CREDITS LEFT</span><span>PROJECTS</span><span>STATUS</span><span>LAST ACTIVE</span><span></span></div>
      @forelse ($users as $u)
        <div class="hv-row" style="display:grid;{{ $grid }};gap:12px;padding:10px 16px;border-bottom:1px solid #16161B;align-items:center">
          <div style="display:flex;align-items:center;gap:10px;min-width:0"><span style="width:30px;height:30px;border-radius:50%;background:#1F1F26;display:grid;place-items:center;font-size:11px;font-weight:600;flex:none">{{ $u->initials() }}</span><div style="min-width:0"><div style="font-weight:500">{{ $u->name }}@if ($u->is_admin) <span style="font-size:10.5px;color:#E8B66B;margin-left:4px">ADMIN</span>@endif</div><div style="font-size:12px;color:#6E6E79;overflow:hidden;text-overflow:ellipsis">{{ $u->email }}</div></div></div>
          <span>{{ $u->plan?->name ?? '—' }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px;color:{{ $u->credits < 200 && ! $u->is_admin ? '#E8B66B' : '#D4D4DA' }}">{{ $u->is_admin ? '∞' : number_format($u->credits) }}</span>
          <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px">{{ $u->projects_count }}</span>
          <span style="justify-self:start;font-size:12px;padding:2px 9px;border-radius:10px;background:{{ $sCol[$u->status][1] ?? '#1C1C22' }};color:{{ $sCol[$u->status][0] ?? '#9A9AA5' }}">{{ ucfirst($u->status) }}</span>
          <span style="color:#8A8A94;font-size:12.5px">{{ $u->last_active_at?->diffForHumans() ?? 'Never' }}</span>
          <a href="{{ route('admin.users', array_merge(request()->only('q', 'filter', 'page'), ['user' => $u->id])) }}" class="btn sm" style="height:28px">Manage</a>
        </div>
      @empty
        <div style="padding:40px;text-align:center;color:#8A8A94">No users match your search. Try a different name or clear the filter.</div>
      @endforelse
    </div>
  </div>
  @if ($users->hasPages())
    <div style="display:flex;justify-content:space-between;align-items:center;color:#8A8A94;font-size:12.5px"><span>Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}</span><span style="display:flex;gap:6px">@if ($users->previousPageUrl())<a class="btn sm" href="{{ $users->previousPageUrl() }}">← Previous</a>@endif @if ($users->nextPageUrl())<a class="btn sm" href="{{ $users->nextPageUrl() }}">Next →</a>@endif</span></div>
  @endif
</div>
@endsection

@section('overlays')
@if ($drawer)
@php($close = route('admin.users', request()->only('q', 'filter', 'page')))
<div x-data="{ confirmDel: false, act: null }" @keydown.escape.window="location.href = @js($close)" style="position:fixed;inset:0;z-index:40;display:flex;justify-content:flex-end">
  <a href="{{ $close }}" style="position:absolute;inset:0;background:rgba(5,5,7,0.55)"></a>
  <div style="position:relative;width:min(420px,100%);height:100%;background:#0E0E11;border-left:1px solid #26262E;display:flex;flex-direction:column;overflow:auto">
    <div style="padding:18px;border-bottom:1px solid #1C1C22;display:flex;gap:12px;align-items:center"><span style="width:44px;height:44px;border-radius:50%;background:#1F1F26;display:grid;place-items:center;font-weight:600">{{ $drawer->initials() }}</span><div style="flex:1;min-width:0"><div style="font-weight:600;font-size:15px">{{ $drawer->name }}</div><div style="font-size:12.5px;color:#6E6E79;overflow:hidden;text-overflow:ellipsis">{{ $drawer->email }}</div></div><a href="{{ $close }}" style="color:#8A8A94;font-size:18px">×</a></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#1C1C22;border-bottom:1px solid #1C1C22">
      <div style="background:#0E0E11;padding:12px 18px"><div style="font-size:12px;color:#6E6E79">Plan</div><div style="margin-top:3px">{{ $drawer->plan?->name ?? '—' }}</div></div>
      <div style="background:#0E0E11;padding:12px 18px"><div style="font-size:12px;color:#6E6E79">Credits left</div><div style="margin-top:3px;font-family:'Geist Mono',ui-monospace,monospace">{{ $drawer->is_admin ? '∞' : number_format($drawer->credits) }}</div></div>
      <div style="background:#0E0E11;padding:12px 18px"><div style="font-size:12px;color:#6E6E79">Projects</div><div style="margin-top:3px">{{ $drawer->projects_count }}</div></div>
      <div style="background:#0E0E11;padding:12px 18px"><div style="font-size:12px;color:#6E6E79">Joined</div><div style="margin-top:3px">{{ $drawer->created_at->format('M Y') }}</div></div>
    </div>
    <div style="padding:18px;display:flex;flex-direction:column;gap:8px">
      <div style="font-size:12px;color:#6E6E79;margin-bottom:2px">ACTIONS</div>
      @php($row = 'display:flex;align-items:center;gap:12px;padding:10px 12px;border:1px solid #1F1F26;border-radius:8px;cursor:pointer;width:100%;background:none;color:inherit;text-align:left;font-size:13.5px')

      <div style="border:1px solid #1F1F26;border-radius:8px">
        <div @click="act = act === 'credits' ? null : 'credits'" class="hv-soft" style="{{ $row }};border:none"><span style="width:18px;text-align:center;color:#A99BFF">＋</span><div style="flex:1"><div>Add credits</div><div style="font-size:12px;color:#6E6E79">Give free AI credits to this user.</div></div></div>
        <form class="d-flex" x-show="act === 'credits'" x-cloak method="POST" action="{{ route('admin.users.credits', $drawer) }}" style="gap:6px;padding:0 12px 12px">@csrf<input class="inp sm" type="number" name="amount" value="1000" style="flex:1"><button class="btn primary sm" style="height:34px">Add</button></form>
      </div>
      <div style="border:1px solid #1F1F26;border-radius:8px">
        <div @click="act = act === 'plan' ? null : 'plan'" class="hv-soft" style="{{ $row }};border:none"><span style="width:18px;text-align:center;color:#A99BFF">⇄</span><div style="flex:1"><div>Change plan</div><div style="font-size:12px;color:#6E6E79">Upgrade or downgrade without payment.</div></div></div>
        <form class="d-flex" x-show="act === 'plan'" x-cloak method="POST" action="{{ route('admin.users.plan', $drawer) }}" style="gap:6px;padding:0 12px 12px">@csrf<select class="inp sm" name="plan_id" style="flex:1">@foreach ($plans as $p)<option value="{{ $p->id }}" @selected($p->id === $drawer->plan_id)>{{ $p->name }}</option>@endforeach</select><button class="btn primary sm" style="height:34px">Save</button></form>
      </div>
      <form method="POST" action="{{ route('admin.users.reset', $drawer) }}">@csrf<button class="hv-soft" style="{{ $row }}"><span style="width:18px;text-align:center;color:#A99BFF">⟲</span><div style="flex:1"><div>Send password reset</div><div style="font-size:12px;color:#6E6E79">Emails them a reset link.</div></div></button></form>
      @unless ($drawer->is(auth()->user()))
      <form method="POST" action="{{ route('admin.users.impersonate', $drawer) }}">@csrf<button class="hv-soft" style="{{ $row }}"><span style="width:18px;text-align:center;color:#A99BFF">◉</span><div style="flex:1"><div>Log in as this user</div><div style="font-size:12px;color:#6E6E79">See exactly what they see — for support.</div></div></button></form>
      <form method="POST" action="{{ route('admin.users.status', $drawer) }}">@csrf<input type="hidden" name="status" value="{{ $drawer->isSuspended() ? 'active' : 'suspended' }}"><button class="hv-soft" style="{{ $row }}"><span style="width:18px;text-align:center;color:#A99BFF">{{ $drawer->isSuspended() ? '▶' : '⏸' }}</span><div style="flex:1"><div>{{ $drawer->isSuspended() ? 'Restore account' : 'Suspend account' }}</div><div style="font-size:12px;color:#6E6E79">{{ $drawer->isSuspended() ? 'Let them sign in again.' : 'They can’t sign in until you restore access.' }}</div></div></button></form>
      <div class="d-flex" x-show="!confirmDel" @click="confirmDel = true" style="align-items:center;gap:12px;padding:10px 12px;border:1px solid #3A1D1D;border-radius:8px;cursor:pointer;color:#E5695E;margin-top:8px"><span style="width:18px;text-align:center">✕</span>Delete this user</div>
      <div class="d-flex" x-show="confirmDel" x-cloak style="border:1px solid #5A2626;background:#1A0F0F;border-radius:8px;padding:14px;margin-top:8px;flex-direction:column;gap:10px"><div style="font-weight:500;color:#F2B8B2">Delete {{ $drawer->name }}?</div><div style="font-size:12.5px;color:#C9A5A0;line-height:1.5">Their {{ $drawer->projects_count }} projects and all files will be permanently removed. This can’t be undone.</div><div style="display:flex;gap:8px"><form method="POST" action="{{ route('admin.users.destroy', $drawer) }}">@csrf @method('DELETE')<button style="height:30px;padding:0 12px;border-radius:6px;border:none;background:#E5695E;color:#fff;font-size:12.5px;cursor:pointer">Yes, delete</button></form><button @click="confirmDel = false" style="height:30px;padding:0 12px;border-radius:6px;border:1px solid #3A2A2A;background:transparent;color:#E4E4E9;font-size:12.5px;cursor:pointer">Cancel</button></div></div>
      @endunless
    </div>
  </div>
</div>
@endif
@endsection
