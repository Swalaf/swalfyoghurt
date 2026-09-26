@extends('layouts.base')

@php
  $V = '#A99BFF'; $G = '#58C98A'; $M = '#6E6E79';
  $mockTree = [['app', 0, '▤', '#5CC8E0'], ['dashboard', 1, '▤', '#5CC8E0'], ['page.tsx', 2, 'TS', '#6EA8F7'], ['kpi-card.tsx', 2, 'TS', '#6EA8F7'], ['backend', 0, '▤', '#5CC8E0'], ['routes/api.php', 1, 'PHP', $V], ['database', 0, '▤', '#5CC8E0'], ['package.json', 0, '{}', '#E8C27D']];
  $mockCode = [['export default async function Dashboard() {', '#D4D4DA'], ['  const stats = await getDashboardStats()', '#D4D4DA'], ['  return (', '#D4D4DA'], ['    <section className="grid grid-cols-4">', '#7DD3E8'], ['      <KpiCard label="Deliveries" />', '#9FD9A3', 1], ['      <KpiCard label="Active riders" />', '#9FD9A3', 1], ['      <KpiCard label="Wallet" />', '#9FD9A3', 1], ['    </section>', '#7DD3E8'], ['  )', '#D4D4DA'], ['}', '#D4D4DA']];
  $mockSteps = [['✓', 'Read project structure', $G], ['✓', 'Created KpiCard', $G], ['●', 'Building dashboard page', $V], ['○', 'Run tests', '#44444C'], ['○', 'Prepare preview', '#44444C']];
  $features = [
    ['✦', 'Agents that actually build', 'Plans, creates files, and checks its work — and shows every change for your approval.'],
    ['</>', 'A real IDE underneath', 'Multi-file editor, problems panel, change history and command palette. Take over any time.'],
    ['▣', 'Live preview & visual edits', 'See your app running as it’s built and describe changes in plain language.'],
    ['⇄', 'Plans you can edit', 'Review the features, pages, database and API the AI proposes before a single line is written.'],
    ['↑', 'One-click publish', 'Put your app on the internet with its own address — and roll back any time.'],
    ['◇', 'Bring your own AI', 'Connect the model providers you prefer. Route by quality, cost or speed, with automatic fallback.'],
  ];
  $how = [['01', 'Describe it', 'Write what you want in plain language — pages, users, payments, anything.'], ['02', 'Approve the plan', 'AI proposes architecture, database, APIs and a tech stack. Edit anything.'], ['03', 'Watch it build', 'Agents write and check the code while you review each change.'], ['04', 'Preview & publish', 'Try it live, fix with AI, then publish in one click.']];
  $buildables = ['Websites', 'Web apps', 'SaaS', 'Mobile apps', 'APIs', 'Backends', 'Admin dashboards', 'eCommerce', 'WordPress plugins', 'Laravel', 'Next.js', 'Vue', 'Flutter', 'React Native', 'Python', 'Node.js', 'Chrome extensions', 'CLI tools', 'Automation scripts', 'AI apps', 'Internal tools'];
  $faqs = [
    ['What can I build?', 'Websites, web apps, SaaS products, mobile apps, APIs, plugins, scripts and more — in any mainstream language or framework.'],
    ['Do I own the code?', 'Yes. Every project is a normal set of source files. Download it as a zip, push it to GitHub, or deploy it anywhere.'],
    ['What are AI credits?', 'Credits measure AI usage. Bigger tasks use more credits. You can see the cost of every run and top up any time.'],
    ['Do I need to know how to code?', 'No. Describe what you want and approve the plan. Developers can open the code editor whenever they like.'],
    ['Can I publish to my own domain?', 'Yes — every app gets its own address, and you can download the code to host it anywhere you like.'],
  ];
  $cardPlans = $plans->map(fn ($p) => [
    'name' => $p->name, 'desc' => $p->description, 'items' => $p->features ?? [], 'tag' => $p->tag,
    'm' => $p->isCustom() ? null : $p->price_cents / 100, 'cta' => $p->cta ?: 'Get started', 'hi' => $p->highlighted,
  ]);
@endphp

@section('body')
<div style="min-height:100vh;background:#0A0A0C;font-size:15px" x-data="{ idea: '', yearly: false, open: 0 }">

<header style="position:sticky;top:0;z-index:10;background:rgba(10,10,12,0.85);backdrop-filter:blur(10px);border-bottom:1px solid #16161B">
  <div style="max-width:1200px;margin:0 auto;padding:0 24px;height:60px;display:flex;align-items:center;gap:28px">
    <a href="{{ route('home') }}" style="display:flex;align-items:center;gap:9px;color:#F2F2F5">
      @include('partials.logo')
      <span style="font-weight:600;letter-spacing:-0.01em">{{ $brand }}</span>
    </a>
    <nav data-r="hidem" style="display:flex;gap:22px;font-size:14px;flex:1;flex-wrap:wrap">
      <a href="#features" style="color:#A5A5AF">Product</a><a href="#build" style="color:#A5A5AF">What you can build</a>@if ($showPricing)<a href="#pricing" style="color:#A5A5AF">Pricing</a>@endif<a href="#faq" style="color:#A5A5AF">FAQ</a>
    </nav>
    <div style="display:flex;gap:8px;align-items:center;margin-left:auto">
      @auth
        <a href="{{ route('studio.dashboard') }}" style="height:36px;padding:0 16px;border-radius:7px;background:var(--accent);color:#fff;font-size:14px;font-weight:500;display:flex;align-items:center">Open studio</a>
      @else
        <a href="{{ route('login') }}" style="font-size:14px;color:#D4D4DA;padding:0 10px">Sign in</a>
        <a href="{{ route('register') }}" class="btn primary" style="height:36px;padding:0 16px;font-size:14px">Start building</a>
      @endauth
    </div>
  </div>
</header>

<section style="max-width:1200px;margin:0 auto;padding:88px 24px 40px;display:flex;flex-direction:column;align-items:center;text-align:center">
  <div style="display:flex;align-items:center;gap:8px;padding:5px 12px;border:1px solid #26222F;border-radius:20px;font-size:12.5px;color:#B4A5FF;margin-bottom:26px;font-family:'Geist Mono',ui-monospace,monospace">IDEATE → PLAN → CODE → TEST → PREVIEW → DEPLOY</div>
  <h1 style="margin:0;font-size:clamp(40px,6.4vw,76px);line-height:1.02;letter-spacing:-0.035em;font-weight:600;max-width:900px;text-wrap:balance">{{ $headline }}</h1>
  <p style="margin:22px 0 0;font-size:18px;line-height:1.55;color:#9A9AA5;max-width:620px;text-wrap:pretty">Describe your idea. A team of AI agents plans it, writes the code, checks it and publishes it — while you keep full control of every file and change.</p>
  <form method="GET" action="{{ auth()->check() ? route('studio.new') : route('register') }}" style="margin-top:34px;width:min(680px,100%);border:1px solid #2A2540;background:#110F1A;border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:12px;text-align:left">
    <input name="idea" x-model="idea" placeholder="Describe what you want to build…" style="background:transparent;border:none;outline:none;color:#F2F2F5;font-size:16px;font-family:inherit;padding:4px 4px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        @foreach (['Logistics platform', 'Booking SaaS', 'Shopify-style store', 'Flutter app'] as $chip)
          <span @click="idea = 'Build a {{ strtolower($chip) }} with an admin dashboard and user accounts'" class="hv-violet" style="font-size:12.5px;padding:4px 10px;border-radius:14px;border:1px solid #2A2A32;color:#A5A5AF;cursor:pointer">{{ $chip }}</span>
        @endforeach
      </div>
      <button class="btn primary" style="height:36px;padding:0 16px;font-size:14px">Generate plan →</button>
    </div>
  </form>
  <div style="margin-top:14px;font-size:13px;color:#6E6E79">Free to start · No credit card · Export your code anytime</div>
</section>

<section style="max-width:1200px;margin:0 auto;padding:30px 24px 80px">
  <div style="border:1px solid #1F1F26;border-radius:14px;overflow:hidden;background:#0D0D10;box-shadow:0 40px 100px rgba(0,0,0,0.5)">
    <div style="height:36px;display:flex;align-items:center;gap:7px;padding:0 14px;border-bottom:1px solid #1C1C22"><span style="width:10px;height:10px;border-radius:50%;background:#2A2A32"></span><span style="width:10px;height:10px;border-radius:50%;background:#2A2A32"></span><span style="width:10px;height:10px;border-radius:50%;background:#2A2A32"></span><span style="flex:1;text-align:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#55555F">{{ request()->getHost() }}/studio/projects/logistix-platform</span></div>
    <div data-r="mockgrid" style="display:grid;grid-template-columns:minmax(0,180px) minmax(0,1fr) minmax(0,300px);min-height:380px">
      <div data-r="mockside" style="border-right:1px solid #1C1C22;padding:12px;display:flex;flex-direction:column;gap:7px">
        @foreach ($mockTree as [$n, $d, $i, $c])<div style="display:flex;gap:7px;align-items:center;font-size:12px;color:#8A8A94;padding-left:{{ $d * 12 }}px"><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:9.5px;color:{{ $c }}">{{ $i }}</span>{{ $n }}</div>@endforeach
      </div>
      <div style="padding:14px 0;font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px;line-height:22px;overflow:hidden">
        @foreach ($mockCode as $i => $l)<div style="display:flex;white-space:pre;background:{{ ($l[2] ?? 0) ? 'rgba(88,201,138,0.07)' : 'transparent' }}"><span style="width:40px;text-align:right;padding-right:14px;color:#3A3A44">{{ $i + 1 }}</span><span style="color:{{ $l[1] }}">{{ $l[0] }}</span></div>@endforeach
      </div>
      <div data-r="mockside" style="border-left:1px solid #1C1C22;padding:14px;display:flex;flex-direction:column;gap:10px;text-align:left">
        <div style="font-size:12.5px;font-weight:600;display:flex;gap:7px"><span style="color:#A99BFF">✦</span>AI Development Agent</div>
        @foreach ($mockSteps as [$i, $t, $c])<div style="display:flex;gap:8px;font-size:12.5px;color:{{ $c === '#44444C' ? $M : '#D4D4DA' }}"><span style="font-family:'Geist Mono',ui-monospace,monospace;width:12px;color:{{ $c }}">{{ $i }}</span>{{ $t }}</div>@endforeach
        <div style="margin-top:auto;display:flex;gap:6px"><span style="flex:1;height:28px;border-radius:6px;background:var(--accent);color:#fff;font-size:12px;display:grid;place-items:center">Approve</span><span style="height:28px;padding:0 10px;border-radius:6px;border:1px solid #2A2A32;font-size:12px;display:grid;place-items:center;color:#C9C9D1">Review</span></div>
      </div>
    </div>
  </div>
</section>

<section id="features" style="max-width:1200px;margin:0 auto;padding:60px 24px">
  <div style="max-width:620px;margin-bottom:40px">
    <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#A99BFF;margin-bottom:12px">PRODUCT</div>
    <h2 style="margin:0;font-size:clamp(30px,4vw,44px);letter-spacing:-0.03em;font-weight:600;line-height:1.1">An AI coding team, not a chatbot.</h2>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1px;background:#1C1C22;border:1px solid #1C1C22;border-radius:14px;overflow:hidden">
    @foreach ($features as [$i, $t, $d])
      <div style="background:#0D0D10;padding:26px;display:flex;flex-direction:column;gap:10px">
        <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:13px;color:#A99BFF">{{ $i }}</span>
        <div style="font-size:17px;font-weight:600;letter-spacing:-0.01em">{{ $t }}</div>
        <div style="color:#8A8A94;line-height:1.55;font-size:14.5px;text-wrap:pretty">{{ $d }}</div>
      </div>
    @endforeach
  </div>
</section>

<section style="max-width:1200px;margin:0 auto;padding:60px 24px">
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:40px;align-items:start">
    <div>
      <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#A99BFF;margin-bottom:12px">HOW IT WORKS</div>
      <h2 style="margin:0;font-size:clamp(30px,4vw,44px);letter-spacing:-0.03em;font-weight:600;line-height:1.1">From one sentence to a deployed product.</h2>
    </div>
    <div style="display:flex;flex-direction:column">
      @foreach ($how as [$n, $t, $d])
        <div style="display:grid;grid-template-columns:48px 1fr;gap:16px;padding:20px 0;border-top:1px solid #1C1C22">
          <span style="font-family:'Geist Mono',ui-monospace,monospace;color:#6E6E79;font-size:13px">{{ $n }}</span>
          <div><div style="font-weight:600;font-size:17px">{{ $t }}</div><div style="color:#8A8A94;margin-top:6px;line-height:1.55">{{ $d }}</div></div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section id="build" style="max-width:1200px;margin:0 auto;padding:60px 24px">
  <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#A99BFF;margin-bottom:12px">WHAT YOU CAN BUILD</div>
  <h2 style="margin:0 0 28px;font-size:clamp(30px,4vw,44px);letter-spacing:-0.03em;font-weight:600;line-height:1.1;max-width:700px">Any language. Any framework. Any kind of software.</h2>
  <div style="display:flex;flex-wrap:wrap;gap:8px">
    @foreach ($buildables as $b)<span style="padding:8px 14px;border:1px solid #1F1F26;border-radius:8px;background:#0D0D10;font-size:14px;color:#C9C9D1">{{ $b }}</span>@endforeach
  </div>
</section>

@if ($showPricing && $cardPlans->isNotEmpty())
<section id="pricing" style="max-width:1200px;margin:0 auto;padding:60px 24px">
  <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:16px;margin-bottom:28px">
    <div><div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#A99BFF;margin-bottom:12px">PRICING</div><h2 style="margin:0;font-size:clamp(30px,4vw,44px);letter-spacing:-0.03em;font-weight:600">Start free. Scale when you ship.</h2></div>
    <div style="display:flex;border:1px solid #1F1F26;border-radius:8px;padding:3px;gap:2px">
      <span @click="yearly = false" :style="{ background: yearly ? 'transparent' : 'var(--accent-soft)', color: yearly ? '#8A8A94' : '#E4E4E9' }" style="padding:6px 14px;border-radius:6px;font-size:13.5px;cursor:pointer">Monthly</span>
      <span @click="yearly = true" :style="{ background: yearly ? 'var(--accent-soft)' : 'transparent', color: yearly ? '#E4E4E9' : '#8A8A94' }" style="padding:6px 14px;border-radius:6px;font-size:13.5px;cursor:pointer">Yearly · save 20%</span>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
    @foreach ($cardPlans as $p)
      <div style="border:1px solid {{ $p['hi'] ? 'var(--accent-line)' : '#1C1C22' }};background:{{ $p['hi'] ? '#110F1A' : '#0D0D10' }};border-radius:12px;padding:22px;display:flex;flex-direction:column;gap:16px">
        <div style="display:flex;justify-content:space-between;align-items:center"><span style="font-weight:600;font-size:16px">{{ $p['name'] }}</span><span style="font-size:11.5px;color:#C7BDFF;font-family:'Geist Mono',ui-monospace,monospace">{{ $p['tag'] }}</span></div>
        <div>
          @if ($p['m'] === null)
            <span style="font-size:36px;font-weight:600;letter-spacing:-0.03em">Custom</span>
          @else
            <span style="font-size:36px;font-weight:600;letter-spacing:-0.03em" x-text="'$' + (yearly ? Math.round({{ $p['m'] }} * 0.8) : {{ $p['m'] + 0 }}).toLocaleString()">${{ number_format($p['m']) }}</span>@if ($p['m'] > 0)<span style="color:#6E6E79;font-size:14px"> / month</span>@endif
          @endif
        </div>
        <div style="color:#8A8A94;font-size:14px;line-height:1.5">{{ $p['desc'] }}</div>
        <a href="{{ $p['m'] === null && ($support = \App\Support\Settings::get('support_email')) ? 'mailto:'.$support : route('register') }}" style="height:38px;border-radius:7px;display:grid;place-items:center;font-size:14px;font-weight:500;background:{{ $p['hi'] ? 'var(--accent)' : 'transparent' }};color:{{ $p['hi'] ? '#fff' : '#E4E4E9' }};border:1px solid {{ $p['hi'] ? 'var(--accent)' : '#2A2A32' }}">{{ $p['cta'] }}</a>
        <div style="display:flex;flex-direction:column;gap:9px;border-top:1px solid #1C1C22;padding-top:16px">
          @foreach ($p['items'] as $it)<div style="display:flex;gap:9px;font-size:14px;color:#C9C9D1"><span style="color:#58C98A">✓</span>{{ $it }}</div>@endforeach
        </div>
      </div>
    @endforeach
  </div>
</section>
@endif

<section id="faq" style="max-width:820px;margin:0 auto;padding:60px 24px">
  <h2 style="margin:0 0 24px;font-size:clamp(28px,3.6vw,38px);letter-spacing:-0.03em;font-weight:600">Questions</h2>
  @foreach ($faqs as $i => [$q, $a])
    <div style="border-top:1px solid #1C1C22">
      <div @click="open = open === {{ $i }} ? -1 : {{ $i }}" style="display:flex;justify-content:space-between;gap:16px;padding:18px 0;cursor:pointer;font-size:16px;font-weight:500"><span>{{ $q }}</span><span style="color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace" x-text="open === {{ $i }} ? '−' : '+'">{{ $i === 0 ? '−' : '+' }}</span></div>
      <div x-show="open === {{ $i }}" @if ($i) x-cloak @endif style="padding:0 0 20px;color:#9A9AA5;line-height:1.6;text-wrap:pretty">{{ $a }}</div>
    </div>
  @endforeach
</section>

<section style="max-width:1200px;margin:0 auto;padding:60px 24px 90px">
  <div style="border:1px solid #2A2540;background:#110F1A;border-radius:16px;padding:56px 32px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:18px">
    <h2 style="margin:0;font-size:clamp(30px,4.4vw,48px);letter-spacing:-0.03em;font-weight:600;line-height:1.08;text-wrap:balance">One idea → complete software.</h2>
    <p style="margin:0;color:#9A9AA5;font-size:17px">Your first project is free.</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center">
      <a href="{{ route('register') }}" class="btn primary lg">Start building free</a>
      <a href="{{ auth()->check() ? route('studio.dashboard') : route('login') }}" style="height:42px;padding:0 20px;border-radius:8px;border:1px solid #2A2A32;color:#E4E4E9;display:flex;align-items:center">See the studio</a>
    </div>
  </div>
</section>

<footer style="border-top:1px solid #16161B">
  <div style="max-width:1200px;margin:0 auto;padding:40px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:28px;font-size:14px">
    <div style="display:flex;flex-direction:column;gap:10px"><span style="font-weight:600">{{ $brand }}</span><span style="color:#6E6E79;font-size:13px">© {{ date('Y') }} {{ $company }}</span></div>
    <div style="display:flex;flex-direction:column;gap:9px"><span style="color:#6E6E79;font-size:12px;font-family:'Geist Mono',ui-monospace,monospace">PRODUCT</span><a href="#features" style="color:#A5A5AF">Features</a>@if ($showPricing)<a href="#pricing" style="color:#A5A5AF">Pricing</a>@endif<a href="#build" style="color:#A5A5AF">What you can build</a></div>
    <div style="display:flex;flex-direction:column;gap:9px"><span style="color:#6E6E79;font-size:12px;font-family:'Geist Mono',ui-monospace,monospace">ACCOUNT</span><a href="{{ route('login') }}" style="color:#A5A5AF">Sign in</a><a href="{{ route('register') }}" style="color:#A5A5AF">Create account</a></div>
    <div style="display:flex;flex-direction:column;gap:9px"><span style="color:#6E6E79;font-size:12px;font-family:'Geist Mono',ui-monospace,monospace">COMPANY</span>@if ($support = \App\Support\Settings::get('support_email'))<a href="mailto:{{ $support }}" style="color:#A5A5AF">Contact support</a>@endif<a href="#faq" style="color:#A5A5AF">FAQ</a></div>
  </div>
</footer>
</div>
@endsection
