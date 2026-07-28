@extends('user.layouts.main')
@section('content')
<main class="status-page">
    <section class="status-heading-section">
        <div class="content status-heading-section__container">
            @include('user.partials.page-heading', [
                'crumbs' => [
                    ['label' => __('site.item_home'), 'url' => '/'],
                    ['label' => __('site.crumb_status')],
                ],
                'badge' => __('site.status_badge_label'),
                'badgeIcon' => '/assets/img/hero-crown.svg',
                'title' => __('site.section_statuses_caption'),
                'subtitle' => __('site.section_statuses_subcaption'),
            ])
            <div class="attention status-heading-section__attention">
                <svg width="18" height="21" viewBox="0 0 18 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_435_92979)">
                        <path d="M8.99926 15.3359C8.49604 15.3359 8.07422 15.7578 8.07422 16.261C8.07422 16.7642 8.49604 17.186 8.99926 17.186C9.48399 17.186 9.92431 16.7642 9.90211 16.2832C9.92431 15.7541 9.50619 15.3359 8.99926 15.3359Z" fill="#F5C48A"/>
                        <path d="M17.562 18.6139C18.1429 17.6111 18.1466 16.416 17.5694 15.4169L11.7749 5.38204C11.2014 4.37189 10.1653 3.77246 9.00348 3.77246C7.84162 3.77246 6.80557 4.37559 6.23204 5.37834L0.430163 15.4243C-0.147064 16.4345 -0.143364 17.637 0.441264 18.6398C1.01849 19.6314 2.05084 20.2272 3.2053 20.2272H14.7795C15.9376 20.2272 16.9774 19.624 17.562 18.6139ZM16.3039 17.8886C15.982 18.4437 15.4122 18.773 14.7758 18.773H3.2016C2.57257 18.773 2.00644 18.4511 1.69192 17.9071C1.37371 17.3558 1.37001 16.6972 1.68822 16.1422L7.4901 6.09987C7.80462 5.54855 8.36705 5.22293 9.00348 5.22293C9.63621 5.22293 10.2023 5.55225 10.5168 6.10357L16.315 16.1459C16.6258 16.6861 16.6221 17.3373 16.3039 17.8886Z" fill="#F5C48A"/>
                        <path d="M8.77077 8.84155C8.33045 8.96735 8.05664 9.36697 8.05664 9.85169C8.07884 10.144 8.09734 10.44 8.11954 10.7323C8.18245 11.8461 8.24535 12.9376 8.30825 14.0514C8.33045 14.4288 8.62277 14.7026 9.00019 14.7026C9.3776 14.7026 9.67362 14.4103 9.69212 14.0292C9.69212 13.7998 9.69212 13.5889 9.71432 13.3558C9.75502 12.6416 9.79942 11.9275 9.84013 11.2134C9.86233 10.7508 9.90303 10.2883 9.92523 9.82579C9.92523 9.65928 9.90303 9.51128 9.84013 9.36327C9.65142 8.94885 9.2111 8.73794 8.77077 8.84155Z" fill="#F5C48A"/>
                    </g>
                    <defs>
                        <clipPath id="clip0_435_92979">
                            <rect width="18" height="21" fill="white"/>
                        </clipPath>
                    </defs>
                </svg>
                {{ __('site.section_statuses_attention') }}
            </div>
            <div class="status-heading-section__filter">
                <input type="text" name="prevent_autofill_u" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="password" name="prevent_autofill_p" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="search" placeholder="{{ __('site.section_statuses_placeholder_search') }}" id="search_query" name="site_search" autocomplete="new-password">
                <div class="st-select" id="status-select" data-value="0">
                    <button type="button" class="st-select__trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="st-select__bar st-select__bar--all"></span>
                        <span class="st-select__value">{{ __('site.section_statuses_status_0') }}</span>
                        <svg class="st-select__chev" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="st-select__menu" role="listbox" hidden>
                        <button type="button" class="st-select__opt is-active" role="option" data-value="0" data-mod="all" data-label="{{ __('site.section_statuses_status_0') }}" onclick="changeStatusQuery(0)">
                            <span class="st-select__bar st-select__bar--all"></span>
                            <span class="st-select__opt-label">{{ __('site.section_statuses_status_0') }}</span>
                        </button>
                        <button type="button" class="st-select__opt" role="option" data-value="1" data-mod="recommend" data-label="{{ __('site.section_statuses_status_1') }}" onclick="changeStatusQuery(1)">
                            <span class="st-select__bar st-select__bar--recommend"></span>
                            <span class="st-select__opt-label">{{ __('site.section_statuses_status_1') }}</span>
                        </button>
                        <button type="button" class="st-select__opt" role="option" data-value="4" data-mod="risk" data-label="{{ __('site.section_statuses_status_4') }}" onclick="changeStatusQuery(4)">
                            <span class="st-select__bar st-select__bar--risk"></span>
                            <span class="st-select__opt-label">{{ __('site.section_statuses_status_4') }}</span>
                        </button>
                        <button type="button" class="st-select__opt" role="option" data-value="3" data-mod="on-update" data-label="{{ __('site.section_statuses_status_3') }}" onclick="changeStatusQuery(3)">
                            <span class="st-select__bar st-select__bar--on-update"></span>
                            <span class="st-select__opt-label">{{ __('site.section_statuses_status_3') }}</span>
                        </button>
                        <button type="button" class="st-select__opt" role="option" data-value="2" data-mod="not-recommend" data-label="{{ __('site.section_statuses_status_2') }}" onclick="changeStatusQuery(2)">
                            <span class="st-select__bar st-select__bar--not-recommend"></span>
                            <span class="st-select__opt-label">{{ __('site.section_statuses_status_2') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="cheat-statuses">
        <div class="content">
            <div class="cheat-statuses__header">
                <p>{{ __('site.section_statuses_header_title') }}</p>
                <div class="cheat-statuses__header__all-statuses">
                    @foreach([1 => 'check', 4 => 'warning', 3 => 'refresh', 2 => 'cross'] as $__st => $__ic)
                        @php $__m = \App\Models\StatusCheat::statusMeta($__st); @endphp
                        <span class="status _{{ $__m['key'] }}">
                            @include('user.partials.status-icon', ['icon' => $__m['icon']])
                            <span class="status__label">{{ $__m['label'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
            <div id="results_empty"></div>
            <div class="cheat-statuses__container" id="results_search">
                @foreach($categories as $card)
                    <div class="game-status-block">
                        <div class="game-status-block__info">
                            <p class="game-status-block__name">{{ $card['title'] }}</p>
                            <div class="game-status-block__cheats">
                                @foreach($card['cheats'] as $c)
                                    <span class="status _{{ $c['status'] }}">
                                        @include('user.partials.status-icon', ['icon' => $c['status_icon'] ?? 'question'])
                                        <span class="status__name">{{ $c['title'] }}</span>
                                        <span class="status__label">{{ $c['status_label'] ?? '' }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>
</main>
<script>
(function(){
    var sel = document.getElementById('status-select');
    if (!sel) return;
    var trigger = sel.querySelector('.st-select__trigger');
    var menu = sel.querySelector('.st-select__menu');
    var valueLbl = sel.querySelector('.st-select__value');
    var triggerBar = trigger.querySelector('.st-select__bar');

    function close(){ sel.classList.remove('is-open'); menu.hidden = true; trigger.setAttribute('aria-expanded','false'); }
    function open(){ sel.classList.add('is-open'); menu.hidden = false; trigger.setAttribute('aria-expanded','true'); }

    trigger.addEventListener('click', function(e){
        e.stopPropagation();
        if (sel.classList.contains('is-open')) close(); else open();
    });
    menu.addEventListener('click', function(e){
        var opt = e.target.closest('.st-select__opt');
        if (!opt) return;
        var label = opt.getAttribute('data-label');
        var mod = opt.getAttribute('data-mod');
        valueLbl.textContent = label;
        triggerBar.className = 'st-select__bar st-select__bar--' + mod;
        menu.querySelectorAll('.st-select__opt').forEach(function(o){ o.classList.toggle('is-active', o === opt); });
        sel.setAttribute('data-value', opt.getAttribute('data-value'));
        close();
        // changeStatusQuery already invoked via inline onclick
    });
    document.addEventListener('click', function(e){ if (!sel.contains(e.target)) close(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
})();
</script>
@endsection
