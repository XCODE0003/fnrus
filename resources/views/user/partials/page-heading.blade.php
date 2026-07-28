{{--
  Unified page heading (ТЗ §6) — breadcrumbs + icon pill + big H1 + muted subtitle.
  Used by /status, /reviews and /about so the three pages share one look.

  Params:
    $crumbs    array of ['label' => string, 'url' => string|null]; the last item
               renders unlinked as the current page.
    $badge     pill label (uppercased by CSS, so pass normal case)
    $badgeIcon icon path for the pill (optional)
    $title     heading HTML — printed raw, the ru/en strings carry <br> and
               <span class="section-caption__accent">
    $subtitle  muted line under the heading (optional)
    $modifier  extra class on the wrapper (optional)

  A dedicated .page-head__* namespace on purpose: the legacy .section-caption /
  .reviews-page__title rules cap the font size and force uppercase.
--}}
@php
    $crumbs    = $crumbs    ?? [];
    $badgeIcon = $badgeIcon ?? '/assets/img/hero-crown.svg';
    $modifier  = $modifier  ?? '';
@endphp
<div class="page-head {{ $modifier }}">
    @if(count($crumbs))
        <nav class="page-head__crumbs" aria-label="breadcrumb">
            @foreach($crumbs as $i => $crumb)
                @if($i > 0)<span class="page-head__crumbs-sep">/</span>@endif
                @if(!empty($crumb['url']) && $i < count($crumbs) - 1)
                    <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                @else
                    <span class="page-head__crumbs-current" aria-current="page">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    @isset($badge)
        <div class="page-head__badge">
            <span class="page-head__badge-icon"><img src="{{ $badgeIcon }}" alt="" decoding="async"></span>
            <span class="page-head__badge-text">{{ $badge }}</span>
        </div>
    @endisset

    @isset($title)
        <h1 class="page-head__title">{!! $title !!}</h1>
    @endisset

    @if(!empty($subtitle))
        <p class="page-head__subtitle">{{ $subtitle }}</p>
    @endif
</div>
