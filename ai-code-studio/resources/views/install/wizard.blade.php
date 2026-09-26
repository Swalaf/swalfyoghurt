@extends('layouts.base')
@section('title', 'Setup')

@section('body')
<div data-screen-label="Installer" x-data="installer()" x-init="init()" style="min-height:100vh;display:flex;background:#0A0A0C;font-size:14px">
  <aside data-r="rail" style="width:280px;flex:none;border-right:1px solid #1C1C22;background:#0D0D10;padding:28px 20px;display:flex;flex-direction:column;gap:24px">
    <div style="display:flex;align-items:center;gap:9px">@include('partials.logo')<div style="display:flex;flex-direction:column;line-height:1.2"><span style="font-weight:600">AI Code Studio</span><span style="font-size:11.5px;color:#6E6E79">Setup · v{{ config('studio.version') }}</span></div></div>
    <div style="display:flex;flex-direction:column;gap:2px">
      <template x-for="(t, i) in names" :key="i">
        <div @click="i < step && inst < 0 && (step = i)" :style="{ background: i === step ? '#17142A' : 'transparent', cursor: i < step && inst < 0 ? 'pointer' : 'default' }" style="display:flex;align-items:center;gap:10px;height:32px;padding:0 10px;border-radius:6px">
          <span :style="{ background: i < step ? 'var(--accent)' : 'transparent', color: i < step ? '#fff' : (i === step ? '#C7BDFF' : '#6E6E79'), borderColor: i <= step ? 'var(--accent)' : '#2A2A32' }" style="width:20px;height:20px;border-radius:50%;display:grid;place-items:center;font-size:10.5px;flex:none;border:1px solid" x-text="i < step ? '✓' : i + 1"></span>
          <span :style="{ color: i === step ? '#F2F2F5' : (i < step ? '#B4B4BE' : '#6E6E79') }" x-text="t"></span>
        </div>
      </template>
    </div>
    <div style="margin-top:auto;font-size:12.5px;color:#6E6E79;line-height:1.5">Stuck? Check the README that came with the download — it covers cPanel, VPS and local setups.</div>
  </aside>

  <main style="flex:1;min-width:0;display:flex;flex-direction:column;padding:40px 32px">
    <div style="width:min(620px,100%);margin:0 auto;display:flex;flex-direction:column;gap:22px;flex:1">
      <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#6E6E79">STEP <span x-text="step + 1"></span> OF 11</div>
      <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em" x-text="steps[step][0]"></h1><p style="margin:6px 0 0;color:#8A8A94;line-height:1.55;text-wrap:pretty" x-text="steps[step][1]"></p></div>

      {{-- Welcome --}}
      <div class="d-flex" x-show="step === 0" style="flex-direction:column;gap:10px">
        @foreach ([['◇', 'Bring your own AI', 'Connect the AI providers you choose and control costs.'], ['◐', 'Your brand everywhere', 'Name, logo, colors and domain — fully white-label.'], ['$', 'Your pricing', 'Create plans and charge your customers directly.']] as [$i, $t, $d])
          <div style="display:flex;gap:12px;padding:12px 14px;border:1px solid #1C1C22;border-radius:9px;background:#0E0E11"><span style="color:#A99BFF;font-family:'Geist Mono',ui-monospace,monospace">{{ $i }}</span><div><div style="font-weight:500">{{ $t }}</div><div style="font-size:13px;color:#8A8A94;margin-top:2px">{{ $d }}</div></div></div>
        @endforeach
        <label class="lbl" style="margin-top:6px"><span>Language</span><select class="inp"><option>English</option></select></label>
      </div>

      {{-- System check --}}
      <div x-show="step === 1" x-cloak style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;overflow:hidden">
        <template x-for="q in reqs">
          <div style="display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid #16161B;flex-wrap:wrap">
            <span :style="{ background: q.ok === 1 ? '#15261C' : q.ok === 2 ? '#2A2214' : '#2A1616', color: col(q.ok) }" style="width:18px;height:18px;border-radius:50%;display:grid;place-items:center;font-size:10.5px;flex:none" x-text="q.ok === 1 ? '✓' : q.ok === 2 ? '!' : '✕'"></span>
            <div style="flex:1;min-width:180px"><div x-text="q.name"></div><div x-show="q.ok !== 1" style="font-size:12.5px;color:#C9A56B;margin-top:3px;line-height:1.45" x-text="q.help"></div></div>
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#8A8A94" x-text="q.need"></span>
            <span :style="{ color: col(q.ok) }" style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;min-width:70px;text-align:right" x-text="q.have"></span>
          </div>
        </template>
        <div style="padding:12px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
          <span style="font-size:13px;color:#8A8A94" x-text="reqSummary()"></span>
          <span @click="recheck()" style="height:30px;padding:0 12px;border-radius:6px;border:1px solid #2A2A32;display:flex;align-items:center;font-size:12.5px;cursor:pointer" x-text="checking ? 'Checking…' : '⟳ Check again'"></span>
        </div>
      </div>

      {{-- Option cards + fields (steps 2–8) --}}
      <div class="d-flex" x-show="step >= 2 && step <= 8" x-cloak style="flex-direction:column;gap:14px">
        <div class="d-grid" x-show="opts().length" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px">
          <template x-for="o in opts()">
            <div @click="choose(o[0])" :style="{ borderColor: chosen() === o[0] ? 'var(--accent)' : '#1F1F26', background: chosen() === o[0] ? '#110F1A' : '#0E0E11' }" style="border:1px solid;border-radius:9px;padding:12px;cursor:pointer">
              <div style="font-weight:500;display:flex;justify-content:space-between;gap:6px"><span x-text="o[0]"></span><span style="font-size:10.5px;color:#C7BDFF" x-text="o[2] || ''"></span></div>
              <div style="font-size:12.5px;color:#8A8A94;margin-top:3px;line-height:1.4" x-text="o[1]"></div>
            </div>
          </template>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
          <template x-for="f in fields()" :key="f.k">
            <label class="lbl"><span x-text="f.l"></span>
              <input class="inp" :type="f.type || 'text'" :placeholder="f.ph || ''" x-model="form[f.k]" :style="{ fontFamily: f.mono ? `'Geist Mono', ui-monospace, monospace` : 'Geist, system-ui, sans-serif' }">
              <span class="hint" x-text="f.h || ''"></span>
              <span class="err" x-show="errors[f.k]" x-text="errors[f.k]"></span>
            </label>
          </template>
        </div>
        <div class="d-flex" x-show="testKind()" style="align-items:center;gap:12px;flex-wrap:wrap">
          <span @click="runTest()" style="height:34px;padding:0 14px;border-radius:7px;border:1px solid #2A2A32;display:flex;align-items:center;font-size:13px;cursor:pointer" x-text="tests[testKind()]?.state === 'run' ? 'Testing…' : 'Test connection'"></span>
          <span :style="{ color: tests[testKind()]?.state === 'ok' ? '#58C98A' : tests[testKind()]?.state === 'bad' ? '#E5695E' : '#6E6E79' }" style="font-size:13px;flex:1;min-width:200px;line-height:1.45" x-text="tests[testKind()]?.msg || 'Not tested yet'"></span>
        </div>
        <div class="d-flex" x-show="step === 7" style="border:1px solid #1C1C22;border-radius:9px;background:#0B0B0E;padding:12px 14px;flex-direction:column;gap:6px">
          <span style="font-size:12px;color:#8A8A94">Add this line to your server’s crontab (cPanel → Cron Jobs, “Once per minute”):</span>
          <div style="display:flex;gap:10px;align-items:center"><code x-ref="cron" style="flex:1;font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;color:#E8C27D;white-space:pre-wrap;word-break:break-all">* * * * * cd {{ $defaults['base'] }} && php artisan schedule:run >> /dev/null 2>&1</code><span @click="navigator.clipboard.writeText($refs.cron.innerText); copied = true" style="font-size:12px;color:#C7BDFF;cursor:pointer;flex:none" x-text="copied ? 'Copied' : 'Copy'"></span></div>
        </div>
      </div>

      {{-- Install --}}
      <div class="d-flex" x-show="step === 9" x-cloak style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11;padding:18px;flex-direction:column;gap:14px">
        <div style="display:flex;justify-content:space-between"><span style="font-weight:500" x-text="instMsg()"></span><span style="font-family:'Geist Mono',ui-monospace,monospace" x-text="Math.min(inst, 100) + '%'"></span></div>
        <div style="height:8px;border-radius:4px;background:#1C1C22"><div :style="{ width: Math.max(0, Math.min(inst, 100)) + '%' }" style="height:100%;border-radius:4px;background:var(--accent);transition:width .1s"></div></div>
        <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;line-height:20px;background:#0B0B0E;border-radius:7px;padding:10px 12px;max-height:180px;overflow:auto">
          <template x-for="(l, i) in shownLog()"><div :style="{ color: l.c }" x-text="l.t"></div></template>
        </div>
        <div x-show="failure" style="padding:10px 12px;border-radius:8px;background:#1A0F0F;border:1px solid #5A2626;color:#F2B8B2;font-size:13px;line-height:1.5"><b>Installation stopped.</b> <span x-text="failure"></span><div style="margin-top:8px"><span @click="step = 2; inst = -1; failure = ''" style="color:#C7BDFF;cursor:pointer">← Go back and fix it</span></div></div>
      </div>

      {{-- Done --}}
      <div class="d-flex" x-show="step === 10" x-cloak style="flex-direction:column;gap:16px">
        <div style="display:flex;gap:14px;align-items:center;padding:16px;border-radius:11px;background:#12211A;border:1px solid #1E3A2A"><span style="width:40px;height:40px;border-radius:50%;background:#58C98A;color:#0A0A0C;display:grid;place-items:center;font-size:18px;font-weight:700;flex:none">✓</span><div><div style="font-weight:600">Your platform is ready</div><div style="font-size:13px;color:#9FD1B0;margin-top:2px">Installed at <span x-text="form.url"></span></div></div></div>
        <div style="border:1px solid #1C1C22;border-radius:11px;background:#0E0E11"><div style="padding:12px 16px;border-bottom:1px solid #1C1C22;font-weight:500">Recommended next steps</div>
          @foreach (['Upload your logo and choose brand colors', 'Connect a payment gateway', 'Set up email sending', 'Invite your first users'] as $i => $t)
            <div style="display:flex;gap:12px;padding:10px 16px;border-bottom:1px solid #16161B"><span style="font-family:'Geist Mono',ui-monospace,monospace;color:#6E6E79">0{{ $i + 1 }}</span><span>{{ $t }}</span></div>
          @endforeach
        </div>
        <div style="font-size:12.5px;color:#E8B66B">For security, the installer is now locked. To reinstall, delete storage/app/installed.json.</div>
      </div>

      <div style="margin-top:auto;padding-top:20px;border-top:1px solid #16161B;display:flex;justify-content:space-between;align-items:center;gap:10px">
        <span class="d-flex" x-show="step > 0 && step < 9" @click="step--" style="height:40px;padding:0 16px;border-radius:8px;border:1px solid #2A2A32;align-items:center;cursor:pointer">← Back</span>
        <span style="flex:1"></span>
        <a class="d-flex" x-show="step === 10" x-cloak :href="adminUrl" style="height:40px;padding:0 20px;border-radius:8px;background:var(--accent);color:#fff;font-weight:500;align-items:center">Open admin panel →</a>
        <span class="d-flex" x-show="step < 9" @click="next()" :style="{ background: blocked() ? '#2A2A32' : 'var(--accent)', cursor: blocked() ? 'default' : 'pointer' }" style="height:40px;padding:0 20px;border-radius:8px;color:#fff;font-weight:500;align-items:center" x-text="step === 0 ? 'Get started →' : step === 8 ? 'Install now' : blocked() ? 'Fix issues to continue' : 'Continue →'"></span>
      </div>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script>
function installer() {
  return {
    step: 0, inst: -1, checking: false, copied: false, failure: '', log: [], adminUrl: '/admin',
    reqs: @json($requirements),
    tests: {}, errors: {},
    choice: { db: 'MySQL', ai: 'OpenRouter', store: 'Local disk', queue: 'Database' },
    form: {
      host: 'localhost', port: '3306', database: '', username: '', password: '',
      name: 'AI Code Studio', url: @json($defaults['url']), timezone: @json($defaults['timezone']), currency: 'USD',
      admin_name: '', admin_email: '', admin_password: '', admin_password2: '',
      ai_key: '', storage_path: @json($defaults['storage']), bucket: '', region: 'us-east-1', s3_key: '', s3_secret: '',
      redis_host: '127.0.0.1', redis_port: '6379', s3_endpoint: '', license: '', domain: @json($defaults['domain']),
    },
    names: ['Welcome', 'System check', 'Database', 'Application', 'Admin account', 'AI provider', 'Storage', 'Queue & cron', 'License', 'Install', 'Complete'],
    steps: [
      ['Welcome to the setup', 'This wizard installs your own AI software development platform. It takes about 5 minutes — you’ll need your database details and, ideally, one AI provider key.'],
      ['Checking your server', 'We’re making sure your server has everything the platform needs. Fix anything red before continuing.'],
      ['Connect your database', 'Where users, projects and settings are stored. Your hosting provider gives you these details (look for “MySQL Databases” in cPanel).'],
      ['About your platform', 'The basics. You can change all of this later in Admin → Branding.'],
      ['Create your admin account', 'This is the owner account with full control. Use a strong password.'],
      ['Connect an AI provider', 'The AI that writes code for your users. You can add more providers later.'],
      ['File storage', 'Where project files and uploads are saved.'],
      ['Background jobs', 'AI runs and builds happen in the background so users don’t have to wait.'],
      ['Activate your license', 'Enter the purchase code from your receipt. You can skip this and add it later.'],
      ['Installing', 'Please keep this page open. This usually takes under a minute.'],
      ['All done!', 'You’re signed in as the admin. Open the admin panel to finish setting up.'],
    ],
    init() {},
    col(ok) { return ok === 1 ? '#58C98A' : ok === 2 ? '#E8B66B' : '#E5695E'; },
    reqSummary() {
      const bad = this.reqs.filter(r => r.ok === 0).length, opt = this.reqs.filter(r => r.ok === 2).length;
      return bad ? bad + ' problem' + (bad > 1 ? 's' : '') + ' to fix before continuing.' : 'All required checks passed.' + (opt ? ' ' + opt + ' optional item' + (opt > 1 ? 's' : '') + ' missing.' : '');
    },
    async recheck() { this.checking = true; try { this.reqs = await (await fetch(@json(route('install.requirements')))).json(); } finally { this.checking = false; } },
    blocked() { return this.step === 1 && this.reqs.some(r => r.ok === 0); },
    opts() {
      return ({
        2: [['MySQL', 'Most common on shared hosting', 'RECOMMENDED'], ['MariaDB', 'MySQL-compatible'], ['PostgreSQL', 'For VPS & cloud'], ['SQLite', 'Single file · for testing']],
        5: [['OpenRouter', 'One key, hundreds of models', 'EASIEST'], ['Anthropic', 'Strong coding models'], ['Google Gemini', 'Has a free tier'], ['Skip for now', 'Add one later in Admin']],
        6: [['Local disk', 'Simple. Good to start.', 'RECOMMENDED'], ['Amazon S3', 'Scales with you'], ['S3-compatible', 'R2, Wasabi, DO Spaces']],
        7: [['Database', 'Needs the cron line below', 'RECOMMENDED'], ['Redis', 'Faster, needs Redis installed'], ['Sync', 'No background jobs · simplest']],
      })[this.step] || [];
    },
    key() { return ({ 2: 'db', 5: 'ai', 6: 'store', 7: 'queue' })[this.step]; },
    chosen() { return this.choice[this.key()]; },
    choose(v) { this.choice[this.key()] = v; if (this.key() === 'db') this.form.port = v === 'PostgreSQL' ? '5432' : '3306'; },
    fields() {
      const F = (k, l, h, o = {}) => ({ k, l, h, ...o });
      const s = this.step, c = this.choice;
      if (s === 2) return c.db === 'SQLite' ? [] : [F('host', 'Host', 'Usually “localhost”.', { mono: 1 }), F('port', 'Port', 'Leave as is unless told otherwise.', { mono: 1 }), F('database', 'Database name', 'Create an empty one first.', { mono: 1 }), F('username', 'Username', '', { mono: 1 }), F('password', 'Password', '', { type: 'password' })];
      if (s === 3) return [F('name', 'Platform name', 'Shown to your users.'), F('url', 'Website address', 'Where the platform will live.', { mono: 1 }), F('timezone', 'Time zone', 'e.g. Africa/Lagos, Europe/London'), F('currency', 'Default currency', 'For plans and billing.')];
      if (s === 4) return [F('admin_name', 'Your name', '', { ph: 'Ada Okafor' }), F('admin_email', 'Email', 'You’ll sign in with this.', { ph: 'you@company.com' }), F('admin_password', 'Password', 'At least 10 characters.', { type: 'password' }), F('admin_password2', 'Confirm password', '', { type: 'password' })];
      if (s === 5) return c.ai === 'Skip for now' ? [] : [F('ai_key', 'API key', 'Find it in your ' + c.ai + ' account under “API keys”. Stored encrypted.', { ph: 'sk-…', mono: 1 })];
      if (s === 6) return c.store === 'Local disk' ? [F('storage_path', 'Storage folder', 'Must be writable.', { mono: 1 })] : [F('bucket', 'Bucket', '', { mono: 1 }), F('region', 'Region', '', { mono: 1 }), F('s3_key', 'Access key', '', { mono: 1 }), F('s3_secret', 'Secret key', '', { type: 'password' })].concat(c.store === 'S3-compatible' ? [F('s3_endpoint', 'Endpoint URL', 'e.g. https://<account>.r2.cloudflarestorage.com', { mono: 1 })] : []);
      if (s === 7) return c.queue === 'Redis' ? [F('redis_host', 'Redis host', '', { mono: 1 }), F('redis_port', 'Port', '', { mono: 1 })] : [];
      if (s === 8) return [F('license', 'Licence key or purchase code', 'From your purchase email (ACS-XXXX-… or an Envato purchase code). Optional.', { mono: 1, ph: 'ACS-XXXX-XXXX-XXXX-XXXX' }), F('domain', 'Domain', 'The license will be locked to this domain.', { mono: 1 })];
      return [];
    },
    testKind() { return ({ 2: 'db', 7: this.choice.queue === 'Sync' ? null : 'cron', 8: 'lic' })[this.step] || (this.step === 5 && this.choice.ai !== 'Skip for now' ? 'ai' : null); },
    payload() {
      const f = this.form, c = this.choice;
      return {
        db: { driver: c.db, host: f.host, port: f.port, database: f.database, username: f.username, password: f.password },
        app: { name: f.name, url: f.url, timezone: f.timezone, currency: f.currency },
        admin: { name: f.admin_name, email: f.admin_email, password: f.admin_password, password_confirmation: f.admin_password2 },
        ai: { driver: c.ai, key: f.ai_key }, storage: { driver: c.store, bucket: f.bucket, region: f.region, key: f.s3_key, secret: f.s3_secret, endpoint: f.s3_endpoint }, queue: { driver: c.queue, redis_host: f.redis_host, redis_port: f.redis_port },
        license: { code: f.license, domain: f.domain },
      };
    },
    async runTest() {
      const k = this.testKind(); this.tests[k] = { state: 'run' };
      try { const r = await api(@json(url('install/test')) + '/' + k, this.payload()); this.tests[k] = { state: r.ok ? 'ok' : 'bad', msg: r.message }; }
      catch (e) { this.tests[k] = { state: 'bad', msg: e.message }; }
    },
    validate() {
      const f = this.form, e = {};
      if (this.step === 2 && this.choice.db !== 'SQLite' && !f.database) e.database = 'Enter the database name.';
      if (this.step === 3) { if (!f.name) e.name = 'Give your platform a name.'; if (!/^https?:\/\//.test(f.url)) e.url = 'Start with https:// (or http:// for local).'; if (!/^[A-Za-z]{3}$/.test(f.currency)) e.currency = 'Use a 3-letter code like USD.'; }
      if (this.step === 6 && this.choice.store !== 'Local disk') { if (!f.bucket) e.bucket = 'Enter the bucket name.'; if (!f.s3_key) e.s3_key = 'Required.'; if (!f.s3_secret) e.s3_secret = 'Required.'; if (this.choice.store === 'S3-compatible' && !/^https?:\/\//.test(f.s3_endpoint)) e.s3_endpoint = 'Enter the endpoint URL.'; }
      if (this.step === 4) { if (!f.admin_name) e.admin_name = 'Enter your name.'; if (!/^\S+@\S+\.\S+$/.test(f.admin_email)) e.admin_email = 'Enter a valid email.'; if (f.admin_password.length < 10) e.admin_password = 'At least 10 characters.'; if (f.admin_password !== f.admin_password2) e.admin_password2 = 'Passwords don’t match.'; }
      this.errors = e; return !Object.keys(e).length;
    },
    async next() {
      if (this.blocked() || !this.validate()) return;
      if (this.step === 2 && this.tests.db?.state !== 'ok') { await this.runTest(); if (this.tests.db.state !== 'ok') return; }
      if (this.step === 8) return this.install();
      this.step = Math.min(10, this.step + 1);
    },
    instLines: ['Writing configuration file', 'Creating database tables', 'Seeding default plans & AI providers', 'Setting up admin account', 'Encrypting API keys', 'Registering background jobs', 'Saving license details', 'Warming caches'],
    shownLog() {
      if (this.inst < 0) return [];
      const lines = this.log.length ? this.log : this.instLines, n = this.inst >= 100 ? lines.length : Math.min(lines.length, Math.floor(this.inst / 12.5) + 1);
      return lines.slice(0, n).map((t, i) => ({ t: (i < n - 1 || this.inst >= 100 ? '✓ ' : '● ') + t, c: i < n - 1 || this.inst >= 100 ? '#58C98A' : '#A99BFF' }));
    },
    instMsg() { const l = this.shownLog(); return this.failure ? 'Failed' : this.inst >= 100 ? 'Finished' : (l.length ? l[l.length - 1].t.slice(2) + '…' : 'Starting…'); },
    async install() {
      this.step = 9; this.inst = 0; this.failure = '';
      const t = setInterval(() => { if (this.inst < 90) this.inst += 3; }, 120);
      try {
        const r = await api(@json(route('install.run')), this.payload());
        clearInterval(t); this.log = r.log; this.adminUrl = r.admin;
        const f = setInterval(() => { this.inst += 4; if (this.inst >= 100) { clearInterval(f); this.inst = 100; setTimeout(() => this.step = 10, 400); } }, 60);
      } catch (e) {
        clearInterval(t);
        const errs = e.data?.errors ? Object.values(e.data.errors).flat().join(' ') : '';
        this.failure = errs || e.message; this.log = e.data?.log || [];
      }
    },
  };
}
</script>
@endpush
