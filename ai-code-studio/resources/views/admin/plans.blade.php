@extends('admin.layout', ['page' => 'plans'])

@section('cta')
<a href="{{ route('admin.plans', ['edit' => 'new']) }}" class="btn primary">+ Create plan</a>
@endsection

@section('content')
@php($G = \App\Http\Controllers\Admin\PlanController::GATEWAYS)
<div data-screen-label="Plans" style="display:flex;flex-direction:column;gap:20px">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:14px">
    @foreach ($plans as $p)
      <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:18px;display:flex;flex-direction:column;gap:14px">
        <div style="display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600;font-size:15px">{{ $p->name }}</span><span style="font-size:11.5px;padding:2px 8px;border-radius:10px;background:{{ $p->status === 'live' ? '#15261C' : '#1C1C22' }};color:{{ $p->status === 'live' ? '#58C98A' : '#6E6E79' }}">{{ ucfirst($p->status) }}</span></div>
        <div><span style="font-size:28px;font-weight:600;letter-spacing:-0.02em">{{ $p->priceLabel() }}</span>@unless ($p->isCustom())<span style="color:#6E6E79"> / month</span>@endunless</div>
        <div style="display:flex;flex-direction:column;gap:8px;border-top:1px solid #1C1C22;padding-top:12px">
          @foreach ([['AI credits / month', $p->limit($p->credits, 'Custom')], ['Projects', $p->limit($p->max_projects)], ['Team seats', $p->limit($p->seats)], ['Support', $p->support]] as [$k, $v])
            <div style="display:flex;justify-content:space-between;font-size:13px"><span style="color:#8A8A94">{{ $k }}</span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px">{{ $v }}</span></div>
          @endforeach
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:#6E6E79;border-top:1px solid #1C1C22;padding-top:12px"><span>{{ $p->users_count }} customers</span><a href="{{ route('admin.plans', ['edit' => $p->id]) }}">Edit</a></div>
      </div>
    @endforeach
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
    <div style="padding:14px 18px;border-bottom:1px solid #1C1C22"><div style="font-weight:600">Payment gateways</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">Where your customers’ payments go. Customers see a “Pay with …” button for each connected gateway. Prices are charged in {{ \App\Services\Billing\Billing::currency() }} (set during install).</div></div>
    @foreach ($G as $key => [$ini, $name, $d, $docs])
      @php($on = ! empty($gateways[$key]['secret']))
      <div x-data="{ open: false }" style="border-bottom:1px solid #16161B">
        <div style="display:flex;align-items:center;gap:14px;padding:12px 18px;flex-wrap:wrap"><span style="width:34px;height:34px;border-radius:8px;background:#17171C;display:grid;place-items:center;font-weight:600;font-size:13px;flex:none">{{ $ini }}</span><div style="flex:1;min-width:180px"><div style="font-weight:500">{{ $name }}</div><div style="font-size:12px;color:#6E6E79">{{ $d }}</div></div><span style="font-size:12px;color:{{ $on ? '#58C98A' : '#6E6E79' }}">{{ $on ? 'Connected' : 'Not connected' }}</span><button @click="open = !open" class="btn sm">{{ $on ? 'Settings' : 'Connect' }}</button></div>
        <form class="d-grid" x-show="open" x-cloak method="POST" action="{{ route('admin.gateways.update', $key) }}" style="padding:0 18px 14px 66px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;align-items:end">@csrf
          <label class="lbl"><span>{{ $key === 'stripe' ? 'Publishable key' : 'Public key' }}</span><input class="inp sm mono" name="public" value="{{ $gateways[$key]['public'] ?? '' }}"></label>
          <label class="lbl"><span>Secret key</span><input class="inp sm mono" type="password" name="secret" placeholder="{{ $on ? '•••••••• (saved)' : '' }}" autocomplete="off"></label>
          @if ($key === 'stripe')<label class="lbl"><span>Webhook signing secret</span><input class="inp sm mono" type="password" name="webhook" placeholder="{{ ! empty($gateways[$key]['webhook']) ? '•••••••• (saved)' : 'whsec_…' }}" autocomplete="off"></label>@endif
          <div style="grid-column:1/-1;font-size:12px;color:#6E6E79;line-height:1.5">Webhook URL: <span class="mono" style="color:#C9C9D1">{{ route('webhooks', $key) }}</span> — {{ $key === 'stripe' ? 'add it in Stripe → Developers → Webhooks for the “checkout.session.completed” event.' : 'paste it in Paystack → Settings → API Keys & Webhooks.' }} Payments are also confirmed when customers return from checkout; the webhook is a safety net.</div>
          <div style="display:flex;gap:6px"><button class="btn primary sm" style="height:34px">Save</button>@if ($on)<button name="disconnect" value="1" class="btn danger sm" style="height:34px">Disconnect</button>@endif<a href="{{ $docs }}" target="_blank" rel="noopener" class="btn ghost sm" style="height:34px">Get keys ↗</a></div>
        </form>
      </div>
    @endforeach
  </div>
</div>
@endsection

@section('overlays')
@if ($editing || request('edit') === 'new')
@php($p = $editing)
<div style="position:fixed;inset:0;z-index:40;display:grid;place-items:center;padding:20px">
  <a href="{{ route('admin.plans') }}" style="position:absolute;inset:0;background:rgba(5,5,7,0.6)"></a>
  <form method="POST" action="{{ $p ? route('admin.plans.update', $p) : route('admin.plans.store') }}" style="position:relative;width:min(520px,100%);max-height:90vh;overflow:auto;background:#0E0E11;border:1px solid #26262E;border-radius:12px">
    @csrf @if ($p) @method('PUT') @endif
    <div style="padding:16px 20px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between"><div><div style="font-weight:600;font-size:15px">{{ $p ? 'Edit '.$p->name : 'Create a plan' }}</div><div style="font-size:12.5px;color:#8A8A94;margin-top:3px">Customers pick a plan when they sign up or upgrade.</div></div><a href="{{ route('admin.plans') }}" style="color:#8A8A94;font-size:18px">×</a></div>
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
      @foreach ([['name', 'Plan name', $p?->name ?? 'Pro', 'What customers see, e.g. “Pro”.'], ['price', 'Monthly price ('.\App\Services\Billing\Billing::currency().')', $p ? ($p->isCustom() ? '' : $p->price_cents / 100) : '29', 'Use 0 for a free plan. Leave empty for “Custom” pricing.'], ['credits', 'AI credits per month', $p?->credits ?? '12000', 'Roughly 1 credit ≈ 1,000 AI tokens.'], ['max_projects', 'Max projects', $p ? $p->max_projects : '10', 'Leave empty for unlimited.'], ['seats', 'Team seats', $p?->seats ?? '1', 'How many people can share the account.'], ['support', 'Support level', $p?->support ?? 'Email', 'e.g. Community, Email, Priority.'], ['description', 'Short description', $p?->description ?? '', 'One line shown on the pricing page.']] as [$k, $l, $v, $h])
        <label class="lbl"><span>{{ $l }}</span><input class="inp sm" name="{{ $k }}" value="{{ old($k, $v) }}" style="height:36px"><span class="hint">{{ $h }}</span></label>
      @endforeach
      <label class="lbl"><span>Visibility</span><select class="inp sm" name="status" style="height:36px"><option value="live" @selected(($p?->status ?? 'live') === 'live')>Live — shown on the pricing page</option><option value="hidden" @selected($p?->status === 'hidden')>Hidden — assign manually</option></select></label>
    </div>
    <div style="padding:14px 20px;border-top:1px solid #1C1C22;display:flex;justify-content:space-between;gap:8px">
      <span>@if ($p)<button form="del-plan" class="btn danger">Delete</button>@endif</span>
      <span style="display:flex;gap:8px"><a href="{{ route('admin.plans') }}" class="btn">Cancel</a><button class="btn primary">{{ $p ? 'Save plan' : 'Create plan' }}</button></span>
    </div>
  </form>
  @if ($p)<form id="del-plan" method="POST" action="{{ route('admin.plans.destroy', $p) }}" onsubmit="return confirm('Delete this plan?')">@csrf @method('DELETE')</form>@endif
</div>
@endif
@endsection
