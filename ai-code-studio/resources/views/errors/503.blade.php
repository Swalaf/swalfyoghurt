@extends('auth.layout')
@section('title', 'Scheduled maintenance')
@section('screen')
<div style="display:flex;flex-direction:column;gap:16px">
  <span style="align-self:flex-start;font-size:12px;padding:4px 10px;border-radius:12px;background:#2A2214;color:#E8B66B">● {{ ($preview ?? false) ? 'Preview · ' : '' }}Scheduled maintenance</span>
  <div><h1 style="margin:0;font-size:24px;font-weight:600">We’re upgrading the platform</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ $message ?? \App\Support\Settings::get('maintenance_message') }}</p></div>
  <div style="height:4px;border-radius:2px;background:#1C1C22;overflow:hidden"><div style="width:64%;height:100%;border-radius:2px;background:#E8B66B"></div></div>
  <span style="font-size:13px;color:#6E6E79">Please check back in a few minutes.@if (!auth()->check()) Admins can still <a href="{{ route('login') }}">sign in</a>.@endif</span>
</div>
@endsection
