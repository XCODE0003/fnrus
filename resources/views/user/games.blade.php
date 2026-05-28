@extends('user.layouts.main')
@section('content')
<main class="games-page">
    <section class="games-catalog">
        <div class="content">
            <div class="games-catalog__head">
                <div class="catalog__badge">
                    <span class="catalog__badge-icon"><img src="/assets/img/hero-crown.svg" alt=""></span>
                    <span class="catalog__badge-text">{{ __('site.games_page_title') }}</span>
                </div>
                <h1 class="games-catalog__title">{{ __('site.games_title') }}</h1>
                <p class="games-catalog__subtitle">{{ __('site.games_subtitle') }}</p>
            </div>

            <div class="games-catalog__controls">
                <div class="catalog__tabs games-catalog__tabs" style="margin-top: 0px;">
                    <button type="button" class="catalog__tab is-active" data-platform="all">
                        <span class="catalog__tab-ico"><img src="/assets/img/catalog-tab-all.svg" alt=""></span>
                        {{ __('site.catalog_tab_all') }}
                    </button>
                    <button type="button" class="catalog__tab" data-platform="pc">
                        <span class="catalog__tab-ico"><img src="/assets/img/catalog-tab-pc.svg" alt=""></span>
                        {{ __('site.catalog_tab_pc') }}
                    </button>
                    <button type="button" class="catalog__tab" data-platform="mobile">
                        <span class="catalog__tab-ico"><img src="/assets/img/catalog-tab-mobile.svg" alt=""></span>
                        {{ __('site.catalog_tab_mobile') }}
                    </button>
                </div>
                <div class="games-catalog__tools">
                    <label class="games-search">
                        <img class="games-search__icon" src="/assets/img/header-search.svg" alt="">
                        <input type="text" id="games-search" class="games-search__input" placeholder="{{ __('site.games_search_placeholder') }}" autocomplete="off">
                    </label>
                    <div class="select _unfold games-select">
                        <select id="games-sort" class="games-sort">
                            <option value="popular">{{ __('site.games_sort_popular') }}</option>
                            <option value="new">{{ __('site.games_sort_new') }}</option>
                            <option value="name">{{ __('site.games_sort_name') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="catalog__cards-container" id="games-grid">
                @foreach($categories as $card)
                <a href="/{{ $card->alias }}" class="catalog-card"
                   data-platforms="{{ $card->platforms ?? 'pc mobile' }}"
                   data-name="{{ \Str::lower($card->title) }}"
                   data-count="{{ (int) $card->count_products }}"
                   data-id="{{ (int) $card->id }}">
                    <div class="catalog-card__img" role="img" aria-label="{{ $card->title }}" style="background-image:url('/{{ $card->image_site }}')"></div>
                    <div class="catalog-card__body">
                        <div class="catalog-card__name">{{ $card->title }}</div>
                        <div class="catalog-card__tags">
                            <span class="catalog-card__tag catalog-card__tag--game">
                                <img src="/assets/img/catalog-tag-purple.svg" alt="">
                                {{ \Str::before($card->title, ' ') }}
                            </span>
                            <span class="catalog-card__tag catalog-card__tag--count">
                                <img src="/assets/img/catalog-tag-green.svg" alt="">
                                @plural($card->count_products, __('site.text_x_cheats_one'), __('site.text_x_cheats_few'), __('site.text_x_cheats_many'))
                            </span>
                        </div>
                        @if(!empty($card->description))
                        <p class="catalog-card__desc">{{ \Str::limit(strip_tags($card->description), 90) }}</p>
                        @endif
                        <span class="catalog-card__more">
                            {{ __('site.catalog_more') }}
                            <img src="/assets/img/catalog-more-arrow.svg" alt="">
                        </span>
                    </div>
                </a>
                @endforeach
            </div>
            <p class="games-catalog__empty" id="games-empty" hidden>{{ __('site.section_category_not_found') }}</p>
        </div>
    </section>
</main>
<script>
    (function() {
        var grid = document.getElementById('games-grid');
        if (!grid) return;
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.catalog-card'));
        var tabs = document.querySelectorAll('.games-catalog__tabs .catalog__tab');
        var search = document.getElementById('games-search');
        var sort = document.getElementById('games-sort');
        var empty = document.getElementById('games-empty');
        var platform = 'all';

        function render() {
            var q = (search.value || '').toLowerCase().trim();
            var visible = cards.filter(function(c) {
                var pl = (c.getAttribute('data-platforms') || '').split(' ');
                var okPlatform = platform === 'all' || pl.indexOf(platform) !== -1;
                var okSearch = !q || (c.getAttribute('data-name') || '').indexOf(q) !== -1;
                return okPlatform && okSearch;
            });
            var mode = sort.value;
            visible.sort(function(a, b) {
                if (mode === 'name') {
                    return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
                }
                if (mode === 'new') {
                    return (+b.getAttribute('data-id')) - (+a.getAttribute('data-id'));
                }
                return (+b.getAttribute('data-count')) - (+a.getAttribute('data-count'));
            });
            cards.forEach(function(c) { c.style.display = 'none'; });
            visible.forEach(function(c) { c.style.display = ''; grid.appendChild(c); });
            empty.hidden = visible.length > 0;
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                platform = tab.getAttribute('data-platform');
                render();
            });
        });
        search.addEventListener('input', render);
        sort.addEventListener('change', render);
        render();
    })();
</script>
@endsection
