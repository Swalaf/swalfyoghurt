@extends('layouts.base')
@section('title', $title)
@push('head')
<style>
.legal h1{font-size:34px;letter-spacing:-0.03em;margin:0 0 18px}.legal h2{font-size:19px;margin:30px 0 8px}.legal p,.legal li{color:#B4B4BE;line-height:1.7}.legal a{color:#C7BDFF}.legal em{color:#6E6E79}
</style>
@endpush
@section('body')
<div style="min-height:100vh">
  <header style="border-bottom:1px solid #16161B"><div style="max-width:820px;margin:0 auto;padding:0 24px;height:60px;display:flex;align-items:center;gap:9px"><a href="{{ route('home') }}" style="display:flex;align-items:center;gap:9px;color:#F2F2F5">@include('partials.logo')<span style="font-weight:600">{{ $brand }}</span></a></div></header>
  <main class="legal" style="max-width:820px;margin:0 auto;padding:48px 24px 80px">{!! $html !!}</main>
  @include('partials.footer-links')
</div>
@endsection
