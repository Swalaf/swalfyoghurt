@extends('admin.layout', ['page' => 'licenses'])

@section('content')
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:16px;align-items:start">
  <form method="POST" action="{{ route('admin.licenses.store') }}" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;flex-direction:column;gap:12px">@csrf
    <div style="font-weight:600">Issue a licence</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <label class="lbl"><span>Type</span><select class="inp sm" name="type"><option value="regular">Regular</option><option value="extended">Extended</option></select></label>
      <label class="lbl"><span>Support (months)</span><input class="inp sm" type="number" name="months" value="12"></label>
      <label class="lbl"><span>Name</span><input class="inp sm" name="name"></label>
      <label class="lbl"><span>Email</span><input class="inp sm" type="email" name="email"></label>
    </div>
    <button class="btn primary sm" style="align-self:flex-start;height:32px">Issue key</button>
  </form>
  <form method="POST" action="{{ route('admin.storefront') }}" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;flex-direction:column;gap:12px">@csrf
    <div style="font-weight:600">Storefront <a href="{{ route('license.buy') }}" target="_blank" style="font-weight:400;font-size:12.5px">/buy ↗</a></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <label class="lbl"><span>Regular price ({{ \App\Services\Billing\Billing::currency() }})</span><input class="inp sm" name="license_price_regular" value="{{ $settings['license_price_regular'] }}"></label>
      <label class="lbl"><span>Extended price</span><input class="inp sm" name="license_price_extended" value="{{ $settings['license_price_extended'] }}"></label>
    </div>
    <label class="lbl"><span>Envato API token (optional)</span><input class="inp sm mono" type="password" name="envato_token" placeholder="{{ $settings['envato_token'] ? '•••••••• (saved)' : 'Personal token with “View your items’ sales history”' }}"><span class="hint">Lets CodeCanyon buyers activate with their purchase code.</span></label>
    <button class="btn sm" style="align-self:flex-start;height:32px">Save</button>
  </form>
  <form method="POST" action="{{ route('admin.releases.store') }}" enctype="multipart/form-data" style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:16px 18px;display:flex;flex-direction:column;gap:12px">@csrf
    <div style="font-weight:600">Publish a release</div>
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px">
      <label class="lbl"><span>Version</span><input class="inp sm mono" name="version" placeholder="{{ config('studio.version') }}"></label>
      <label class="lbl"><span>Zip (from <span class="mono">php artisan studio:package</span>)</span><input class="inp sm" type="file" name="file" accept=".zip" style="padding-top:6px"></label>
    </div>
    <label class="lbl"><span>What’s new</span><textarea class="inp" name="notes" style="height:auto;min-height:70px;padding:8px 10px"></textarea></label>
    <button class="btn sm" style="align-self:flex-start;height:32px">Publish</button>
  </form>
</div>

<div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11">
  <div style="padding:12px 18px;border-bottom:1px solid #1C1C22;font-weight:600">Releases</div>
  @forelse ($releases as $r)
    <div style="display:flex;gap:12px;padding:9px 18px;border-bottom:1px solid #16161B;flex-wrap:wrap"><span class="mono">v{{ $r->version }}</span><span style="color:#8A8A94;flex:1;min-width:200px">{{ \Illuminate\Support\Str::limit($r->notes, 120) }}</span><span style="color:#6E6E79;font-size:12px">{{ number_format($r->size / 1048576, 1) }} MB · {{ $r->created_at->toFormattedDateString() }}</span></div>
  @empty
    <div style="padding:14px 18px;color:#6E6E79">No releases yet. Build one with <span class="mono">php artisan studio:package</span> and upload it above.</div>
  @endforelse
</div>

<form method="GET" style="display:flex;gap:8px"><input class="inp sm" name="q" value="{{ request('q') }}" placeholder="Search key, email, domain…" style="max-width:360px;height:34px"></form>
<div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;overflow:auto">
  <div style="min-width:900px">
    <div style="display:grid;grid-template-columns:1.6fr 0.7fr 1.3fr 1.3fr 1fr 0.8fr 1.4fr;gap:10px;padding:10px 16px;border-bottom:1px solid #1C1C22;font-size:11.5px;color:#6E6E79"><span>KEY</span><span>TYPE</span><span>LICENSEE</span><span>DOMAIN</span><span>SUPPORT</span><span>STATUS</span><span></span></div>
    @forelse ($licenses as $l)
      <div style="display:grid;grid-template-columns:1.6fr 0.7fr 1.3fr 1.3fr 1fr 0.8fr 1.4fr;gap:10px;padding:9px 16px;border-bottom:1px solid #16161B;align-items:center;font-size:12.5px">
        <span class="mono" style="font-size:12px">{{ $l->key }}<br><span style="color:#6E6E79">{{ $l->source }}{{ $l->last_version ? ' · v'.$l->last_version : '' }}</span></span>
        <span>{{ ucfirst($l->type) }}</span>
        <span style="overflow:hidden;text-overflow:ellipsis">{{ $l->name }}<br><span style="color:#6E6E79">{{ $l->email }}</span></span>
        <span class="mono" style="font-size:12px">{{ $l->domain ?? '—' }}<br><span style="color:#6E6E79">{{ $l->last_check_at?->diffForHumans() ?? 'never checked' }}</span></span>
        <span style="color:{{ $l->supportActive() ? '#58C98A' : '#E8B66B' }}">{{ $l->supported_until?->toFormattedDateString() ?? 'Lifetime' }}</span>
        <span style="color:{{ $l->isActive() ? '#58C98A' : '#E5695E' }}">{{ ucfirst($l->status) }}</span>
        <span style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end">
          @foreach (($l->isActive() ? ['reset' => 'Reset domain', 'extend' => '+12 mo', 'revoke' => 'Revoke'] : ['reactivate' => 'Reactivate']) as $a => $label)
            <form method="POST" action="{{ route('admin.licenses.update', $l) }}" class="inline" @if ($a === 'revoke') onsubmit="return confirm('Revoke {{ $l->key }}?')" @endif>@csrf<input type="hidden" name="action" value="{{ $a }}"><button class="btn xs {{ $a === 'revoke' ? 'danger' : '' }}">{{ $label }}</button></form>
          @endforeach
        </span>
      </div>
    @empty
      <div style="padding:30px;text-align:center;color:#8A8A94">No licences yet.</div>
    @endforelse
  </div>
</div>
@if ($licenses->hasPages())<div style="display:flex;gap:6px;justify-content:flex-end">@if ($licenses->previousPageUrl())<a class="btn sm" href="{{ $licenses->previousPageUrl() }}">←</a>@endif @if ($licenses->nextPageUrl())<a class="btn sm" href="{{ $licenses->nextPageUrl() }}">→</a>@endif</div>@endif
@endsection
