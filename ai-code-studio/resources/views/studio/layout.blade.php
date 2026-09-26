@extends('layouts.base')

@php
  $user = auth()->user();
  $simple = ! $user->isDeveloper();
  $myProjects = $user->projects()->latest('updated_at')->get(['id', 'name', 'slug', 'status', 'branch']);
  $current = $project ?? $myProjects->firstWhere('slug', session('studio.project')) ?? $myProjects->first();
  $route = request()->route()->getName();
  $pr = fn ($name) => $current ? route('studio.projects.'.$name, $current) : route('studio.new');
  $working = $current ? $current->agentTasks()->where('status', 'working')->count() : 0;
  $item = fn ($routeName, $url, $name, $icon, $meta = '') => ['on' => $route === $routeName, 'url' => $url, 'name' => $name, 'icon' => $icon, 'meta' => $meta];
  $adminItems = $user->is_admin ? [$item('admin', route('admin.overview'), 'Admin panel', '⚙')] : [];
  $nav = $simple ? [
    ['HOME', [$item('studio.dashboard', route('studio.dashboard'), 'My apps', '◧'), $item('studio.new', route('studio.new'), 'Create a new app', '＋')]],
    ['THIS APP', [$item('studio.projects.builder', $pr('builder'), 'Build with AI', '✦'), $item('studio.projects.agents', $pr('agents'), 'AI team at work', '◎', $working ?: ''), $item('studio.projects.deploy', $pr('deploy'), 'Publish', '↑')]],
    ['ACCOUNT', array_merge([$item('studio.account', route('studio.account'), 'Account & security', '◉')], $adminItems)],
  ] : array_values(array_filter([
    ['WORKSPACE', [$item('studio.dashboard', route('studio.dashboard'), 'Dashboard', '◧'), $item('studio.new', route('studio.new'), 'Create Project', '＋')]],
    ['DEVELOPMENT', [$item('studio.projects.builder', $pr('builder'), 'Builder', '▣'), $item('studio.projects.code', $pr('code'), 'Code', '</>'), $item('studio.projects.agents', $pr('agents'), 'Agents', '✦', $working ?: '')]],
    ['DEPLOYMENT', [$item('studio.projects.deploy', $pr('deploy'), 'Deployments', '↑')]],
    $user->is_admin ? ['ADMIN', array_merge([$item('studio.providers', route('studio.providers'), 'AI Providers', '◇'), $item('studio.brand', route('studio.brand'), 'White Label', '◐')], $adminItems)] : null,
  ]));
  $allowance = max(1, $user->creditAllowance());
  $commands = array_values(array_filter([
    ['SUGGESTED', array_values(array_filter([
      $current ? ['✦', 'Ask AI about '.$current->name, '', $pr($simple ? 'builder' : 'code')] : null,
      ['＋', 'Create project', '', route('studio.new')],
      $current ? ['⇣', 'Download '.$current->slug.'.zip', '', $pr('download')] : null,
    ]))],
    ['NAVIGATE', array_values(array_filter(array_merge(
      $current ? [['↑', ($simple ? 'Publish ' : 'Deploy ').$current->slug, '', $pr('deploy')], ['◎', 'Agents', '', $pr('agents')]] : [],
      $myProjects->take(5)->map(fn ($p) => ['▣', 'Open '.$p->name, '', route('studio.projects.builder', $p)])->all(),
      $user->is_admin ? [['◇', 'Open AI providers', '', route($simple ? 'admin.providers' : 'studio.providers')], ['◐', 'White-label settings', '', route($simple ? 'admin.branding' : 'studio.brand')], ['⚙', 'Admin panel', '', route('admin.overview')]] : [],
      [['◉', 'Account & security', '', route('studio.account')]],
    )))],
  ]));
@endphp

@section('body')
<div x-data="{ palette: false, q: '' }" @keydown.window.prevent.meta.k="palette = !palette; q = ''" @keydown.window.prevent.ctrl.k="palette = !palette; q = ''" @keydown.window.escape="palette = false" style="height:100vh;display:flex;flex-direction:column;background:#0A0A0C;font-size:13px;overflow:hidden">

@if (session('impersonator_id'))
<form method="POST" action="{{ route('impersonation.stop') }}" style="flex:none;display:flex;align-items:center;justify-content:center;gap:10px;height:30px;background:#2A2214;color:#E8B66B;font-size:12.5px">@csrf You’re viewing the studio as {{ $user->name }}. <button style="background:none;border:none;color:#F2D19B;text-decoration:underline;cursor:pointer;font-size:12.5px">Return to admin</button></form>
@endif

<header style="height:46px;flex:none;display:flex;align-items:center;gap:10px;padding:0 12px;border-bottom:1px solid #1C1C22;background:#0D0D10">
  <a href="{{ route('studio.dashboard') }}" style="display:flex;align-items:center;gap:9px;width:196px;flex:none;overflow:hidden;white-space:nowrap;color:#E4E4E9" data-r="logo">
    @include('partials.logo', ['size' => 22])
    <span style="font-weight:600;letter-spacing:-0.01em" data-r="hide1">{{ $brand }}</span>
  </a>
  @if ($current)
  <div x-data="{ open: false }" data-r="hidem" style="position:relative">
    <div @click="open = !open" style="display:flex;align-items:center;gap:6px;padding:0 10px;height:28px;border:1px solid #1F1F26;border-radius:6px;color:#C9C9D1;cursor:pointer;white-space:nowrap;max-width:220px">
      <span style="width:7px;height:7px;border-radius:2px;background:#5CC8E0;flex:none"></span><span style="overflow:hidden;text-overflow:ellipsis">{{ $current->slug }}</span><span style="color:#5E5E68">▾</span>
    </div>
    <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;left:0;top:34px;z-index:40;width:260px;background:#111115;border:1px solid #26262E;border-radius:9px;padding:6px;box-shadow:0 16px 40px rgba(0,0,0,.45);max-height:60vh;overflow:auto">
      @foreach ($myProjects as $p)
        <a href="{{ route('studio.projects.builder', $p) }}" class="hv-list" style="display:flex;justify-content:space-between;gap:8px;padding:7px 10px;border-radius:6px;color:{{ $p->id === $current->id ? '#F2F2F5' : '#B4B4BE' }}"><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->name }}</span><span class="mono" style="font-size:11px;color:#6E6E79">{{ $p->status }}</span></a>
      @endforeach
      <a href="{{ route('studio.new') }}" class="hv-list" style="display:block;padding:7px 10px;border-radius:6px;color:#C7BDFF;border-top:1px solid #1C1C22;margin-top:4px">＋ New project</a>
    </div>
  </div>
  @endif
  @if (! $simple && $current)<div data-r="hidem" style="display:flex;align-items:center;gap:6px;padding:0 10px;height:28px;border:1px solid #1F1F26;border-radius:6px;color:#9A9AA5;font-family:'Geist Mono',ui-monospace,monospace;font-size:12px">⎇ {{ $current->branch }}</div>@endif
  <form method="POST" action="{{ route('studio.experience') }}" title="Simple hides code and technical tools. Switch any time." style="display:flex;border:1px solid #1F1F26;border-radius:7px;padding:2px;gap:2px;flex:none;margin:0">
    @csrf
    <button name="experience" value="simple" style="padding:3px 10px;border-radius:5px;font-size:12px;cursor:pointer;white-space:nowrap;border:none;background:{{ $simple ? 'var(--accent-soft)' : 'transparent' }};color:{{ $simple ? '#C7BDFF' : '#8A8A94' }}">Simple</button>
    <button name="experience" value="developer" style="padding:3px 10px;border-radius:5px;font-size:12px;cursor:pointer;white-space:nowrap;border:none;background:{{ $simple ? 'transparent' : 'var(--accent-soft)' }};color:{{ $simple ? '#8A8A94' : '#C7BDFF' }}">Developer</button>
  </form>
  <div data-r="hidem" style="flex:1;min-width:0;display:flex;justify-content:center">
    <button @click="palette = true; q = ''" class="hv-card" style="width:min(420px,100%);min-width:0;height:28px;display:flex;align-items:center;gap:8px;padding:0 10px;background:#121216;border:1px solid #1F1F26;border-radius:6px;color:#6E6E79;font-size:12px;cursor:pointer">
      <span>⌕</span><span style="flex:1;min-width:0;text-align:left;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Search projects, commands, settings…</span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;padding:1px 5px;border:1px solid #2A2A32;border-radius:4px">⌘K</span>
    </button>
  </div>
  @if ($current)
  <div style="display:flex;gap:6px;margin-left:auto">
    <a href="{{ $pr($simple ? 'builder' : 'code') }}" class="btn sm hv-btn" style="background:#121216;border-color:#1F1F26;color:#D4D4DA;font-size:12px" data-r="hidem">Preview</a>
    <a href="{{ $pr('deploy') }}" class="btn primary sm" style="font-size:12px">{{ $simple ? 'Publish' : 'Deploy' }}</a>
  </div>
  @else
  <span style="margin-left:auto"></span>
  @endif
  <div style="width:1px;height:20px;background:#1F1F26"></div>
  <div x-data="{ open: false }" style="position:relative">
    <div @click="open = !open" style="width:26px;height:26px;border-radius:50%;background:#26262E;display:grid;place-items:center;font-size:11px;font-weight:600;color:#C9C9D1;cursor:pointer">{{ $user->initials() }}</div>
    <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;right:0;top:34px;z-index:40;width:210px;background:#111115;border:1px solid #26262E;border-radius:9px;padding:6px;box-shadow:0 16px 40px rgba(0,0,0,.45)">
      <div style="padding:6px 10px 8px;font-size:12px;color:#8A8A94;border-bottom:1px solid #1C1C22;margin-bottom:4px">{{ $user->email }}</div>
      <a href="{{ route('studio.account') }}" class="hv-list" style="display:block;padding:7px 10px;border-radius:6px;color:#D4D4DA">Account & security</a>
      @if ($user->is_admin)<a href="{{ route('admin.overview') }}" class="hv-list" style="display:block;padding:7px 10px;border-radius:6px;color:#D4D4DA">Admin panel</a>@endif
      <form method="POST" action="{{ route('logout') }}">@csrf<button class="hv-list" style="width:100%;text-align:left;padding:7px 10px;border-radius:6px;background:none;border:none;color:#D4D4DA;cursor:pointer;font-size:13px">Sign out</button></form>
    </div>
  </div>
</header>

<div style="flex:1;display:flex;min-height:0">
<nav data-r="nav" style="width:208px;flex:none;border-right:1px solid #1C1C22;background:#0D0D10;padding:10px 8px;overflow:auto;display:flex;flex-direction:column;gap:14px">
  @foreach ($nav as [$label, $items])
    <div style="display:flex;flex-direction:column;gap:1px">
      <div style="font-size:10.5px;letter-spacing:0.08em;color:#55555F;font-weight:500;padding:0 8px 5px" data-r="hide1">{{ $label }}</div>
      @foreach ($items as $it)
        <a href="{{ $it['url'] }}" class="hv-bg" title="{{ $it['name'] }}" style="display:flex;align-items:center;gap:9px;height:28px;padding:0 8px;border-radius:5px;background:{{ $it['on'] ? '#1A1A20' : 'transparent' }};color:{{ $it['on'] ? '#F2F2F5' : '#A5A5AF' }}">
          <span style="width:14px;text-align:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:{{ $it['on'] ? '#A99BFF' : '#6E6E79' }}">{{ $it['icon'] }}</span>
          <span data-r="hide1" style="flex:1;white-space:nowrap">{{ $it['name'] }}</span>
          <span style="font-size:10.5px;font-family:'Geist Mono',ui-monospace,monospace;color:#6E6E79" data-r="hide1">{{ $it['meta'] }}</span>
        </a>
      @endforeach
    </div>
  @endforeach
  <div style="margin-top:auto;border:1px solid #1C1C22;border-radius:7px;padding:10px;display:flex;flex-direction:column;gap:7px" data-r="hide1">
    @if ($user->is_admin)
      <div style="display:flex;justify-content:space-between;font-size:11.5px"><span style="color:#8A8A94">AI credits</span><span style="font-family:'Geist Mono',ui-monospace,monospace">Unlimited</span></div>
      <div style="font-size:11px;color:#6E6E79">Admins aren’t charged credits.</div>
    @else
      <div style="display:flex;justify-content:space-between;font-size:11.5px"><span style="color:#8A8A94">AI credits</span><span style="font-family:'Geist Mono',ui-monospace,monospace">{{ number_format($user->credits) }} / {{ $allowance >= 1000 ? round($allowance / 1000, 1).'k' : $allowance }}</span></div>
      <div style="height:4px;border-radius:2px;background:#1C1C22"><div style="width:{{ min(100, round($user->credits / $allowance * 100)) }}%;height:100%;border-radius:2px;background:var(--accent)"></div></div>
      <div style="font-size:11px;color:#6E6E79">{{ $user->plan?->name ?? 'No' }} plan</div>
    @endif
  </div>
</nav>

<main style="flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden">
  @yield('main')
</main>
</div>

<div class="d-flex" x-show="palette" x-cloak @click="palette = false" style="position:fixed;inset:0;background:rgba(5,5,7,0.6);justify-content:center;padding-top:12vh;z-index:50">
  <div @click.stop style="width:min(580px,92vw);height:fit-content;background:#111115;border:1px solid #26262E;border-radius:11px;box-shadow:0 24px 60px rgba(0,0,0,0.5);overflow:hidden">
    <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid #1F1F26"><span style="color:#6E6E79">⌕</span><input x-model="q" x-effect="palette && $nextTick(() => $el.focus())" placeholder="Type a command or search…" style="flex:1;background:transparent;border:none;outline:none;color:#E4E4E9;font-size:14px"><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:#6E6E79;border:1px solid #2A2A32;border-radius:4px;padding:1px 5px">esc</span></div>
    <div style="padding:6px;max-height:60vh;overflow:auto">
      @foreach ($commands as [$group, $items])
        <div style="font-size:10.5px;color:#55555F;letter-spacing:0.08em;padding:8px 10px 4px">{{ $group }}</div>
        @foreach ($items as [$icon, $name, $key, $url])
          <a href="{{ $url }}" x-show="!q || @js(strtolower($name)).includes(q.toLowerCase())" class="hv-list d-flex" style="align-items:center;gap:10px;padding:7px 10px;border-radius:6px;color:#E4E4E9">
            <span style="width:16px;font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#A99BFF;text-align:center">{{ $icon }}</span><span style="flex:1">{{ $name }}</span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79">{{ $key }}</span>
          </a>
        @endforeach
      @endforeach
    </div>
  </div>
</div>
</div>
@endsection
