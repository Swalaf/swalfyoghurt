@extends('studio.layout')
@section('title', __('Plans & billing'))

@section('main')
@php($B = \App\Services\Billing\Billing::class)
<div style="flex:1;overflow:auto;padding:28px 28px 60px" x-data="{ period: 'monthly' }">
  <div style="max-width:1080px;margin:0 auto;display:flex;flex-direction:column;gap:20px">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap">
      <div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">{{ __('Plans & billing') }}</h1>
        <div style="color:#8A8A94;margin-top:5px">
          {{ __('You’re on :plan', ['plan' => $user->plan?->name ?? __('no plan')]) }}@if ($user->plan_expires_at) · {{ __('paid until :date', ['date' => $user->plan_expires_at->toFormattedDateString()]) }}@endif
          · {{ $user->is_admin ? __('unlimited credits') : __(':n credits left', ['n' => number_format($user->credits)]) }}
          @if ($user->credits_reset_at && ! $user->is_admin) · {{ __('refills :date', ['date' => $user->credits_reset_at->toFormattedDateString()]) }}@endif
        </div>
      </div>
      <div style="display:flex;border:1px solid #1F1F26;border-radius:8px;padding:3px;gap:2px">
        <span @click="period = 'monthly'" :style="{ background: period === 'monthly' ? 'var(--accent-soft)' : 'transparent', color: period === 'monthly' ? '#E4E4E9' : '#8A8A94' }" style="padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer">{{ __('Monthly') }}</span>
        <span @click="period = 'yearly'" :style="{ background: period === 'yearly' ? 'var(--accent-soft)' : 'transparent', color: period === 'yearly' ? '#E4E4E9' : '#8A8A94' }" style="padding:6px 14px;border-radius:6px;font-size:13px;cursor:pointer">{{ __('Yearly · save 20%') }}</span>
      </div>
    </div>

    @if (! $gateways)
      <div style="padding:12px 14px;border:1px solid #3A2F1A;background:#1A150C;border-radius:9px;color:#E8C99A;line-height:1.5">{{ __('Online payments aren’t set up on this platform yet.') }} @if ($s = \App\Support\Settings::get('support_email')){{ __('Contact') }} <a href="mailto:{{ $s }}">{{ $s }}</a> {{ __('to upgrade.') }}@endif</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:14px">
      @foreach ($plans as $p)
        @php($current = $user->plan_id === $p->id)
        <div style="border:1px solid {{ $p->highlighted ? 'var(--accent-line)' : '#1C1C22' }};background:{{ $p->highlighted ? '#110F1A' : '#0E0E11' }};border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:14px">
          <div style="display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600;font-size:15px">{{ $p->name }}</span>@if ($current)<span style="font-size:11px;padding:2px 8px;border-radius:10px;background:#15261C;color:#58C98A">{{ __('Current') }}</span>@elseif ($p->tag)<span class="mono" style="font-size:11px;color:#C7BDFF">{{ $p->tag }}</span>@endif</div>
          <div>
            @if ($p->isCustom())
              <span style="font-size:30px;font-weight:600">{{ __('Custom') }}</span>
            @else
              <span style="font-size:30px;font-weight:600;letter-spacing:-0.02em" x-text="period === 'yearly' ? @js($B::format($B::amountFor($p, 'yearly'))) : @js($B::format($B::amountFor($p, 'monthly')))">{{ $B::format($B::amountFor($p, 'monthly')) }}</span>
              @if ($p->price_cents > 0)<span style="color:#6E6E79" x-text="period === 'yearly' ? ' / {{ __('year') }}' : ' / {{ __('month') }}'"> / {{ __('month') }}</span>@endif
            @endif
          </div>
          <div style="display:flex;flex-direction:column;gap:7px;font-size:13px;color:#C9C9D1">@foreach ($p->features ?? [] as $f)<div style="display:flex;gap:8px"><span style="color:#58C98A">✓</span>{{ $f }}</div>@endforeach</div>
          <div style="margin-top:auto;display:flex;flex-direction:column;gap:6px">
            @if ($p->isCustom())
              @if ($s = \App\Support\Settings::get('support_email'))<a href="mailto:{{ $s }}?subject={{ rawurlencode($p->name) }}" class="btn">{{ __('Contact sales') }}</a>@endif
            @elseif ($p->price_cents === 0)
              <span class="btn ghost" style="opacity:.6;cursor:default">{{ $current ? __('Your plan') : __('Free') }}</span>
            @else
              @forelse ($gateways as $key => $label)
                <form method="POST" action="{{ route('studio.billing.checkout') }}">@csrf
                  <input type="hidden" name="plan_id" value="{{ $p->id }}"><input type="hidden" name="period" :value="period"><input type="hidden" name="gateway" value="{{ $key }}">
                  <button class="btn {{ $loop->first ? 'primary' : '' }}" style="width:100%">{{ $current ? __('Renew with :g', ['g' => $label]) : __('Pay with :g', ['g' => $label]) }}</button>
                </form>
              @empty
                <span class="btn ghost" style="opacity:.6;cursor:default">{{ __('Not available yet') }}</span>
              @endforelse
            @endif
          </div>
        </div>
      @endforeach
    </div>

    <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
      <div style="padding:14px 18px;border-bottom:1px solid #1C1C22;font-weight:600">{{ __('Payment history') }}</div>
      @forelse ($payments as $pay)
        <div style="display:flex;gap:12px;padding:10px 18px;border-bottom:1px solid #16161B;align-items:center;flex-wrap:wrap">
          <span style="width:7px;height:7px;border-radius:50%;background:{{ ['paid' => '#58C98A', 'failed' => '#E5695E'][$pay->status] ?? '#6E6E79' }}"></span>
          <span style="flex:1;min-width:160px">{{ $pay->plan?->name }} · {{ $pay->period === 'yearly' ? __('1 year') : __('1 month') }}</span>
          <span class="mono" style="font-size:12.5px">{{ $B::format($pay->amount_cents, $pay->currency) }}</span>
          <span style="color:#8A8A94;font-size:12.5px;min-width:90px">{{ ucfirst($pay->gateway) }} · {{ __(ucfirst($pay->status)) }}</span>
          <span style="color:#6E6E79;font-size:12px">{{ $pay->created_at->toFormattedDateString() }}</span>
        </div>
      @empty
        <div style="padding:14px 18px;color:#6E6E79">{{ __('No payments yet.') }}</div>
      @endforelse
    </div>
    <div style="font-size:12px;color:#6E6E79;line-height:1.5">{{ __('Payments are one-off for the period you choose — nothing renews automatically. Credits refill every month while your plan is active.') }} <a href="{{ route('terms') }}">{{ __('Terms') }}</a></div>
  </div>
</div>
@endsection
