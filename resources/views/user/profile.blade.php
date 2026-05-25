@extends('user.layouts.main')
@section('content')
    <main>
        <section class="profile">
            <div class="content profile__container">
                <div class="profile__sidebar">
                    <ul class="profile__block profile__menu">
                        <li class="profile__menu__item _active">
                            <a href="/my/profile">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.0157 9.27363C14.2954 8.34031 15.1288 6.82984 15.1288 5.1282C15.1288 2.30051 12.8283 0 10.0006 0C7.17289 0 4.87238 2.30051 4.87238 5.1282C4.87238 6.82984 5.7057 8.34031 6.98547 9.27363C3.80422 10.491 1.53906 13.5754 1.53906 17.1795C1.53906 18.7347 2.80434 20 4.35957 20H15.6416C17.1968 20 18.4621 18.7347 18.4621 17.1795C18.4621 13.5754 16.197 10.491 13.0157 9.27363ZM6.41086 5.1282C6.41086 3.14883 8.02121 1.53848 10.0006 1.53848C11.98 1.53848 13.5903 3.14883 13.5903 5.1282C13.5903 7.10758 11.98 8.71797 10.0006 8.71797C8.02121 8.71797 6.41086 7.10758 6.41086 5.1282ZM15.6416 18.4615H4.35957C3.65266 18.4615 3.07754 17.8864 3.07754 17.1795C3.07754 13.362 6.18316 10.2564 10.0006 10.2564C13.8181 10.2564 16.9237 13.362 16.9237 17.1795C16.9237 17.8864 16.3486 18.4615 15.6416 18.4615Z" fill="white"/>
                                </svg>
                                {{ __('site.section_user_menu_profile') }}
                            </a>
                        </li>
                        <li class="profile__menu__item">
                            <a href="/my/orders">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g opacity="0.5">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.125 5.42534C13.125 3.59754 11.5546 2.13355 9.69368 2.31408L9.69141 2.3143C8.17354 2.45597 6.875 3.99404 6.875 5.58367V6.392C6.875 6.73718 6.59518 7.017 6.25 7.017C5.90482 7.017 5.625 6.73718 5.625 6.392V5.58367C5.625 3.42367 7.34255 1.27877 9.57412 1.06981C12.1794 0.817724 14.375 2.87017 14.375 5.42534V6.57534C14.375 6.92051 14.0952 7.20034 13.75 7.20034C13.4048 7.20034 13.125 6.92051 13.125 6.57534V5.42534Z" fill="white"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.81165 7.34899C3.54949 6.47178 4.8145 6.04297 6.66703 6.04297H13.3337C15.1862 6.04297 16.4512 6.47178 17.1891 7.34899C17.921 8.21913 17.9895 9.36273 17.8716 10.4284L17.8706 10.4372L17.2462 15.4319C17.1541 16.2871 16.9383 17.2238 16.175 17.9211C15.4158 18.6147 14.2394 18.9596 12.5004 18.9596H7.50036C5.76132 18.9596 4.58497 18.6147 3.82569 17.9211C3.0624 17.2237 2.84665 16.287 2.75452 15.4318L2.12909 10.4284C2.01117 9.36274 2.07975 8.21913 2.81165 7.34899ZM3.3711 10.2866L3.9969 15.293C4.08011 16.0696 4.25249 16.6179 4.66879 16.9982C5.09076 17.3837 5.88941 17.7096 7.50036 17.7096H12.5004C14.1113 17.7096 14.91 17.3837 15.3319 16.9982C15.7482 16.6179 15.9207 16.0697 16.0039 15.2931L16.0051 15.2821L16.6296 10.2865C16.7359 9.32108 16.625 8.62026 16.2325 8.15361C15.8453 7.69332 15.0395 7.29297 13.3337 7.29297H6.66703C4.96123 7.29297 4.15541 7.69332 3.76825 8.15361C3.37573 8.62028 3.26482 9.32112 3.3711 10.2866Z" fill="white"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0801 10.0013C12.0801 9.54106 12.4532 9.16797 12.9134 9.16797H12.9209C13.3811 9.16797 13.7542 9.54106 13.7542 10.0013C13.7542 10.4615 13.3811 10.8346 12.9209 10.8346H12.9134C12.4532 10.8346 12.0801 10.4615 12.0801 10.0013Z" fill="white"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.24512 10.0013C6.24512 9.54106 6.61821 9.16797 7.07845 9.16797H7.08594C7.54617 9.16797 7.91927 9.54106 7.91927 10.0013C7.91927 10.4615 7.54617 10.8346 7.08594 10.8346H7.07845C6.61821 10.8346 6.24512 10.4615 6.24512 10.0013Z" fill="white"/>
                                    </g>
                                </svg>
                                {{ __('site.section_user_menu_my_buys') }}
                            </a>
                        </li>
                        <li class="profile__menu__item">
                            <a href="/my/referral">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g opacity="0.5" clip-path="url(#clip0_472_19524)">
                                        <path d="M14.3582 10.0355C13.9765 9.85392 13.5839 9.69631 13.1827 9.56359C14.475 8.59359 15.3125 7.04902 15.3125 5.3125C15.3125 2.3832 12.9293 0 9.99998 0C7.07065 0 4.68749 2.3832 4.68749 5.3125C4.68749 7.05094 5.52686 8.59703 6.82159 9.56684C5.63534 9.95738 4.52225 10.5651 3.54803 11.3664C1.76163 12.8358 0.519555 14.8854 0.0506881 17.1376C-0.096265 17.8433 0.0797896 18.5682 0.533618 19.1262C0.985219 19.6815 1.65487 20 2.37081 20H11.9922C12.4237 20 12.7734 19.6502 12.7734 19.2188C12.7734 18.7873 12.4237 18.4375 11.9922 18.4375H2.37081C2.03729 18.4375 1.83608 18.2513 1.74584 18.1404C1.59002 17.9488 1.52971 17.6994 1.58038 17.4561C2.39112 13.5617 5.82612 10.717 9.79288 10.621C9.93157 10.6264 10.0704 10.6264 10.2091 10.6209C11.4248 10.6495 12.5939 10.9269 13.6874 11.4467C14.0771 11.6319 14.5431 11.4662 14.7284 11.0765C14.9136 10.6868 14.7479 10.2207 14.3582 10.0355ZM10.1905 9.0577C10.064 9.05536 9.93745 9.05538 9.81092 9.05777C7.83065 8.95898 6.24999 7.31687 6.24999 5.3125C6.24999 3.24473 7.93221 1.5625 9.99998 1.5625C12.0678 1.5625 13.75 3.24473 13.75 5.3125C13.75 7.31637 12.1701 8.95816 10.1905 9.0577Z" fill="white"/>
                                        <path d="M19.2188 15.5078H17.0703V13.3594C17.0703 12.9279 16.7205 12.5781 16.2891 12.5781C15.8576 12.5781 15.5078 12.9279 15.5078 13.3594V15.5078H13.3594C12.9279 15.5078 12.5781 15.8576 12.5781 16.2891C12.5781 16.7205 12.9279 17.0703 13.3594 17.0703H15.5078V19.2188C15.5078 19.6502 15.8576 20 16.2891 20C16.7205 20 17.0703 19.6502 17.0703 19.2188V17.0703H19.2188C19.6502 17.0703 20 16.7205 20 16.2891C20 15.8576 19.6502 15.5078 19.2188 15.5078Z" fill="white"/>
                                    </g>
                                    <defs>
                                        <clipPath id="clip0_472_19524">
                                            <rect width="20" height="20" fill="white"/>
                                        </clipPath>
                                    </defs>
                                </svg>
                                {{ __('site.section_user_menu_ref_system') }}
                            </a>
                        </li>
                        <li class="profile__menu__item">
                            <a href="javascript:;" data-popup="supportModal">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g opacity="0.5">
                                        <path d="M10 0C4.48 0 0 4.48 0 10C0 15.52 4.48 20 10 20C15.52 20 20 15.52 20 10C20 4.48 15.52 0 10 0ZM10 18C5.59 18 2 14.41 2 10C2 5.59 5.59 2 10 2C14.41 2 18 5.59 18 10C18 14.41 14.41 18 10 18ZM11 14H9V16H11V14ZM10.07 4C7.79 4 6 5.79 6 8.07H8C8 6.9 8.9 6 10.07 6C11.24 6 12.14 6.9 12.14 8.07C12.14 10 9.07 9.77 9.07 13H11.07C11.07 10.77 14.14 10.5 14.14 8.07C14.14 5.79 12.35 4 10.07 4Z" fill="white"/>
                                    </g>
                                </svg>
                                {{ __('site.support_title') }}
                            </a>
                        </li>
                    </ul>
                    <div class="profile__block profile__identity">
                        <div class="profile__identity__container">
                            <div class="profile__avatar">
                                <img src="/assets/img/user.png?7" alt="">
                            </div>
                            <div class="profile__identity__user-info">
                                <p class="profile__identity__nickname" id="profile-username">...</p>
                                <p class="profile__identity__balance"><span id="profile-balance">0</span></p>
                            </div>
                            <a href="javascript:;" onclick="logout();" class="profile__identity__exit"></a>
                        </div>
                        <button class="btn btn-accent profile__identity__replenishment-btn" data-popup="replenishment">{{ __('site.section_user_menu_topup_balance') }}</button>
                    </div>
                </div>
                <div class="profile__main"><div class="profile__block">
                        <div class="profile__main__header">
                            <!-- <button class="profile__main__btn-back"></button> -->
                            <p class="profile__caption">{{ __('site.profile_title') }}</p>
                        </div>
                        <div class="profile__tabs">
                            <div class="profile__tabs__choose">
                                <div class="profile__tabs__scroll-container" data-simplebar>
                                    <div class="profile__tabs__choose__inner">
                                        <button class="profile__tabs__choose__btn _active" data-tab="1">{{ __('site.profile_personal_data') }}</button>
                                        <button class="profile__tabs__choose__btn " data-tab="3">{{ __('site.profile_security') }}</button>
                                        <button class="profile__tabs__choose__btn " data-tab="4">{{ __('site.profile_notifications') }}</button>
                                    </div>
                                </div>
                            </div>
                            <div class="profile__tabs__tab _active" data-tab="1">
{{--                                <div class="profile__settings-block profile__settings-block_avatar">--}}
{{--                                    <div class="profile__avatar profile__settings-block__avatar">--}}
{{--                                        <img src="/assets/img/avatar.png" alt="">--}}
{{--                                    </div>--}}
{{--                                    <div class="profile__settings-block__text">--}}
{{--                                        <p class="profile__settings-block__name">Аватар</p>--}}
{{--                                        <p class="profile__settings-block__descr">Вы можете поменять аватарку</p>--}}
{{--                                        <span class="profile__settings-block__avatar-ext">Доступные форматы: .png, .jpg, jpeg</span>--}}
{{--                                    </div>--}}
{{--                                    <button class="profile__settings-block__edit-btn"><span class="edit">Изменить</span><span class="submit">Применить</span></button>--}}
{{--                                </div>--}}
                                <div class="profile__settings-block profile__settings-block_input edit-login">
                                    <div class="profile__settings-block__text">
                                        <p class="profile__settings-block__name">{{ __('site.profile_login') }}</p>
                                        <p class="profile__settings-block__descr">{{ __('site.profile_login_hint') }}</p>
                                    </div>
                                    <input type="text" class="profile__settings-block__input" id="edit-login" readonly>
                                    <button class="profile__settings-block__cancel-btn"></button>
                                    <button class="profile__settings-block__edit-btn"><span class="edit">{{ __('site.profile_edit') }}</span><span class="submit">{{ __('site.profile_apply') }}</span></button>
                                </div>
                                <div class="profile__settings-block profile__settings-block_input edit-email">
                                    <div class="profile__settings-block__text">
                                        <p class="profile__settings-block__name">Email</p>
                                        <p class="profile__settings-block__descr">{{ __('site.profile_email_hint') }}</p>
                                    </div>
                                    <input type="text" class="profile__settings-block__input" id="edit-email" readonly>
                                    <button class="profile__settings-block__cancel-btn"></button>
                                    <button class="profile__settings-block__edit-btn"><span class="edit">{{ __('site.profile_edit') }}</span><span class="submit">{{ __('site.profile_apply') }}</span></button>
                                </div>
                            </div>
                            <div class="profile__tabs__tab" data-tab="3">
                                @php
                                    $_pUser = null;
                                    $_pToken = request()->cookie('session_token');
                                    if ($_pToken) {
                                        try {
                                            $_pUser = \PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth::setToken($_pToken)->authenticate();
                                        } catch (\Exception $e) { $_pUser = null; }
                                    }
                                    $_tgOn = $_pUser && (int) $_pUser->tid > 0;
                                    $_yaOn = $_pUser && (int) $_pUser->yandex_id > 0;
                                @endphp
                                <div class="profile__settings-block profile__settings-block_password">
                                    <div class="profile__settings-block__text">
                                        <p class="profile__settings-block__name">{{ __('site.profile_change_password') }}</p>
                                        <p class="profile__settings-block__descr">{{ __('site.profile_change_password_hint') }}</p>
                                    </div>
                                    <button class="profile__settings-block__edit-btn" data-popup="changePass"><span class="edit">{{ __('site.profile_edit') }}</span><span class="submit">{{ __('site.profile_apply') }}</span></button>
                                </div>
                                <div class="profile__auth-methods">
                                    <p class="profile__auth-methods__title">{{ __('site.profile_login_methods') }}</p>
                                    <div class="profile__auth-method">
                                        <span class="profile__auth-method__name">{{ __('site.profile_method_email') }}</span>
                                        <span class="profile__auth-method__status is-on">{{ __('site.profile_method_connected') }}</span>
                                    </div>
                                    <div class="profile__auth-method">
                                        <span class="profile__auth-method__name">Telegram</span>
                                        <span class="profile__auth-method__status {{ $_tgOn ? 'is-on' : '' }}">{{ $_tgOn ? __('site.profile_method_connected') : __('site.profile_method_not_connected') }}</span>
                                    </div>
                                    <div class="profile__auth-method">
                                        <span class="profile__auth-method__name">{{ __('site.profile_method_yandex') }}</span>
                                        <span class="profile__auth-method__status {{ $_yaOn ? 'is-on' : '' }}">{{ $_yaOn ? __('site.profile_method_connected') : __('site.profile_method_not_connected') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="profile__tabs__tab" data-tab="4">
                                <div class="profile__settings-block toggle popup__toggle">
                                    <input type="checkbox" class="toggle__input" id="notify-orders-toggle" onclick="changeNotify('orders');">
                                    <label for="notify-orders-toggle" class="toggle__label">
                                        <span class="toggle__icon"></span>
                                        <span class="toggle__text">{{ __('site.profile_notify_orders') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
