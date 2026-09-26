@extends('auth.layout')
@section('title', __('Buy a licence'))
@section('screen')
@php($B = \App\Services\Billing\Billing::class)
<form method="POST" action="{{ route('license.checkout') }}" x-data="{ type: @js(old('type', 'regular')) }" style="display:flex;flex-direction:column;gap:16px">
  @csrf <input type="hidden" name="type" :value="type">
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">{{ __('Get your own :brand', ['brand' => $brand]) }}</h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.5">{{ __('Run the whole platform on your server, under your brand.') }}</p></div>
  @foreach (['regular' => [__('Regular licence'), __('One production site. You or your clients use it; you don’t charge end users.')], 'extended' => [__('Extended licence'), __('One production site where you charge your users (SaaS).')]] as $k => [$t, $d])
    <div @click="type = '{{ $k }}'" :style="{ borderColor: type === '{{ $k }}' ? 'var(--accent-line)' : '#1F1F26', background: type === '{{ $k }}' ? '#110F1A' : '#0D0D10' }" style="border:1px solid;border-radius:10px;padding:13px 14px;cursor:pointer;display:flex;gap:12px;justify-content:space-between">
      <div><div style="font-weight:600">{{ $t }}</div><div style="color:#8A8A94;font-size:13px;margin-top:3px;line-height:1.45">{{ $d }}</div></div>
      <div style="font-weight:600;font-size:18px;white-space:nowrap">{{ $B::format($prices[$k]) }}</div>
    </div>
  @endforeach
  <div style="font-size:12.5px;color:#8A8A94;line-height:1.5">{{ __('Includes 12 months of updates and support. Local and staging domains are free.') }}</div>
  <label class="lbl"><span>{{ __('Your name or company') }}</span><input class="inp" name="name" value="{{ old('name') }}" required></label>
  <label class="lbl"><span>{{ __('Email for your licence key') }}</span><input class="inp" type="email" name="email" value="{{ old('email') }}" required></label>
  <label style="display:flex;gap:8px;align-items:flex-start;color:#9A9AA5;font-size:13px;line-height:1.45"><input type="checkbox" name="agree" value="1" style="accent-color:var(--accent);margin-top:3px" required>{{ __('I agree to the licence agreement and') }} <a href="{{ route('terms') }}" target="_blank">{{ __('Terms') }}</a>.</label>
  @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
  @forelse ($gateways as $key => $label)
    <button name="gateway" value="{{ $key }}" class="btn {{ $loop->first ? 'primary' : '' }} lg">{{ __('Pay with :g', ['g' => $label]) }}</button>
  @empty
    <div class="err">{{ __('Payments aren’t set up yet.') }}</div>
  @endforelse
</form>
@endsection
