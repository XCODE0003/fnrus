@extends('user.layouts.main')
@section('content')
<main class="game">
    <section class="game-hero">
        <div class="content game-hero__inner">
            <div class="game-hero__text">
                {{-- Breadcrumb --}}
                @php $tp = preg_split('/(?=\()/u', trim($title), 2); @endphp
                <nav class="game-hero__crumbs" aria-label="breadcrumb">
                    <a href="/">{{ __('site.item_home') }}</a>
                    <span class="game-hero__crumbs-sep">/</span>
                    <a href="/games">{{ __('site.menu_catalog') ?? 'Магазин' }}</a>
                    <span class="game-hero__crumbs-sep">/</span>
                    <span class="game-hero__crumbs-current">{{ trim($tp[0]) }}</span>
                </nav>

                {{-- Title with dim parenthetical subtitle --}}
                {{-- Title = category name only (no hard-coded "Читы"); the
                     parenthetical part "(CS2)" comes from the category name
                     itself in admin and is dimmed for SEO. --}}
                <h1 class="game-hero__title">
                    {{ trim($tp[0]) }}
                    @if(!empty($tp[1]))
                        <span class="game-hero__title-dim">{{ $tp[1] }}</span>
                    @endif
                </h1>

                {{-- Description --}}
                @php $heroDesc = trim(strip_tags($description ?? '')); @endphp
                @if($heroDesc !== '')
                    <p class="game-hero__desc">{{ $heroDesc }}</p>
                @endif

                {{-- Stats row: count · undetected · delivery · support --}}
                @php $cnt = 0; foreach(($categories ?: []) as $c) { $cnt += count($c['products']); } @endphp
                <ul class="game-hero__stats">
                    <li class="game-hero__stat">
                        <span>@plural($cnt, __('site.game_product_one'), __('site.game_product_few'), __('site.game_product_many'))</span>
                    </li>
                    <li class="game-hero__stat game-hero__stat--badge">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z" stroke="#8af59b" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12l2 2 4-4" stroke="#8af59b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Undetected</span>
                    </li>
                    <li class="game-hero__stat">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ __('site.delivery_instant') ?? 'Мгновенная доставка' }}</span>
                    </li>
                    <li class="game-hero__stat">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 12a9 9 0 1118 0v3a3 3 0 01-3 3h-1v-7h4M3 12v3a3 3 0 003 3h1v-7H3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ __('site.support_24_7') ?? 'Поддержка 24/7' }}</span>
                    </li>
                </ul>

                {{-- Back link --}}
                <a href="/games" class="game-hero__back">
                    <span class="game-hero__back__icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="game-hero__back__label">{{ __('site.game_back') }}</span>
                </a>
            </div>
            <div class="game-hero__image">
                <img src="{{ !empty($hero_image) ? '/'.ltrim($hero_image, '/') : '/assets/img/pubg.png' }}" alt="" onerror="this.onerror=null;this.src='/assets/img/pubg.png'">
            </div>
        </div>
    </section>

    @if (!$categories)
    <div class="content">
        <div class="profile__empty-block" style="padding: 50px 0">
            <p class="profile__empty-block__caption">{{ __('site.section_category_not_found') }}</p>
        </div>
    </div>
    @else
    @foreach($categories as $card)
    <section class="game-list">
        <div class="content game-list__head">
            <div class="game-list__platform">
                @if($card['title'] == 'iOS')
                <img class="game-list__platform-icon" src="/assets/img/icon_apple.svg" alt="">
                @elseif($card['title'] == 'Android')
                <img class="game-list__platform-icon" src="/assets/img/icon_android.svg" alt="">
                @elseif(in_array($card['title'], ['Пк', 'Gameloop']))
                <img class="game-list__platform-icon" src="/assets/img/icon_windows.svg" alt="">
                @elseif($card['title'] == 'Эмуляторы')
                <img class="game-list__platform-icon" src="/assets/img/icon_idk.svg" alt="">
                @endif
                <span>{{ $card['title'] }}</span>
            </div>
            <div class="slider-arrows game-list__arrows">
                <button class="slider-arrows__arrow slider-arrows__prev"></button>
                <button class="slider-arrows__arrow slider-arrows__next"></button>
            </div>
        </div>
        <div class="content">
            <div class="swiper game-cheats-slider js-carousel" data-carousel="game-cheats">
                <div class="swiper-wrapper">
                    @foreach($card['products'] as $p)
                    @php
                    $hack_status = \App\Models\HackStatus::getByID($p['hack_status']);
                    $rootText = ($hack_status && $hack_status->title_pub != '') ? $hack_status->title_pub : '';
                    $rootGreen = $rootText !== '' && mb_stripos($rootText, 'без') !== false;
                    @endphp
                    <a href="/{{ $alias }}/{{ $p['alias'] }}" class="swiper-slide game-card">
                        <div>
                            <div class="game-card__top">
                                <div class="game-card__logo">
                                    <img src="/i{{ $p['image_site'] }}" alt="">
                                </div>
                                <span class="game-card__status cheat-status_{{ $p['status_class'] }}">
                                    <svg width="11" height="13" viewBox="0 0 10.0001 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.96538 4.8176C9.92869 4.72405 9.86468 4.64374 9.78167 4.58712C9.69866 4.5305 9.60052 4.50021 9.50004 4.50019H6.1234L6.98814 0.608664C7.04814 0.339114 6.87832 0.07196 6.60877 0.0119537C6.52331 -0.00707642 6.43436 -0.00333246 6.35081 0.022811C6.26726 0.0489544 6.19204 0.0965806 6.13268 0.160924L0.132787 6.66081C-0.0546344 6.8636 -0.0421368 7.17992 0.160657 7.36734C0.253094 7.45277 0.374358 7.50019 0.500228 7.50011H3.30659L2.02586 11.3418C1.93851 11.6038 2.08008 11.8869 2.34205 11.9743C2.42719 12.0027 2.5184 12.0076 2.6061 11.9886C2.69381 11.9696 2.77478 11.9273 2.84052 11.8662L9.8404 5.36632C9.914 5.29804 9.9653 5.20914 9.9876 5.11125C10.0099 5.01336 10.0021 4.91102 9.96538 4.8176Z" fill="currentColor" />
                                    </svg>

                                    {{ $p['status_title'] }}
                                </span>
                            </div>
                            <p class="game-card__name">{{ $p['title'] }}</p>
                            <ul class="game-card__list">
                                @foreach(json_decode($p['advantages'], true) ?? [] as $a)
                                <li>{{ $a }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="game-card__bottom">
                            @if($rootText !== '')
                            <span class="game-card__tag {{ $rootGreen ? 'game-card__tag--green' : 'game-card__tag--purple' }}">
                                <img src="/assets/img/{{ $rootGreen ? 'catalog-tag-green' : 'catalog-tag-purple' }}.svg" alt="">
                                {{ $rootText }}
                            </span>
                            @else
                            <span></span>
                            @endif
                            @if(!empty($p['price']))
                            <span class="game-card__price">{{ __('site.game_price_from') }} {{ $p['price'] }}₽</span>
                            @else
                            <span></span>
                            @endif
                        </div>

                        <div class="game-card__hover">
                            <span class="game-card__hover-btn">{{ __('site.btn_more') }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endforeach
    @endif




</main>
@endsection