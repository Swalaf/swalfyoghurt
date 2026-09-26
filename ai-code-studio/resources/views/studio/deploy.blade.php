@extends('studio.layout')
@section('title', 'Deployments · '.$project->name)

@section('main')
@php
  $stages = ['build' => 'BUILD', 'test' => 'TEST', 'package' => 'PACKAGE', 'deploy' => 'DEPLOY', 'done' => 'VERIFY'];
  $order = array_keys($stages);
  $at = $current ? array_search($current->stage, $order) : -1;
  $running = $current && $current->status === 'running';
  $failed = $current && $current->status === 'failed';
  $dc = ['live' => '#58C98A', 'running' => '#A99BFF', 'failed' => '#E5695E', 'superseded' => '#6E6E79'];
@endphp
<div data-screen-label="Deployments" style="flex:1;overflow:auto;padding:22px 28px 40px" @if ($running) x-data x-init="setTimeout(() => location.reload(), 1500)" @endif>
  <form method="POST" action="{{ route('studio.projects.deploy.store', $project) }}" style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    @csrf
    <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">Deployments</h1><div style="color:#8A8A94;margin-top:5px">Production · {{ $live ? \App\Jobs\DeployProject::url($live->subdomain) : 'not published yet' }}</div></div>
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
      <div style="display:flex;align-items:center;border:1px solid #26262E;border-radius:6px;background:#111115;overflow:hidden;height:32px"><input name="subdomain" value="{{ old('subdomain', $subdomain) }}" class="mono" style="width:150px;height:100%;padding:0 9px;background:transparent;border:none;outline:none;color:#E4E4E9;font-size:12px"><span style="padding:0 9px;color:#6E6E79;font-size:12px;border-left:1px solid #1F1F26">{{ config('studio.publish_domain') ? '.'.config('studio.publish_domain') : '/p/…' }}</span></div>
      <button class="btn primary sm" style="height:32px" @disabled($running)>{{ $running ? 'Deploying…' : 'Deploy Project' }}</button>
    </div>
  </form>
  @error('subdomain')<div class="err" style="margin:-8px 0 14px">{{ $message }}</div>@enderror
  <div data-r="pipe" style="display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:1px;background:#1C1C22;border:1px solid #1C1C22;border-radius:9px;overflow:hidden;margin-bottom:18px">
    @foreach ($stages as $key => $name)
      @php($i = array_search($key, $order))
      @php($st = ! $current ? 'p' : (! $running && ! $failed ? 'd' : ($i < $at ? 'd' : ($i === $at ? ($failed ? 'f' : 'r') : 'p'))))
      @php($c = ['d' => '#58C98A', 'r' => '#A99BFF', 'f' => '#E5695E', 'p' => '#2A2A32'][$st])
      <div style="background:#0E0E11;padding:12px 14px;display:flex;flex-direction:column;gap:6px;box-shadow:inset 0 2px 0 {{ $c }}">
        <div style="display:flex;justify-content:space-between;align-items:center"><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;letter-spacing:0.06em">{{ $name }}</span><span style="font-family:'Geist Mono',ui-monospace,monospace;color:{{ $c }}">{{ ['d' => '✓', 'r' => '●', 'f' => '✕', 'p' => '○'][$st] }}</span></div>
        <span style="font-size:11.5px;color:#8A8A94">{{ ['build' => 'collect project files', 'test' => 'entry point & checks', 'package' => 'immutable snapshot', 'deploy' => 'switch traffic', 'done' => 'live URL responds'][$key] }}</span>
      </div>
    @endforeach
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:16px;align-items:start">
    <div style="border:1px solid #1C1C22;border-radius:9px;background:#0B0B0E;overflow:hidden">
      <div style="padding:10px 13px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><span style="font-weight:600">Deploy log</span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79">{{ $current ? '#d-'.$current->id.' · '.$current->commit : '' }}</span></div>
      <div style="padding:10px 13px;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;line-height:19px;min-height:120px">
        @forelse ($current?->log ?? [] as $dl)<div style="white-space:pre-wrap;color:{{ $dl['c'] ?? '#6E6E79' }}"><span style="color:#44444C">{{ $dl['t'] }}  </span>{{ $dl['m'] }}</div>@empty<div style="color:#6E6E79">{{ $running ? 'Waiting for a worker to pick this up…' : 'No deployments yet. Pick an address and click Deploy Project.' }}</div>@endforelse
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:16px">
      <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11">
        <div style="padding:10px 13px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><span style="font-weight:600">Environment</span><span style="font-size:11px;color:#58C98A">🔒 sandboxed</span></div>
        @foreach ([['RUNTIME', 'static (HTML/CSS/JS)'], ['FILES', $project->files()->count().' in working copy'], ['STACK', collect($project->stack ?? [])->except('why')->filter(fn ($v) => $v !== 'None')->implode(' · ') ?: '—']] as [$k, $v])
          <div style="display:flex;justify-content:space-between;gap:10px;padding:7px 13px;border-bottom:1px solid #16161B;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px"><span>{{ $k }}</span><span style="color:#6E6E79;text-align:right">{{ $v }}</span></div>
        @endforeach
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#1C1C22">
          <div style="background:#0E0E11;padding:10px 13px;min-width:0"><div style="font-size:11px;color:#6E6E79">Address</div><div style="margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">@if ($live)<a href="{{ \App\Jobs\DeployProject::url($live->subdomain) }}" target="_blank">{{ $live->subdomain }} ↗</a>@else — @endif</div></div>
          <div style="background:#0E0E11;padding:10px 13px"><div style="font-size:11px;color:#6E6E79">SSL</div><div style="margin-top:3px;color:{{ str_starts_with(config('app.url'), 'https') ? '#58C98A' : '#E8B66B' }}">{{ str_starts_with(config('app.url'), 'https') ? 'Valid' : 'Not on https' }}</div></div>
        </div>
      </div>
      <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11">
        <div style="padding:10px 13px;border-bottom:1px solid #1C1C22;font-weight:600">History</div>
        @forelse ($deployments as $dh)
          <div style="display:flex;align-items:center;gap:10px;padding:8px 13px;border-bottom:1px solid #16161B">
            <span style="width:7px;height:7px;border-radius:50%;flex:none;background:{{ $dc[$dh->status] ?? '#6E6E79' }}"></span>
            <a href="{{ route('studio.projects.deploy', [$project, 'd' => $dh->id]) }}" style="flex:1;min-width:0;color:inherit"><div style="font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $dh->message }}</div><div style="font-size:11px;color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace">{{ $dh->commit }} · {{ $dh->created_at->diffForHumans() }} · {{ $dh->status }}</div></a>
            @if ($dh->status === 'superseded' && $dh->hasFiles())
              <form method="POST" action="{{ route('studio.projects.deploy.rollback', [$project, $dh]) }}" class="inline">@csrf<button style="font-size:11.5px;color:#A99BFF;background:none;border:none;cursor:pointer">Rollback</button></form>
            @elseif ($dh->status === 'failed')
              <a href="{{ route('studio.projects.code', $project) }}" style="font-size:11.5px">Fix with AI</a>
            @else
              <a href="{{ route('studio.projects.deploy', [$project, 'd' => $dh->id]) }}" style="font-size:11.5px">Logs</a>
            @endif
          </div>
        @empty
          <div style="padding:12px 13px;color:#6E6E79;font-size:12.5px">Nothing deployed yet.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
