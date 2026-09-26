@extends('auth.layout')
@section('title', 'Page not found')
@section('screen')
<div style="display:flex;flex-direction:column;gap:16px">
  <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:64px;font-weight:500;letter-spacing:-0.04em;color:#2E2E38">404</div>
  <div><h1 style="margin:0;font-size:24px;font-weight:600">This page doesn’t exist</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">The link may be broken, or the project may have been moved or deleted.</p></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap"><a href="{{ auth()->check() ? route('studio.dashboard') : route('home') }}" class="btn primary" style="height:38px;padding:0 16px">{{ auth()->check() ? 'Go to dashboard' : 'Go home' }}</a>@if ($s = \App\Support\Settings::get('support_email'))<a href="mailto:{{ $s }}" class="btn ghost" style="height:38px;padding:0 16px">Contact support</a>@endif</div>
</div>
@endsection
