@extends('auth.layout')
@section('title', 'Not allowed')
@section('screen')
<div style="display:flex;flex-direction:column;gap:16px">
  <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:64px;font-weight:500;letter-spacing:-0.04em;color:#2E2E38">403</div>
  <div><h1 style="margin:0;font-size:24px;font-weight:600">{{ __('You don’t have access to this') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ (isset($exception) && $exception->getMessage()) ? $exception->getMessage() : 'This area is for admins only.' }}</p></div>
  <div><a href="{{ auth()->check() ? route('studio.dashboard') : route('home') }}" class="btn primary" style="height:38px;padding:0 16px">{{ __('Go back') }}</a></div>
</div>
@endsection
