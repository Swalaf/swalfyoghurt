@extends('auth.layout')
@section('title', __('App removed'))
@section('screen')
<div style="display:flex;flex-direction:column;gap:16px">
  <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:64px;font-weight:500;letter-spacing:-0.04em;color:#2E2E38">451</div>
  <div><h1 style="margin:0;font-size:24px;font-weight:600">{{ __('This app has been removed') }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('It was taken down for breaking our terms of use.') }}@if (! empty($reason)) {{ __('Reason:') }} {{ $reason }}@endif</p></div>
  <div><a href="{{ route('terms') }}" class="btn" style="height:38px;padding:0 16px">{{ __('Terms of use') }}</a></div>
</div>
@endsection
