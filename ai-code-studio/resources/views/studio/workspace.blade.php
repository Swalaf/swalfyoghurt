@extends('studio.layout')
@section('title', ($current?->path ?? 'Code').' · '.$project->name)

@section('main')
@php
  $ext = $current ? $current->extension() : '';
  $crumbs = $current ? explode('/', $current->path) : [];
  $openDirs = $current ? collect(range(1, max(1, count($crumbs) - 1)))->map(fn ($i) => implode('/', array_slice($crumbs, 0, $i)))->all() : [];
@endphp
<div data-screen-label="Workspace" x-data="workspace(@js($state), @js([
    'save' => route('studio.projects.files.save', $project),
    'state' => route('studio.projects.state', $project).'?channel=workspace',
    'messages' => route('studio.projects.messages', $project),
    'undo' => route('studio.projects.undo', $project),
    'preview' => $project->previewUrl(),
  ]), @js($current?->path), @js($current?->content ?? ''), @js($ext))" x-init="init()" @keydown.window.ctrl.s.prevent="save()" @keydown.window.meta.s.prevent="save()" style="flex:1;display:flex;min-height:0">
  <div style="flex:1;min-width:0;display:flex;flex-direction:column">
    <div style="flex:1;display:flex;min-height:0">
      <div data-r="tree" style="width:218px;flex:none;border-right:1px solid #1C1C22;background:#0C0C0F;display:flex;flex-direction:column;overflow:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;font-size:10.5px;letter-spacing:0.08em;color:#6E6E79">{{ strtoupper($project->slug) }}<span style="letter-spacing:0;font-size:12px;display:flex;gap:8px"><span @click="newFile()" title="New file" style="cursor:pointer">＋</span><a href="{{ route('studio.projects.download', $project) }}" title="Download .zip" style="color:#6E6E79">⇣</a><a href="{{ request()->fullUrl() }}" title="Refresh" style="color:#6E6E79">⟳</a></span></div>
        @forelse ($tree as $node)
          @php([$icon, $ic] = $node['dir'] ? ['▤', '#5CC8E0'] : \App\Models\ProjectFile::iconFor($node['path']))
          @php($sel = $current && $current->path === $node['path'])
          @php($parent = str_contains($node['path'], '/') ? dirname($node['path']) : '')
          @php($pending = collect($state['pending']['files'] ?? [])->firstWhere('path', $node['path']))
          <a @if (! $node['dir']) href="{{ route('studio.projects.code', [$project, 'file' => $node['path']]) }}" @endif
             x-show="{{ $parent === '' ? 'true' : "open.includes(".json_encode($parent).")" }}"
             @if ($node['dir']) @click="toggle(@js($node['path']))" @endif
             class="hv-bg" style="display:flex;align-items:center;gap:6px;height:24px;padding-right:10px;padding-left:{{ 8 + $node['depth'] * 12 }}px;font-size:12.5px;cursor:pointer;background:{{ $sel ? '#18152A' : 'transparent' }};color:{{ $sel ? '#F2F2F5' : '#B4B4BE' }}">
            <span style="width:10px;font-size:9px;color:#55555F">@if ($node['dir'])<span x-text="open.includes(@js($node['path'])) ? '▾' : '▸'"></span>@endif</span>
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10px;width:14px;color:{{ $ic }}">{{ $icon }}</span>
            <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $node['name'] }}</span>
            @if ($pending)<span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:{{ $pending['action'] === 'add' ? '#58C98A' : '#E8B66B' }}">{{ $pending['action'] === 'add' ? 'U' : 'M' }}</span>@endif
          </a>
        @empty
          <div style="padding:12px;color:#6E6E79;font-size:12px;line-height:1.5">No files yet. Start a build from the <a href="{{ route('studio.projects.plan', $project) }}">plan</a>, or ask the agent to create them.</div>
        @endforelse
      </div>
      <div style="flex:1;min-width:0;display:flex;flex-direction:column;background:#0A0A0C">
        <div style="height:35px;flex:none;display:flex;background:#0C0C0F;border-bottom:1px solid #1C1C22;overflow:hidden">
          @foreach ($tabs as $t)
            @php([$icon, $ic] = \App\Models\ProjectFile::iconFor($t))
            @php($on = $current && $current->path === $t)
            <a href="{{ route('studio.projects.code', [$project, 'file' => $t]) }}" style="display:flex;align-items:center;gap:7px;padding:0 13px;border-right:1px solid #1C1C22;font-size:12.5px;white-space:nowrap;background:{{ $on ? '#0A0A0C' : 'transparent' }};color:{{ $on ? '#F2F2F5' : '#8A8A94' }};box-shadow:{{ $on ? 'inset 0 1px 0 var(--accent)' : 'none' }}">
              <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10px;color:{{ $ic }}">{{ $icon }}</span>{{ basename($t) }}@if ($on)<span style="color:#55555F;font-size:11px" x-text="dirty ? '●' : ''"></span>@endif
            </a>
          @endforeach
          <div style="flex:1"></div>
          @if ($current)
          <div style="display:flex;align-items:center;gap:8px;padding:0 10px">
            <form method="POST" action="{{ route('studio.projects.files.delete', $project) }}" onsubmit="return confirm('Delete {{ $current->path }}?')" class="inline">@csrf @method('DELETE')<input type="hidden" name="path" value="{{ $current->path }}"><button class="btn xs danger" style="border:none">Delete</button></form>
            <button @click="save()" class="btn xs" :class="dirty && 'primary'" x-text="saving ? 'Saving…' : (dirty ? 'Save ⌘S' : 'Saved')">Saved</button>
          </div>
          @endif
        </div>
        <div style="height:28px;flex:none;display:flex;align-items:center;gap:6px;padding:0 14px;font-size:12px;color:#6E6E79;border-bottom:1px solid #141418;white-space:nowrap;overflow:hidden">
          @foreach ($crumbs as $i => $c)<span style="color:{{ $i === count($crumbs) - 1 ? '#C9C9D1' : '#6E6E79' }}">{{ $c }}</span>@if ($i < count($crumbs) - 1)<span>›</span>@endif @endforeach
          <span style="flex:1"></span>
          <span class="d-flex" x-show="s.pending" x-cloak style="align-items:center;gap:6px;padding:2px 8px;border-radius:4px;background:#15122A;color:#B4A5FF;font-size:11px">✦ Agent proposed changes · <span x-text="s.pending?.files.length"></span> files pending</span>
        </div>
        @if ($current)
        <div x-ref="scroller" style="flex:1;min-height:0;display:flex;overflow:auto;font-family:'Geist Mono',ui-monospace,monospace;font-size:12.5px;line-height:21px">
          <div style="flex:none;padding:8px 0 40px;text-align:right;color:#44444C;user-select:none;min-width:46px">
            <template x-for="n in lines"><div style="padding-right:16px" x-text="n"></div></template>
          </div>
          <div class="editor" style="display:grid">
            <pre x-ref="hl" aria-hidden="true" style="grid-area:1/1;color:#D4D4DA" x-html="html"></pre>
            <textarea x-ref="ta" x-model="code" @input="dirty = true; render()" @keydown.tab.prevent="indent($event)" spellcheck="false" wrap="off" style="grid-area:1/1;position:static"></textarea>
          </div>
        </div>
        @else
        <div style="flex:1;display:grid;place-items:center;color:#6E6E79">Select a file on the left, or ask the agent to create one.</div>
        @endif
      </div>
    </div>

    <div style="height:236px;flex:none;border-top:1px solid #1C1C22;background:#0C0C0F;display:flex;flex-direction:column">
      <div style="height:32px;flex:none;display:flex;align-items:center;gap:2px;padding:0 8px;border-bottom:1px solid #1C1C22;overflow-x:auto">
        @foreach ([['out', 'Output', ''], ['prob', 'Problems', count($problems) ?: ''], ['prev', 'Preview', ''], ['git', 'History', $history->count() ?: '']] as [$id, $name, $badge])
          <div @click="panel = '{{ $id }}'" :style="{ color: panel === '{{ $id }}' ? '#F2F2F5' : '#8A8A94', boxShadow: panel === '{{ $id }}' ? 'inset 0 -1px 0 var(--accent)' : 'none' }" style="height:32px;display:flex;align-items:center;gap:6px;padding:0 10px;font-size:12px;cursor:pointer;white-space:nowrap">{{ $name }}@if ($badge !== '')<span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;padding:0 5px;border-radius:8px;background:#1C1C22;color:{{ $id === 'prob' ? '#E5695E' : '#9A9AA5' }}">{{ $badge }}</span>@endif</div>
        @endforeach
        <span style="flex:1"></span>
        <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px;color:#6E6E79;white-space:nowrap" data-r="hidem">{{ $project->files()->count() }} files · {{ $project->slug }}</span>
      </div>
      <div class="d-flex" x-show="panel === 'out'" style="flex:1;min-height:0;overflow:auto;padding:10px 14px;font-family:'Geist Mono',ui-monospace,monospace;font-size:12px;line-height:19px;flex-direction:column;gap:2px">
        @forelse ($logs as $task)
          @foreach ($task->log ?? [] as $l)<div style="white-space:pre-wrap;color:{{ str_starts_with($l['m'], '✕') ? '#E5695E' : (str_starts_with($l['m'], '⚠') ? '#E8B66B' : '#9A9AA5') }}"><span style="color:#55555F">{{ $l['t'] }}</span>  <span style="color:#A99BFF">{{ strtolower($task->agent) }} ✦</span> {{ $l['m'] }}</div>@endforeach
        @empty
          <div style="color:#6E6E79">No build output yet. Builds log each agent’s work here.</div>
        @endforelse
      </div>
      <div x-show="panel === 'prob'" x-cloak style="flex:1;min-height:0;overflow:auto">
        @forelse ($problems as $pr)
          <div style="display:flex;align-items:center;gap:12px;padding:8px 14px;border-bottom:1px solid #16161B">
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;padding:1px 6px;border-radius:4px;flex:none;background:{{ $pr['kind'] === 'ERROR' ? '#2A1616' : '#2A2214' }};color:{{ $pr['kind'] === 'ERROR' ? '#E5695E' : '#E8B66B' }}">{{ $pr['kind'] }}</span>
            <div style="flex:1;min-width:0"><div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:12px">{{ $pr['msg'] }}</div><div style="font-size:11px;color:#6E6E79;margin-top:2px">{{ $pr['loc'] }}</div></div>
            <button @click="ask(@js('Explain this problem: '.$pr['msg'].' in '.$pr['loc']), 'Explain')" class="btn xs">Explain</button>
            <button @click="ask(@js('Fix this problem: '.$pr['msg'].' in '.$pr['loc']), 'Debug')" style="height:24px;padding:0 9px;border-radius:5px;border:none;background:#221D3D;color:#C7BDFF;font-size:11.5px;cursor:pointer">✦ Fix with AI</button>
          </div>
        @empty
          <div style="padding:14px;color:#6E6E79;font-size:12.5px">✓ No problems found.</div>
        @endforelse
      </div>
      <div class="d-flex" x-show="panel === 'prev'" x-cloak style="flex:1;min-height:0;flex-direction:column;padding:8px 12px;gap:8px">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="flex:1;height:26px;border:1px solid #1F1F26;border-radius:5px;background:#101014;display:flex;align-items:center;padding:0 9px;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#8A8A94;overflow:hidden;white-space:nowrap"><span style="color:#58C98A;margin-right:6px">●</span>{{ $project->previewUrl() }}</div>
          <a href="{{ $project->previewUrl() }}" target="_blank" style="color:#8A8A94;font-size:12px">↗</a>
          <span @click="frameV++" style="color:#8A8A94;font-size:12px;cursor:pointer">⟳</span>
        </div>
        <template x-if="panel === 'prev'"><iframe :src="urls.preview + '?v=' + frameV" sandbox="allow-scripts allow-forms" style="flex:1;border:1px solid #1C1C22;border-radius:6px;background:#fff;width:100%"></iframe></template>
      </div>
      <div x-show="panel === 'git'" x-cloak style="flex:1;min-height:0;overflow:auto">
        @php($hc = ['applied' => '#58C98A', 'pending' => '#A99BFF', 'rejected' => '#6E6E79', 'reverted' => '#E8B66B'])
        @forelse ($history as $h)
          <div style="display:flex;align-items:center;gap:10px;padding:8px 14px;border-bottom:1px solid #16161B"><span style="width:7px;height:7px;border-radius:50%;flex:none;background:{{ $hc[$h->status] ?? '#6E6E79' }}"></span><div style="flex:1;min-width:0"><div style="font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $h->summary }}</div><div style="font-size:11px;color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace">#{{ $h->id }} · {{ $h->status }} · {{ $h->files->count() }} files · +{{ $h->files->sum('additions') }} −{{ $h->files->sum('deletions') }} · {{ $h->created_at->diffForHumans() }}</div></div></div>
        @empty
          <div style="padding:14px;color:#6E6E79;font-size:12.5px">No AI changes yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  <aside data-r="ai" style="width:372px;flex:none;border-left:1px solid #1C1C22;background:#0D0D10;display:flex;flex-direction:column;min-height:0">
    <div style="height:35px;flex:none;display:flex;align-items:center;justify-content:space-between;padding:0 12px;border-bottom:1px solid #1C1C22">
      <span style="font-weight:600;font-size:12.5px;display:flex;gap:7px;align-items:center"><span style="color:#A99BFF;font-family:'Geist Mono',ui-monospace,monospace">✦</span>AI Development Agent</span>
      <span style="font-size:11px;color:#6E6E79;font-family:'Geist Mono',ui-monospace,monospace">{{ \App\Support\Settings::get('routing') }}</span>
    </div>
    <div style="display:flex;gap:2px;padding:7px 10px;border-bottom:1px solid #1C1C22;flex-wrap:wrap">
      <template x-for="m in ['Ask', 'Edit', 'Agent', 'Debug', 'Explain', 'Refactor', 'Generate', 'Review']"><span @click="mode = m" :style="{ background: mode === m ? 'var(--accent-soft)' : 'transparent', color: mode === m ? '#C7BDFF' : '#8A8A94' }" style="font-size:11.5px;padding:3px 8px;border-radius:4px;cursor:pointer" x-text="m"></span></template>
    </div>
    <div x-ref="feed" style="flex:1;overflow:auto;padding:12px;display:flex;flex-direction:column;gap:12px">
      <div x-show="s.tasks.length" style="border:1px solid #1F1F26;border-radius:9px;background:#0F0F13">
        <div style="padding:9px 11px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;font-size:12px"><span style="font-weight:500" x-text="'Build · ' + s.tasks.length + ' agents'"></span><a href="{{ route('studio.projects.agents', $project) }}" style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11px">details</a></div>
        <div style="padding:6px 0">
          <template x-for="t in s.tasks"><div style="display:flex;align-items:center;gap:9px;padding:4px 11px;font-size:12.5px"><span style="width:14px;text-align:center;font-family:'Geist Mono',ui-monospace,monospace" :style="{ color: icon(t.status)[1] }" x-text="icon(t.status)[0]"></span><span style="flex:1" :style="{ color: t.status === 'queued' ? '#6E6E79' : '#D4D4DA' }" x-text="t.agent + ' · ' + t.task"></span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:#55555F" x-text="t.status === 'working' ? t.progress + '%' : ''"></span></div></template>
        </div>
      </div>
      <template x-for="m in s.messages" :key="m.id">
        <div class="bub sm" :class="m.role === 'user' ? 'user' : 'ai'" x-text="m.text"></div>
      </template>
      <div x-show="thinking" x-cloak style="color:#8A8A94;font-size:12px"><span style="color:#A99BFF">✦</span> Working…</div>
      <div x-show="s.pending" x-cloak style="border:1px solid #1F1F26;border-radius:9px;background:#0F0F13">
        <div style="padding:9px 11px;border-bottom:1px solid #1C1C22;display:flex;justify-content:space-between;font-size:12px"><span style="font-weight:500">Changes</span><span style="font-family:'Geist Mono',ui-monospace,monospace;color:#6E6E79"><span style="color:#58C98A" x-text="'+' + (s.pending?.additions || 0)"></span> <span style="color:#E5695E" x-text="'−' + (s.pending?.deletions || 0)"></span></span></div>
        <template x-for="ch in (s.pending?.files || [])">
          <div style="display:flex;align-items:center;gap:8px;padding:6px 11px;font-size:12px;border-bottom:1px solid #16161B">
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;width:12px" :style="{ color: ch.action === 'add' ? '#58C98A' : ch.action === 'delete' ? '#E5695E' : '#E8B66B' }" x-text="({ add: 'A', modify: 'M', delete: 'D' })[ch.action]"></span>
            <span style="flex:1;font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="ch.path"></span>
            <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:#58C98A" x-text="ch.add ? '+' + ch.add : ''"></span><span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;color:#E5695E" x-text="ch.del ? '−' + ch.del : ''"></span>
          </div>
        </template>
        <div style="display:flex;gap:6px;padding:9px 11px">
          <button @click="decide('approve')" class="btn primary sm" style="flex:1;height:28px;font-size:12px">Approve all</button>
          <button @click="ask('Walk me through each proposed change and why.', 'Review')" class="btn sm" style="height:28px;font-size:12px">Review</button>
          <button @click="decide('reject')" class="btn sm danger" style="height:28px;font-size:12px">Reject</button>
        </div>
      </div>
      <button x-show="s.canUndo && !s.pending" x-cloak @click="undo()" class="btn sm ghost" style="align-self:flex-start">↶ Undo last applied change</button>
    </div>
    <div style="padding:10px;border-top:1px solid #1C1C22;display:flex;flex-direction:column;gap:8px">
      <div style="display:flex;gap:5px;flex-wrap:wrap">
        @if ($current)<span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;padding:2px 7px;border-radius:4px;border:1px solid #22222A;color:#9A9AA5">{{ basename($current->path) }}</span>@endif
        <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;padding:2px 7px;border-radius:4px;border:1px solid #22222A;color:#9A9AA5">{{ $files->count() }} files</span>
        <span style="font-family:'Geist Mono',ui-monospace,monospace;font-size:10.5px;padding:2px 7px;border-radius:4px;border:1px solid #22222A;color:#9A9AA5">spec</span>
      </div>
      <div style="border:1px solid #26262E;border-radius:8px;background:#111115;padding:9px 10px;display:flex;flex-direction:column;gap:8px">
        <input x-model="draft" @keydown.enter="ask()" :disabled="thinking" :placeholder="mode === 'Ask' || mode === 'Explain' ? 'Ask about this project…' : 'Tell the agent what to build or change…'" style="background:transparent;border:none;outline:none;color:#E4E4E9;font-size:12.5px">
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:#6E6E79"><span x-text="error || (mode + ' mode')" :style="{ color: error ? '#E5695E' : '#6E6E79' }"></span><span @click="ask()" style="width:24px;height:24px;border-radius:5px;background:var(--accent);color:#fff;display:grid;place-items:center;font-size:12px;cursor:pointer">↑</span></div>
      </div>
    </div>
  </aside>
</div>
@endsection

@push('scripts')
<script>
function workspace(state, urls, path, content, ext) {
  const KW = /^(import|from|export|default|function|const|let|var|return|await|async|if|else|for|foreach|while|new|class|extends|implements|public|private|protected|static|use|namespace|echo|true|false|null|interface|type|as|fn|def|try|catch|throw)$/;
  const esc = (t) => t.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  const hashComments = ['py', 'yml', 'yaml', 'sh', 'rb', 'toml', 'env'].includes(ext);
  const re = new RegExp('(' + (hashComments ? '#.*$|' : '') + '\\/\\/.*$|\\/\\*.*?\\*\\/|<!--.*?-->|\'(?:\\\\.|[^\'])*\'|"(?:\\\\.|[^"])*"|`[^`]*`|<\\/?[A-Za-z][\\w.-]*|\\/?>|\\$\\w+|\\b[A-Za-z_]\\w*(?=\\()|\\b[A-Z][A-Za-z]+\\b|\\b\\d+(?:\\.\\d+)?\\b|\\b[a-z_]\\w*\\b)', 'g');
  const color = (v) => /^(\/\/|#|\/\*|<!--)/.test(v) ? '#5E5E6A' : /^['"`]/.test(v) ? '#9FD9A3' : KW.test(v) ? '#B4A5FF' : (v.startsWith('<') || v.endsWith('>')) ? '#7DD3E8' : v.startsWith('$') ? '#F0A07A' : /^[A-Z]/.test(v) ? '#E8C27D' : /^\d/.test(v) ? '#F0A07A' : /\w\($/.test(v) ? '#7DD3E8' : null;
  return {
    s: state, urls, path, code: content, html: '', lines: 1, dirty: false, saving: false, panel: 'out', mode: 'Agent', draft: '', thinking: false, error: '', frameV: 1,
    open: @js($openDirs),
    init() {
      this.render();
      this.poll();
      window.addEventListener('beforeunload', (e) => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } });
    },
    toggle(p) { this.open = this.open.includes(p) ? this.open.filter(x => x !== p && !x.startsWith(p + '/')) : [...this.open, p]; },
    render() {
      const src = this.code || '';
      this.lines = src.split('\n').length;
      this.html = src.split('\n').map(line => {
        let out = '', last = 0, m; re.lastIndex = 0;
        while ((m = re.exec(line))) {
          if (!m[0].length) { re.lastIndex++; continue; }
          out += esc(line.slice(last, m.index));
          const call = line[m.index + m[0].length] === '(' && /^[A-Za-z_]\w*$/.test(m[0]) && !KW.test(m[0]);
          const c = call ? '#7DD3E8' : /^[a-z_]\w*$/.test(m[0]) && !KW.test(m[0]) ? null : color(m[0]);
          out += c ? '<span style="color:' + c + '">' + esc(m[0]) + '</span>' : esc(m[0]);
          last = m.index + m[0].length;
        }
        return out + esc(line.slice(last));
      }).join('\n') + '\n';
    },
    indent(e) { const ta = e.target, s = ta.selectionStart; this.code = this.code.slice(0, s) + '  ' + this.code.slice(ta.selectionEnd); this.$nextTick(() => { ta.selectionStart = ta.selectionEnd = s + 2; }); this.dirty = true; this.render(); },
    async save() {
      if (!this.path || this.saving) return;
      this.saving = true;
      try { await api(this.urls.save, { path: this.path, content: this.code }); this.dirty = false; this.frameV++; }
      catch (e) { alert(e.message); } finally { this.saving = false; }
    },
    async newFile() {
      const p = prompt('New file path (e.g. pages/about.html)'); if (!p) return;
      try { const r = await api(this.urls.save, { path: p, content: '' }); location.href = r.url; } catch (e) { alert(e.message); }
    },
    poll() {
      const busy = this.s.status === 'building' || this.s.tasks.some(t => ['queued', 'working'].includes(t.status));
      setTimeout(async () => { try { const n = await (await fetch(this.urls.state, { headers: { Accept: 'application/json' } })).json(); this.merge(n); } catch (e) {} this.poll(); }, busy ? 2500 : 10000);
    },
    merge(n) {
      const filesChanged = n.updated !== this.s.updated;
      this.s = n;
      if (filesChanged && !this.dirty && this.path) location.reload();
    },
    icon(st) { return ({ done: ['✓', '#58C98A'], working: ['●', '#A99BFF'], waiting: ['◐', '#E8B66B'], failed: ['✕', '#E5695E'] })[st] || ['○', '#44444C']; },
    scroll() { this.$nextTick(() => this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight); },
    async ask(text = null, mode = null) {
      const t = (text ?? this.draft).trim(); if (!t || this.thinking) return;
      if (this.dirty) await this.save();
      this.thinking = true; this.error = ''; this.draft = '';
      this.s.messages.push({ id: 'tmp' + Date.now(), role: 'user', text: t }); this.scroll();
      try { const n = await api(this.urls.messages, { text: (this.path ? '[Open file: ' + this.path + '] ' : '') + t, channel: 'workspace', mode: mode || this.mode }); this.s = n; }
      catch (e) { this.error = e.message; this.s.messages.pop(); this.draft = t; }
      finally { this.thinking = false; this.scroll(); }
    },
    async decide(which) { try { this.s = await api(this.s.pending[which], { channel: 'workspace' }); if (which === 'approve') location.reload(); } catch (e) { this.error = e.message; } },
    async undo() { try { this.s = await api(this.urls.undo, { channel: 'workspace' }); location.reload(); } catch (e) { this.error = e.message; } },
  };
}
</script>
@endpush
