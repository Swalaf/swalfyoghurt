@extends('studio.layout')
@section('title', $project ? 'Review specification' : 'Create project')

@section('main')
@php
  $active = $project ? 2 : 0;
  $wz = ['What', 'Describe', 'Specification', 'Technology', 'Create'];
@endphp
<div data-screen-label="Create Project" style="flex:1;overflow:auto;padding:22px 28px 40px">
  <div style="max-width:1180px;margin:0 auto">
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:22px">
    @foreach ($wz as $i => $label)
      @php($s = $project ? ($i < 2 ? 'd' : ($i <= 3 ? 'a' : 'p')) : ($i < 2 ? 'a' : 'p'))
      <div style="display:flex;align-items:center;gap:8px;padding:6px 12px 6px 7px;border-radius:6px;border:1px solid {{ $s === 'a' ? '#3A3160' : '#1C1C22' }};background:{{ $s === 'a' ? '#110F1A' : 'transparent' }}">
        <span style="width:20px;height:20px;border-radius:4px;display:grid;place-items:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;background:{{ $s === 'a' ? 'var(--accent)' : ($s === 'd' ? '#15261C' : '#17171C') }};color:{{ $s === 'a' ? '#fff' : ($s === 'd' ? '#58C98A' : '#6E6E79') }}">{{ $s === 'd' ? '✓' : $i + 1 }}</span>
        <span style="font-size:12.5px;color:{{ $s === 'p' ? '#6E6E79' : '#E4E4E9' }}">{{ $label }}</span>
      </div>
    @endforeach
  </div>

  @if (! $project)
  <form method="POST" action="{{ route('studio.projects.store') }}" x-data="{ kind: @js(old('kind', 'Web app')), busy: false }" @submit="busy = true" style="display:flex;flex-direction:column;gap:18px;max-width:820px">
    @csrf <input type="hidden" name="kind" :value="kind">
    <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">What are you building?</h1><div style="color:#8A8A94;margin-top:5px">The AI turns this into a specification you can edit before any code is written.</div></div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      @foreach ($kinds as [$i, $t])
        <span @click="kind = @js($t)" :style="{ borderColor: kind === @js($t) ? 'var(--accent-line)' : '#22222A', background: kind === @js($t) ? '#17142A' : 'transparent', color: kind === @js($t) ? '#E4E4E9' : '#8A8A94' }" style="font-size:12.5px;padding:6px 11px;border-radius:6px;border:1px solid;cursor:pointer"><span style="color:#A99BFF;margin-right:5px">{{ $i }}</span>{{ $t }}</span>
      @endforeach
    </div>
    <label class="lbl"><span>Describe the product</span><textarea name="idea" required class="inp" placeholder="Build a logistics management platform with rider management, delivery tracking, wallet management, admin dashboard and customer portal." style="height:auto;min-height:110px;padding:10px 12px;line-height:1.55;resize:vertical">{{ old('idea', $idea) }}</textarea>@error('idea')<span class="err">{{ $message }}</span>@enderror</label>
    <label class="lbl" style="max-width:360px"><span>Project name <span style="color:#55555F">(optional)</span></span><input class="inp sm" name="name" value="{{ old('name') }}" placeholder="Logistix Platform" style="height:36px"></label>
    <div><button class="btn primary" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}" x-text="busy ? 'Generating specification…' : 'Generate specification →'">Generate specification →</button></div>
  </form>
  @else
  <form method="POST" action="{{ route('studio.projects.plan.update', $project) }}" x-data="{ busy: false, stack: @js($project->stack ?? []) }" @submit="busy = true">
    @csrf
    <template x-for="(v, k) in stack"><input type="hidden" :name="'stack[' + k + ']'" :value="v"></template>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:16px">
      <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">Review the project specification</h1><div style="color:#8A8A94;margin-top:5px">Generated from your idea. Edit anything before the agents start building.</div></div>
      <div style="display:flex;gap:6px"><button name="action" value="regenerate" class="btn sm hv-btn" style="height:32px;background:#121216;border-color:#1F1F26;color:#C9C9D1">✦ Regenerate</button><button name="action" value="build" class="btn primary sm" style="height:32px" :style="busy ? { opacity: .6, pointerEvents: 'none' } : {}">Approve plan & build →</button></div>
    </div>
    <div x-data="{ edit: false }" style="border:1px solid #1F1F26;border-radius:9px;background:#0E0E11;padding:12px 14px;margin-bottom:18px;display:flex;gap:12px;align-items:flex-start">
      <span style="font-size:10.5px;padding:2px 7px;border-radius:4px;background:#17171C;color:#9A9AA5;flex:none">{{ $project->kind }}</span>
      <span x-show="!edit" style="flex:1;line-height:1.55;color:#C9C9D1">{{ $project->idea }}</span>
      <textarea x-show="edit" x-cloak name="idea" class="inp" style="flex:1;height:auto;min-height:80px;padding:8px 10px;line-height:1.5">{{ $project->idea }}</textarea>
      <span @click="edit = !edit" style="color:#6E6E79;font-size:12px;flex:none;cursor:pointer" x-text="edit ? 'Done' : 'Edit'">Edit</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,520px),1fr));gap:18px;align-items:start">
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px">
        @foreach ([['features', 'Features', 'Geist'], ['roles', 'User roles', 'Geist'], ['pages', 'Pages', 'Geist'], ['database', 'Database', 'Geist Mono'], ['api', 'API', 'Geist Mono'], ['phases', 'Build plan', 'Geist']] as [$key, $title, $font])
          @php($items = $project->spec[$key] ?? [])
          <div x-data="{ adding: false }" style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11;padding:12px 13px;display:flex;flex-direction:column;gap:9px">
            <div style="display:flex;justify-content:space-between"><span style="font-weight:600">{{ $title }}</span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79">{{ count($items) }}{{ $key === 'database' ? ' tables' : ($key === 'api' ? ' endpoints' : ($key === 'phases' ? ' phases' : '')) }}</span></div>
            <div style="display:flex;flex-direction:column;gap:5px">
              @foreach ($items as $si)<div style="font-family:{{ $font }},system-ui,sans-serif;font-size:12px;color:#B4B4BE;display:flex;gap:7px"><span style="color:#55555F">–</span>{{ $si }}</div>@endforeach
            </div>
            <span x-show="!adding" @click="adding = true; $nextTick(() => $refs.i.focus())" style="font-size:11.5px;color:#6E6E79;cursor:pointer">＋ Add</span>
            <div class="d-flex" x-show="adding" x-cloak style="gap:6px"><input x-ref="i" :name="adding ? 'extra' : ''" class="inp sm" style="height:28px;font-size:12px"><input type="hidden" :name="adding ? 'section' : ''" value="{{ $key }}"><button name="action" value="add" class="btn xs" style="height:28px">Add</button></div>
          </div>
        @endforeach
      </div>
      <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11">
        <div style="padding:12px 14px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600">Technology</span><span style="font-size:11px;padding:3px 8px;border-radius:4px;background:#15122A;color:#B4A5FF">✦ AI recommended stack</span></div>
        @foreach (\App\Services\Studio\Blueprints::STACK_OPTIONS as $key => $opts)
          <div style="padding:11px 14px;border-bottom:1px solid #16161B;display:flex;flex-direction:column;gap:7px">
            <span style="font-size:11px;color:#6E6E79;letter-spacing:0.06em">{{ strtoupper($key) }}</span>
            <div style="display:flex;gap:5px;flex-wrap:wrap">
              @foreach ($opts as $o)
                <span @click="stack[@js($key)] = @js($o)" :style="{ borderColor: stack[@js($key)] === @js($o) ? 'var(--accent-line)' : '#22222A', background: stack[@js($key)] === @js($o) ? '#17142A' : 'transparent', color: stack[@js($key)] === @js($o) ? '#E4E4E9' : '#8A8A94' }" style="font-size:12px;padding:4px 9px;border-radius:5px;border:1px solid;cursor:pointer">{{ ($project->stack[$key] ?? null) === $o ? '✦ ' : '' }}{{ $o }}</span>
              @endforeach
            </div>
          </div>
        @endforeach
        <div style="padding:11px 14px;font-size:12px;color:#8A8A94;line-height:1.5">Why: {{ $project->stack['why'] ?? 'Picked to match the kind of project you described.' }}</div>
      </div>
    </div>
  </form>
  @endif
  </div>
</div>
@endsection
