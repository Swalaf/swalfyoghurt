@extends('studio.layout')
@section('title', $project->name)

@section('main')
@php
  $live = $project->liveDeployment();
@endphp
<div data-screen-label="Builder" x-data="builder(@js($state), @js([
    'state' => route('studio.projects.state', $project),
    'messages' => route('studio.projects.messages', $project),
    'undo' => route('studio.projects.undo', $project),
    'build' => route('studio.projects.build', $project),
    'preview' => $project->previewUrl(),
    'live' => $live ? \App\Jobs\DeployProject::url($live->subdomain) : null,
  ]))" x-init="init()" style="flex:1;display:flex;min-height:0">
  <aside data-r="bpanel" style="width:380px;flex:none;border-right:1px solid #1C1C22;background:#0D0D10;display:flex;flex-direction:column;min-height:0">
    <div style="padding:14px 16px;border-bottom:1px solid #1C1C22;display:flex;flex-direction:column;gap:8px">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px"><span style="font-weight:600;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $project->name }}</span><span style="font-size:11.5px;display:flex;gap:6px;align-items:center;white-space:nowrap" :style="{ color: statusColor() }"><span style="width:6px;height:6px;border-radius:50%" :style="{ background: statusColor() }"></span><span x-text="statusText()"></span></span></div>
      <div style="height:4px;border-radius:2px;background:#1C1C22"><div style="height:100%;border-radius:2px;background:var(--accent);transition:width .4s" :style="{ width: (s.status === 'ready' ? 100 : s.progress) + '%' }"></div></div>
    </div>
    <div x-ref="feed" style="flex:1;overflow-y:auto;overflow-x:hidden;padding:16px;display:flex;flex-direction:column;gap:14px;min-width:0">
      <div style="align-self:flex-end;max-width:88%;background:#1A1A20;border-radius:12px 12px 4px 12px;padding:10px 12px;line-height:1.5">{{ $project->idea }}</div>
      <div style="display:flex;gap:10px">
        <span style="width:26px;height:26px;border-radius:7px;background:var(--accent-soft);color:#C7BDFF;display:grid;place-items:center;font-size:12px;flex:none">✦</span>
        <div style="flex:1;display:flex;flex-direction:column;gap:10px;line-height:1.5;min-width:0">
          <div>{{ __('I planned') }} <b style="font-weight:600">{{ count($project->spec['features'] ?? []) }} features</b> across {{ count($project->spec['pages'] ?? []) }} pages. <span x-text="s.tasks.length ? 'Here’s where I am:' : 'Start the build whenever you’re ready.'"></span></div>
          <div x-show="s.tasks.length" style="border:1px solid #1F1F26;border-radius:10px;background:#0F0F13;padding:6px 0">
            <template x-for="t in s.tasks" :key="t.agent">
              <div style="display:flex;gap:10px;padding:6px 12px;align-items:flex-start">
                <span style="width:14px;font-family:'Geist Mono',ui-monospace,monospace;flex:none" :style="{ color: icon(t.status)[1] }" x-text="icon(t.status)[0]"></span>
                <div><div :style="{ color: ['queued'].includes(t.status) ? '#6E6E79' : '#E4E4E9' }" x-text="plain(t)"></div><div style="font-size:12px;color:#6E6E79" x-text="t.model || t.task"></div></div>
              </div>
            </template>
          </div>
          <form x-show="!s.tasks.length || s.status === 'failed'" method="POST" action="{{ route('studio.projects.build', $project) }}">@csrf<button class="btn primary" x-text="s.status === 'failed' ? 'Try the build again' : 'Start building'">{{ __('Start building') }}</button></form>
        </div>
      </div>
      <template x-for="m in s.messages" :key="m.id">
        <div class="bub" :class="m.role === 'user' ? 'user' : 'ai'" x-text="m.text"></div>
      </template>
      <div class="d-flex" x-show="thinking" x-cloak style="align-self:flex-start;gap:8px;align-items:center;color:#8A8A94;font-size:12.5px"><span style="color:#A99BFF">✦</span>{{ __('Working on it…') }}</div>
      <div class="d-flex" x-show="s.pending" x-cloak style="border:1px solid #3A3160;background:#110F1A;border-radius:12px;padding:14px;flex-direction:column;gap:10px">
        <div style="font-size:11.5px;color:#C7BDFF;font-weight:500">{{ __('NEEDS YOUR OK') }}</div>
        <div style="line-height:1.5" x-text="s.pending?.summary"></div>
        <div style="font-size:12px;color:#8A8A94"><span x-text="s.pending?.files.length"></span> {{ __('file(s) ·') }} <span style="color:#58C98A" x-text="'+' + (s.pending?.additions || 0)"></span> <span style="color:#E5695E" x-text="'−' + (s.pending?.deletions || 0)"></span> {{ __('lines · safe to undo later') }}</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><button @click="decide('approve')" class="btn primary" style="height:34px">{{ __('Yes, go ahead') }}</button><button @click="send('Explain the changes you just proposed in plain language.', 'Ask')" class="btn ghost" style="height:34px">{{ __('Explain more') }}</button><button @click="decide('reject')" class="btn danger" style="height:34px;border:none">{{ __('Discard') }}</button></div>
      </div>
    </div>
    <div style="padding:12px;border-top:1px solid #1C1C22;display:flex;flex-direction:column;gap:10px">
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <template x-for="t in ['Make it work on phones', 'Add a contact form', 'Change the colors', 'Add a pricing section']"><span @click="send(t)" class="hv-violet" style="font-size:12px;padding:5px 10px;border-radius:14px;border:1px solid #26262E;color:#B4B4BE;cursor:pointer" x-text="t"></span></template>
      </div>
      <div style="border:1px solid #2A2A32;border-radius:10px;background:#111115;padding:10px 12px;display:flex;flex-direction:column;gap:8px">
        <input x-model="draft" @keydown.enter="send()" :disabled="thinking" placeholder="{{ __('Tell the AI what to change, in your own words…') }}" style="background:transparent;border:none;outline:none;color:#F2F2F5;font-size:13.5px">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#6E6E79"><span x-text="error" style="color:#E5695E"></span><span @click="send()" style="height:28px;padding:0 12px;border-radius:6px;background:var(--accent);color:#fff;display:flex;align-items:center;cursor:pointer;font-size:12.5px" :style="{ opacity: thinking ? .6 : 1 }">{{ __('Send') }}</span></div>
      </div>
    </div>
  </aside>
  <div data-r="bprev" style="flex:1;min-width:0;display:flex;flex-direction:column;background:#111114">
    <div style="min-height:48px;flex:none;display:flex;align-items:center;gap:8px;padding:8px 14px;border-bottom:1px solid #1C1C22;background:#0D0D10;flex-wrap:wrap">
      <div style="display:flex;border:1px solid #1F1F26;border-radius:7px;padding:2px;gap:2px;flex:none">
        <template x-for="d in [['desktop', 'Computer'], ['tablet', 'Tablet'], ['phone', 'Phone']]"><span @click="device = d[0]" :style="{ background: device === d[0] ? 'var(--accent-soft)' : 'transparent', color: device === d[0] ? '#C7BDFF' : '#8A8A94' }" style="padding:4px 10px;border-radius:5px;font-size:12px;cursor:pointer" x-text="d[1]"></span></template>
      </div>
      <span @click="toggleEdit()" :style="{ borderColor: edit ? 'var(--accent-line)' : '#1F1F26', background: edit ? 'var(--accent-soft)' : 'transparent', color: edit ? '#C7BDFF' : '#C9C9D1' }" style="height:30px;padding:0 12px;border-radius:7px;display:flex;align-items:center;gap:7px;font-size:12.5px;cursor:pointer;flex:none;border:1px solid">{{ __('⌖ Click to edit') }}</span>
      <span style="flex:1"></span>
      <span style="font-size:12px;display:flex;gap:6px;align-items:center;white-space:nowrap" :style="{ color: s.files ? '#58C98A' : '#6E6E79' }"><span style="width:6px;height:6px;border-radius:50%" :style="{ background: s.files ? '#58C98A' : '#6E6E79' }"></span><span x-text="s.files ? s.files + ' files' : 'No files yet'"></span></span>
      <span @click="undo()" :style="{ opacity: s.canUndo ? 1 : .45, cursor: s.canUndo ? 'pointer' : 'default' }" style="height:30px;padding:0 11px;border-radius:7px;border:1px solid #1F1F26;display:flex;align-items:center;font-size:12.5px;color:#C9C9D1;flex:none">{{ __('↶ Undo') }}</span>
      <span @click="share()" style="height:30px;padding:0 11px;border-radius:7px;border:1px solid #1F1F26;display:flex;align-items:center;font-size:12.5px;color:#C9C9D1;cursor:pointer;flex:none" x-text="copied ? 'Link copied' : 'Share link'"></span>
      <a href="{{ route('studio.projects.deploy', $project) }}" class="btn primary" style="height:30px;font-size:12.5px;flex:none">{{ __('Publish') }}</a>
    </div>
    <div style="flex:1;overflow:auto;padding:20px;display:flex;justify-content:center;position:relative">
      <iframe x-ref="frame" :src="frameSrc" sandbox="allow-scripts allow-forms allow-popups allow-modals" title="{{ __('App preview') }}" :style="{ width: device === 'phone' ? '390px' : device === 'tablet' ? '768px' : '100%' }" style="max-width:100%;min-height:100%;height:100%;border:0;background:#FAFAF8;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,0.4)"></iframe>
      <div class="d-flex" x-show="sel" x-cloak style="position:absolute;right:28px;top:28px;z-index:5;width:min(320px,calc(100% - 56px));background:#0E0E11;color:#E4E4E9;border:1px solid #3A3160;border-radius:10px;padding:12px;flex-direction:column;gap:10px;box-shadow:0 16px 40px rgba(0,0,0,0.4)">
        <div style="display:flex;justify-content:space-between;gap:8px"><div style="font-size:11.5px;color:#C7BDFF;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="'Selected: ' + selLabel()"></div><span @click="sel = null" style="cursor:pointer;color:#8A8A94">×</span></div>
        <div style="display:flex;gap:5px;flex-wrap:wrap"><template x-for="q in ['Make it bigger', 'Darker color', 'Rename', 'Remove']"><span @click="applyEdit(q)" style="font-size:12px;padding:4px 9px;border-radius:12px;border:1px solid #2A2A32;cursor:pointer" x-text="q"></span></template></div>
        <div style="display:flex;gap:6px"><input x-model="editText" @keydown.enter="applyEdit(editText)" placeholder="{{ __('Or describe the change…') }}" class="inp sm" style="flex:1;min-width:0;height:30px;font-size:12.5px"><span @click="applyEdit(editText)" style="height:30px;padding:0 10px;border-radius:6px;background:var(--accent);color:#fff;display:flex;align-items:center;font-size:12px;cursor:pointer">{{ __('Apply') }}</span></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function builder(state, urls) {
  return {
    s: state, urls, draft: '', thinking: false, error: '', device: 'desktop', edit: false, sel: null, editText: '', copied: false, frameSrc: urls.preview + '?v=' + (state.updated || 0), timer: null,
    init() {
      this.poll();
      this.$nextTick(() => this.scroll());
      window.addEventListener('message', (e) => { if (e.source === this.$refs.frame.contentWindow && e.data?.type === 'studio-selected') { this.sel = e.data.el; this.editText = ''; } });
      this.$refs.frame.addEventListener('load', () => this.edit && this.$refs.frame.contentWindow.postMessage({ type: 'studio-edit', on: true }, '*'));
    },
    poll() {
      clearTimeout(this.timer);
      const busy = this.s.status === 'building' || this.s.tasks.some(t => ['queued', 'working'].includes(t.status));
      this.timer = setTimeout(async () => { try { this.apply(await (await fetch(this.urls.state, { headers: { Accept: 'application/json' } })).json()); } catch (e) {} this.poll(); }, busy ? 2000 : 8000);
    },
    apply(next) {
      const reload = next.updated !== this.s.updated || next.files !== this.s.files, more = next.messages.length !== this.s.messages.length;
      this.s = next;
      if (reload) this.frameSrc = this.urls.preview + '?v=' + next.updated;
      if (more) this.$nextTick(() => this.scroll());
    },
    scroll() { this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight; },
    statusText() { const d = this.s.tasks.filter(t => t.status === 'done').length; return ({ building: 'Building · ' + d + ' of ' + this.s.tasks.length + ' done', ready: 'Ready', failed: 'Needs attention', planned: 'Plan ready' })[this.s.status] || 'Draft'; },
    statusColor() { return ({ ready: '#58C98A', failed: '#E8B66B' })[this.s.status] || '#A99BFF'; },
    icon(st) { return ({ done: ['✓', '#58C98A'], working: ['●', '#A99BFF'], waiting: ['◐', '#E8B66B'], failed: ['✕', '#E5695E'] })[st] || ['○', '#44444C']; },
    plain(t) { return ({ Architect: 'Plan the pages and data', Developer: 'Write the app', QA: 'Check everything works', Security: 'Check it’s safe', Deploy: t.status === 'done' ? 'Published' : 'Get it ready to share' })[t.agent] || t.task; },
    async send(text = null, mode = 'Agent') {
      const t = (text ?? this.draft).trim(); if (!t || this.thinking) return;
      this.error = ''; this.thinking = true; this.draft = '';
      this.s.messages.push({ id: 'tmp' + Date.now(), role: 'user', text: t }); this.$nextTick(() => this.scroll());
      try { this.apply(await api(this.urls.messages, { text: t, channel: 'builder', mode })); }
      catch (e) { this.error = e.message; this.s.messages.pop(); this.draft = t; }
      finally { this.thinking = false; this.$nextTick(() => this.scroll()); }
    },
    async decide(which) { try { this.apply(await api(this.s.pending[which], { channel: 'builder' })); } catch (e) { this.error = e.message; } },
    async undo() { if (!this.s.canUndo) return; try { this.apply(await api(this.urls.undo, { channel: 'builder' })); } catch (e) { this.error = e.message; } },
    share() {
      if (!this.urls.live) { this.error = 'Publish the app first to get a link you can share.'; return; }
      navigator.clipboard.writeText(this.urls.live); this.copied = true; setTimeout(() => this.copied = false, 2000);
    },
    toggleEdit() { this.edit = !this.edit; this.sel = null; this.$refs.frame.contentWindow.postMessage({ type: 'studio-edit', on: this.edit }, '*'); },
    selLabel() { const e = this.sel || {}; return (e.tag || '') + (e.text ? ' · “' + e.text.slice(0, 40) + '”' : ''); },
    applyEdit(what) {
      if (!what || !this.sel) return;
      const e = this.sel; this.sel = null;
      this.send('On the <' + e.tag + '> element' + (e.text ? ' with text "' + e.text + '"' : '') + (e.id ? ' (id="' + e.id + '")' : '') + (e.cls ? ' (class="' + e.cls + '")' : '') + ': ' + what);
    },
  };
}
</script>
@endpush
