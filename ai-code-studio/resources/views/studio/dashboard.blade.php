@extends('studio.layout')
@section('title', 'Dashboard')

@section('main')
@php($hour = now()->hour)
<div data-screen-label="Dashboard" style="flex:1;overflow:auto;padding:24px 28px 40px">
  <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px">
    <div>
      <div style="font-size:11.5px;color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace;margin-bottom:6px">IDEATE → PLAN → CODE → TEST → PREVIEW → DEPLOY</div>
      <h1 style="margin:0;font-size:24px;font-weight:600;letter-spacing:-0.02em">Good {{ $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->firstName() }}</h1>
      <div style="color:#8A8A94;margin-top:4px">{{ $running->where('status', 'working')->count() }} agents working · {{ $projects->where('status', 'failed')->count() }} build{{ $projects->where('status', 'failed')->count() === 1 ? '' : 's' }} need{{ $projects->where('status', 'failed')->count() === 1 ? 's' : '' }} your attention</div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="{{ route('studio.new') }}" class="btn primary" style="height:32px;font-size:12.5px">+ Create New Project</a>
    </div>
  </div>

  <form method="GET" action="{{ route('studio.new') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #2A2540;background:#110F1A;border-radius:9px;margin-bottom:22px">
    <span style="font-family:'Geist Mono',ui-monospace,monospace;color:#A99BFF">✦</span>
    <input name="idea" placeholder="Describe what you want to build… e.g. “A booking platform for clinics with SMS reminders”" style="flex:1;min-width:0;background:transparent;border:none;outline:none;color:#E4E4E9;font-size:13px">
    <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79" data-r="hidem">↵ to plan</span>
  </form>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,130px),1fr));gap:1px;background:#1C1C22;border:1px solid #1C1C22;border-radius:9px;overflow:hidden;margin-bottom:26px">
    @foreach ($stats as [$label, $value, $sub, $c])
      <div style="background:#0E0E11;padding:13px 15px;display:flex;flex-direction:column;gap:6px">
        <span style="font-size:11.5px;color:#7A7A86">{{ $label }}</span>
        <span style="font-size:20px;font-weight:600;letter-spacing:-0.02em;font-family:'Geist Mono',ui-monospace,monospace">{{ $value }}</span>
        <span style="font-size:11px;color:{{ $c }}">{{ $sub }}</span>
      </div>
    @endforeach
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,560px),1fr));gap:24px;align-items:start">
    <div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><h2 style="margin:0;font-size:14px;font-weight:600">Projects</h2><span style="color:#6E6E79;font-size:12px">Sorted by last modified</span></div>
      @if ($projects->isEmpty())
        <div style="border:1px dashed #26262E;border-radius:9px;padding:28px;text-align:center;color:#8A8A94">No projects yet. <a href="{{ route('studio.new') }}">Create your first one →</a></div>
      @endif
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px">
        @foreach ($projects as $p)
          @php([, , , $deploy, $dc] = $p->statusBadge())
          @php($lastTask = $p->agentTasks()->latest('updated_at')->first())
          <div class="hv-card" style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11;overflow:hidden;display:flex;flex-direction:column">
            <div style="height:92px;background:repeating-linear-gradient(135deg,#131317 0 7px,#16161B 7px 14px);display:flex;align-items:flex-end;justify-content:space-between;padding:8px 10px;font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:#55555F">
              <span>preview · {{ $p->slug }}</span>
              <span style="display:flex;align-items:center;gap:5px;padding:2px 7px;border-radius:4px;background:#0E0E11;color:{{ $dc }}"><span style="width:6px;height:6px;border-radius:50%;background:{{ $dc }}"></span>{{ $deploy }}</span>
            </div>
            <div style="padding:11px 12px;display:flex;flex-direction:column;gap:8px">
              <div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px"><span style="font-weight:600">{{ $p->name }}</span><span style="font-size:11px;color:#6E6E79;white-space:nowrap">{{ $p->updated_at->diffForHumans(short: true) }}</span></div>
              <div style="display:flex;gap:5px;flex-wrap:wrap">
                <span style="font-size:11px;padding:2px 6px;border-radius:4px;background:#17171C;color:#B4B4BE">{{ $p->stackLabel('frontend', $p->kind) }}{{ ($b = $p->stackLabel('backend')) ? ' · '.$b : '' }}</span>
                <span style="font-size:11px;padding:2px 6px;border-radius:4px;background:#17171C;color:#B4B4BE">{{ $p->kind }}</span>
                <span style="font-size:11px;padding:2px 6px;border-radius:4px;background:#17171C;color:#8A8A94;font-family:'Geist Mono',ui-monospace,monospace">⎇ {{ $p->branch }}</span>
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;border-top:1px solid #18181D;padding-top:8px">
                @php($busy = $lastTask && in_array($lastTask->status, ['working', 'waiting']))
                <span style="font-size:11.5px;color:{{ $busy ? '#A99BFF' : ($p->status === 'failed' ? '#E8B66B' : '#6E6E79') }};display:flex;gap:6px;align-items:center;min-width:0"><span style="font-family:'Geist Mono',ui-monospace,monospace">✦</span><span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $p->status === 'failed' ? 'Build failed — ask the agent to fix it' : ($busy ? $lastTask->agent.': '.$lastTask->task : ($p->status === 'planned' ? 'Plan ready for review' : 'Idle')) }}</span></span>
                <a href="{{ $p->status === 'planned' ? route('studio.projects.plan', $p) : route('studio.projects.code', $p) }}" class="btn xs" style="flex:none">Open</a>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px">
      <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11">
        <div style="padding:11px 13px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><span style="font-weight:600">Running agents</span></div>
        @forelse ($running as $a)
          <a href="{{ route('studio.projects.agents', [$a->project, 'agent' => $a->agent]) }}" style="padding:10px 13px;border-bottom:1px solid #16161B;display:flex;flex-direction:column;gap:6px;color:inherit">
            <div style="display:flex;justify-content:space-between;gap:8px"><span style="font-weight:500">{{ $a->agent }} Agent</span><span style="font-size:11px;color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace">{{ $a->project->slug }}</span></div>
            <div style="font-size:12px;color:#8A8A94">{{ $a->task }}</div>
            <div style="height:3px;background:#1C1C22;border-radius:2px"><div style="height:100%;border-radius:2px;background:var(--accent);width:{{ $a->progress }}%"></div></div>
          </a>
        @empty
          <div style="padding:14px 13px;color:#6E6E79;font-size:12.5px">No agents running. Start a build from a project’s plan.</div>
        @endforelse
      </div>
      <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11">
        <div style="padding:11px 13px;border-bottom:1px solid #1C1C22;font-weight:600">Activity</div>
        @php($cmap = ['ERROR' => '#E5695E', 'WARN' => '#E8B66B'])
        @forelse ($activity as $e)
          <div style="padding:9px 13px;display:flex;gap:10px;align-items:flex-start">
            <span style="width:7px;height:7px;border-radius:50%;margin-top:5px;flex:none;background:{{ $cmap[$e->level] ?? ($e->category === 'Deploy' ? '#58C98A' : '#A99BFF') }}"></span>
            <div style="flex:1;min-width:0"><div style="font-size:12.5px;text-wrap:pretty">{{ $e->message }}</div><div style="font-size:11px;color:#6E6E79;margin-top:2px">{{ $e->created_at->diffForHumans() }}</div></div>
          </div>
        @empty
          <div style="padding:14px 13px;color:#6E6E79;font-size:12.5px">Nothing yet.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
