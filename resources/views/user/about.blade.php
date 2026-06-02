@extends('user.layouts.main')
@section('content')
<main class="about-page">
    <section class="about-heading-section">
        <div class="content about-heading-section__container">
            <div class="decor about-heading-section__decor1"></div>
            <div class="decor about-heading-section__decor2"></div>
            <div class="decor about-heading-section__decor3"></div>
            <div class="section-category">
                <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                <span class="section-category__text">{{ __('site.about_store_label') }}</span>
            </div>
            <p class="section-caption about-heading-section__section-caption">{!! __('site.about_heading') !!} <span class="about-heading-section__get-result">{{ __('site.about_get_result') }}</span></p>
            <p class="section-subcaption about-heading-section__section-subcaption">{{ __('site.about_description') }}</p>
            <button href="/" class="btn about-heading-section__btn" data-scroll=".catalog">
                <span class="btn__icon"><img src="/assets/img/icon_1.svg" alt=""></span>
                {{ __('site.btn_catalog_cheats') }}
            </button>
        </div>
    </section>
    <section class="about-section">
        <div class="content">
            <div class="about-section__block about-section__stats">
                <div class="about-section__stat">
                    <p>20к+</p>
                    <span>{{ __('site.about_purchases') }}</span>
                </div>
                <div class="about-section__stat">
                    <p>12к+</p>
                    <span>{{ __('site.about_reviews') }}</span>
                </div>
                <div class="about-section__stat">
                    <p>17к+</p>
                    <span>{{ __('site.about_satisfied_customers') }}</span>
                </div>
                <div class="about-section__stat">
                    <p>15+</p>
                    <span>{{ __('site.about_active_software') }}</span>
                </div>
            </div>
            <div class="about-section__block about-section__contacts">
                @foreach($about_items as $item)
                <a target="_blank" href="{{ $item->url }}" class="about-contact">
                    <span class="about-contact__icon">
                        @include('user.partials.icon', ['icon' => (in_array($item->icon, ['telegram','discord','vk','youtube','instagram','star']) ? $item->icon : 'link'), 'color' => '#ac8dff', 'size' => 22])
                    </span>
                    <span class="about-contact__body">
                        <span class="about-contact__label">{{ $item->localized_label }}</span>
                        <span class="about-contact__value">{{ $item->url_text }}</span>
                    </span>
                </a>
                @endforeach
            </div>
            <div class="about-section__block about-section__history">
                <p class="about-section__history__caption">{{ __('site.about_history_title') }} </p>
                <div class="swiper about-section__history__slider js-carousel" data-carousel="history">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide about-section__history__slide">
                            <div class="point"></div>
                            <p class="years">2023-2024</p>
                            <p class="record">{{ __('site.about_history_updates') }}</p>
                            <p class="small-text">{{ __('site.about_history_updates_text') }}</p>
                        </div>
                        <div class="swiper-slide about-section__history__slide">
                            <div class="point"></div>
                            <p class="years">2022-2023</p>
                            <p class="record">{{ __('site.about_history_expansion') }}</p>
                            <p class="small-text">{{ __('site.about_history_expansion_text') }}</p>
                        </div>
                        <div class="swiper-slide about-section__history__slide">
                            <div class="point"></div>
                            <p class="years">2021-2022</p>
                            <p class="record">{{ __('site.about_history_first_website') }}</p>
                            <p class="small-text">{{ __('site.about_history_first_website_text') }}</p>
                        </div>
                        <div class="swiper-slide about-section__history__slide">
                            <div class="point"></div>
                            <p class="years">2020-2021</p>
                            <p class="record">{{ __('site.about_history_beginning') }}</p>
                            <p class="small-text">{{ __('site.about_history_beginning_text') }}</p>
                        </div>
                    </div>
                    <button class="about-section__history__slider-btn about-section__history__prev-btn"></button>
                    <button class="about-section__history__slider-btn about-section__history__next-btn"></button>
                </div>
            </div>
            <p class="about-section__text">{!! __('site.about_long_text') !!}</p>
        </div>
    </section>
    <section class="catalog">
        <div class="content">
            <div class="section-caption-container">
                <div class="section-caption-container__inner">
                    <p class="section-caption">{{ __('site.about_games_caption') }}:</p>
                </div>
            </div>
            <div class="catalog__cards-container">
                @foreach($categories as $card)
                <a href="/{{ $card->alias }}" class="catalog-card" data-platforms="{{ $card->platforms ?? 'pc mobile' }}">
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
        </div>
    </section>
</main>
@endsection
