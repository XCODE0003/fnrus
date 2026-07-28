{{-- ТЗ §10 — one glyph per cheat status, so a status reads without relying
     on colour alone. $icon comes from StatusCheat::statusMeta()['icon'].
     Keep this markup in sync with the JS copy in public/assets/js/app.js
     (pmStatusIcon), which rebuilds the same chips after an AJAX search. --}}
@php $s = $size ?? 14; @endphp
@if($icon === 'check')
<svg class="status__icon" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12.5l5.5 5.5L20 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif($icon === 'cross')
<svg class="status__icon" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>
@elseif($icon === 'refresh')
<svg class="status__icon" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.4-5.7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><path d="M20 4v5h-5" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
@elseif($icon === 'warning')
<svg class="status__icon" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5L21 19H3L12 4.5z" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/><path d="M12 10v4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="16.6" r="1.1" fill="currentColor"/></svg>
@else
<svg class="status__icon" width="{{ $s }}" height="{{ $s }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.2"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.3c-.7.3-1 .9-1 1.6v.3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="17" r="1.1" fill="currentColor"/></svg>
@endif
