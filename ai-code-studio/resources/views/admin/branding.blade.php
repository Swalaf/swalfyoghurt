@extends('admin.layout', ['page' => 'branding'])

@section('content')
<form method="POST" action="{{ route('admin.branding.save') }}" enctype="multipart/form-data" x-data="{ name: @js($settings['brand_name']), color: @js($settings['brand_color']) }" data-screen-label="Branding" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,380px),1fr));gap:18px;align-items:start">
  @csrf
  <input type="hidden" name="brand_color" :value="color">
  <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:18px;display:flex;flex-direction:column;gap:16px">
    <label class="lbl"><span>Platform name</span><input class="inp" name="brand_name" x-model="name" style="height:38px"><span class="hint">Shown in the browser tab, emails and on the login page.</span></label>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
      @foreach ([['logo', 'Logo', 'Drop logo here<br>SVG or PNG'], ['favicon', 'Browser tab icon', 'Favicon<br>32 × 32']] as [$k, $l, $ph])
        <label x-data="{ file: '' }" style="display:flex;flex-direction:column;gap:6px;cursor:pointer"><span style="font-size:12.5px;color:#9A9AA5">{{ $l }}</span>
          <div style="height:72px;border:1px dashed #2A2A32;border-radius:7px;display:grid;place-items:center;font-size:12.5px;color:#6E6E79;text-align:center;overflow:hidden">
            @if (! empty($settings['brand_'.$k]))<img x-show="!file" src="{{ $settings['brand_'.$k] }}" style="max-height:52px;max-width:90%">@else<span x-show="!file">{!! $ph !!}</span>@endif
            <span x-show="file" x-text="file" style="padding:0 8px;word-break:break-all"></span>
          </div>
          <input type="file" name="{{ $k }}" accept="image/*" hidden @change="file = $event.target.files[0]?.name || ''">
        </label>
      @endforeach
    </div>
    <div style="display:flex;flex-direction:column;gap:8px"><span style="font-size:12.5px;color:#9A9AA5">Brand color</span>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        @foreach (['#7C6CF0', '#2F6FEB', '#0F9D76', '#E0572F', '#7C3AED', '#DB2777', '#18181B'] as $hex)
          <span @click="color = '{{ $hex }}'" :style="{ boxShadow: color === '{{ $hex }}' ? '0 0 0 2px #0E0E11, 0 0 0 4px #E4E4E9' : 'none' }" style="width:32px;height:32px;border-radius:7px;cursor:pointer;background:{{ $hex }}"></span>
        @endforeach
        <input type="color" x-model="color" style="width:32px;height:32px;border:none;background:none;padding:0;cursor:pointer" title="Custom color">
        <span class="mono" style="font-size:12px;color:#8A8A94" x-text="color"></span>
      </div>
    </div>
    <label class="lbl"><span>Your web address</span><input class="inp mono" name="brand_domain" value="{{ $settings['brand_domain'] }}" placeholder="app.yourbrand.com" style="height:38px"><span style="font-size:12px;color:{{ str_starts_with(config('app.url'), 'https://') ? '#58C98A' : '#E8B66B' }}">● {{ str_starts_with(config('app.url'), 'https://') ? 'Secure (SSL) active on '.parse_url(config('app.url'), PHP_URL_HOST) : 'Not on https yet — ask your host to enable SSL' }}</span></label>
    <label class="lbl"><span>Support email</span><input class="inp" name="support_email" value="{{ $settings['support_email'] }}" placeholder="help@yourbrand.com" style="height:38px"></label>
    <div style="display:flex;flex-direction:column;gap:8px;border-top:1px solid #1C1C22;padding-top:14px"><span style="font-size:12.5px;color:#9A9AA5">Also customise</span>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        @foreach ([['Landing page headline', route('admin.settings', 'General')], ['Sign-up & login', route('admin.settings', 'Sign-up & login')], ['Email sender', route('admin.settings', 'Email')], ['Maintenance page', route('admin.settings', 'Maintenance')]] as [$b, $u])
          <a href="{{ $u }}" style="padding:6px 10px;border-radius:6px;border:1px solid #22222A;font-size:12.5px;color:#B4B4BE">{{ $b }} →</a>
        @endforeach
      </div>
    </div>
    <button class="btn primary" style="height:38px;font-size:13.5px">Save & publish</button>
  </div>
  <div style="border:1px solid #1C1C22;border-radius:11px;overflow:hidden">
    <div style="padding:10px 14px;border-bottom:1px solid #1C1C22;background:#0E0E11;display:flex;justify-content:space-between;font-size:12.5px"><span style="font-weight:600">Live preview</span><span class="mono" style="color:#6E6E79">{{ $settings['brand_domain'] ?: request()->getHost() }}/login</span></div>
    <div style="background:#F6F6F4;padding:40px 24px;display:grid;place-items:center;min-height:420px">
      <div style="width:min(320px,100%);background:#fff;border:1px solid #E6E6E2;border-radius:12px;padding:26px;display:flex;flex-direction:column;gap:12px;color:#18181B">
        <div style="display:flex;align-items:center;gap:8px"><span :style="{ background: color }" style="width:24px;height:24px;border-radius:6px"></span><span style="font-weight:600" x-text="name"></span></div>
        <div style="font-size:18px;font-weight:600;margin-top:6px">Welcome back</div>
        <div style="height:36px;border:1px solid #E1E1DC;border-radius:7px;padding:0 10px;display:flex;align-items:center;font-size:13px;color:#8A8A90">you@company.com</div>
        <div style="height:36px;border:1px solid #E1E1DC;border-radius:7px;padding:0 10px;display:flex;align-items:center;font-size:13px;color:#8A8A90">••••••••</div>
        <div :style="{ background: color }" style="height:38px;border-radius:7px;display:grid;place-items:center;font-size:13.5px;font-weight:500;color:#fff">Sign in</div>
      </div>
    </div>
  </div>
</form>
@endsection
