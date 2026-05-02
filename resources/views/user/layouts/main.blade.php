<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">

<title>@if(empty($title)) {{ config('app.name') }}  @else @if (Route::currentRouteName() !== 'home'){{ $title }} — {{ config('app.name') }} @else {{ config('app.name') }} — {{ __('site.meta_subtitle') }} @endif @endif</title>
<meta name="description" content="{{ __('site.meta_description') }}">
    <meta name="keywords" content="keywords"/>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16.png?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png?v=3">
    <link rel="shortcut icon" href="/assets/img/favicon.ico?v=3">

    <link rel="stylesheet" href="/assets/css/style.min.css?v=1.9.39">
    <!-- Libs -->
    <link rel="stylesheet" href="/assets/libs/Swiper/swiper-bundle.min.css?v=1.0">
    <link rel="stylesheet" href="/assets/libs/simplebar/simplebar.css">
    @if(env("CAPTCHA_ENABLED", false))<script src="https://js.hcaptcha.com/1/api.js" async defer></script>@endif
    <!-- End Libs -->
    <style>
        /* Reserve scrollbar gutter — prevents page shift when scrollbar appears/disappears or modals open */
        html { scrollbar-gutter: stable; }
        /* Zoom out to 90% on desktop only (like one Ctrl+minus) */
        @media (min-width: 1024px) {
            body { zoom: 0.9; }
        }

        /* Disable text selection globally, allow on instruction/delivery pages */
        body {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .instruction, .instruction *, .key, .key *, input, textarea {
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
            line-height:1.9;
        }

        .instruction p {
            line-height:1.9;
        }

        @media (max-width: 767px) {
            .instruction iframe {
                height: 300px; /* Установите желаемую высоту для мобильных устройств */
            }
        }

        /* Стили для компьютеров (например, высота 500px) */
        @media (min-width: 768px) {
            .instruction iframe {
                height: 500px; /* Установите желаемую высоту для компьютеров */
            }
        }

        .swiper-functional.swiper-horizontal {
            display: block;
            grid-template-columns: repeat(3, 1fr);
        }

    </style>
</head>
<body>

<div class="preloader" id="preloader">
    <style>.preloader{position:fixed;left:0;right:0;top:0;bottom:0;background-color:#253144;z-index:999;display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;-webkit-box-pack:center;-ms-flex-pack:center;justify-content:center;-webkit-transition:opacity .15s;-o-transition:opacity .15s;transition:opacity .15s}.preloader .circular-loader{-webkit-animation:2s linear infinite rotate;animation:2s linear infinite rotate;width:200px;height:200px;-webkit-transition:opacity .3s ease-in-out;-o-transition:opacity .3s ease-in-out;transition:opacity .3s ease-in-out}.preloader .circular-loader circle{stroke:#8456ff}.preloader .loader-path{stroke-dasharray:150,200;stroke-dashoffset:-10;-webkit-animation:1.5s ease-in-out infinite dash;animation:1.5s ease-in-out infinite dash;stroke-linecap:round}body:not(._loaded){overflow:hidden}body._loaded .preloader{opacity:0;pointer-events:none}body._loaded .preloader .circular-loader{opacity:0}@-webkit-keyframes rotate{to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@keyframes rotate{to{-webkit-transform:rotate(360deg);transform:rotate(360deg)}}@-webkit-keyframes dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35}100%{stroke-dasharray:89,200;stroke-dashoffset:-124}}@keyframes dash{0%{stroke-dasharray:1,200;stroke-dashoffset:0}50%{stroke-dasharray:89,200;stroke-dashoffset:-35}100%{stroke-dasharray:89,200;stroke-dashoffset:-124}}</style>
    <div class="loader">
        <svg class="circular-loader" viewBox="25 25 50 50" style="max-width:20vh;max-height:20vh;">
            <circle class="loader-path" cx="50" cy="50" r="20" fill="none" stroke="#F1FF9D" stroke-width="2" />
        </svg>
    </div>
</div>
<script>try{if(document.referrer&&new URL(document.referrer).origin===location.origin){document.getElementById("preloader").style.display="none"}}catch(e){}document.addEventListener("DOMContentLoaded",function(){document.body.classList.add("_loaded")});</script>

<div class="wrapper">
    <header class="header">
        <div class="content header__container">
            <a href="/" class="header__logo">
                <img src="/assets/img/logo.png" alt="">
            </a>
            <ul class="header__menu">
                <li class="header__menu__item"><a href="/">{{ __('site.item_home') }}</a></li>
                <li class="header__menu__item"><a href="/status">{{ __('site.item_statuses') }}</a></li>
                <li class="header__menu__item"><a href="/about">{{ __('site.item_about') }}</a></li>
                <li class="header__menu__item"><a href="/#faq" data-scroll="#faq">{{ __('site.item_help') }}</a></li>
            </ul>
            <div class="select header__lang">
                <select name="lang">
                    <option value="ru" @if(app()->getLocale() == 'ru') selected @endif>RU</option>
                    <option value="en" @if(app()->getLocale() == 'en') selected @endif>EN</option>
                </select>
                @if(app()->getLocale() == 'ru')
                <div class="select__selected">
                    <img src="/assets/img/flag_ru.svg" alt="">
                    <span>RU</span>
                </div>
                @endif
                @if(app()->getLocale() == 'en')
                    <div class="select__selected">
                        <img src="/assets/img/flag_en.svg" alt="">
                        <span>EN</span>
                    </div>
                @endif
                <div class="select__inner" id="lang">
                    <div onclick="changeLanguage('ru');" class="select__option @if(app()->getLocale() == 'ru') _active @endif" data-value="ru">
                        <img src="/assets/img/flag_ru.svg" alt="">
                        <span>RU</span>
                    </div>
                    <div onclick="changeLanguage('en');" class="select__option @if(app()->getLocale() == 'en') _active @endif" data-value="en">
                        <img src="/assets/img/flag_en.svg" alt="">
                        <span>EN</span>
                    </div>
                </div>
            </div>

            <button class="header__search-btn"></button>
            <form class="header__search" autocomplete="off">
                <!-- Anti-autofill honeypot: absorbs browser credential autofill -->
                <input type="text" name="prevent_autofill_username" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="password" name="prevent_autofill_password" style="display:none" tabindex="-1" aria-hidden="true">
                <input type="search" id="search_query" name="site_search" autocomplete="new-password" class="header__search__input" placeholder="{{ __('site.input_search') }}">
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
                        <span>{{ $_authUser->username }}</span>
                </a>
            @else
                <a id="block-user" class="btn header__login" data-popup="auth">
                        <span class="btn__icon">
                            <img src="/assets/img/icon_user.svg" alt="">
                        </span>
                {{ __('site.btn_login_register') }}
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
                <span class="social-btn__text">{{ \App\Models\ShopSettings::getDefault()->btn_tg_bot_text ?? 'Telegram Bot' }}</span>
            </a>
        </div>
        <div class="content footer__bottom-container">
            <a href="/policy" class="footer__menu__item">{{ __('site.item_policy') }}</a>
            <p class="footer__copy">© Fnrus 2026. {{ __('site.text_all_rights_reserved') }}</p>
        </div>
    </footer>
</div>

<div class="popup" id="auth">
    <p class="popup__caption">{{ __('site.modal_auth_title') }}</p>
    <div class="input-block">
        <div class="input-block__label-container">
            <label
                for="username"
                class="input-block__label"
            >
                {{ __('site.modal_auth_label_username') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="text"
                class="input-block__input"
                placeholder="{{ __('site.modal_auth_placeholder_username') }}"
                id="username"
            >
        </div>
    </div>
    <div class="input-block input-block_showHide">
        <div class="input-block__label-container">
            <label
                for="password"
                class="input-block__label"
            >
                {{ __('site.modal_auth_label_password') }}
            </label>
            <button class="input-block__label-link" data-popup="resetPass">{{ __('site.btn_remember_password') }}</button>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_auth_placeholder_password') }}"
                id="password"
            >
            <button class="input-block__showHide-btn"></button>
        </div>
    </div>
    @if(env("CAPTCHA_ENABLED", false))<div class="h-captcha" data-sitekey="{{ env("HCAPTCHA_SITE_KEY") }}" data-theme="dark" style="margin: 0 auto;margin-top:20px;display:block;width:304px"></div>@endif
    <button class="btn btn-accent popup__submit-btn" style="margin-bottom: 20px;margin-top:20px" onclick="signIn();return false;">{{ __('site.btn_login') }}</button>
    <hr style="border: 1px solid #1d2533;" />
    <a href="/oauth/yandex/redirect" class="btn btn-yandex popup__submit-btn" style="margin-top: 20px"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px"><path d="M13.32 21H15.9V3h-3.78c-3.78 0-5.94 2.16-5.94 5.28 0 2.52 1.26 4.02 3.54 5.58L6 21h2.82l4.2-7.86c-2.52-1.68-3.42-2.82-3.42-5.04 0-2.1 1.38-3.42 3.42-3.42h.3V21z"/></svg> {{ __('site.login_via_yandex') }}</a>
    {{-- <a href="#" class="btn btn-auth-tg popup__submit-btn" style="margin-top: 20px"><svg xmlns="http://www.w3.org/2000/svg" style="fill: #fff;margin-right: 7px" viewBox="0 0 448 512" width="18" height="18"><path d="M446.7 98.6l-67.6 318.8c-5.1 22.5-18.4 28.1-37.3 17.5l-103-75.9-49.7 47.8c-5.5 5.5-10.1 10.1-20.7 10.1l7.4-104.9 190.9-172.5c8.3-7.4-1.8-11.5-12.9-4.1L117.8 284 16.2 252.2c-22.1-6.9-22.5-22.1 4.6-32.7L418.2 66.4c18.4-6.9 34.5 4.1 28.5 32.2z"/></svg> {{ __('site.login_via_telegram') }}</a> --}}
    <p class="popup__hint">{{ __('site.text_no_account') }} <button class="popup__hint__btn" data-popup="register">{{ __('site.btn_register_two') }}</button></p>
</div>

<div class="popup" id="register">
    <p class="popup__caption">{{ __('site.modal_register_title') }}</p>
    <div class="input-block">
        <div class="input-block__label-container">
            <label
                for="reg-username"
                class="input-block__label"
            >
                {{ __('site.modal_register_label_username') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="text"
                class="input-block__input"
                placeholder="{{ __('site.modal_register_placeholder_username') }}"
                id="reg-username"
            >
        </div>
    </div>
    <div class="input-block input-block_showHide">
        <div class="input-block__label-container">
            <label
                for="reg-password"
                class="input-block__label"
            >
                {{ __('site.modal_register_label_password') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_register_placeholder_password') }}"
                id="reg-password"
            >
            <button class="input-block__showHide-btn"></button>
        </div>
    </div>
    <div class="input-block input-block_showHide">
        <div class="input-block__label-container">
            <label
                for="reg-re-password"
                class="input-block__label"
            >
                {{ __('site.modal_register_label_confirm_password') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_register_placeholder_confirm_password') }}"
                id="reg-re-password"
            >
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
            id="register-referral-toggle"
        >
        <label
            for="register-referral-toggle"
            class="toggle__label"
        >
            <span class="toggle__icon"></span>
            <span class="toggle__text">
					{{ __('site.modal_register_toggle_is_referral_code') }}
				</span>
        </label>
    </div>
    <div class="input-block" id="register-referral-input-block" @if (!Session::get('referral_code'))style="display: none"@endif>
        <div class="input-block__label-container">
            <label
                for="reg-referral-code"
                class="input-block__label"
            >
                {{ __('site.modal_register_label_referral_code') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="text"
                class="input-block__input"
                placeholder="{{ __('site.modal_register_placeholder_referral_code') }}"
                id="reg-referral-code"
                value="@if (Session::get('referral_code')){{Session::get('referral_code')}}@endif"
                >

        </div>
    </div>
    @if(env("CAPTCHA_ENABLED", false))<div class="h-captcha" data-sitekey="{{ env("HCAPTCHA_SITE_KEY") }}" data-theme="dark" style="margin: 0 auto;margin-top:20px;display:block;width:304px"></div>@endif
    <button class="btn btn-accent popup__submit-btn" style="margin-bottom: 20px;margin-top:20px" onclick="signUp();">{{ __('site.btn_register_two') }}</button>
    <hr style="border: 1px solid #1d2533;" />
    <a href="/oauth/yandex/redirect" class="btn btn-yandex popup__submit-btn" style="margin-top: 20px"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" style="fill:#fff;margin-right:8px"><path d="M13.32 21H15.9V3h-3.78c-3.78 0-5.94 2.16-5.94 5.28 0 2.52 1.26 4.02 3.54 5.58L6 21h2.82l4.2-7.86c-2.52-1.68-3.42-2.82-3.42-5.04 0-2.1 1.38-3.42 3.42-3.42h.3V21z"/></svg> {{ __('site.login_via_yandex') }}</a>
    {{-- <a href="#" class="btn btn-auth-tg popup__submit-btn" style="margin-top: 20px"><svg xmlns="http://www.w3.org/2000/svg" style="fill: #fff;margin-right: 7px" viewBox="0 0 448 512" width="18" height="18"><path d="M446.7 98.6l-67.6 318.8c-5.1 22.5-18.4 28.1-37.3 17.5l-103-75.9-49.7 47.8c-5.5 5.5-10.1 10.1-20.7 10.1l7.4-104.9 190.9-172.5c8.3-7.4-1.8-11.5-12.9-4.1L117.8 284 16.2 252.2c-22.1-6.9-22.5-22.1 4.6-32.7L418.2 66.4c18.4-6.9 34.5 4.1 28.5 32.2z"/></svg> {{ __('site.login_via_telegram') }}</a> --}}
    <p class="popup__note">{{ __('site.text_processing_personal_data') }}</p>
    <p class="popup__hint">{{ __('site.text_isset_account') }} <button class="popup__hint__btn" data-popup="auth">{{ __('site.btn_login') }}</button></p>
</div>

<div class="popup" id="resetPass">
    <p class="popup__caption">{{ __('site.forgot_password_title') }}</p>
    <div class="input-block">
        <div class="input-block__label-container">
            <label
                for="reset-email"
                class="input-block__label"
            >
                {{ __('site.modal_reset_pass_label_username') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="text"
                class="input-block__input"
                placeholder="{{ __('site.modal_reset_pass_placeholder_username') }}"
                id="reset-email"
            >
        </div>
    </div>
    <div class="input-block" style="display: none" id="reset-block-code">
        <div class="input-block__label-container">
            <label
                for="reset-code"
                class="input-block__label"
            >
                {{ __('site.modal_reset_pass_label_code') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="text"
                class="input-block__input"
                placeholder="{{ __('site.modal_reset_pass_placeholder_code') }}"
                id="reset-code"
            >
        </div>
    </div>
    <div class="input-block" style="display: none" id="reset-block-new-password">
        <div class="input-block__label-container">
            <label
                for="reset-new-password"
                class="input-block__label"
            >
                {{ __('site.modal_reset_pass_label_new_pass') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_reset_pass_placeholder_new_pass') }}"
                id="reset-new-password"
            >
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
                class="input-block__label"
            >
                {{ __('site.modal_change_pass_label_old_pass') }}
            </label>
            <button class="input-block__label-link" data-popup="resetPass">{{ __('site.btn_remember_password') }}</button>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_change_pass_placeholder_old_pass') }}"
                id="change-old-password"
            >
            <button class="input-block__showHide-btn"></button>
        </div>
    </div>
    <div class="input-block input-block_showHide">
        <div class="input-block__label-container">
            <label
                for="change-new-password"
                class="input-block__label"
            >
                {{ __('site.modal_change_pass_label_new_pass') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_change_pass_placeholder_new_pass') }}"
                id="change-new-password"
            >
            <button class="input-block__showHide-btn"></button>
        </div>
    </div>
    <div class="input-block input-block_showHide">
        <div class="input-block__label-container">
            <label
                for="change-repeat-password"
                class="input-block__label"
            >
                {{ __('site.modal_change_pass_label_repeat_pass') }}
            </label>
        </div>
        <div class="input-block__input-container">
            <input
                type="password"
                class="input-block__input"
                placeholder="{{ __('site.modal_change_pass_placeholder_repeat_pass') }}"
                id="change-repeat-password"
            >
            <button class="input-block__showHide-btn"></button>
        </div>
    </div>
    <button class="btn btn-accent popup__submit-btn" type="submit" onclick="changePassword();">{{ __('site.modal_change_pass_btn') }}</button>
</div>

{{--<div class="popup" id="replenishment">--}}
{{--    <p class="popup__caption">{{ __('site.modal_topup_balance_title') }}</p>--}}
{{--    <div class="popup__step _active" data-popup-step="1">--}}
{{--        <div class="popup__payment-methods" id="topup-payments-methods"></div>--}}
{{--        <div class="">--}}
{{--            <div class="input-block">--}}
{{--                <div class="input-block__input-container">--}}
{{--                    <input type="text" class="input-block__input" onkeyup="paymentMethodsTopup(this.value)" id="topup_sum" placeholder="{{ __('site.modal_topup_balance_placeholder_sum') }}">--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <button class="btn btn-accent popup__submit-btn" type="submit" onclick="createOrderTopup();">{{ __('site.modal_topup_balance_btn') }}</button>--}}
{{--    </div>--}}
{{--    <div class="popup__step" data-popup-step="2">--}}
{{--        <div id="system-msg" style="margin: 87px auto; display: block; text-align: center;">--}}
{{--            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;"><path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>--}}
{{--            <h2 style="margin-top: 15px;">Баланс пополнен</h2>--}}
{{--            <a href="javascript:;" data-popup-switch-step="1" class="btn popup__back-btn" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 230px;">Пополнить еще</a>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}


<div class="popup" id="replenishment">
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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;"><path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg>
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

<script src="/assets/js/scripts.min.js?48"></script>
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
function changeLanguage(locale){window.location.href='/lang/'+locale;}
</script>
<script src="/assets/js/app.js?v=3.0"></script>
<!-- JivoChat Widget -->
<script>
(function(){
    function loadJivo(){
        if (window.__jivoLoaded) return;
        window.__jivoLoaded = true;
        var s = document.createElement('script');
        s.src = '//code.jivo.ru/widget/3tZ9JRQowl';
        s.async = true;
        document.head.appendChild(s);
    }
    if (document.readyState === 'complete') {
        (window.requestIdleCallback || function(cb){setTimeout(cb,3000);})(loadJivo, {timeout: 5000});
    } else {
        window.addEventListener('load', function(){
            (window.requestIdleCallback || function(cb){setTimeout(cb,3000);})(loadJivo, {timeout: 5000});
        });
    }
})();
</script>
</body>
</html>
