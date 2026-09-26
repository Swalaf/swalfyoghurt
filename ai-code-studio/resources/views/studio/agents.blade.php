@extends('studio.layout')
@section('title', 'Agents · '.$project->name)

@section('main')
@php
  $sc = ['done' => ['Done', '#58C98A'], 'working' => ['Working', '#A99BFF'], 'waiting' => ['Waiting', '#E8B66B'], 'failed' => ['Failed', '#E5695E'], 'queued' => ['Queued', '#6E6E79']];
  $flow = [['Architect'], ['Developer'], ['QA', 'Security'], ['Deploy']];
  $busy = $tasks->contains(fn ($t) => in_array($t->status, ['queued', 'working']));
@endphp
<div data-screen-label="Multi-Agent" style="flex:1;display:flex;min-height:0" @if ($busy) x-data x-init="setTimeout(() => location.reload(), 2500)" @endif>
  <div style="flex:1;min-width:0;overflow:auto;padding:22px 28px 40px">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:22px">
      <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">Multi-agent build</h1><div style="color:#8A8A94;margin-top:5px">{{ $project->slug }}@if ($run) · run #{{ $run }} · started {{ $tasks->first()?->created_at->diffForHumans() }}@endif</div></div>
      <div style="display:flex;gap:6px">
        @if ($run && ! $busy)<form method="POST" action="{{ route('studio.projects.build', $project) }}" class="inline">@csrf<button class="btn sm hv-btn" style="height:30px;background:#121216;border-color:#1F1F26;color:#C9C9D1">⟳ Run again</button></form>@endif
        <a href="{{ route(auth()->user()->isDeveloper() ? 'studio.projects.code' : 'studio.projects.builder', $project) }}" class="btn sm hv-btn" style="height:30px;background:#121216;border-color:#1F1F26;color:#C9C9D1">Open workspace</a>
      </div>
    </div>
    @if (! $run)
      <div style="max-width:620px;margin:40px auto;border:1px dashed #26262E;border-radius:12px;padding:28px;text-align:center;color:#8A8A94;line-height:1.6">No build has run yet.<br><a href="{{ route('studio.projects.plan', $project) }}">Review the plan and start the build →</a></div>
    @else
    <div style="max-width:620px;margin:0 auto;display:flex;flex-direction:column;align-items:center">
      @foreach ($flow as $ri => $row)
        <div style="width:100%;display:flex;flex-direction:column;align-items:center">
          <div style="display:grid;gap:14px;width:100%;grid-template-columns:repeat({{ count($row) }},minmax(0,1fr))">
            @foreach ($row as $name)
              @php($t = $tasks->get($name))
              @if ($t)
              @php([$label, $c] = $sc[$t->status] ?? $sc['queued'])
              <a href="{{ route('studio.projects.agents', [$project, 'agent' => $name]) }}" class="hv-dim" style="border:1px solid {{ $selected?->agent === $name ? 'var(--accent-line)' : '#1C1C22' }};background:#0E0E11;border-radius:9px;padding:11px 13px;display:flex;flex-direction:column;gap:7px;color:inherit">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><span style="font-weight:600;font-size:12.5px">{{ $name }} Agent</span><span style="font-size:10.5px;display:flex;gap:5px;align-items:center;color:{{ $c }}"><span style="width:6px;height:6px;border-radius:50%;background:{{ $c }}"></span>{{ $label }}</span></div>
                <div style="font-size:12px;color:#8A8A94">{{ $t->task }}</div>
                <div style="height:3px;background:#1C1C22;border-radius:2px"><div style="height:100%;border-radius:2px;width:{{ $t->progress }}%;background:{{ $c }}"></div></div>
              </a>
              @endif
            @endforeach
          </div>
          @if ($ri < count($flow) - 1)<div style="width:1px;height:20px;background:#2A2A32"></div>@endif
        </div>
      @endforeach
    </div>
    @endif
  </div>
  @if ($selected)
  <aside data-r="inspect" style="width:360px;flex:none;border-left:1px solid #1C1C22;background:#0D0D10;overflow:auto">
    <div style="padding:14px;border-bottom:1px solid #1C1C22"><div style="font-size:11px;color:#6E6E79;letter-spacing:0.06em;margin-bottom:4px">INSPECTING</div><div style="font-size:15px;font-weight:600">{{ $selected->agent }} Agent</div><div style="font-size:12px;color:#8A8A94;margin-top:3px">{{ $selected->model ?: ($selected->agent === 'Developer' ? 'Model chosen when it starts' : 'Built-in checks') }}</div></div>
    <div style="padding:12px 14px;border-bottom:1px solid #1C1C22"><div style="font-size:11px;color:#6E6E79;margin-bottom:6px">Current task</div><div style="line-height:1.5">{{ $selected->task }}</div></div>
    <div style="padding:12px 14px;border-bottom:1px solid #1C1C22"><div style="font-size:11px;color:#6E6E79;margin-bottom:8px">Files touched</div>
      @forelse ($selected->files ?? [] as $ff)<a href="{{ route('studio.projects.code', [$project, 'file' => $ff]) }}" style="display:block;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#B4B4BE;padding:2px 0">{{ $ff }}</a>@empty<div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#6E6E79">— none —</div>@endforelse
    </div>
    <div style="padding:12px 14px"><div style="font-size:11px;color:#6E6E79;margin-bottom:8px">Live log</div>
      <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;line-height:19px;display:flex;flex-direction:column">
        @forelse ($selected->log ?? [] as $lg)<div style="color:#9A9AA5"><span style="color:#55555F">{{ $lg['t'] }}</span>  {{ $lg['m'] }}</div>@empty<div style="color:#9A9AA5"><span style="color:#55555F">--:--</span>  {{ $selected->status === 'waiting' ? 'waiting for you to publish' : 'waiting on upstream agents' }}</div>@endforelse
      </div>
    </div>
  </aside>
  @endif
</div>
@endsection
