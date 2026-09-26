@extends('layouts.base')

@php
  $pages = [
    'overview' => ['Overview', 'Your platform at a glance: who signed up, how much you earned, and whether everything is running.', 'A checklist gets your platform ready for customers. Work top to bottom — each step takes a minute or two.'],
    'users' => ['Users', 'Everyone who has an account on your platform. Click Manage to add credits, reset a password or suspend someone.', '“Credits” are what customers spend when the AI does work. You can top anyone up for free — handy for support or promotions.'],
    'plans' => ['Plans & pricing', 'Decide what customers pay and what each plan includes. Prices are yours to set.', 'Start with 2–3 plans. A free plan with a small credit allowance helps people try the product before paying.'],
    'providers' => ['AI providers', 'The AI services that power your platform. You pay them directly for usage — then charge your customers through plans.', 'You only need one provider to get started. OpenRouter is the easiest: one key gives you access to hundreds of models.'],
    'branding' => ['Branding', 'Make the platform yours — name, logo, colors and web address. Changes appear instantly for all users.', 'Customers never see “AI Code Studio” unless you want them to. Everything here replaces it with your brand.'],
    'settings' => ['Settings', 'Everything else: sign-up rules, email sending, storage and security.', 'Not sure about a setting? The defaults are safe. Only change what you need.'],
    'health' => ['System health', 'Checks that your server and background services are working properly.', 'Green means fine. If anything turns amber or red, the button next to it tells you what to do.'],
    'logs' => ['Activity logs', 'A record of everything that happens on the platform — useful when something goes wrong.', 'Filter by “Errors” first when investigating a problem. Each entry shows which user it affected.'],
    'license' => ['License & updates', 'Your license details and new versions of the software.', 'Updates add features and security fixes. Always keep “Back up first” ticked.'],
  ];
  [$pageTitle, $pageDesc, $tip] = $pages[$page];
  $badges = \App\Http\Controllers\Admin\AdminController::navBadges();
  $A = '#E8B66B'; $M = '#6E6E79'; $V = '#A99BFF';
  $nav = [
    ['START HERE', [['overview', 'Overview', '◧', $badges['overview'], $A]]],
    ['CUSTOMERS', [['users', 'Users', '◉', $badges['users'], $M], ['plans', 'Plans & pricing', '$', '', $M]]],
    ['AI', [['providers', 'AI providers', '✦', '', $M]]],
    ['YOUR BRAND', [['branding', 'Branding', '◐', '', $M]]],
    ['SYSTEM', [['settings', 'Settings', '⚙', '', $M], ['health', 'System health', '♥', $badges['health'], $A], ['logs', 'Activity logs', '≡', '', $M], ['license', 'License & updates', '↻', '', $V]]],
  ];
  $tipsOn = session('admin_tips', true);
@endphp

@section('title', $pageTitle.' · Admin')

@section('body')
<div style="height:100vh;display:flex;background:#0A0A0C;font-size:13.5px;overflow:hidden">

<nav data-r="anav" style="width:236px;flex:none;border-right:1px solid #1C1C22;background:#0D0D10;display:flex;flex-direction:column;overflow:auto">
  <div style="height:54px;display:flex;align-items:center;gap:9px;padding:0 16px;border-bottom:1px solid #1C1C22;flex:none">
    @include('partials.logo')
    <div data-r="anavtxt" style="display:flex;flex-direction:column;line-height:1.2;white-space:nowrap"><span style="font-weight:600">{{ $brand }}</span><span style="font-size:11px;color:#E8B66B">Admin</span></div>
  </div>
  <div style="padding:10px 8px;display:flex;flex-direction:column;gap:14px">
    @foreach ($nav as [$label, $items])
      <div style="display:flex;flex-direction:column;gap:1px">
        <div data-r="anavtxt" style="font-size:10.5px;letter-spacing:0.08em;color:#55555F;font-weight:500;padding:0 10px 5px">{{ $label }}</div>
        @foreach ($items as [$id, $name, $icon, $badge, $bc])
          @php($on = $page === $id)
          <a href="{{ route('admin.'.$id) }}" class="hv-bg" style="display:flex;align-items:center;gap:10px;min-height:32px;padding:0 10px;border-radius:6px;background:{{ $on ? '#1A1A20' : 'transparent' }};color:{{ $on ? '#F2F2F5' : '#A5A5AF' }}">
            <span style="width:16px;text-align:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;flex:none;color:{{ $on ? $V : $M }}">{{ $icon }}</span>
            <span data-r="anavtxt" style="flex:1;white-space:nowrap">{{ $name }}</span>
            <span data-r="anavtxt" style="font-size:10.5px;font-family:'Geist Mono',ui-monospace,monospace;color:{{ $bc }}">{{ $badge }}</span>
          </a>
        @endforeach
      </div>
    @endforeach
  </div>
  <a href="{{ route('studio.dashboard') }}" style="margin:auto 8px 12px;display:flex;align-items:center;gap:10px;height:34px;padding:0 10px;border-radius:6px;border:1px solid #1F1F26;color:#C9C9D1;font-size:13px"><span style="width:16px;text-align:center">←</span><span data-r="anavtxt">Back to Studio</span></a>
</nav>

<main style="flex:1;min-width:0;display:flex;flex-direction:column">
  <header style="height:54px;flex:none;display:flex;align-items:center;gap:12px;padding:0 24px;border-bottom:1px solid #1C1C22">
    <span style="color:#6E6E79">Admin</span><span style="color:#3A3A44">/</span><span style="font-weight:500">{{ $pageTitle }}</span>
    <span style="flex:1"></span>
    <form method="POST" action="{{ route('admin.tips') }}" class="inline">@csrf
      <button style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;color:#9A9AA5;background:none;border:none;padding:0">
        <span style="width:30px;height:17px;border-radius:9px;padding:2px;display:flex;background:{{ $tipsOn ? 'var(--accent)' : '#2A2A32' }};justify-content:{{ $tipsOn ? 'flex-end' : 'flex-start' }}"><span style="width:13px;height:13px;border-radius:50%;background:#fff"></span></span><span data-r="hidem">Beginner tips</span>
      </button>
    </form>
    <div x-data="{ open: false }" style="position:relative">
      <div @click="open = !open" style="width:28px;height:28px;border-radius:50%;background:#26262E;display:grid;place-items:center;font-size:11px;font-weight:600;cursor:pointer">{{ auth()->user()->initials() }}</div>
      <div x-show="open" x-cloak @click.outside="open = false" style="position:absolute;right:0;top:36px;z-index:30;width:200px;background:#111115;border:1px solid #26262E;border-radius:9px;padding:6px;box-shadow:0 16px 40px rgba(0,0,0,.45)">
        <div style="padding:6px 10px 8px;font-size:12px;color:#8A8A94;border-bottom:1px solid #1C1C22;margin-bottom:4px">{{ auth()->user()->email }}</div>
        <a href="{{ route('studio.account') }}" class="hv-list" style="display:block;padding:7px 10px;border-radius:6px;color:#D4D4DA">Account & security</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="hv-list" style="width:100%;text-align:left;padding:7px 10px;border-radius:6px;background:none;border:none;color:#D4D4DA;cursor:pointer;font-size:13.5px">Sign out</button></form>
      </div>
    </div>
  </header>

  <div style="flex:1;overflow:auto">
  <div style="max-width:1240px;margin:0 auto;padding:24px 28px 60px;display:flex;flex-direction:column;gap:20px">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap">
      <div><h1 style="margin:0;font-size:23px;font-weight:600;letter-spacing:-0.02em">{{ $pageTitle }}</h1><p style="margin:5px 0 0;color:#8A8A94;max-width:660px;line-height:1.5">{{ $pageDesc }}</p></div>
      @yield('cta')
    </div>
    @if ($tipsOn)
      <div style="display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border:1px solid #2A2540;background:#110F1A;border-radius:9px">
        <span style="width:20px;height:20px;border-radius:50%;background:#221D3D;color:#C7BDFF;display:grid;place-items:center;font-size:11px;font-weight:600;flex:none">i</span>
        <div style="flex:1;line-height:1.55;color:#C9C9D1;text-wrap:pretty">{{ $tip }}</div>
      </div>
    @endif
    @if ($errors->any())<div class="toast bad" style="position:static;max-width:none;box-shadow:none">! {{ $errors->first() }}</div>@endif
    @yield('content')
  </div>
  </div>
</main>
@yield('overlays')
</div>
@endsection
