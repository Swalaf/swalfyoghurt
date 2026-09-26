@extends('studio.layout')
@section('title', 'Publish · '.$project->name)

@section('main')
@php
  $running = $current && $current->status === 'running';
  $pct = $current?->progress ?? 0;
  $steps = [['Checking everything works', 35], ['Packing up your app', 60], ['Putting it online', 85], ['Making it live', 100]];
@endphp
<div data-screen-label="Publish (Simple)" style="flex:1;overflow:auto;padding:36px 28px 60px" @if ($running) x-data x-init="setTimeout(() => location.reload(), 1500)" @endif>
  <div style="max-width:760px;margin:0 auto;display:flex;flex-direction:column;gap:20px">
    <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Publish your app</h1><p style="margin:6px 0 0;color:#8A8A94">Put {{ $project->name }} on the internet so anyone can use it.</p></div>
    <div style="border:1px solid #1C1C22;border-radius:14px;background:#0E0E11;padding:22px;display:flex;flex-direction:column;gap:16px">
      @if ($running)
        <div style="display:flex;flex-direction:column;gap:12px">
          <div style="font-weight:600;font-size:15px">Publishing… {{ $pct }}%</div>
          <div style="height:8px;border-radius:4px;background:#1C1C22"><div style="height:100%;border-radius:4px;background:var(--accent);width:{{ $pct }}%"></div></div>
          @foreach ($steps as $i => [$t, $at])
            @php($prev = $i ? $steps[$i - 1][1] : 0)
            @php($s = $pct >= $at ? 'd' : ($pct >= $prev ? 'r' : 'p'))
            <div style="display:flex;gap:10px;font-size:13.5px;color:{{ $s === 'p' ? '#6E6E79' : '#E4E4E9' }}"><span style="width:14px;font-family:'Geist Mono',ui-monospace,monospace;color:{{ ['d' => '#58C98A', 'r' => '#A99BFF', 'p' => '#44444C'][$s] }}">{{ ['d' => '✓', 'r' => '●', 'p' => '○'][$s] }}</span>{{ $t }}</div>
          @endforeach
        </div>
      @else
        @if ($current && $current->status === 'live')
          <div style="display:flex;flex-direction:column;gap:14px;padding-bottom:16px;border-bottom:1px solid #1C1C22">
            <div style="display:flex;gap:12px;align-items:center"><span style="width:40px;height:40px;border-radius:50%;background:#15261C;color:#58C98A;display:grid;place-items:center;font-size:18px">✓</span><div><div style="font-weight:600;font-size:16px">Your app is live!</div><div style="color:#8A8A94;font-size:13px">Anyone with the link can use it now.</div></div></div>
            @php($url = \App\Jobs\DeployProject::url($current->subdomain))
            <div x-data="{ copied: false }" style="display:flex;align-items:center;gap:8px;border:1px solid #26262E;border-radius:9px;background:#111115;padding:0 6px 0 14px;height:44px"><span class="mono" style="flex:1;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $url }}</span><span @click="navigator.clipboard.writeText(@js($url)); copied = true" class="btn sm" style="height:32px" x-text="copied ? 'Copied' : 'Copy'">Copy</span><a href="{{ $url }}" target="_blank" class="btn primary sm" style="height:32px">Open</a></div>
          </div>
        @elseif ($current && $current->status === 'failed')
          <div style="padding:10px 12px;border-radius:8px;background:#1A0F0F;color:#F2B8B2;font-size:13px;line-height:1.5">That didn’t work: {{ collect($current->log)->last()['m'] ?? 'unknown error' }}. <a href="{{ route('studio.projects.builder', $project) }}">Ask the AI to fix it →</a></div>
        @endif
        <form method="POST" action="{{ route('studio.projects.deploy.store', $project) }}" style="display:flex;flex-direction:column;gap:14px">
          @csrf
          <div style="font-weight:600;font-size:15px">Your web address</div>
          <div style="display:flex;align-items:center;border:1px solid #26262E;border-radius:9px;background:#111115;overflow:hidden"><input name="subdomain" value="{{ old('subdomain', $subdomain) }}" style="flex:1;min-width:0;height:42px;padding:0 12px;background:transparent;border:none;outline:none;color:#F2F2F5;font-size:15px"><span style="padding:0 14px;color:#8A8A94;font-size:14px;white-space:nowrap">{{ config('studio.publish_domain') ? '.'.config('studio.publish_domain') : ' at '.request()->getHost().'/p/' }}</span></div>
          @error('subdomain')<div class="err">{{ $message }}</div>@enderror
          <div style="font-size:13px;color:#8A8A94">Want your own domain like <span style="color:#C9C9D1">mybusiness.com</span>? Download your app from the Code view and host it anywhere — it’s just files.</div>
          <button class="btn primary" style="align-self:flex-start;height:44px;padding:0 22px;border-radius:10px;font-size:15px">{{ $live ? 'Publish latest changes' : 'Publish now' }}</button>
        </form>
      @endif
    </div>
    <div style="border:1px solid #1C1C22;border-radius:14px;background:#0E0E11">
      <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;font-weight:600">Earlier versions</div>
      @forelse ($deployments->where('status', '!=', 'running') as $h)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #16161B"><span style="width:8px;height:8px;border-radius:50%;background:{{ $h->status === 'failed' ? '#E5695E' : '#58C98A' }};flex:none"></span><div style="flex:1;min-width:0"><div>{{ $h->message }}</div><div style="font-size:12px;color:#6E6E79">{{ $h->created_at->diffForHumans() }}{{ $h->status === 'live' ? ' · live now' : '' }}{{ $h->status === 'failed' ? ' · didn’t publish' : '' }}</div></div>
          @if ($h->status === 'superseded' && $h->snapshot)<form method="POST" action="{{ route('studio.projects.deploy.rollback', [$project, $h]) }}" class="inline">@csrf<button style="font-size:12.5px;color:#C7BDFF;background:none;border:none;cursor:pointer">Go back to this</button></form>@endif
        </div>
      @empty
        <div style="padding:14px 18px;color:#6E6E79;font-size:13px">Nothing published yet.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection
