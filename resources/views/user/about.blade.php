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
                <div class="about-section__contact">
                    <div class="about-section__contact-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    @if($item->icon === 'telegram' || $item->icon === 'discord' || $item->icon === 'vk' || $item->icon === 'youtube' || $item->icon === 'instagram' || $item->icon === 'star')
                    @include('user.partials.icon', ['icon' => $item->icon, 'color' => '#F5C48A', 'size' => 20])
                    @else
                    @include('user.partials.icon', ['icon' => 'link', 'color' => '#F5C48A', 'size' => 20])
                    @endif
                    <span style="margin: 0; opacity: .4; font-size: 16px; font-family: 'Mazzard M';">{{ $item->localized_label }}</span>
                    </div>
                    <p><a target="_blank" href="{{ $item->url }}" style="display: inline-block; padding: 6px 16px; background: rgba(245, 196, 138, 0.12); border: 1px solid rgba(245, 196, 138, 0.35); border-radius: 6px; color: #F5C48A; text-decoration: none; font-size: 14px; transition: background 0.2s, border-color 0.2s;" onmouseover="this.style.background='rgba(245, 196, 138, 0.22)';this.style.borderColor='rgba(245, 196, 138, 0.6)'" onmouseout="this.style.background='rgba(245, 196, 138, 0.12)';this.style.borderColor='rgba(245, 196, 138, 0.35)'">{{ $item->url_text }}</a></p>
                </div>
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
                    <p class="section-caption">{{ __('site.about_games_caption') }}</p>
                </div>
            </div>
            <div class="catalog__cards-container">
                @foreach($categories as $card)
                    <div class="card">
                        <a href="/{{ $card->alias }}" class="card__link"></a>
                        <div class="card__img">
                            <img src="{{ $card->image_site }}" alt="">
                        </div>
                        <div class="types">@plural($card->count_products, __('site.text_x_cheats_one'), __('site.text_x_cheats_few'), __('site.text_x_cheats_many'))</div>
                        <div class="card__name-container">
                            <div class="card__name">{{ $card->title }}</div>
                            <button class="card__btn"></button>
                        </div>
                    </div>
                @endforeach
                {{-- <div class="card tg-card">
                    <div class="tg-card__icon">
                        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M34.1444 47.2108L33.2445 59.8679C34.532 59.8679 35.0896 59.3148 35.7583 58.6507L41.7944 52.882L54.3019 62.0416C56.5957 63.32 58.2119 62.6468 58.8307 59.9314L67.0405 21.4615L67.0428 21.4593C67.7704 18.0683 65.8165 16.7423 63.5816 17.5742L15.3243 36.0498C12.0308 37.3282 12.0807 39.1642 14.7644 39.996L27.1019 43.8335L55.7593 25.9019C57.108 25.0089 58.3343 25.503 57.3256 26.3961L34.1444 47.2108Z" fill="#8AF59B"/>
                        </svg>
                    </div>
                    <p class="tg-card__caption">Покупка через&nbsp;бота</p>
                    <p class="tg-card__text">Вы также можете купить любой софт через нашего бота в Telegram</p>
                    <a href="https://t.me/{{ $shop->username }}" target="_blank" class="tg-card__btn">Перейти</a>
                </div> --}}
            </div>
        </div>
    </section>
</main>
@endsection
