<div x-data="{ show: (() => { try { return !localStorage.getItem('cookie-ok') } catch (e) { return false } })() }" x-show="show" x-cloak class="d-flex" style="position:fixed;left:16px;bottom:16px;z-index:70;max-width:min(420px,calc(100vw - 32px));background:#111115;border:1px solid #26262E;border-radius:10px;padding:12px 14px;gap:12px;align-items:center;font-size:12.5px;line-height:1.5;color:#B4B4BE;box-shadow:0 16px 40px rgba(0,0,0,.45)">
  <span>{{ __('We only use cookies needed to keep you signed in and secure — no tracking.') }} <a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a></span>
  <button @click="show = false; try { localStorage.setItem('cookie-ok', '1') } catch (e) {}" class="btn sm">{{ __('OK') }}</button>
</div>
