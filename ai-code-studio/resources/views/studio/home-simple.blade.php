@extends('studio.layout')
@section('title', 'My apps')

@section('main')
<div data-screen-label="Home (Simple)" style="flex:1;overflow:auto;padding:40px 28px 60px">
  <div style="max-width:980px;margin:0 auto;display:flex;flex-direction:column;gap:36px">
    <form method="GET" action="{{ route('studio.new') }}" x-data="{ idea: '' }" style="display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px">
      <h1 style="margin:0;font-size:clamp(26px,3.4vw,36px);font-weight:600;letter-spacing:-0.025em">What would you like to make, {{ auth()->user()->firstName() }}?</h1>
      <p style="margin:0;color:#9A9AA5;font-size:15px">{{ __('Describe it the way you’d explain it to a friend. No technical knowledge needed.') }}</p>
      <div style="width:min(720px,100%);border:1px solid #3A3160;background:#110F1A;border-radius:14px;padding:14px;display:flex;flex-direction:column;gap:12px;text-align:left;margin-top:6px">
        <textarea name="idea" x-model="idea" placeholder="{{ __('e.g. A website for my bakery where people can see the menu and order cakes for pickup') }}" style="background:transparent;border:none;outline:none;color:#F2F2F5;font-size:15px;line-height:1.5;resize:none;min-height:64px"></textarea>
        <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-wrap:wrap">
          <button class="btn primary" style="height:38px;padding:0 18px;border-radius:9px;font-size:14px">{{ __('Start building →') }}</button>
        </div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
        @foreach (['An online shop', 'A booking system for my salon', 'A website for my restaurant', 'A mobile app for my gym', 'A tool to track my invoices'] as $t)
          <span @click="idea = @js($t.' — ')" class="hv-violet" style="font-size:13px;padding:6px 12px;border-radius:16px;border:1px solid #26262E;color:#B4B4BE;cursor:pointer">{{ $t }}</span>
        @endforeach
      </div>
    </form>

    <div style="display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;justify-content:space-between;align-items:baseline"><h2 style="margin:0;font-size:16px;font-weight:600">{{ __('My apps') }}</h2><span style="font-size:13px;color:#8A8A94">{{ $projects->count() }} {{ \Illuminate\Support\Str::plural('app', $projects->count()) }}</span></div>
      @if ($projects->isEmpty())
        <div style="border:1px dashed #26262E;border-radius:12px;padding:32px;text-align:center;color:#8A8A94">{{ __('No apps yet. Describe one above and I’ll build it for you.') }}</div>
      @else
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
        @foreach ($projects as $p)
          @php([$label, $sc, $sb] = $p->statusBadge())
          <a href="{{ $p->status === 'planned' ? route('studio.projects.plan', $p) : route('studio.projects.builder', $p) }}" class="hv-violet2" style="border:1px solid #1C1C22;border-radius:12px;background:#0E0E11;overflow:hidden;display:flex;flex-direction:column;color:inherit">
            <div style="height:120px;background:repeating-linear-gradient(135deg,#131317 0 7px,#16161B 7px 14px);display:grid;place-items:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#55555F">{{ $p->kind }} preview</div>
            <div style="padding:14px;display:flex;flex-direction:column;gap:8px">
              <div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><span style="font-weight:600;font-size:14.5px">{{ $p->name }}</span><span style="font-size:12px;padding:3px 9px;border-radius:10px;white-space:nowrap;background:{{ $sb }};color:{{ $sc }}">{{ $p->status === 'planned' ? 'Plan ready' : $label }}</span></div>
              <div style="font-size:13px;color:#8A8A94;line-height:1.45">
                @if ($p->status === 'building') AI is building it · {{ $p->progress }}% done
                @elseif ($p->status === 'failed') Something stopped working — open it and ask the AI to fix it
                @elseif ($live = $p->deployments->first()) {{ \App\Jobs\DeployProject::url($live->subdomain) }}
                @elseif ($p->status === 'planned') Review the plan, then start the build
                @else Ready to try — open it to see the preview
                @endif
              </div>
              <div style="font-size:12px;color:#6E6E79">Edited {{ $p->updated_at->diffForHumans() }}</div>
            </div>
          </a>
        @endforeach
      </div>
      @endif
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
      @foreach ([['✦', 'Describe, then approve', 'I plan your app first. Nothing is built until you say “build it”.', route('studio.new')], ['↶', 'Nothing is permanent', 'Every change the AI makes can be undone from the builder.', null], ['?', 'Talk to a person', 'Stuck? Our support team is happy to help.', ($s = \App\Support\Settings::get('support_email')) ? 'mailto:'.$s : null]] as [$i, $t, $d, $u])
        <a @if ($u) href="{{ $u }}" @endif class="hv-card" style="border:1px solid #1C1C22;border-radius:12px;background:#0E0E11;padding:16px;display:flex;flex-direction:column;gap:6px;color:inherit"><span style="color:#A99BFF;font-size:15px">{{ $i }}</span><span style="font-weight:600">{{ $t }}</span><span style="font-size:13px;color:#8A8A94;line-height:1.5">{{ $d }}</span></a>
      @endforeach
    </div>
  </div>
</div>
@endsection
