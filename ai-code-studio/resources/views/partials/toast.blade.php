@php($msg = session('status') ?? session('error'))
@if ($msg)
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition.opacity class="toast {{ session('error') ? 'bad' : 'ok' }}">
  <span>{{ session('error') ? '!' : '✓' }}</span><span style="flex:1">{{ $msg }}</span><span @click="show = false" style="cursor:pointer;opacity:.7">×</span>
</div>
@endif
