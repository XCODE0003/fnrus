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
                                    @php
                                        // Normalise to the resizer URL /i{attachmentId}. New uploads store the
                                        // bare 40-char hash; legacy values were stored as "i"+hash (41 chars).
                                        $gid = ltrim((string) $image, '/');
                                        if (mb_strlen($gid) === 41 && \Illuminate\Support\Str::startsWith($gid, 'i')) {
                                            $gid = mb_substr($gid, 1);
                                        }
                                    @endphp
                                    <div class="swiper-slide cheat-block__slider__slide">
                                        <img src="/i{{ $gid }}" alt="" loading="lazy" onerror="this.onerror=null;this.parentElement.classList.add('is-empty');this.remove();">
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
                                <button class="btn-play" data-popup="cheat-video" aria-label="{{ __('site.cheat_video_review') }}" title="{{ __('site.cheat_video_review') }}">
                                    <span class="btn-play__icon" aria-hidden="true">
                                        <svg width="14" height="16" viewBox="0 0 14 16" fill="none"><path d="M1.5 1.3v13.4a.8.8 0 0 0 1.22.68l11-6.7a.8.8 0 0 0 0-1.36l-11-6.7A.8.8 0 0 0 1.5 1.3Z" fill="currentColor"/></svg>
                                    </span>
                                    <span class="btn-play__label">{{ __('site.cheat_video_review') }}</span>
                                </button>
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
                @php
                    $functional = json_decode($product->functional, true) ?: [];
                    $accentMap = ['visuals' => 'func--visuals', 'aimbot' => 'func--aimbot', 'misc' => 'func--misc'];
                @endphp
                @if(count($functional))
                <div class="cheat-functions" id="cheat-functions">
                    <div class="cheat-functions__caption-container">
                        <p class="section-caption">{{ __('site.section_functional_title') }}</p>
                    </div>

                    {{-- All categories shown side-by-side (Figma cards), no tabs --}}
                    @php
                        $funcIcons = [
                            'visuals' => '<img src="/assets/img/func-visuals.svg" alt="" width="42" height="42">',
                            'aimbot'  => '<img src="/assets/img/func-aimbot.svg" alt="" width="42" height="42">',
                            'misc'    => '<img src="/assets/img/func-misc.svg" alt="" width="42" height="42">',
                        ];
                    @endphp
                    <div class="func-grid">
                        @foreach($functional as $i => $func)
                            @php
                                $id = $func['id'] ?? '';
                                $acc = $accentMap[$id] ?? 'func--default';
                                $icon = $funcIcons[$id] ?? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 6 6 .9-4.5 4.3 1 6L12 16.5 6.5 19.2l1-6L3 8.9 9 8z"/></svg>';
                            @endphp
                            <div class="func-cat {{ $acc }}">
                                <div class="func-cat__head">
                                    <span class="func-cat__icon" aria-hidden="true">{!! $icon !!}</span>
                                    <span class="func-cat__name">{{ $func['title'] }}</span>
                                </div>
                                <div class="func-cat__body">
                                    <ul class="func-cat__list">
                                        @foreach($func['lines'] as $line)
                                            <li>{{ trim($line) }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
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
                            @php
                                $rHack = \App\Models\HackStatus::getByID($card->hack_status);
                                $rRoot = ($rHack && $rHack->title_pub != '') ? $rHack->title_pub : '';
                                $rGreen = $rRoot !== '' && mb_stripos($rRoot, 'без') !== false;
                                $rAdv = json_decode($card->advantages, true) ?: [];
                            @endphp
                            <a href="{{ $card->alias }}" class="swiper-slide game-card">
                                <div>
                                    <div class="game-card__top">
                                        <div class="game-card__logo">
                                            <img src="/i{{ $card->image_site }}" alt="" onerror="this.style.visibility='hidden'">
                                        </div>
                                        <span class="game-card__status cheat-status_{{ $card->status_class }}">
                                            <svg width="11" height="13" viewBox="0 0 10.0001 12" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M9.96538 4.8176C9.92869 4.72405 9.86468 4.64374 9.78167 4.58712C9.69866 4.5305 9.60052 4.50021 9.50004 4.50019H6.1234L6.98814 0.608664C7.04814 0.339114 6.87832 0.07196 6.60877 0.0119537C6.52331 -0.00707642 6.43436 -0.00333246 6.35081 0.022811C6.26726 0.0489544 6.19204 0.0965806 6.13268 0.160924L0.132787 6.66081C-0.0546344 6.8636 -0.0421368 7.17992 0.160657 7.36734C0.253094 7.45277 0.374358 7.50019 0.500228 7.50011H3.30659L2.02586 11.3418C1.93851 11.6038 2.08008 11.8869 2.34205 11.9743C2.42719 12.0027 2.5184 12.0076 2.6061 11.9886C2.69381 11.9696 2.77478 11.9273 2.84052 11.8662L9.8404 5.36632C9.914 5.29804 9.9653 5.20914 9.9876 5.11125C10.0099 5.01336 10.0021 4.91102 9.96538 4.8176Z" fill="currentColor"/></svg>
                                            {{ $card->status_title }}
                                        </span>
                                    </div>
                                    <p class="game-card__name">{{ $card->title }}</p>
                                    <ul class="game-card__list">
                                        @foreach(array_slice($rAdv, 0, 4) as $a)<li>{{ $a }}</li>@endforeach
                                    </ul>
                                </div>
                                <div class="game-card__bottom">
                                    @if($rRoot !== '')
                                        <span class="game-card__tag {{ $rGreen ? 'game-card__tag--green' : 'game-card__tag--purple' }}">
                                            <img src="/assets/img/{{ $rGreen ? 'catalog-tag-green' : 'catalog-tag-purple' }}.svg" alt="">
                                            {{ $rRoot }}
                                        </span>
                                    @else<span></span>@endif
                                    <span></span>
                                </div>
                                <div class="game-card__hover">
                                    <span class="game-card__hover-btn">{{ __('site.catalog_more') }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="popup popup--pay" id="buy">
        <div id="result">
            <div id="body">
                <p class="popup__caption pay-card__title">{{ __('site.modal_buy_title') }} <span id="modal_title">...</span></p>

                {{-- ========== Step 1: tariff + method + promo ========== --}}
                <div class="popup__step _active" data-popup-step="1">
                    <div class="pay-section">
                        <span class="pay-section__label">{{ __('site.cheat_tariffs') }}</span>
                        <div class="pay-options" id="block_tariffs"></div>
                    </div>

                    <div class="pay-section">
                        <span class="pay-section__label">{{ __('site.modal_buy_label_methods_payments') }}</span>
                        <div class="pay-options">
                            <input type="radio" name="buy-method" id="buy-method-1" class="pay-option__input" checked>
                            <label for="buy-method-1" class="pay-option">
                                <span class="pay-option__radio"></span>
                                <span class="pay-option__label">{{ __('site.modal_buy_label_method_1') }}</span>
                            </label>
                            <input type="radio" name="buy-method" id="buy-method-2" class="pay-option__input">
                            <label for="buy-method-2" class="pay-option">
                                <span class="pay-option__radio"></span>
                                <span class="pay-option__label">{{ __('site.modal_buy_label_method_2') }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="pay-section">
                        <div class="pay-promo" id="block_promo">
                            <input
                                type="text"
                                class="pay-promo__input"
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
                            <button type="button" class="pay-promo__apply input-block__input-container__apply-promo" data-id="{{ $product->id }}" id="check-promo">OK</button>
                        </div>
                    </div>

                    <button class="pay-btn pay-btn--primary popup__submit-btn" type="button" onclick="createOrderWeb({{ $product->id }});">
                        {{ __('site.modal_buy_btn_to_pay') }}
                    </button>
                </div>

                {{-- ========== Step 2: payment methods + order info ========== --}}
                <div class="popup__step" data-popup-step="2">
                    <p class="pay-card__title pay-card__title--sm">{{ __('site.invoice_choose_method') }}</p>
                    <div class="pay-methods popup__payment-methods" id="buy-payments-methods"></div>

                    <div class="pay-order-info">
                        <div class="pay-order-info__row">
                            <span class="pay-order-info__label">{{ __('site.modal_buy_label_validity_period') }}</span>
                            <span class="pay-order-info__value" id="buy-expired">…</span>
                        </div>
                        <div class="pay-order-info__row" id="block_payment">
                            <span class="pay-order-info__label">{{ __('site.modal_buy_label_email') }}</span>
                            <span class="pay-order-info__value" id="buy-email">…</span>
                        </div>
                        <div class="pay-order-info__row">
                            <span class="pay-order-info__label">{{ __('site.modal_buy_total_payable') }}</span>
                            <span class="pay-order-info__value pay-order-info__value--accent" id="buy-sum">…</span>
                        </div>
                        <div class="pay-order-info__row">
                            <span class="pay-order-info__label">{{ __('site.modal_buy_label_expired_payment') }}</span>
                            <span class="pay-order-info__value pay-order-info__value--accent" id="timer">…</span>
                        </div>
                    </div>

                    <div class="pay-actions">
                        <button type="button" class="pay-btn pay-btn--primary popup__submit-btn" id="btn-pay">
                            {{ __('site.modal_buy_btn_payment') }}
                        </button>
                        <div class="pay-actions__row">
                            <button type="button" class="pay-btn pay-btn--secondary popup__back-btn" data-popup-switch-step="1" onclick="clearInterval(timerInterval);">
                                {{ __('site.modal_buy_btn_back') }}
                            </button>
                            <button type="button" class="pay-btn pay-btn--secondary pay-btn--danger" id="buy-cancel-trigger">
                                {{ __('site.invoice_cancel_order') }}
                            </button>
                        </div>
                    </div>

                    {{-- Cancel confirmation — overlay inside #buy popup --}}
                    <div class="buy-cancel-confirm" id="buy-cancel-confirm" hidden>
                        <div class="buy-cancel-confirm__panel">
                            <h3 class="buy-cancel-confirm__title">{{ __('site.invoice_cancel_title') }}</h3>
                            <div class="buy-cancel-confirm__icon" aria-hidden="true">
                                <svg width="88" height="88" viewBox="0 0 111 111" fill="none">
                                    <circle cx="55.5" cy="55.5" r="50" stroke="#E74C3C" stroke-width="5"/>
                                    <line x1="22" y1="22" x2="89" y2="89" stroke="#E74C3C" stroke-width="5" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="pay-actions__row">
                                <button type="button" class="pay-btn pay-btn--secondary" id="buy-cancel-confirm-yes">
                                    {{ __('site.invoice_cancel_order') }}
                                </button>
                                <button type="button" class="pay-btn pay-btn--primary" id="buy-cancel-confirm-no">
                                    {{ __('site.invoice_continue_pay') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== Step 3: success ========== --}}
                <div class="popup__step" data-popup-step="3">
                    <div class="pay-terminal pay-terminal--inline" id="system-msg">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="#4ABD5C" stroke-width="2"/>
                            <path d="M7 12l3.5 3.5L17 9" stroke="#4ABD5C" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="pay-terminal__text">{{ __('site.cheat_order_paid') }}</h2>
                        <div class="pay-actions">
                            <a target="_blank" id="material_link" class="pay-btn pay-btn--primary popup__submit-btn">
                                {{ __('site.cheat_get_product') }}
                            </a>
                            <a href="javascript:;" data-popup-switch-step="1" class="pay-btn pay-btn--secondary popup__back-btn">
                                {{ __('site.cheat_buy_more') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ========== Step 4: expired ========== --}}
                <div class="popup__step" data-popup-step="4">
                    <div class="pay-terminal pay-terminal--inline" id="system-msg">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="#F39C12" stroke-width="2"/>
                            <path d="M12 7v5l3 2" stroke="#F39C12" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h2 class="pay-terminal__text">{{ __('site.cheat_payment_expired') }}</h2>
                        <a href="javascript:;" data-popup-switch-step="1" class="pay-btn pay-btn--secondary popup__back-btn">
                            {{ __('site.modal_buy_btn_back') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        var trigger = document.getElementById('buy-cancel-trigger');
        var overlay = document.getElementById('buy-cancel-confirm');
        var yes = document.getElementById('buy-cancel-confirm-yes');
        var no  = document.getElementById('buy-cancel-confirm-no');
        if (!trigger || !overlay) return;

        function open(){ overlay.hidden = false; requestAnimationFrame(function(){ overlay.classList.add('is-open'); }); }
        function close(){ overlay.classList.remove('is-open'); setTimeout(function(){ overlay.hidden = true; }, 220); }

        trigger.addEventListener('click', open);
        no.addEventListener('click', close);

        yes.addEventListener('click', function(){
            var orderId = window._createdOrderId;
            if (!orderId) { close(); return; }
            yes.disabled = true;
            $.ajax({
                type: 'POST',
                url: (window.api_url || (location.origin + '/api')) + '/orders/' + orderId + '/cancel',
                dataType: 'json',
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token')); },
                success: function(data){
                    yes.disabled = false;
                    close();
                    if (data && data.ok === true) {
                        try { clearInterval(window.timerInterval); } catch(e) {}
                        var popup = document.querySelector('#buy');
                        popup.querySelectorAll('[data-popup-step]._active').forEach(function(s){ s.classList.remove('_active'); });
                        popup.querySelector('[data-popup-step="1"]').classList.add('_active');
                        if (typeof window.showNotification === 'function') {
                            window.showNotification(@json(__('site.invoice_order_cancelled')), 'success');
                        }
                    } else if (window.showNotification) {
                        window.showNotification((data && data.description) || @json(__('site.invoice_cancel_error')), 'fail');
                    }
                },
                error: function(){
                    yes.disabled = false;
                    if (window.showNotification) window.showNotification(@json(__('site.invoice_network_error')), 'fail');
                }
            });
        });

        overlay.addEventListener('click', function(e){ if (e.target === overlay) close(); });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
    })();
    </script>

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
