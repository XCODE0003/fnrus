@extends('user.layouts.main')
@section('content')
    <main class="cheat-page">
        <section class="cheat">
            <div class="content cheat__container">
                <ul class="breadcrumbs">
                    <li><a href="/">{{ __('site.item_home') }}</a></li><span style="font-size:14px;margin:0 7px;opacity: 0.7">→</span><li><a href="/{{ $cat_one->alias }}">{{ $cat_one->localized_title }}</a></li>
                </ul>
                <div class="cheat-block">
                    <div class="cheat-block__slider">
                        <div class="swiper">
                            <div class="swiper-wrapper">
                                @if($product->gallery != 'null')
                                @foreach(json_decode($product->gallery, true) as $image)
                                    <div class="swiper-slide cheat-block__slider__slide">
                                        <img src="/{{ $image }}" alt="">
                                    </div>
                                @endforeach
                                @endif
                            </div>
                            <button class="cheat-block__slider__arrow cheat-block__slider__prev"></button>
                            <button class="cheat-block__slider__arrow cheat-block__slider__next"></button>
                        </div>
                    </div>
                    <div class="cheat-block__info">
                        <p class="cheat-block__name" style="margin-bottom: 6px;margin-top: 0px">{{ $product->title }}</p>
                        @php
                            $hack_status = \App\Models\HackStatus::getByID($product->hack_status);
                            $status = '';
                            if ($hack_status->title_pub != '') {
                                $status = $hack_status->title_pub;
                            }
                        @endphp
                        @if($status != '')
                            <div class="types">{{ $status }}</div>
                        @endif
                        <span style="margin:6px 0px" class="cheat-status cheat-status_{{ $status_class }}">
                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M25.5865 11.2411C25.5009 11.0228 25.3516 10.8354 25.1579 10.7033C24.9642 10.5712 24.7352 10.5005 24.5008 10.5004H16.6219L18.6396 1.42022C18.7797 0.791267 18.3834 0.167907 17.7544 0.027892C17.5551 -0.0165117 17.3475 -0.00777573 17.1525 0.0532257C16.9576 0.114227 16.7821 0.225355 16.6436 0.37549L2.64382 15.5419C2.2065 16.0151 2.23567 16.7531 2.70885 17.1905C2.92454 17.3898 3.20749 17.5004 3.50118 17.5003H10.0494L7.06099 26.4643C6.85717 27.0755 7.18749 27.7362 7.79876 27.94C7.99742 28.0062 8.21024 28.0178 8.41489 27.9734C8.61955 27.929 8.80848 27.8304 8.96186 27.6878L25.2949 12.5214C25.4667 12.3621 25.5864 12.1547 25.6384 11.9263C25.6904 11.6978 25.6723 11.459 25.5865 11.2411Z" fill="white"/>
                                </svg>
                                {{ $status_title }}
                        </span>

                        <div class="cheat-block__text" style="margin-top: 6px">{!! $product->description !!}</div>
                        <div class="cheat-block__info__btn-line">
                            <button class="btn btn-accent" data-popup="buy" onclick="openModalBuy({{ $product->id }});">
                                <span class="btn__icon"><img src="/assets/img/icon_1.svg" alt=""></span>
                                <span id="btn_price">...</span>
                            </button>
                            @if($product->link_video != '#' and $product->link_video != '' and (Str::startsWith($product->link_video, 'http') or Str::startsWith($product->link_video, '/uploads/')))
                                <button class="btn-play" data-popup="cheat-video"></button>
                            @else
                                <button class="btn-play" disabled></button>
                            @endif
                        </div>
                    </div>
                    <div class="cheat-block__requirements">
                        <p class="caption">{{ __('site.section_requirements_title') }}</p>
                        <div class="cheat-block__requirements-container">
                            <div class="cheat-block__requirement">
							<span>
								<img src="/assets/img/icon_9.svg" alt="">
								{{ __('site.section_requirements_platform') }}
							</span>
                                <p>{{ ucfirst(explode('/', url()->current())[4]) }}</p>
                            </div>
                            <div class="cheat-block__requirement">
							<span>
								<img src="/assets/img/icon_10.svg" alt="">
								{{ __('site.section_requirements_system_versions') }}
							</span>
                                <p>{{ $product->system_versions }}</p>
                            </div>
                            <div class="cheat-block__requirement">
							<span>
								<img src="/assets/img/icon_12.svg" alt="">
								{{ __('site.section_requirements_auth') }}
							</span>
                                <p>{{ $product->system_auth }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="cheat-functions">
                    <div class="cheat-functions__caption-container">
                        <p class="cheat-functions__caption">{{ __('site.section_functional_title') }}</p>
                        <div class="slider-arrows">
                            <button class="slider-arrows__arrow slider-arrows__prev"></button>
                            <button class="slider-arrows__arrow slider-arrows__next"></button>
                        </div>
                    </div>
                    <div class="swiper swiper-functional">
                        <div class="swiper-wrapper">
                            @foreach(json_decode($product->functional, true) as $func)
                                <div class="swiper-slide cheat-functions__block">
                                    <div class="cheat-functions__block__name-panel" id="{{ !empty($func['id']) ? $func['id'] : 'visuals' }}"><span>{{ $func['title'] }}</span></div>
                                    <div class="cheat-functions__block__scroll-container" data-simplebar>
                                        <ul class="cheat-functions__block__list">
                                            @foreach($func['lines'] as $line)
                                                <li>{{ $line }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="game-cheats">
            <div class="content game-cheats__slider-container">
                <div class="section-caption-container">
                    <div class="section-caption-container__inner">
                        <p class="section-caption">{{ __('site.section_recommendation_title') }}</p>
                    </div>
                    <div class="slider-arrows">
                        <button class="slider-arrows__arrow slider-arrows__prev game-cheats__slider-arrow"></button>
                        <button class="slider-arrows__arrow slider-arrows__next game-cheats__slider-arrow"></button>
                    </div>
                </div>
                <div class="swiper game-cheats-slider">
                    <div class="swiper-wrapper">
                        @foreach($recommendations as $card)
                            <div class="swiper-slide cheat-card">
                                <div class="cheat-card__logo" style="overflow: hidden;">
                                    <img src="/i{{ $card->image_site }}" alt="">
                                </div>
                                <span class="cheat-status cheat-status_{{ $card->status_class }}">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M25.5865 11.2411C25.5009 11.0228 25.3516 10.8354 25.1579 10.7033C24.9642 10.5712 24.7352 10.5005 24.5008 10.5004H16.6219L18.6396 1.42022C18.7797 0.791267 18.3834 0.167907 17.7544 0.027892C17.5551 -0.0165117 17.3475 -0.00777573 17.1525 0.0532257C16.9576 0.114227 16.7821 0.225355 16.6436 0.37549L2.64382 15.5419C2.2065 16.0151 2.23567 16.7531 2.70885 17.1905C2.92454 17.3898 3.20749 17.5004 3.50118 17.5003H10.0494L7.06099 26.4643C6.85717 27.0755 7.18749 27.7362 7.79876 27.94C7.99742 28.0062 8.21024 28.0178 8.41489 27.9734C8.61955 27.929 8.80848 27.8304 8.96186 27.6878L25.2949 12.5214C25.4667 12.3621 25.5864 12.1547 25.6384 11.9263C25.6904 11.6978 25.6723 11.459 25.5865 11.2411Z" fill="white"/>
                                    </svg>
							        {{ $card->status_title }}
						        </span>
                                <p class="cheat-card__name">{{ $card->title }}</p>
                                <ul class="cheat-card__list" style="margin-bottom: 15px">
                                    @foreach(json_decode($card->advantages, true) as $a)
                                        <li>{{ $a }}</li>
                                    @endforeach
                                </ul>
                                @php
                                    $hack_status = \App\Models\HackStatus::getByID($card->hack_status);
                                    $status = '';
                                    if ($hack_status->title_pub != '') {
                                        $status = $hack_status->title_pub;
                                    }
                                @endphp
                                @if($status != '')
                                    <div class="types">{{ $status }}</div>
                                @endif
                                <a href="{{ $card->alias }}" class="cheat-card__btn">{{ __('site.btn_more') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="popup" id="buy">
        <div id="result">
            <div id="body">
                <p class="popup__caption">{{ __('site.modal_buy_title') }} <span id="modal_title">...</span></p>
                <div class="popup__step _active" data-popup-step="1">
                    <div class="input-block">
                        <div class="input-block__label-container">
                            <label
                                for="withdraw-details"
                                class="input-block__label"
                            >
                                {{ __('site.cheat_tariffs') }}
                            </label>
                        </div>
                        <div class="input-block__radio-container" id="block_tariffs"></div>
                    </div>
                    <div class="input-block">
                        <div class="input-block__label-container">
                            <label
                                for="withdraw-details"
                                class="input-block__label"
                            >
                                {{ __('site.modal_buy_label_methods_payments') }}
                            </label>
                        </div>
                        <div class="input-block__radio-container">
                            <input type="radio" name="buy-method" id="buy-method-1" checked>
                            <label for="buy-method-1" class="input-block__radio-label">
                                <div class="input-block__radio-label__icon"></div>
                                <span>{{ __('site.modal_buy_label_method_1') }}</span>
                            </label>
                            <input type="radio" name="buy-method" id="buy-method-2">
                            <label for="buy-method-2" class="input-block__radio-label">
                                <div class="input-block__radio-label__icon"></div>
                                <span>{{ __('site.modal_buy_label_method_2') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="input-block input-block_promo">
                        <div class="input-block__input-container" id="block_promo">
                            <input
                                type="text"
                                class="input-block__input"
                                placeholder="{{ __('site.modal_buy_placeholder_promocode') }}"
                                data-is-applied="0"
                                id="buy-promo"
                                autocomplete="new-password"
                                name="promo_field_no_autofill"
                                data-lpignore="true"
                                data-1p-ignore="true"
                                data-bwignore="true"
                                data-form-type="other"
                                readonly
                                onfocus="this.removeAttribute('readonly');"
                            >
                            <button class="input-block__input-container__apply-promo" data-id="{{ $product->id }}" id="check-promo">OK</button>
                        </div>
                    </div>
                    <button class="btn btn-accent popup__submit-btn" type="button" onclick="createOrderWeb({{ $product->id }});">
                        {{ __('site.modal_buy_btn_to_pay') }}</button>
                </div>
                <div class="popup__step" data-popup-step="2">
                    <div class="popup__payment-methods" id="buy-payments-methods"></div>
                    <div class="popup__buy-info">
                        <div class="popup__buy-info__block">{{ __('site.modal_buy_label_validity_period') }} <span id="buy-expired">...</span></div>
                        <div class="popup__buy-info__block-line">
                            <div class="popup__buy-info__block" id="block_payment">{{ __('site.modal_buy_label_email') }} <span id="buy-email">...</span></div>
                            <div class="popup__buy-info__block">{{ __('site.modal_buy_total_payable') }} <span id="buy-sum">...</span></div>
                        </div>
                        <div class="popup__buy-info__block">{{ __('site.modal_buy_label_expired_payment') }} <span id="timer">...</span></div>
                    </div>
                    <div class="popup__btns-container">
                        <button class="btn popup__back-btn" data-popup-switch-step="1" onclick="clearInterval(timerInterval);">{{ __('site.modal_buy_btn_back') }}</button>
                        <button class="btn btn-accent popup__submit-btn" type="button" id="btn-pay">{{ __('site.modal_buy_btn_payment') }}</button>
                    </div>
                </div>
                <div class="popup__step" data-popup-step="3">
                    <div id="system-msg" style="margin: 87px auto; display: block; text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;"><path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
                        <h2 style="margin-top: 15px;">{{ __('site.cheat_order_paid') }}</h2>
                        <a target="_blank" id="material_link" class="btn btn-accent popup__submit-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 200px;height: auto">{{ __('site.cheat_get_product') }}</a>
                        <a href="javascript:;" data-popup-switch-step="1" class="btn popup__back-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 12px;max-width: 200px;">{{ __('site.cheat_buy_more') }}</a>
                    </div>
                </div>
                <div class="popup__step" data-popup-step="4">
                    <div id="system-msg" style="margin: 87px auto; display: block; text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #ff0000;"><path d="M256 8C119.043 8 8 119.083 8 256c0 136.997 111.043 248 248 248s248-111.003 248-248C504 119.083 392.957 8 256 8zm0 448c-110.532 0-200-89.431-200-200 0-110.495 89.472-200 200-200 110.491 0 200 89.471 200 200 0 110.53-89.431 200-200 200zm42-104c0 23.159-18.841 42-42 42s-42-18.841-42-42 18.841-42 42-42 42 18.841 42 42zm-81.37-211.401l6.8 136c.319 6.387 5.591 11.401 11.985 11.401h41.17c6.394 0 11.666-5.014 11.985-11.401l6.8-136c.343-6.854-5.122-12.599-11.985-12.599h-54.77c-6.863 0-12.328 5.745-11.985 12.599z"/></svg>
                        <h2 style="margin-top: 15px;">{{ __('site.cheat_payment_expired') }}</h2>
                        <a href="javascript:;" data-popup-switch-step="1" class="btn popup__back-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 200px;">{{ __('site.modal_buy_btn_back') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($product->link_video != '#' and $product->link_video != '' and (Str::startsWith($product->link_video, 'http') or Str::startsWith($product->link_video, '/uploads/')))
    <div class="popup" id="cheat-video">
        <div class="popup__video-wrap">
            <button class="popup__close" onclick="closePopup()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6l12 12" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            @php
                $videoUrl = $product->link_video;
                $ytId = null;
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&?#]+)/', $videoUrl, $ytMatch)) {
                    $ytId = $ytMatch[1];
                }
            @endphp
            <div class="popup__inner">
                <div class="popup__video-container" id="video-player-container"
                     data-video-url="{{ $videoUrl }}"
                     @if($ytId) data-youtube-id="{{ $ytId }}" @endif>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
