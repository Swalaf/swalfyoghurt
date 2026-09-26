<div style="max-width:1200px;margin:0 auto;padding:20px 24px;display:flex;gap:16px;flex-wrap:wrap;align-items:center;font-size:12.5px;color:#6E6E79">
  <a href="{{ route('terms') }}" style="color:#8A8A94">{{ __('Terms') }}</a>
  <a href="{{ route('privacy') }}" style="color:#8A8A94">{{ __('Privacy') }}</a>
  @include('partials.language-switcher')
</div>
