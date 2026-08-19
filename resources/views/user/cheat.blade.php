@extends('user.layouts.main')
@section('content')
    @php
        $gallery = json_decode((string) $product->gallery, true);
        $gallery = is_array($gallery) ? array_values(array_filter($gallery, 'is_scalar')) : [];
        $recommendationItems = is_array($recommendations ?? null)
            ? $recommendations
            : (($recommendations ?? null) instanceof \Traversable ? iterator_to_array($recommendations) : []);
        $recommendationItems = array_values(array_filter($recommendationItems, 'is_object'));
    @endphp
    {{-- ТЗ §8.3 — the restore check needs to know which product this page is --}}
    <script>window.__productId = {{ (int) $product->id }};</script>
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
                                @foreach($gallery as $image)
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
                            if ($hack_status && $hack_status->title_pub != '') {
                                $status = $hack_status->title_pub;
                            }
                        @endphp
                        @if($status != '')
                            <div class="types">{{ $status }}</div>
                        @endif
                        <span style="margin:6px 0px" class="cheat-status cheat-status_{{ $status_class }}">
                                @include('user.partials.status-icon', ['icon' => $status_icon ?? 'question', 'size' => 20])
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
        @if(count($recommendationItems) > 0)
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
                        @foreach($recommendationItems as $card)
                            @php
                                $rHack = \App\Models\HackStatus::getByID($card->hack_status);
                                $rRoot = ($rHack && $rHack->title_pub != '') ? $rHack->title_pub : '';
                                $rGreen = $rRoot !== '' && mb_stripos($rRoot, 'без') !== false;
                                $rAdv = json_decode((string) $card->advantages, true);
                                $rAdv = is_array($rAdv) ? array_values(array_filter($rAdv, 'is_scalar')) : [];
                            @endphp
                            <a href="{{ $card->alias }}" class="swiper-slide game-card">
                                <div>
                                    <div class="game-card__top">
                                        <div class="game-card__logo">
                                            <img src="/i{{ $card->image_site }}" alt="" onerror="this.style.visibility='hidden'">
                                        </div>
                                        <span class="game-card__status cheat-status_{{ $card->status_class }}">
                                            @include('user.partials.status-icon', ['icon' => $card->status_icon ?? 'question', 'size' => 12])
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
        @endif
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

                    {{-- ТЗ §4 — the legal pages must be linked at checkout --}}
                    <p class="legal-consent">{!! __('site.legal_consent_pay') !!}</p>
                    <button class="pay-btn pay-btn--primary popup__submit-btn" type="button" onclick="createOrderWeb({{ $product->id }});">
                        {{ __('site.modal_buy_btn_to_pay') }}
                    </button>
                </div>

                {{-- ========== Step 2: (A) choose method → (B) order info ========== --}}
                <div class="popup__step" data-popup-step="2">

                    {{-- ----- Screen A: payment method picker (with currencies) ----- --}}
                    <div class="pay-substep _active" data-substep="method">
                        <p class="pay-card__title pay-card__title--sm">{{ __('site.invoice_choose_method') }}</p>
                        <div class="pay-methods popup__payment-methods" id="buy-payments-methods"></div>
                        <div class="pay-actions">
                            <button type="button" class="pay-btn pay-btn--primary" id="buy-method-continue">
                                {{ __('site.invoice_continue') }}
                            </button>
                            {{-- ТЗ §8.4 — «Назад» alone left the order pending with stock
                                 and keys reserved, so cancelling is offered here too. --}}
                            <div class="pay-actions__row">
                                <button type="button" class="pay-btn pay-btn--secondary popup__back-btn" data-popup-switch-step="1" onclick="clearInterval(timerInterval);">
                                    {{ __('site.modal_buy_btn_back') }}
                                </button>
                                <button type="button" class="pay-btn pay-btn--secondary pay-btn--danger" id="buy-cancel-trigger-method">
                                    {{ __('site.invoice_cancel_order') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ----- Screen B: order info + pay ----- --}}
                    <div class="pay-substep" data-substep="order">
                        <p class="pay-card__title pay-card__title--sm">{{ __('site.invoice_order_info') }}</p>

                        <div class="pay-fields">
                            <div class="pay-field">
                                <span class="pay-field__label">{{ __('site.invoice_product_name') }}</span>
                                <div class="pay-field__box" id="buy-product-name">…</div>
                            </div>
                            <div class="pay-field">
                                <span class="pay-field__label">{{ __('site.invoice_order_id') }}</span>
                                <div class="pay-field__box" id="buy-order-id">…</div>
                            </div>
                            <div class="pay-field pay-field--half">
                                <span class="pay-field__label">{{ __('site.invoice_product_period') }}</span>
                                <div class="pay-field__box" id="buy-period">…</div>
                            </div>
                            <div class="pay-field pay-field--half">
                                <span class="pay-field__label">{{ __('site.modal_buy_total_payable') }}</span>
                                <div class="pay-field__box pay-field__box--accent" id="buy-sum">…</div>
                            </div>
                        </div>

                        <p class="pay-timer">
                            <span class="pay-timer__label">{{ __('site.invoice_payment_period') }}</span>
                            <span class="pay-timer__value" id="timer">…</span>
                        </p>
                        <span class="pay-hidden" id="buy-expired" hidden></span>

                        <div class="pay-note" role="note">
                            <span class="pay-note__icon" aria-hidden="true">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-1 5h2v6h-2zm0 8h2v2h-2z" fill="currentColor"/></svg>
                            </span>
                            <span class="pay-note__text">{!! __('site.invoice_hint_must_check') !!}</span>
                        </div>

                        <div class="pay-actions">
                            <button type="button" class="pay-btn pay-btn--primary popup__submit-btn" id="btn-pay">
                                {{ __('site.modal_buy_btn_payment') }}
                            </button>
                            <button type="button" class="pay-btn pay-btn--check" id="buy-check-pay" data-checking="{{ __('site.invoice_checking') }}">
                                {{ __('site.invoice_check_payment') }}
                            </button>
                            <div class="pay-actions__row">
                                <button type="button" class="pay-btn pay-btn--secondary" data-substep-back="method">
                                    {{ __('site.modal_buy_btn_back') }}
                                </button>
                                <button type="button" class="pay-btn pay-btn--secondary pay-btn--danger" id="buy-cancel-trigger">
                                    {{ __('site.invoice_cancel_order') }}
                                </button>
                            </div>
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
        var triggers = [
            document.getElementById('buy-cancel-trigger'),
            document.getElementById('buy-cancel-trigger-method')
        ].filter(Boolean);
        var trigger = triggers[0];
        var overlay = document.getElementById('buy-cancel-confirm');
        var yes = document.getElementById('buy-cancel-confirm-yes');
        var no  = document.getElementById('buy-cancel-confirm-no');
        if (!trigger || !overlay) return;

        function open(){ overlay.hidden = false; requestAnimationFrame(function(){ overlay.classList.add('is-open'); }); }
        function close(){ overlay.classList.remove('is-open'); setTimeout(function(){ overlay.hidden = true; }, 220); }

        triggers.forEach(function(t){ t.addEventListener('click', open); });
        no.addEventListener('click', close);

        yes.addEventListener('click', function(){
            // ТЗ §8.4 — /orders/{hash}/cancel looks the order up by hash;
            // sending the numeric id made every in-modal cancel fail with
            // "Заказ не найден" and left stock and keys locked until the cron.
            var orderHash = window._createdOrderHash;
            if (!orderHash) { close(); return; }
            yes.disabled = true;
            $.ajax({
                type: 'POST',
                url: (window.api_url || (location.origin + '/api')) + '/orders/' + orderHash + '/cancel',
                dataType: 'json',
                contentType: 'application/json',
                beforeSend: function(xhr) { xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token')); },
                success: function(data){
                    yes.disabled = false;
                    close();
                    if (data && data.ok === true) {
                        try { clearInterval(window.timerInterval); } catch(e) {}
                        if (window.clearPendingOrder) { window.clearPendingOrder(); }
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
    @php
        // ТЗ §9 — work out the player kind server-side so the JS does not have
        // to re-sniff the URL. Covers watch?v=, youtu.be, /embed/, /shorts/,
        // /live/, /v/ and m.youtube; a /uploads/ path with no extension is a
        // file too (that case used to match no branch and rendered a black box).
        $videoUrl  = $product->link_video;
        $videoKind = 'iframe';
        $embedSrc  = $videoUrl;

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $videoUrl, $m)) {
            $videoKind = 'youtube';
            $embedSrc  = 'https://www.youtube-nocookie.com/embed/' . $m[1]
                . '?autoplay=1&mute=1&playsinline=1&rel=0&modestbranding=1';
        } elseif (preg_match('~(?:vimeo\.com/|player\.vimeo\.com/video/)(\d+)~i', $videoUrl, $m)) {
            $videoKind = 'vimeo';
            $embedSrc  = 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&playsinline=1';
        } elseif (preg_match('~\.(mp4|webm|mov|ogg|m4v)(\?|$)~i', $videoUrl) || Str::startsWith($videoUrl, '/uploads/')) {
            $videoKind = 'file';
            $embedSrc  = $videoUrl;
        }
    @endphp
    <div class="popup video-modal" id="cheat-video" role="dialog" aria-modal="true" aria-label="{{ __('site.cheat_video_review') }}">
        <div class="popup__video-wrap video-modal__panel">
            <div class="video-modal__head">
                <p class="video-modal__title">{{ __('site.cheat_video_review') }}</p>
                <button type="button" class="popup__close video-modal__close" aria-label="{{ __('site.btn_close') ?? 'Закрыть' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
            <div class="popup__inner video-modal__body">
                <div class="popup__video-container video-modal__frame" id="video-player-container"
                     data-video-url="{{ $videoUrl }}"
                     data-video-kind="{{ $videoKind }}"
                     data-embed-src="{{ $embedSrc }}">
                    <span class="video-modal__spinner" aria-hidden="true"></span>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
