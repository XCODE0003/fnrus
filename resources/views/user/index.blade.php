@extends('user.layouts.main')
@section('content')
<main class="main">
    <section class="hero">
        <div class="content hero__content">
            <div class="hero__col">
                <div class="hero__badge">
                    <span class="hero__badge-icon"><img src="/assets/img/hero-crown.svg" alt=""></span>
                    <span class="hero__badge-text">{{ __('site.hero_badge') }}</span>
                </div>
                <h1 class="hero__title">{{ __('site.hero_title_1') }}<span class="hero__title-bolt"><img src="/assets/img/icon_lightning.svg" alt=""></span>{!! __('site.hero_title_2') !!}</h1>
                <p class="hero__subtitle">{{ __('site.hero_subtitle') }}</p>
                <div class="hero__actions">
                    <button type="button" class="hero__btn-catalog" data-scroll=".catalog">
                        <span class="hero__btn-catalog__tab"><img src="/assets/img/hero-catalog.svg" alt=""></span>
                        <span class="hero__btn-catalog__label">{{ __('site.hero_btn_catalog') }}</span>
                    </button>
                    <button type="button" class="hero__btn-register" data-popup="register" id="register">
                        <span class="hero__btn-register__icon"><img src="/assets/img/icon_2.svg" alt=""></span>
                        <span class="name">{{ __('site.hero_btn_register') }}</span>
                    </button>
                </div>
            </div>
            <div class="hero__image">
                <img src="/assets/img/spider-hero.png" alt="">
            </div>
        </div>
    </section>
    <section class="catalog" id="catalog">
        <div class="content">
            <div class="catalog__top">
                <div class="catalog__head">
                    <div class="catalog__badge">
                        <span class="catalog__badge-icon"><img src="/assets/img/hero-crown.svg" alt=""></span>
                        <span class="catalog__badge-text">{{ __('site.text_best_soft') }}</span>
                    </div>
                    <h2 class="catalog__title">{{ __('site.text_exclusive_cheats') }}</h2>
                    <p class="catalog__subtitle">{{ __('site.text_exclusive_cheats_caption') }}</p>
                </div>
            </div>
            <div class="catalog__cards-container">
                @foreach($categories as $card)
                <a href="/{{ $card->alias }}" class="catalog-card" data-platforms="{{ $card->platforms ?? 'pc mobile' }}">
                    <div class="catalog-card__img">
                        <img src="/{{ $card->image_site }}" alt="" loading="lazy">
                    </div>
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
            <div class="catalog__more-wrap">
                <a href="/games" class="section-more-btn">
                    {{ __('site.btn_all_catalog') }}
                    <img src="/assets/img/catalog-more-arrow.svg" alt="">
                </a>
            </div>
        </div>
    </section>
    <section class="section2">
        <div class="content section2__inner">
            <div class="section2__badge">
                <span class="section2__badge-icon"><img src="/assets/img/s2-badge-icon.svg" alt=""></span>
                <span class="section2__badge-text">{{ __('site.text_why_choose_us') }}</span>
            </div>
            <h2 class="section2__title">
                <span>{{ __('site.s2_title_1') }}</span>
                <span class="section2__title-spark"><img src="/assets/img/s2-sparkle.svg" alt=""></span>
                <span>{{ __('site.s2_title_2') }}</span>
            </h2>
            <div class="section2__grid">
                @foreach([
                ['icon' => 's2-card1', 'title' => 'text_unique_features', 'text' => 'text_unique_features_text'],
                ['icon' => 's2-card2', 'title' => 'text_good_defence', 'text' => 'text_good_defence_text'],
                ['icon' => 's2-card3', 'title' => 'text_updates', 'text' => 'text_updates_text'],
                ['icon' => 's2-card4', 'title' => 'text_support', 'text' => 'text_support_text'],
                ] as $b)
                <div class="s2-card">
                    <div class="s2-card__icon">
                        <span class="s2-card__icon-inner"><img src="/assets/img/{{ $b['icon'] }}.svg" alt=""></span>
                    </div>
                    <p class="s2-card__title">{{ __('site.'.$b['title']) }}</p>
                    <p class="s2-card__text">{{ __('site.'.$b['text']) }}</p>
                    <span class="s2-card__pill"></span>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    <section class="reviews">
        <div class="content">
            <div class="reviews__top">
                <div class="reviews__head">
                    <div class="reviews__badge">
                        <span class="reviews__badge-icon"><img src="/assets/img/rv-badge.svg" alt=""></span>
                        <span class="reviews__badge-text">{{ __('site.section_reviews_title') }}</span>
                    </div>
                    <h2 class="reviews__title">{{ __('site.section_reviews_caption') }}</h2>
                    <p class="reviews__subtitle">{{ __('site.section_reviews_subcaption') }}</p>
                </div>
                <a href="/reviews" class="reviews__all">
                    {{ __('site.btn_all_reviews') }}
                    <img src="/assets/img/catalog-more-arrow.svg" alt="">
                </a>
            </div>
            @if(count($reviews))
            <div class="reviews-grid">
                @foreach($reviews->take(6) as $review)
                <div class="rev-card">
                    <div class="rev-card__head">
                        <img class="rev-card__avatar" src="{{ $review->avatar }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/assets/img/default_avatar.svg'">
                        <p class="rev-card__name">{{ $review->author }}</p>
                    </div>
                    <div class="rev-card__stars">
                        @for($i = 0; $i < 5; $i++)<img src="/assets/img/rv-star.svg" alt="">@endfor
                    </div>
                    <p class="rev-card__text">{{ $review->text }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </section>
    @if($faq)
    <section class="faq" id="faq">
        <div class="content">
            <div class="faq__badge">
                <span class="faq__badge-icon"><img src="/assets/img/faq-badge.svg" alt=""></span>
                <span class="faq__badge-text">{{ __('site.section_faq_title') }}</span>
            </div>
            <h2 class="faq__title">{{ __('site.section_faq_caption') }}</h2>
            <div class="faq__container">
                @foreach($faq as $f)
                <div class="accordion">
                    <div class="accordion__header">
                        <span class="accordion__header__content">{{ $f->localized_question }}</span>
                        <div class="accordion__header__btn"></div>
                    </div>
                    <div class="accordion__body">
                        <div class="accordion__body__content">{!! $f->localized_answer !!}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</main>
@endsection