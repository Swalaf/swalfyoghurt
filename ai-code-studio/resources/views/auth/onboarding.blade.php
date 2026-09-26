@extends('auth.layout')
@section('title', 'Welcome')
@section('screen')
<form method="POST" action="{{ route('onboarding') }}" x-data="{ level: {{ $level }} }" style="display:flex;flex-direction:column;gap:18px">
  @csrf
  <input type="hidden" name="level" :value="level">
  <div style="font-family:'Geist Mono',ui-monospace,monospace;font-size:11.5px;color:#6E6E79">ONE QUICK QUESTION</div>
  <div><h1 style="margin:0;font-size:26px;font-weight:600;letter-spacing:-0.02em">How much coding do you do?</h1><p style="margin:6px 0 0;color:#8A8A94">We’ll set up the workspace to match. Change it any time.</p></div>
  <div style="display:flex;flex-direction:column;gap:8px">
    @foreach (\App\Http\Controllers\Auth\OnboardingController::LEVELS as $i => [$t, $d])
      <div @click="level = {{ $i }}" :style="{ borderColor: level === {{ $i }} ? 'var(--accent-line)' : '#1F1F26', background: level === {{ $i }} ? '#110F1A' : '#0D0D10' }" style="border:1px solid #1F1F26;border-radius:9px;padding:13px 14px;cursor:pointer;display:flex;gap:12px;align-items:flex-start">
        <span :style="{ borderColor: level === {{ $i }} ? 'var(--accent)' : '#3A3A44' }" style="width:16px;height:16px;border-radius:50%;border:1px solid #3A3A44;margin-top:2px;display:grid;place-items:center;flex:none"><span :style="{ background: level === {{ $i }} ? 'var(--accent)' : 'transparent' }" style="width:8px;height:8px;border-radius:50%"></span></span>
        <div><div style="font-weight:500">{{ $t }}</div><div style="color:#8A8A94;font-size:13px;margin-top:3px">{{ $d }}</div></div>
      </div>
    @endforeach
  </div>
  <button class="btn primary lg">Continue</button>
</form>
@endsection
