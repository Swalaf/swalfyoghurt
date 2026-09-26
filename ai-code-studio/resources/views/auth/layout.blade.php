@extends('layouts.base')

@php
  $G = '#58C98A'; $V = '#A99BFF';
  $sideSteps = [['✓', 'Planned 18 pages and 11 tables', $G], ['✓', 'Created authentication & roles', $G], ['●', 'Building admin dashboard', $V], ['○', 'Check every page', '#44444C'], ['○', 'Publish to logistix.app', '#44444C']];
@endphp

@section('body')
<div style="min-height:100vh;display:flex;background:#0A0A0C;font-size:14px">
  <div style="flex:1;min-width:0;display:flex;flex-direction:column;padding:28px 32px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:9px;color:#F2F2F5">@include('partials.logo')<span style="font-weight:600">{{ $brand }}</span></a>
      @auth
        <form method="POST" action="{{ route('logout') }}" class="inline"><button style="background:none;border:none;color:#6E6E79;font-size:12.5px;cursor:pointer">{{ __('Sign out') }}</button></form>
      @endauth
    </div>

    <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:40px 0">
      <div style="width:min(380px,100%);display:flex;flex-direction:column;gap:18px">
        @yield('screen')
      </div>
    </div>
    <div style="font-size:12px;color:#55555F;display:flex;gap:16px;flex-wrap:wrap;align-items:center"><span>© {{ date('Y') }} {{ \App\Support\Settings::get('company_name') }}</span><a href="{{ route('terms') }}" style="color:#6E6E79">{{ __('Terms') }}</a><a href="{{ route('privacy') }}" style="color:#6E6E79">{{ __('Privacy') }}</a>@if ($s = \App\Support\Settings::get('support_email'))<a href="mailto:{{ $s }}" style="color:#6E6E79">{{ __('Support') }}</a>@endif @include('partials.language-switcher')</div>
  </div>

  <div data-r="side" style="width:46%;max-width:720px;flex:none;border-left:1px solid #1C1C22;background:#0D0D10;display:flex;flex-direction:column;justify-content:center;padding:48px;gap:28px">
    <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#A99BFF">{{ __('IDEATE → PLAN → CODE → TEST → PREVIEW → DEPLOY') }}</div>
    <h2 style="margin:0;font-size:clamp(28px,3vw,40px);letter-spacing:-0.03em;font-weight:600;line-height:1.1;text-wrap:balance">{{ __('Describe it. Approve the plan. Ship it.') }}</h2>
    <div style="border:1px solid #1F1F26;border-radius:12px;background:#0A0A0C;padding:16px;display:flex;flex-direction:column;gap:10px">
      <div style="font-size:12.5px;font-weight:600;display:flex;gap:7px"><span style="color:#A99BFF">✦</span>{{ __('AI Development Agent') }}</div>
      @foreach ($sideSteps as [$i, $t, $c])<div style="display:flex;gap:9px;font-size:13px;color:{{ $c === '#44444C' ? '#6E6E79' : '#D4D4DA' }}"><span style="font-family:'Geist Mono',ui-monospace,monospace;width:12px;color:{{ $c }}">{{ $i }}</span>{{ $t }}</div>@endforeach
    </div>
  </div>
</div>
@endsection
