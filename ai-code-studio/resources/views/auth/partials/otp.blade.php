{{-- Six-box code input backed by one hidden field. --}}
<div x-data="{ d: ['', '', '', '', '', ''], get code() { return this.d.join('') } }" style="display:flex;flex-direction:column;gap:18px">
  <input type="hidden" name="code" :value="code">
  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px" @paste.prevent="const t = ($event.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split(''); t.forEach((v, i) => d[i] = v); $nextTick(() => $refs['b' + Math.min(t.length, 5)].focus())">
    @for ($i = 0; $i < 6; $i++)
      <input x-ref="b{{ $i }}" x-model="d[{{ $i }}]" inputmode="numeric" maxlength="1" autocomplete="one-time-code" @if ($i === 0) autofocus @endif
        @input="d[{{ $i }}] = d[{{ $i }}].replace(/\D/g, ''); if (d[{{ $i }}] && {{ $i }} < 5) $refs.b{{ $i + 1 }}.focus()"
        @keydown.backspace="if (!d[{{ $i }}] && {{ $i }} > 0) $refs.b{{ max(0, $i - 1) }}.focus()"
        :style="{ borderColor: d[{{ $i }}] || code.length === {{ $i }} ? 'var(--accent)' : '#26262E' }"
        style="height:50px;border-radius:8px;border:1px solid #26262E;background:#111115;text-align:center;font-family:'Geist Mono',ui-monospace,monospace;font-size:20px;color:#F2F2F5;outline:none;width:100%">
    @endfor
  </div>
  @error('code')<div class="err">{{ $message }}</div>@enderror
  <button class="btn primary lg" :disabled="code.length < 6">{{ $button }}</button>
</div>
