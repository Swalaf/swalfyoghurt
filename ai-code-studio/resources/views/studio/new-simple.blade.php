@extends('studio.layout')
@section('title', 'Create a new app')

@section('main')
@php($step = $project ? 2 : 1)
<div data-screen-label="Create (Simple)" style="flex:1;overflow:auto;padding:36px 28px 60px">
  <div style="max-width:880px;margin:0 auto;display:flex;flex-direction:column;gap:26px">
    <div style="display:flex;gap:8px;align-items:center;font-size:13px;color:#8A8A94;flex-wrap:wrap">
      @foreach (['Describe', 'Review plan', 'Build'] as $i => $t)
        <span style="display:flex;align-items:center;gap:8px"><span style="width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-size:11.5px;background:{{ $i + 1 <= $step ? 'var(--accent)' : '#1C1C22' }};color:{{ $i + 1 <= $step ? '#fff' : '#6E6E79' }}">{{ $i + 1 < $step ? '✓' : $i + 1 }}</span><span style="color:{{ $i + 1 === $step ? '#F2F2F5' : '#6E6E79' }}">{{ $t }}</span>@if ($i < 2)<span style="color:#3A3A44;margin:0 4px">—</span>@endif</span>
      @endforeach
    </div>

    @if (! $project)
    <form method="POST" action="{{ route('studio.projects.store') }}" x-data="{ kind: @js(old('kind', 'Web app')), busy: false }" @submit="busy = true" style="display:flex;flex-direction:column;gap:18px">
      @csrf
      <input type="hidden" name="kind" :value="kind">
      <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">What kind of thing are you making?</h1><p style="margin:6px 0 0;color:#8A8A94">Pick the closest one — you can change anything later.</p></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
        @foreach ($kinds as [$i, $t, $d])
          <div @click="kind = @js($t)" :style="{ borderColor: kind === @js($t) ? 'var(--accent)' : '#1C1C22', background: kind === @js($t) ? '#110F1A' : '#0E0E11' }" class="hv-violet2" style="border:1px solid;border-radius:12px;padding:16px;cursor:pointer;display:flex;flex-direction:column;gap:6px"><span style="font-size:18px;color:#A99BFF">{{ $i }}</span><span style="font-weight:600">{{ $t }}</span><span style="font-size:12.5px;color:#8A8A94;line-height:1.45">{{ $d }}</span></div>
        @endforeach
      </div>
      <label class="lbl"><span>Describe it in your own words</span>
        <textarea name="idea" required minlength="5" class="inp" placeholder="e.g. A website for my bakery where people can see the menu and order cakes for pickup" style="height:auto;min-height:90px;padding:10px 12px;line-height:1.5;resize:vertical">{{ old('idea', $idea) }}</textarea>
        @error('idea')<span class="err">{{ $message }}</span>@enderror
      </label>
      <div style="display:flex;justify-content:flex-end"><button class="btn primary" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" style="height:40px;padding:0 20px;border-radius:9px;font-size:14px" x-text="busy ? 'Planning your app…' : 'Next →'">Next →</button></div>
    </form>
    @else
    @php($features = $project->spec['features'] ?? [])
    <form method="POST" action="{{ route('studio.projects.plan.update', $project) }}" x-data="{ off: {}, busy: false }" @submit="busy = true" style="display:flex;flex-direction:column;gap:18px">
      @csrf
      <input type="hidden" name="keep[]" value="-1">
      <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">Here’s what I’ll build for you</h1><p style="margin:6px 0 0;color:#8A8A94">Read it through. Untick anything you don’t want, or tell me what to add.</p></div>
      <div style="border:1px solid #1C1C22;border-radius:12px;background:#0E0E11;overflow:hidden">
        @foreach ($features as $i => $f)
          <input type="checkbox" name="keep[]" value="{{ $i }}" :checked="!off[{{ $i }}]" hidden>
          <div @click="off[{{ $i }}] = !off[{{ $i }}]" style="display:flex;gap:14px;padding:14px 16px;border-bottom:1px solid #16161B;cursor:pointer;align-items:flex-start">
            <span :style="{ background: off[{{ $i }}] ? 'transparent' : 'var(--accent)', borderColor: off[{{ $i }}] ? '#3A3A44' : 'var(--accent)' }" style="width:20px;height:20px;border-radius:5px;flex:none;display:grid;place-items:center;font-size:12px;color:#fff;margin-top:1px;border:1px solid" x-text="off[{{ $i }}] ? '' : '✓'">✓</span>
            <div><div :style="{ color: off[{{ $i }}] ? '#6E6E79' : '#F2F2F5' }" style="font-weight:500">{{ $f }}</div><div style="font-size:13px;color:#8A8A94;margin-top:3px;line-height:1.45">Included in the first version.</div></div>
          </div>
        @endforeach
        <div style="padding:12px 16px;display:flex;gap:8px"><input name="extra" placeholder="Anything else? e.g. “Customers should get a text when their order is ready”" class="inp" style="flex:1;min-width:0;height:36px;border-radius:8px;font-size:13.5px"><button name="action" value="add" class="btn" style="height:36px">Add</button></div>
      </div>
      <div style="display:flex;gap:12px;padding:12px 14px;border-radius:10px;background:#0E0E11;border:1px solid #1C1C22;font-size:13px;color:#9A9AA5;line-height:1.5"><span style="color:#A99BFF">i</span><span>A few minutes to build · uses roughly {{ 20 + count($features) * 6 }} of your {{ auth()->user()->is_admin ? 'unlimited' : number_format(auth()->user()->credits) }} credits. I’ll pick the best technology automatically.</span></div>
      <div style="display:flex;justify-content:space-between;gap:10px">
        <a href="{{ route('studio.dashboard') }}" class="btn" style="height:40px;padding:0 16px;border-radius:9px">← Back</a>
        <button name="action" value="build" class="btn primary" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" style="height:40px;padding:0 20px;border-radius:9px;font-size:14px">Looks good — build it</button>
      </div>
    </form>
    @endif
  </div>
</div>
@endsection
