@extends('studio.layout')
@section('title', 'White Label')

@section('main')
<form method="POST" action="{{ route('admin.branding.save') }}" enctype="multipart/form-data" x-data="{ name: @js($settings['brand_name']), color: @js($settings['brand_color']) }" data-screen-label="White Label" style="flex:1;overflow:auto;padding:22px 28px 40px">
  @csrf <input type="hidden" name="brand_color" :value="color">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    <div><div style="font-size:11px;color:#6E6E79;letter-spacing:0.08em;margin-bottom:5px">ADMIN · WHITE-LABEL CONTROL CENTER</div><h1 style="margin:0;font-size:22px;font-weight:600;letter-spacing:-0.02em">Brand settings</h1></div>
    <div style="display:flex;gap:6px"><a href="{{ route('studio.brand') }}" class="btn sm hv-btn" style="height:32px;background:#121216;border-color:#1F1F26;color:#C9C9D1">Discard</a><button class="btn primary sm" style="height:32px">Save changes</button></div>
  </div>
  @if ($errors->any())<div class="err" style="margin-bottom:12px">{{ $errors->first() }}</div>@endif
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));gap:18px;align-items:start">
    <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11;display:flex;flex-direction:column">
      <div style="display:flex;gap:2px;padding:8px 10px;border-bottom:1px solid #1C1C22;flex-wrap:wrap;font-size:12px">
        <span style="padding:4px 9px;border-radius:4px;background:#1A1A20">Identity</span>
        @foreach ([['Login & signup', 'Sign-up & login'], ['Email', 'Email'], ['Home page', 'General'], ['Maintenance', 'Maintenance']] as [$l, $sec])<a href="{{ route('admin.settings', $sec) }}" style="padding:4px 9px;color:#8A8A94">{{ $l }}</a>@endforeach
      </div>
      <div style="padding:16px;display:flex;flex-direction:column;gap:16px">
        <label class="lbl"><span style="font-size:12px">Application name</span><input name="brand_name" x-model="name" class="inp sm"></label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          @foreach ([['logo', 'Logo', 'Drop SVG / PNG'], ['favicon', 'Favicon', '32 × 32']] as [$k, $l, $ph])
            <label x-data="{ file: '' }" style="display:flex;flex-direction:column;gap:6px;cursor:pointer"><span style="font-size:12px;color:#9A9AA5">{{ $l }}</span><div style="height:64px;border:1px dashed #2A2A32;border-radius:6px;display:grid;place-items:center;font-size:12px;color:#6E6E79;overflow:hidden;text-align:center">@if (! empty($settings['brand_'.$k]))<img x-show="!file" src="{{ $settings['brand_'.$k] }}" style="max-height:46px;max-width:90%">@else<span x-show="!file">{{ $ph }}</span>@endif<span x-show="file" x-text="file" style="padding:0 6px;word-break:break-all"></span></div><input type="file" name="{{ $k }}" accept="image/*" hidden @change="file = $event.target.files[0]?.name || ''"></label>
          @endforeach
        </div>
        <div style="display:flex;flex-direction:column;gap:6px"><span style="font-size:12px;color:#9A9AA5">Primary color</span>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            @foreach (['#7C6CF0', '#2F6FEB', '#0F9D76', '#E0572F', '#7C3AED', '#18181B'] as $hex)<span @click="color = '{{ $hex }}'" :style="{ boxShadow: color === '{{ $hex }}' ? '0 0 0 2px #0E0E11, 0 0 0 4px #E4E4E9' : 'none' }" style="width:28px;height:28px;border-radius:6px;cursor:pointer;background:{{ $hex }}"></span>@endforeach
            <input type="color" x-model="color" style="width:28px;height:28px;border:none;background:none;padding:0;cursor:pointer">
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#8A8A94;margin-left:4px" x-text="color"></span>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <label class="lbl"><span style="font-size:12px">Custom domain</span><input name="brand_domain" value="{{ $settings['brand_domain'] }}" class="inp sm mono"></label>
          <label class="lbl"><span style="font-size:12px">Support email</span><input name="support_email" value="{{ $settings['support_email'] }}" class="inp sm"></label>
        </div>
        <div style="font-size:11.5px;color:#6E6E79;display:flex;gap:6px"><span style="color:{{ str_starts_with(config('app.url'), 'https') ? '#58C98A' : '#E8B66B' }}">●</span>{{ str_starts_with(config('app.url'), 'https') ? 'SSL active on '.parse_url(config('app.url'), PHP_URL_HOST) : 'Enable SSL with your host to serve over https' }}</div>
      </div>
    </div>
    <div style="border:1px solid #1C1C22;border-radius:9px;background:#0E0E11;overflow:hidden">
      <div style="padding:9px 12px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;font-size:12px"><span style="font-weight:600">Live preview · Login page</span><span style="font-family:'Geist Mono',ui-monospace,monospace;color:#6E6E79">{{ $settings['brand_domain'] ?: request()->getHost() }}/login</span></div>
      <div style="background:#F6F6F4;padding:36px 24px;display:grid;place-items:center;min-height:380px">
        <div style="width:min(300px,100%);background:#fff;border:1px solid #E6E6E2;border-radius:10px;padding:24px;display:flex;flex-direction:column;gap:12px;color:#18181B">
          <div style="display:flex;align-items:center;gap:8px"><span :style="{ background: color }" style="width:22px;height:22px;border-radius:5px"></span><span style="font-weight:600" x-text="name"></span></div>
          <div style="font-size:17px;font-weight:600;letter-spacing:-0.01em;margin-top:6px">Sign in to your workspace</div>
          <div style="height:34px;border:1px solid #E1E1DC;border-radius:6px;padding:0 10px;display:flex;align-items:center;font-size:12.5px;color:#8A8A90">you@company.com</div>
          <div style="height:34px;border:1px solid #E1E1DC;border-radius:6px;padding:0 10px;display:flex;align-items:center;font-size:12.5px;color:#8A8A90">••••••••</div>
          <div :style="{ background: color }" style="height:34px;border-radius:6px;display:grid;place-items:center;font-size:13px;font-weight:500;color:#fff">Continue</div>
          <div style="font-size:11.5px;color:#8A8A90;text-align:center">No account? <span :style="{ color }">Start building free</span></div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection
