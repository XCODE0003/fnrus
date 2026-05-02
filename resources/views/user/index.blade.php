@extends('user.layouts.main')
@section('content')
    <main class="main">
        <section class="main-section1">
            <div class="content main-section1__container">
                <div class="main-section1__inner">
                    <div class="section-category main-section1__section-category">
                        <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                        <span class="section-category__text">{{ __('site.text_category_text') }}</span>
                    </div>
                    <p class="section-caption main-section1__section-caption">{!! __('site.text_section_caption') !!}</p>
                    <p class="section-subcaption main-section1__section-subcaption">{{ __('site.text_section_subcaption') }}</p>
                    <div class="main-section1__btns">
                        <button href="" class="btn btn-accent" data-scroll=".catalog">
                            <span class="btn__icon"><img src="/assets/img/icon_1.svg" alt=""></span>
                            {{ __('site.btn_catalog_cheats') }}
                        </button>
                        <button class="btn" data-popup="register" id="register">
                            <span class="btn__icon"><img src="/assets/img/icon_2.svg" alt=""></span>
                            <span class="name">{{ __('site.btn_register') }}</span>
                        </button>
                    </div>
                    <div class="main-section1__support">
                        <span>{{ __('site.text_cheat_support') }}</span>
                        <div><img src="/assets/img/icon_apple.svg" alt=""></div>
                        <div><img src="/assets/img/icon_android.svg" alt=""></div>
                        <div><img src="/assets/img/icon_windows.svg" alt=""></div>
                        <div><img src="/assets/img/icon_idk.svg" alt=""></div>
                    </div>
                </div>
                <!-- Дальше говнище -->
                <div id="card">
                    <div class="container">
                        <div class="container__color-btns">
                            <div class="red"></div>
                            <div class="yellow"></div>
                            <div class="green"></div>
                        </div>
                        <img class="container__image" src="/assets/img/main_section1_image.png" alt="">
                        <p class="container__caption">{{ config('app.name') }}</p>
                        <div class="container__load-text"></div>
                        <div class="container__load-text"></div>
                        <a href="/status" class="btn container__btn">
                            <div class="btn__icon">
                                <img src="/assets/img/icon_15.svg" alt="">
                            </div>
                            {{ __('site.btn_view_cheat_status') }}
                        </a>
                    </div>
                    <div class="eclipse"></div>
                    <div class="skill">SKILL +200%</div>
                    <div class="telegram">
                        <a href="{{ $shop_set->btn_buy_bot_url ?? 'https://t.me/Fanru_bot' }}" class="link" target="_blank"></a>
                        <div class="icon">
                            @include('user.partials.icon', ['icon' => $shop_set->btn_buy_bot_icon ?? 'telegram', 'color' => '#FFFFFF', 'size' => 20])
                        </div>
                        <div>
                            <p>@php
                                $iconNames = ['telegram' => 'Telegram', 'discord' => 'Discord', 'vk' => 'VK', 'youtube' => 'YouTube', 'instagram' => 'Instagram'];
                                $currentIcon = $shop_set->btn_buy_bot_icon ?? 'telegram';
                                echo $iconNames[$currentIcon] ?? config('app.name');
                            @endphp</p>
                            <span>{{ $shop_set->btn_buy_bot_text ?? __('site.text_buy_to_bot') }}</span>
                        </div>
                    </div>
                    <div class="platforms">
                        <span><img src="/assets/img/icon_apple.svg" alt=""></span>
                        <span><img src="/assets/img/icon_android.svg" alt=""></span>
                        <span><img src="/assets/img/icon_windows.svg" alt=""></span>
                        <span><img src="/assets/img/icon_idk.svg" alt=""></span>
                    </div>
                    <div class="stats">
                        <span class="caption">{{ __('site.text_why_us') }}</span>
                        <span class="subcaption">{{ __('site.text_updates_protection') }}</span>
                        <div class="types">+200%</div>
                        <div class="graph">
                            <div class="elem">
                                <div class="num">55</div>
                                <div class="bar" style="height: calc(var(--max-bar-height) * 0.275)"></div>
                            </div>
                            <div class="elem">
                                <div class="num">110</div>
                                <div class="bar" style="height: calc(var(--max-bar-height) * 0.55)"></div>
                            </div>
                            <div class="elem">
                                <div class="num">65</div>
                                <div class="bar" style="height: calc(var(--max-bar-height) * 0.325)"></div>
                            </div>
                            <div class="elem">
                                <div class="num">152</div>
                                <div class="bar" style="height: calc(var(--max-bar-height) * 0.76)"></div>
                            </div>
                            <div class="elem">
                                <div class="num">200</div>
                                <div class="bar" style="height: calc(var(--max-bar-height) * 1)"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Вроде закончилось -->
            </div>
        </section>
        <section class="section2">
            <div class="content section2__container">
                <div class="section-category">
                    <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                    <span class="section-category__text">{{ __('site.text_why_choose_us') }}</span>
                </div>
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <p class="section-caption">{{ __('site.text_new_level_gaming_experience') }}</p>
                    </div>
                    <div class="slider-arrows">
                        <button class="slider-arrows__arrow slider-arrows__prev"></button>
                        <button class="slider-arrows__arrow slider-arrows__next"></button>
                    </div>
                </div>
                <div class="swiper section2__slider js-carousel" data-carousel="section2">
                    <div class="swiper-wrapper section2__block-container">
                        <div class="swiper-slide section2__block">
                            <div class="section2__block__icon">
                                <img src="/assets/img/icon_3.svg" alt="">
                            </div>
                            <p class="section2__block__caption">{{ __('site.text_unique_features') }}</p>
                            <p class="section2__block__text">{{ __('site.text_unique_features_text') }}</p>
                        </div>
                        <div class="swiper-slide section2__block">
                            <div class="section2__block__icon">
                                <img src="/assets/img/icon_4.svg" alt="">
                            </div>
                            <p class="section2__block__caption">{{ __('site.text_good_defence') }}</p>
                            <p class="section2__block__text">{{ __('site.text_good_defence_text') }}</p>
                        </div>
                        <div class="swiper-slide section2__block">
                            <div class="section2__block__icon">
                                <img src="/assets/img/icon_5.svg" alt="">
                            </div>
                            <p class="section2__block__caption">{{ __('site.text_updates') }}</p>
                            <p class="section2__block__text">{{ __('site.text_updates_text') }}</p>
                        </div>
                        <div class="swiper-slide section2__block">
                            <div class="section2__block__icon">
                                <img src="/assets/img/icon_6.svg" alt="">
                            </div>
                            <p class="section2__block__caption">{{ __('site.text_support') }}</p>
                            <p class="section2__block__text">{{ __('site.text_support_text') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="catalog" id="catalog">
            <div class="content">
                <div class="section-category">
                    <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                    <span class="section-category__text">{{ __('site.text_best_soft') }}</span>
                </div>
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <p class="section-caption">{{ __('site.text_exclusive_cheats') }}</p>
                        <p class="section-subcaption">{{ __('site.text_exclusive_cheats_caption') }}</p>
                    </div>
{{--                    <div class="select _unfold catalog__platform">--}}
{{--                        <select name="platform">--}}
{{--                            <option value="Выберите платформу" disabled>Выберите платформу</option>--}}
{{--                            <option value="Android">Android</option>--}}
{{--                            <option value="iOS">iOS</option>--}}
{{--                            <option value="Эмулятор">Эмулятор</option>--}}
{{--                        </select>--}}
{{--                    </div>--}}
                </div>
                <div class="catalog__cards-container">
                    @foreach($categories as $card)
                        <div class="card">
                            <a href="/{{ $card->alias }}" class="card__link"></a>
                            <div class="card__img">
                                <img src="/{{ $card->image_site }}" alt="">
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
                        <p class="tg-card__caption">{{ __('site.card_buy_bot_title') }}</p>
                        <p class="tg-card__text">{{ __('site.card_buy_bot_caption') }}</p>
                        <a href="https://t.me/{{ $shop->username }}" target="_blank" class="tg-card__btn">{{ __('site.card_buy_bot_btn') }}</a>
                    </div>
                </div>
{{--                <button class="btn catalog__show-more">Показать еще</button>--}}
            </div>
        </section>
        <section class="reviews">
            <div class="content">
                <div class="section-category">
                    <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                    <span class="section-category__text">{{ __('site.section_reviews_title') }}</span>
                </div>
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <p class="section-caption">{{ __('site.section_reviews_caption') }}</p>
                        <p class="section-subcaption">
                            {{ __('site.section_reviews_subcaption') }}
                            <a href="{{ $shop_set->btn_reviews_url ?? 'https://t.me/Palkey' }}" class="social-btn footer__social-btn" target="_blank">
                                <span class="social-btn__icon">
                                    @include('user.partials.icon', ['icon' => $shop_set->btn_reviews_icon ?? 'telegram', 'color' => '#FFFFFF', 'size' => 80])
                                </span>
                                <span class="social-btn__text">{{ $shop_set->btn_reviews_text ?? '@Palkey' }}</span>
                            </a>
                        </p>
                    </div>
                    <div class="slider-arrows">
                        <button class="slider-arrows__arrow slider-arrows__prev"></button>
                        <button class="slider-arrows__arrow slider-arrows__next"></button>
                    </div>
                </div>
                <div class="swiper reviews-slider js-carousel" data-carousel="reviews">
                    <div class="swiper-wrapper">
                        @foreach($reviews as $review)
                            <div class="swiper-slide reviews__slide">
                                <div class="reviews__slide__user-info">
                                    <img class="reviews__slide__avatar" src="{{ $review->avatar }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/assets/img/default_avatar.svg'">
                                    <p class="reviews__slide__name">{{ $review->author }}</p>
                                </div>
                                <p class="reviews__slide__text">{{ $review->text }}</p>
                                <a target="_blank" href="{{ $review->link }}" class="reviews__slide__btn">{{ __('site.btn_reviews') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        @if($faq)
        <section class="faq" id="faq">
            <div class="content">
                <div class="section-category">
                    <div class="section-category__icon"><img src="/assets/img/icon_star.svg" alt=""></div>
                    <span class="section-category__text">{{ __('site.section_faq_title') }}</span>
                </div>
                <p class="section-caption">{{ __('site.section_faq_caption') }}</p>
                <div class="faq__container">
                    @foreach($faq as $f)
                        <div class="accordion">
                            <div class="accordion__header">
                                <span class="accordion__header__content">{{ $f->localized_question }}</span>
                                <div class="accordion__header__btn"></div>
                            </div>
                            <div class="accordion__body">
                                <p class="accordion__body__content">{!! $f->localized_answer !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

    </main>
@endsection
