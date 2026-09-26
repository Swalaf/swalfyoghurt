<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@hasSection('title')@yield('title') · @endif{{ $brand }}</title>
@php($fav = \App\Support\Settings::get('brand_favicon'))
@if ($fav)<link rel="icon" href="{{ $fav }}">@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/studio.css') }}?v={{ config('studio.version') }}">
<style>:root{--accent:{{ $accent }}}</style>
@stack('head')
<script defer src="{{ asset('js/alpine.min.js') }}?v=3.14.8"></script>
</head>
<body>
@yield('body')
@include('partials.toast')
<script>
window.csrf = document.querySelector('meta[name=csrf-token]').content;
window.api = async (url, body = null, method = 'POST') => {
  const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrf }, body: body ? JSON.stringify(body) : null });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(data.message || 'Something went wrong'), { data, status: res.status });
  return data;
};
</script>
@stack('scripts')
</body>
</html>
