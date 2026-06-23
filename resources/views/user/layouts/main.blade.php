<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">

    @php
    $_isHome = Route::currentRouteName() === 'home';
    $_pageTitle = !empty($title)
    ? ($_isHome ? config('app.name').' — '.__('site.meta_subtitle') : $title.' — '.config('app.name'))
    : config('app.name').' — '.__('site.meta_subtitle');
    $_pageDescription = !empty($description) ? $description : __('site.meta_description');
    $_pageImage = url('/assets/img/apple-touch-icon.png');
    $_pageUrl = url()->current();
    @endphp
    <title>{{ $_pageTitle }}</title>
    <meta name="description" content="{{ $_pageDescription }}">
    <meta name="keywords" content="{{ __('site.meta_subtitle') }}" />

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $_pageTitle }}">
    <meta property="og:description" content="{{ $_pageDescription }}">
    <meta property="og:url" content="{{ $_pageUrl }}">
    <meta property="og:image" content="{{ $_pageImage }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'ru_RU' }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $_pageTitle }}">
    <meta name="twitter:description" content="{{ $_pageDescription }}">
    <meta name="twitter:image" content="{{ $_pageImage }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tg-bot-id" content="{{ (int) strtok((string) config('services.telegram.bot_token'), ':') }}">

    {{-- Suppress Yandex Browser image overlay (camera/search) + image indexing --}}
    <meta name="yandex" content="noimageindex">
    <meta name="robots" content="noimageindex">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16.png?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png?v=3">
    <link rel="shortcut icon" href="/assets/img/favicon.ico?v=3">

    <link rel="stylesheet" href="/assets/css/style.min.css?v=4.9.8">
    <!-- Libs -->
    <link rel="stylesheet" href="/assets/libs/Swiper/swiper-bundle.min.css?v=1.1">
    <link rel="stylesheet" href="/assets/libs/simplebar/simplebar.css">
    @if(env("CAPTCHA_ENABLED", false))<script src="https://js.hcaptcha.com/1/api.js" async defer></script>@endif
    <!-- End Libs -->
    <style>
        /* Hide the scrollbar site-wide — scrolling still works (wheel/touch/keys) */
        html,
        body {
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE/old Edge */
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            width: 0;
            height: 0;
            display: none;
            /* Chrome/Safari/Edge */
        }

        /* Zoom out to 90% (like one Ctrl+minus) — desktop only.
           On mobile/tablet content is already small; zooming it breaks
           viewport math and shifts content. */
        @media (min-width: 1024px) {
            body {
                zoom: 0.9;
            }
        }

        /* Disable text selection globally, allow on instruction/delivery pages */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .instruction,
        .instruction *,
        .key,
        .key *,
        input,
        textarea {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }

        .instruction iframe {
            width: 100%;
            margin: 10px 0;
            border-radius: 16px;
        }

        .instruction ol {
            padding: 15px 22px;
            line-height: 1.9;
        }

        .instruction p {
            line-height: 1.9;
        }

        @media (max-width: 767px) {
            .instruction iframe {
                height: 300px;
                /* Установите желаемую высоту для мобильных устройств */
            }
        }

        /* Стили для компьютеров (например, высота 500px) */
        @media (min-width: 768px) {
            .instruction iframe {
                height: 500px;
                /* Установите желаемую высоту для компьютеров */
            }
        }

        .swiper-functional.swiper-horizontal {
            display: block;
            grid-template-columns: repeat(3, 1fr);
        }
    </style>

    {{-- Schema.org structured data (Organization + WebSite) --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Organization",
                "name": "{{ config('app.name') }}",
                "url": "{{ url('/') }}",
                "logo": "{{ url('/assets/img/logo.png') }}",
                "description": "{{ __('site.meta_description') }}"
            },
            {
                "@type": "WebSite",
                "name": "{{ config('app.name') }}",
                "url": "{{ url('/') }}",
                "inLanguage": "{{ app()->getLocale() === 'en' ? 'en' : 'ru' }}",
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": "{{ url('/games') }}?q={search_term_string}",
                    "query-input": "required name=search_term_string"
                }
            }
        ]
    }
    </script>

    @if(config('services.analytics.yandex_metrika'))
    {{-- Yandex.Metrika --}}
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");
        ym({{ config('services.analytics.yandex_metrika') }}, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ config('services.analytics.yandex_metrika') }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    @endif

    @if(config('services.analytics.google_analytics'))
    {{-- Google Analytics (GA4) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.analytics.google_analytics') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ config('services.analytics.google_analytics') }}');
    </script>
    @endif
</head>

<body>

    <div class="preloader" id="preloader">
        <style>
            .preloader {
                position: fixed;
                left: 0;
                right: 0;
                top: 0;
                bottom: 0;
                background-color: #253144;
                z-index: 999;
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                -webkit-box-align: center;
                -ms-flex-align: center;
                align-items: center;
                -webkit-box-pack: center;
                -ms-flex-pack: center;
                justify-content: center;
                -webkit-transition: opacity .15s;
                -o-transition: opacity .15s;
                transition: opacity .15s
            }

            .preloader .circular-loader {
                -webkit-animation: 2s linear infinite rotate;
                animation: 2s linear infinite rotate;
                width: 200px;
                height: 200px;
                -webkit-transition: opacity .3s ease-in-out;
                -o-transition: opacity .3s ease-in-out;
                transition: opacity .3s ease-in-out
            }

            .preloader .circular-loader circle {
                stroke: #8456ff
            }

            .preloader .loader-path {
                stroke-dasharray: 150, 200;
                stroke-dashoffset: -10;
                -webkit-animation: 1.5s ease-in-out infinite dash;
                animation: 1.5s ease-in-out infinite dash;
                stroke-linecap: round
            }

            body:not(._loaded) {
                overflow: hidden
            }

            body._loaded .preloader {
                opacity: 0;
                pointer-events: none
            }

            body._loaded .preloader .circular-loader {
                opacity: 0
            }

            @-webkit-keyframes rotate {
                to {
                    -webkit-transform: rotate(360deg);
                    transform: rotate(360deg)
                }
            }

            @keyframes rotate {
                to {
                    -webkit-transform: rotate(360deg);
                    transform: rotate(360deg)
                }
            }

            @-webkit-keyframes dash {
                0% {
                    stroke-dasharray: 1, 200;
                    stroke-dashoffset: 0
                }

                50% {
                    stroke-dasharray: 89, 200;
                    stroke-dashoffset: -35
                }

                100% {
                    stroke-dasharray: 89, 200;
                    stroke-dashoffset: -124
                }
            }

            @keyframes dash {
                0% {
                    stroke-dasharray: 1, 200;
                    stroke-dashoffset: 0
                }

                50% {
                    stroke-dasharray: 89, 200;
                    stroke-dashoffset: -35
                }

                100% {
                    stroke-dasharray: 89, 200;
                    stroke-dashoffset: -124
                }
            }
        </style>
        <div class="loader">
            <svg class="circular-loader" viewBox="25 25 50 50" style="max-width:20vh;max-height:20vh;">
                <circle class="loader-path" cx="50" cy="50" r="20" fill="none" stroke="#F1FF9D" stroke-width="2" />
            </svg>
        </div>
    </div>
    <script>
        try {
            if (document.referrer && new URL(document.referrer).origin === location.origin) {
                document.getElementById("preloader").style.display = "none"
            }
        } catch (e) {}
        document.addEventListener("DOMContentLoaded", function() {
            document.body.classList.add("_loaded")
        });
    </script>

    <div class="wrapper">
        <header class="header">
            <div class="content header__container">
                <a href="/" class="header__logo">
                    <img src="/assets/img/logo.png" alt="">
                </a>
                <ul class="header__menu">
                    <li class="header__menu__item header__menu__item--dropdown" data-dropdown>
                        <button type="button" class="header__menu__pill" aria-haspopup="true" aria-expanded="false">
                            <span class="header__menu__pill-icon" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="3" width="7.5" height="7.5" rx="2" stroke="currentColor" stroke-width="2" />
                                    <rect x="13.5" y="3" width="7.5" height="7.5" rx="2" stroke="currentColor" stroke-width="2" />
                                    <rect x="3" y="13.5" width="7.5" height="7.5" rx="2" stroke="currentColor" stroke-width="2" />
                                    <rect x="13.5" y="13.5" width="7.5" height="7.5" rx="2" stroke="currentColor" stroke-width="2" />
                                </svg>
                            </span>
                            <span>{{ __('site.item_catalog') }}</span>
                            <span class="header__menu__pill-chev" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </button>
                        <div class="header__mega" role="menu">
                            <div class="header__mega__head">
                                <div>
                                    <p class="header__mega__title">{{ __('site.mega_pick_game') }}</p>
                                    <p class="header__mega__sub">@plural(($__headerCategories ?? collect())->count(), __('site.mega_count_one'), __('site.mega_count_few'), __('site.mega_count_many'))</p>
                                </div>
                                <a class="header__mega__all" href="/games">
                                    {{ __('site.mega_view_all') }}
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                        <path d="M5 12h14M13 5l7 7-7 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </a>
                            </div>
                            <div class="header__mega__grid">
                                @foreach(($__headerCategories ?? collect())->take(8) as $__cat)
                                <a class="header__mega__card" href="/{{ $__cat->alias }}" role="menuitem">
                                    <span class="header__mega__card-img header__mega__card-img--letter" data-letter="{{ mb_substr($__cat->title, 0, 1) }}">
                                        {{-- letter only — game cover images are heavily compressed and look bad --}}
                                    </span>
                                    <span class="header__mega__card-text">
                                        <span class="header__mega__card-name">{{ $__cat->title }}</span>
                                        @if(!empty($__cat->count_products))
                                        <span class="header__mega__card-meta">@plural((int)$__cat->count_products, __('site.text_x_cheats_one'), __('site.text_x_cheats_few'), __('site.text_x_cheats_many'))</span>
                                        @endif
                                    </span>
                                    <span class="header__mega__card-arrow" aria-hidden="true">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                            <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </a>
                                @endforeach
                            </div>
                            <div class="header__mega__foot">
                                <a class="header__mega__quick" href="/games?platform=pc">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="2" y="4" width="20" height="13" rx="2" />
                                        <path d="M8 21h8M12 17v4" />
                                    </svg>
                                    {{ __('site.catalog_tab_pc') }}
                                </a>
                                <a class="header__mega__quick" href="/games?platform=mobile">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="6" y="2" width="12" height="20" rx="2.5" />
                                        <line x1="11" y1="18" x2="13" y2="18" />
                                    </svg>
                                    {{ __('site.catalog_tab_mobile') }}
                                </a>
                                <a class="header__mega__quick" href="/status">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="9" />
                                        <polyline points="12 7 12 12 15 14" />
                                    </svg>
                                    {{ __('site.item_statuses') }}
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="header__menu__item"><a href="/status">{{ __('site.item_statuses') }}</a></li>
                    <li class="header__menu__item"><a href="/reviews">{{ __('site.item_reviews') }}</a></li>
                    <li class="header__menu__item"><a href="/about">{{ __('site.item_about') }}</a></li>
                    <li class="header__menu__item"><a href="/#faq" data-scroll="#faq">{{ __('site.item_help') }}</a></li>
                </ul>
                <div class="select header__lang">
                    <select name="lang">
                        <option value="ru" @if(app()->getLocale() == 'ru') selected @endif>RU</option>
                        <option value="en" @if(app()->getLocale() == 'en') selected @endif>EN</option>
                    </select>
                    <div class="select__selected">
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </div>
                    <div class="select__inner" id="lang">
                        <div onclick="changeLanguage('ru');" class="select__option @if(app()->getLocale() == 'ru') _active @endif" data-value="ru">
                            <span>RU</span>
                        </div>
                        <div onclick="changeLanguage('en');" class="select__option @if(app()->getLocale() == 'en') _active @endif" data-value="en">
                            <span>EN</span>
                        </div>
                    </div>
                </div>

                <button class="header__search-btn"></button>
                <form class="header__search" autocomplete="off">
                    <!-- Anti-autofill honeypot: absorbs browser credential autofill -->
                    <input type="text" name="prevent_autofill_username" style="display:none" tabindex="-1" aria-hidden="true">
                    <input type="password" name="prevent_autofill_password" style="display:none" tabindex="-1" aria-hidden="true">
                    <input id="search_query" name="site_search" autocomplete="new-password" class="header__search__input" placeholder="{{ __('site.input_search') }}">
                    <button class="header__search-reset-btn"></button>
                    <div class="header__search__inner">
                        <div class="header__search__scroll-container" id="results_search" data-simplebar></div>
                    </div>
                    <div class="header__search__overlay"></div>
                </form>
                @php
                $_authUser = null;
                $_authToken = request()->cookie('session_token');
                if ($_authToken) {
                try {
                $_authUser = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($_authToken)->authenticate();
                } catch (\Exception $e) { $_authUser = null; }
                }
                @endphp
                @if($_authUser)
                <a id="block-user" class="btn header__login" href="/my/profile">
                    <span class="btn__icon">
                        <img src="/assets/img/icon_user.svg" alt="">
                    </span>
                    <span class="header__login__label">{{ $_authUser->username }}</span>
                    <span class="header__login__chev" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </a>
                @else
                <a id="block-user" class="btn header__login" data-popup="auth">
                    <span class="btn__icon">
                        <img src="/assets/img/icon_user.svg" alt="">
                    </span>
                    <span class="header__login__label">{{ __('site.btn_login_register') }}</span>
                    <span class="header__login__chev" aria-hidden="true">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </a>
                @endif
                <button class="header__hamburger"><span></span></button>
            </div>
        </header>

        @yield('content')
        <footer class="footer">
            <div class="content footer__container">
                <a href="/" class="footer__logo">
                    <img src="/assets/img/logo.png" alt="">
                </a>
                <ul class="footer__menu">
                    <li class="footer__menu__item"><a href="/">{{ __('site.item_home') }}</a></li>
                    <li class="footer__menu__item"><a href="/status">{{ __('site.item_statuses') }}</a></li>
                    <li class="footer__menu__item"><a href="/about">{{ __('site.item_about') }}</a></li>
                    <li class="footer__menu__item"><a href="/#faq">{{ __('site.item_help') }}</a></li>
                    <li class="footer__menu__item"><a href="/policy">{{ __('site.item_policy') }}</a></li>
                </ul>
                <a href="{{ \App\Models\ShopSettings::getDefault()->btn_tg_bot_url ?? 'https://t.me/Fanru_bot' }}" class="social-btn footer__social-btn" target="_blank">
                    <span class="social-btn__icon">
                        @include('user.partials.icon', ['icon' => \App\Models\ShopSettings::getDefault()->btn_tg_bot_icon ?? 'telegram', 'color' => '#FFFFFF', 'size' => 80])
                    </span>
                    <span class="social-btn__text">{{ \App\Models\ShopSettings::getDefault()->btn_tg_bot_text ?: __('site.footer_tg_bot') }}</span>
                </a>
            </div>
            <div class="content footer__bottom-container">
                <a href="/policy" class="footer__menu__item">{{ __('site.item_policy') }}</a>
                <p class="footer__copy">© Fnrus 2026. {{ __('site.text_all_rights_reserved') }}</p>
            </div>
        </footer>
    </div>

    <div class="cookie-banner" id="cookie-banner" hidden role="dialog" aria-live="polite" aria-labelledby="cookie-banner-title">
        <div class="cookie-banner__inner">
            <div class="cookie-banner__text">
                <p class="cookie-banner__title" id="cookie-banner-title">{{ __('site.cookie_title') }}</p>
                <p class="cookie-banner__desc">{{ __('site.cookie_text') }}</p>
            </div>
            <div class="cookie-banner__actions">
                <button type="button" class="cookie-banner__btn cookie-banner__btn--primary" data-cookie-action="accept">{{ __('site.cookie_accept') }}</button>
                <button type="button" class="cookie-banner__btn cookie-banner__btn--secondary" data-cookie-action="decline">{{ __('site.cookie_decline') }}</button>
            </div>
        </div>
    </div>
    <script>
        (function() {
            try {
                var KEY = 'cookie_consent_v1';
                if (localStorage.getItem(KEY)) return;
                var banner = document.getElementById('cookie-banner');
                if (!banner) return;
                // Defer show a bit so it doesn't fight the page reveal animation
                setTimeout(function() {
                    banner.hidden = false;
                    requestAnimationFrame(function() {
                        banner.classList.add('is-visible');
                    });
                }, 600);
                banner.addEventListener('click', function(e) {
                    var btn = e.target.closest('[data-cookie-action]');
                    if (!btn) return;
                    try {
                        localStorage.setItem(KEY, btn.dataset.cookieAction + ':' + Date.now());
                    } catch (_) {}
                    banner.classList.remove('is-visible');
                    setTimeout(function() {
                        banner.hidden = true;
                    }, 280);
                });
            } catch (_) {}
        })();
    </script>

    <button class="to-top" type="button" aria-label="{{ __('site.btn_to_top') }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M12 19V5M12 5L5 12M12 5L19 12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>

    <div class="popup" id="auth">
        <p class="popup__caption">{{ __('site.modal_auth_title') }}</p>
        <div class="input-block">
            <div class="input-block__label-container">
                <label
                    for="username"
                    class="input-block__label">
                    {{ __('site.modal_auth_label_username') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_auth_placeholder_username') }}"
                    id="username">
            </div>
        </div>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="password"
                    class="input-block__label">
                    {{ __('site.modal_auth_label_password') }}
                </label>
                <button class="input-block__label-link" data-popup="resetPass">{{ __('site.btn_remember_password') }}</button>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_auth_placeholder_password') }}"
                    id="password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        @if(env("CAPTCHA_ENABLED", false))<div class="h-captcha" data-sitekey="{{ env("HCAPTCHA_SITE_KEY") }}" data-theme="dark" style="margin: 0 auto;margin-top:20px;display:block;width:304px"></div>@endif
        <button class="btn btn-accent popup__submit-btn" style="margin-bottom: 20px;margin-top:20px" onclick="signIn();return false;">{{ __('site.btn_login') }}</button>
        <hr style="border: 1px solid #1d2533;" />
        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
            <div class="popup__social">
            <a href="/oauth/yandex/redirect" class="btn btn-yandex popup__submit-btn" style="margin-top: 20px"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px">
                    <path d="M13.32 21H15.9V3h-3.78c-3.78 0-5.94 2.16-5.94 5.28 0 2.52 1.26 4.02 3.54 5.58L6 21h2.82l4.2-7.86c-2.52-1.68-3.42-2.82-3.42-5.04 0-2.1 1.38-3.42 3.42-3.42h.3V21z" />
                </svg> {{ __('site.login_via_yandex') }}</a>
            <a href="javascript:void(0)" class="btn btn-telegram popup__submit-btn" style="margin-top: 14px" onclick="tgLogin();return false;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px">
                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71l-4.14-3.05-1.99 1.93c-.23.23-.42.42-.83.42z" />
                </svg> {{ __('site.login_via_telegram') }}</a>
            </div>
        </div>

        <p class="popup__hint">{{ __('site.text_no_account') }} <button class="popup__hint__btn" data-popup="register">{{ __('site.btn_register_two') }}</button></p>
    </div>

    <div class="popup" id="register">
        <p class="popup__caption">{{ __('site.modal_register_title') }}</p>
        <div class="input-block">
            <div class="input-block__label-container">
                <label
                    for="reg-username"
                    class="input-block__label">
                    {{ __('site.modal_register_label_username') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_register_placeholder_username') }}"
                    id="reg-username">
            </div>
        </div>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="reg-password"
                    class="input-block__label">
                    {{ __('site.modal_register_label_password') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_register_placeholder_password') }}"
                    id="reg-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="reg-re-password"
                    class="input-block__label">
                    {{ __('site.modal_register_label_confirm_password') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_register_placeholder_confirm_password') }}"
                    id="reg-re-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        <div id="captcha"></div>
        <div class="toggle popup__toggle">
            <input
                type="checkbox"
                class="toggle__input"
                @if (Session::get('referral_code'))
                checked="checked"
                @endif
                id="register-referral-toggle">
            <label
                for="register-referral-toggle"
                class="toggle__label">
                <span class="toggle__icon"></span>
                <span class="toggle__text">
                    {{ __('site.modal_register_toggle_is_referral_code') }}
                </span>
            </label>
        </div>
        <div class="input-block" id="register-referral-input-block" @if (!Session::get('referral_code'))style="display: none" @endif>
            <div class="input-block__label-container">
                <label
                    for="reg-referral-code"
                    class="input-block__label">
                    {{ __('site.modal_register_label_referral_code') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_register_placeholder_referral_code') }}"
                    id="reg-referral-code"
                    value="@if (Session::get('referral_code')){{Session::get('referral_code')}}@endif">

            </div>
        </div>
        @if(env("CAPTCHA_ENABLED", false))<div class="h-captcha" data-sitekey="{{ env("HCAPTCHA_SITE_KEY") }}" data-theme="dark" style="margin: 0 auto;margin-top:20px;display:block;width:304px"></div>@endif
        <button class="btn btn-accent popup__submit-btn" style="margin-bottom: 20px;margin-top:20px" onclick="signUp();">{{ __('site.btn_register_two') }}</button>
        <hr style="border: 1px solid #1d2533;" />
        <div>
            <div class="popup__social">
            <a href="/oauth/yandex/redirect" class="btn btn-yandex popup__submit-btn" style="margin-top: 20px !important;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px">
                    <path d="M13.32 21H15.9V3h-3.78c-3.78 0-5.94 2.16-5.94 5.28 0 2.52 1.26 4.02 3.54 5.58L6 21h2.82l4.2-7.86c-2.52-1.68-3.42-2.82-3.42-5.04 0-2.1 1.38-3.42 3.42-3.42h.3V21z" />
                </svg> {{ __('site.login_via_yandex') }}</a>
            <a href="javascript:void(0)" class="btn btn-telegram popup__submit-btn" style="margin-top: 14px !important;" onclick="tgLogin();return false;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px">
                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71l-4.14-3.05-1.99 1.93c-.23.23-.42.42-.83.42z" />
                </svg> {{ __('site.login_via_telegram') }}</a>
            </div>

        </div>
        <p class="popup__note">{{ __('site.text_processing_personal_data') }}</p>
        <p class="popup__hint">{{ __('site.text_isset_account') }} <button class="popup__hint__btn" data-popup="auth">{{ __('site.btn_login') }}</button></p>
    </div>

    <div class="popup" id="resetPass">
        <p class="popup__caption">{{ __('site.forgot_password_title') }}</p>
        <div class="input-block">
            <div class="input-block__label-container">
                <label
                    for="reset-email"
                    class="input-block__label">
                    {{ __('site.modal_reset_pass_label_username') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_reset_pass_placeholder_username') }}"
                    id="reset-email">
            </div>
        </div>
        <div class="input-block" style="display: none" id="reset-block-code">
            <div class="input-block__label-container">
                <label
                    for="reset-code"
                    class="input-block__label">
                    {{ __('site.modal_reset_pass_label_code') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_reset_pass_placeholder_code') }}"
                    id="reset-code">
            </div>
        </div>
        <div class="input-block" style="display: none" id="reset-block-new-password">
            <div class="input-block__label-container">
                <label
                    for="reset-new-password"
                    class="input-block__label">
                    {{ __('site.modal_reset_pass_label_new_pass') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_reset_pass_placeholder_new_pass') }}"
                    id="reset-new-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        @if(env("CAPTCHA_ENABLED", false))<div class="h-captcha" data-sitekey="{{ env("HCAPTCHA_SITE_KEY") }}" data-theme="dark" style="margin: 0 auto;margin-top:20px;display:block;width:304px"></div>@endif
        <button class="btn btn-accent popup__submit-btn" type="submit" style="margin-top:20px;" onclick="resetPassword();">{{ __('site.modal_reset_pass_btn') }}</button>
    </div>

    <div class="popup" id="changePass">
        <p class="popup__caption">{{ __('site.modal_change_pass_title') }}</p>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="change-old-password"
                    class="input-block__label">
                    {{ __('site.modal_change_pass_label_old_pass') }}
                </label>
                <button class="input-block__label-link" data-popup="resetPass">{{ __('site.btn_remember_password') }}</button>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_change_pass_placeholder_old_pass') }}"
                    id="change-old-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="change-new-password"
                    class="input-block__label">
                    {{ __('site.modal_change_pass_label_new_pass') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_change_pass_placeholder_new_pass') }}"
                    id="change-new-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for="change-repeat-password"
                    class="input-block__label">
                    {{ __('site.modal_change_pass_label_repeat_pass') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="password"
                    class="input-block__input"
                    placeholder="{{ __('site.modal_change_pass_placeholder_repeat_pass') }}"
                    id="change-repeat-password">
                <button class="input-block__showHide-btn"></button>
            </div>
        </div>
        <button class="btn btn-accent popup__submit-btn" type="submit" onclick="changePassword();">{{ __('site.modal_change_pass_btn') }}</button>
    </div>

    {{--<div class="popup" id="replenishment">--}}
    {{-- <p class="popup__caption">{{ __('site.modal_topup_balance_title') }}</p>--}}
    {{-- <div class="popup__step _active" data-popup-step="1">--}}
    {{-- <div class="popup__payment-methods" id="topup-payments-methods"></div>--}}
    {{-- <div class="">--}}
    {{-- <div class="input-block">--}}
    {{-- <div class="input-block__input-container">--}}
    {{-- <input type="text" class="input-block__input" onkeyup="paymentMethodsTopup(this.value)" id="topup_sum" placeholder="{{ __('site.modal_topup_balance_placeholder_sum') }}">--}}
    {{-- </div>--}}
    {{-- </div>--}}
    {{-- </div>--}}
    {{-- <button class="btn btn-accent popup__submit-btn" type="submit" onclick="createOrderTopup();">{{ __('site.modal_topup_balance_btn') }}</button>--}}
    {{-- </div>--}}
    {{-- <div class="popup__step" data-popup-step="2">--}}
    {{-- <div id="system-msg" style="margin: 87px auto; display: block; text-align: center;">--}}
    {{-- <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;"><path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>--}}
    {{-- <h2 style="margin-top: 15px;">Баланс пополнен</h2>--}}
    {{-- <a href="javascript:;" data-popup-switch-step="1" class="btn popup__back-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 230px;">Пополнить еще</a>--}}
    {{-- </div>--}}
    {{-- </div>--}}
    {{--</div>--}}


    <div class="popup popup--pay" id="replenishment">
        <div id="result">
            <div id="body">
                <p class="popup__caption">{{ __('site.modal_topup_balance_title') }}</p>
                <div class="popup__step _active" data-popup-step="1">
                    <div class="input-block">
                        <div class="input-block__input-container">
                            <input type="text" class="input-block__input" id="topup_sum" placeholder="{{ __('site.modal_topup_balance_placeholder_sum') }}">
                        </div>
                    </div>
                    <button class="btn btn-accent popup__submit-btn" type="submit" onclick="createOrderTopup();">
                        {{ __('site.modal_topup_balance_btn') }}</button>
                </div>
                <div class="popup__step" data-popup-step="2">
                    <div class="popup__payment-methods" id="topup-payments-methods"></div>
                    <div class="popup__buy-info">
                        <div class="popup__buy-info__block">{{ __('site.modal_buy_total_payable') }} <span id="buy-sum">...</span></div>
                    </div>
                    <div class="popup__btns-container">
                        <button class="btn popup__back-btn" onclick="clearInterval(timerInterval);" data-popup-switch-step="1">{{ __('site.modal_buy_btn_back') }}</button>
                        <button class="btn btn-accent popup__submit-btn" type="submit" id="btn-pay">{{ __('site.modal_buy_btn_payment') }}</button>
                    </div>
                </div>
                <div class="popup__step" data-popup-step="3">
                    <div id="system-msg" style="margin: 87px auto; display: block; text-align: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;">
                            <path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path>
                        </svg>
                        <h2 style="margin-top: 15px;">{{ __('site.balance_replenished') }}</h2>
                        <a href="javascript:;" data-popup-switch-step="1" class="btn popup__back-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 220px;">{{ __('site.replenish_more') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php $supportSettings = \App\Models\ShopSettings::getDefault(); @endphp
    <div class="popup" id="supportModal">
        <p class="popup__caption">{{ __('site.support_title') }}</p>
        @if($supportSettings->support_text)
        <p style="text-align: center; margin-bottom: 20px; color: rgba(255,255,255,0.7); font-size: 14px; line-height: 1.5;">{{ $supportSettings->support_text }}</p>
        @endif
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @if($supportSettings->support_btn1_text && $supportSettings->support_btn1_url)
            <a href="{{ $supportSettings->support_btn1_url }}" target="_blank" class="btn btn-accent" style="text-align: center; padding: 14px 20px; text-decoration: none;">{{ $supportSettings->support_btn1_text }}</a>
            @endif
            @if($supportSettings->support_btn2_text && $supportSettings->support_btn2_url)
            <a href="{{ $supportSettings->support_btn2_url }}" target="_blank" class="btn btn-accent" style="text-align: center; padding: 14px 20px; text-decoration: none;">{{ $supportSettings->support_btn2_text }}</a>
            @endif
            @if($supportSettings->support_btn3_text && $supportSettings->support_btn3_url)
            <a href="{{ $supportSettings->support_btn3_url }}" target="_blank" class="btn btn-accent" style="text-align: center; padding: 14px 20px; text-decoration: none;">{{ $supportSettings->support_btn3_text }}</a>
            @endif
        </div>
    </div>

    <div class="popup" id="review">
        <p class="popup__caption">{{ __('site.btn_leave_review') }}</p>
        <div class="input-block">
            <div class="input-block__label-container">
                <label for="review-author" class="input-block__label">{{ __('site.review_form_name') }}</label>
            </div>
            <div class="input-block__input-container">
                <input type="text" class="input-block__input" id="review-author" placeholder="{{ __('site.review_form_name_ph') }}">
            </div>
        </div>
        <div class="input-block">
            <div class="input-block__label-container">
                <label for="review-text" class="input-block__label">{{ __('site.review_form_text') }}</label>
            </div>
            <div class="input-block__input-container">
                <textarea class="input-block__input input-block__textarea" id="review-text" rows="4" placeholder="{{ __('site.review_form_text_ph') }}"></textarea>
            </div>
        </div>
        <div class="input-block">
            <div class="input-block__label-container">
                <label for="review-link" class="input-block__label">{{ __('site.review_form_link') }}</label>
            </div>
            <div class="input-block__input-container">
                <input type="text" class="input-block__input" id="review-link" placeholder="https://t.me/...">
            </div>
        </div>
        <button class="btn btn-accent popup__submit-btn" type="button" style="margin-top:20px" onclick="submitReview();return false;">{{ __('site.btn_leave_review') }}</button>
    </div>

    <script src="/assets/js/scripts.min.js?63"></script>
    <script src="/assets/js/animations.js?v=23"></script>
    <script src="/assets/js/bg-fx.js?v=10" defer></script>
    <script src="/assets/js/sticky-header.js?v=2" defer></script>
    <script>
        window.lang = {
            my_profile: @json(__('site.section_user_menu_profile')),
            discount_applied: @json(__('site.js_discount_applied')),
            error_order_data_not_found: @json(__('site.js_error_order_data_not_found')),
            choose_payment_method: @json(__('site.js_choose_payment_method')),
            min_payment_amount: @json(__('site.js_min_payment_amount')),
            creating_order: @json(__('site.js_creating_order')),
            pay: @json(__('site.modal_buy_btn_payment')),
            buy_from: @json(__('site.js_buy_from')),
            bank_card: @json(__('site.js_bank_card')),
            withdrawal_method: @json(__('site.js_withdrawal_method')),
            account_not_connected: @json(__('site.js_account_not_connected')),
            error_occurred: @json(__('site.js_error_occurred')),
            purchase_date: @json(__('site.section_user_orders_table_col_date_buy')),
            period: @json(__('site.section_user_orders_table_col_days')),
            price: @json(__('site.section_user_orders_table_col_price')),
            nothing_found: @json(__('site.js_nothing_found')),
            server_connection_error: @json(__('site.js_server_connection_error')),
            order_paid: @json(__('site.cheat_order_paid'))
        };

        function changeLanguage(locale) {
            window.location.href = '/lang/' + locale;
        }

        // Header dropdown — hover-intent on desktop, click on touch/mobile
        (function() {
            var item = document.querySelector('.header__menu__item--dropdown');
            if (!item) return;
            var btn = item.querySelector('.header__menu__pill');
            if (!btn) return;

            var hoverDelay = 80; // ms to wait before opening (intent guard)
            var closeDelay = 180; // ms after leave to close
            var openTimer = null;
            var closeTimer = null;
            var isTouch = matchMedia('(hover: none)').matches;

            function open() {
                clearTimeout(closeTimer);
                closeTimer = null;
                if (!item.classList.contains('is-open')) {
                    item.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            }

            function close() {
                clearTimeout(openTimer);
                openTimer = null;
                item.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }

            if (!isTouch) {
                item.addEventListener('mouseenter', function() {
                    clearTimeout(closeTimer);
                    openTimer = setTimeout(open, hoverDelay);
                });
                item.addEventListener('mouseleave', function() {
                    clearTimeout(openTimer);
                    closeTimer = setTimeout(close, closeDelay);
                });
            }

            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (item.classList.contains('is-open')) close();
                else open();
            });

            document.addEventListener('click', function(e) {
                if (!item.contains(e.target)) close();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') close();
            });
        })();
    </script>
    <script src="/assets/js/app.js?v=5.3"></script>
    <!-- Telegram login via bot deep-link (no widget) -->
    <script>
        (function () {
            var tgPollTimer = null, tgDeadline = 0;

            function tgCsrf() {
                var m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.content : '';
            }

            function tgFinish(jwt) {
                // logs the user in the same way the normal login flow does
                var exp = new Date(Date.now() + 15 * 24 * 60 * 60 * 1000);
                document.cookie = 'session_token=' + jwt + '; expires=' + exp.toUTCString() + '; path=/';
                window.location.href = '/my/profile';
            }

            function tgPoll(token) {
                if (Date.now() > tgDeadline) { return; } // 5 min timeout — silently stop
                fetch('/api/auth/telegram/poll/' + token, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j && j.ok && j.session_token) { tgFinish(j.session_token); return; }
                        tgPollTimer = setTimeout(function () { tgPoll(token); }, 2000);
                    })
                    .catch(function () {
                        tgPollTimer = setTimeout(function () { tgPoll(token); }, 2500);
                    });
            }

            window.tgLogin = function () {
                if (tgPollTimer) { clearTimeout(tgPollTimer); }
                fetch('/api/auth/telegram/start', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tgCsrf() }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (!j || !j.ok || !j.link) {
                            alert((j && j.description) || 'Вход через Telegram временно недоступен.');
                            return;
                        }
                        // open the bot so the user just presses Start
                        window.open(j.link, '_blank');
                        tgDeadline = Date.now() + 5 * 60 * 1000;
                        tgPoll(j.token);
                    })
                    .catch(function () {
                        alert('Не удалось начать вход через Telegram. Попробуйте позже.');
                    });
            };
        })();
    </script>
    <!-- JivoChat Widget -->
    <script>
        (function() {
            function loadJivo() {
                if (window.__jivoLoaded) return;
                window.__jivoLoaded = true;
                var s = document.createElement('script');
                s.src = '//code.jivo.ru/widget/3tZ9JRQowl';
                s.async = true;
                document.head.appendChild(s);
            }
            if (document.readyState === 'complete') {
                (window.requestIdleCallback || function(cb) {
                    setTimeout(cb, 3000);
                })(loadJivo, {
                    timeout: 5000
                });
            } else {
                window.addEventListener('load', function() {
                    (window.requestIdleCallback || function(cb) {
                        setTimeout(cb, 3000);
                    })(loadJivo, {
                        timeout: 5000
                    });
                });
            }
        })();
    </script>

    <script>
        /* Горизонтальные карусели на нативном скролле (.func-grid + game-слайдеры):
           1) edge-fade только со стороны, где выглядывает соседняя карточка;
           2) стрелки ‹ › управляются по реальной позиции скролла (а не Swiper),
              т.к. Swiper-состояние оторвано от нативного скролла: его transform
              по клику съезжал на «свою» ширину слайда (≠ CSS), обрезая первую
              карточку и оставляя щель, а кнопка prev застревала disabled. */
        (function() {
            function isNative(el) {
                return getComputedStyle(el).overflowX === 'auto';
            }
            function isGame(el) {
                return el.classList.contains('game-cheats-slider') || el.classList.contains('game-cards-slider');
            }
            function arrowsFor(el) {
                var n = el;
                while (n && n !== document.body) {
                    var p = n.querySelector('.slider-arrows__prev, .swiper-button-prev');
                    var x = n.querySelector('.slider-arrows__next, .swiper-button-next');
                    if (p || x) return { prev: p, next: x };
                    n = n.parentElement;
                }
                return {};
            }
            function update(el) {
                var max = el.scrollWidth - el.clientWidth;
                var atStart = el.scrollLeft <= 2;
                var atEnd = el.scrollLeft >= max - 2;
                el.classList.toggle('fade-l', !atStart);
                el.classList.toggle('fade-r', !atEnd);
                if (isGame(el) && isNative(el)) {
                    var a = arrowsFor(el);
                    var prevOff = atStart || max <= 2;
                    var nextOff = atEnd || max <= 2;
                    if (a.prev) { setArrow(a.prev, prevOff); }
                    if (a.next) { setArrow(a.next, nextOff); }
                }
            }
            function setArrow(btn, off) {
                /* Swiper выставляет у <button> атрибут disabled (а disabled-кнопка
                   не даёт click вовсе) и aria-disabled — снимаем их и ведём
                   состояние сами по реальному скроллу. */
                btn.classList.remove('swiper-button-disabled');
                btn.classList.toggle('cf-arrow-off', off);
                btn.disabled = off;
                if (off) { btn.setAttribute('aria-disabled', 'true'); }
                else { btn.removeAttribute('aria-disabled'); btn.removeAttribute('tabindex'); }
            }
            function bind(el) {
                update(el);
                el.addEventListener('scroll', function() { update(el); }, { passive: true });
                window.addEventListener('resize', function() { setTimeout(function(){ update(el); }, 50); }, { passive: true });
            }
            function init() {
                document.querySelectorAll('.func-grid, .game-cheats-slider, .game-cards-slider').forEach(bind);
            }
            if (document.readyState !== 'loading') init();
            else document.addEventListener('DOMContentLoaded', init);

            function sliderFor(arrow) {
                var n = arrow;
                while (n && n !== document.body) {
                    var s = n.querySelector('.game-cheats-slider, .game-cards-slider');
                    if (s) return s;
                    n = n.parentElement;
                }
                return null;
            }
            document.addEventListener('click', function(e) {
                var arrow = e.target.closest('.slider-arrows__arrow, .swiper-button-next, .swiper-button-prev, [class*="slider-arrow"]');
                if (!arrow) return;
                var slider = sliderFor(arrow);
                if (!slider || !isNative(slider)) return;  // desktop Swiper-mode: leave to Swiper
                e.preventDefault();
                e.stopImmediatePropagation();
                if (arrow.classList.contains('cf-arrow-off')) return; // at the edge
                if (slider.swiper) { try { slider.swiper.setTranslate(0); } catch (err) {} }
                var w = slider.querySelector('.swiper-wrapper');
                if (w) w.style.transform = 'none';
                var slide = slider.querySelector('.swiper-slide');
                var pitch = slide ? Math.round(slide.getBoundingClientRect().width) + 16 : Math.round(slider.clientWidth * 0.85);
                var isPrev = /prev|left/i.test(arrow.className);
                slider.scrollBy({ left: (isPrev ? -1 : 1) * pitch, behavior: 'smooth' });
            }, true);
        })();
    </script>
</body>

</html>