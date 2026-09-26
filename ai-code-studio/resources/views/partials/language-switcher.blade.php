<form method="GET" style="display:inline-flex;align-items:center;gap:6px;margin:0">
  <span aria-hidden="true">🌐</span>
  <select name="lang" onchange="this.form.submit()" aria-label="{{ __('Language') }}" style="background:transparent;border:1px solid #26262E;border-radius:6px;color:#9A9AA5;font-size:12.5px;padding:3px 6px">
    @foreach (config('studio.locales') as $code => $label)<option value="{{ $code }}" @selected(app()->getLocale() === $code) style="background:#111115">{{ $label }}</option>@endforeach
  </select>
</form>
