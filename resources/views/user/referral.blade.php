@extends('user.layouts.main')
@section('content')
    <main>
        <section class="profile">
            <div class="content profile__container">
                <div class="profile__sidebar">
                    <ul class="profile__block profile__menu">
                        <li class="profile__menu__item">
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
                        <li class="profile__menu__item _active">
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
                            <a href="javascript;" onclick="logout();" class="profile__identity__exit"></a>
                        </div>
                        <button class="btn btn-accent profile__identity__replenishment-btn" data-popup="replenishment">{{ __('site.section_user_menu_topup_balance') }}</button>
                    </div>
                </div>
                <div class="profile__main">
                    <div class="profile__block _active" data-profile-tab="main">
                        <div class="profile__main__header profile__main__referral-header">
                            <p class="profile__caption">{{ __('site.referral_title') }}</p>
                            <div class="profile__main__header__btns">
                                <button class="profile__main__header__btn" data-profile-switch-tab="withdraw-requests">{{ __('site.referral_withdraw_requests') }}</button>
                                <button class="profile__main__header__btn" data-popup="withdraw">
                                    <svg width="19" height="20" viewBox="0 0 19 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16.625 9.40625H12.4688C11.1661 9.40625 10.0938 10.4786 10.0938 11.7812C10.0938 13.0839 11.1661 14.1562 12.4688 14.1562H14.8438V15.9375C14.8438 16.2635 14.576 16.5312 14.25 16.5312H4.75C3.77269 16.5312 2.96875 15.7273 2.96875 14.75V5.84375C2.96875 4.86644 3.77269 4.0625 4.75 4.0625H5.9375C6.26347 4.0625 6.53125 3.79472 6.53125 3.46875C6.53125 3.14278 6.26347 2.875 5.9375 2.875H4.75C3.12134 2.875 1.78125 4.21509 1.78125 5.84375V14.75C1.78125 16.3787 3.12134 17.7188 4.75 17.7188H14.25C15.2273 17.7188 16.0312 16.9148 16.0312 15.9375V14.1562H16.625C16.951 14.1562 17.2188 13.8885 17.2188 13.5625V10C17.2188 9.67403 16.951 9.40625 16.625 9.40625ZM16.0312 12.9688H12.4688C11.8174 12.9688 11.2812 12.4326 11.2812 11.7812C11.2812 11.1299 11.8174 10.5938 12.4688 10.5938H16.0312V12.9688Z" fill="white"/>
                                        <path d="M4.53593 5.29134C3.8733 5.58049 4.0924 6.43728 4.74968 6.43728H14.2497C14.5756 6.43728 14.8434 6.70506 14.8434 7.03103V7.62478C14.8434 7.95075 15.1112 8.21853 15.4372 8.21853C15.7631 8.21853 16.0309 7.95075 16.0309 7.62478V7.03103C16.0309 6.55861 15.8433 6.10554 15.5092 5.77149C15.1752 5.43744 14.7221 5.24978 14.2497 5.24978H14.0597L13.0206 2.65509C12.9618 2.5105 12.8487 2.39471 12.7055 2.33255C12.5623 2.2704 12.4005 2.26682 12.2547 2.32259L4.53593 5.29134ZM12.7772 5.24978H7.94999L12.1359 3.64071L12.7772 5.24978Z" fill="white"/>
                                    </svg>
                                    <span>{{ __('site.referral_withdraw') }}</span>
                                </button>
                            </div>
                        </div>
                        <div class="profile__referral__container">
                            <div class="profile__referral__info-block-container">
                                <div class="profile__referral__info-block">
                                    <div class="label">{{ __('site.referral_your_code') }}</div>
                                    <div class="value">
                                        <span id="profile-ref-code">...</span>
                                        <button class="copy"></button>
                                    </div>
                                </div>
                                <div class="profile__referral__info-block">
                                    <div class="label">{{ __('site.referral_link') }}</div>
                                    <div class="value">
                                        <span id="profile-ref-link">...</span>
                                        <button class="copy"></button>
                                    </div>
                                </div>
                            </div>
                            <div class="profile__referral__stats">
                                <div class="profile__referral__stat" id="followers">
                                    <svg width="16" height="19" viewBox="0 0 16 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_482_27001)">
                                            <path d="M12.3526 7.72387C13.2649 6.97076 13.8511 5.80755 13.8511 4.50448C13.8511 2.23932 12.0801 0.396484 9.90325 0.396484C7.72641 0.396484 5.95541 2.23932 5.95541 4.50448C5.95541 4.65294 5.9632 4.79957 5.97804 4.944C5.52932 4.68794 5.0147 4.54201 4.46783 4.54201C2.73004 4.54201 1.31628 6.01345 1.31628 7.82208C1.31628 8.76505 1.70112 9.61575 2.31525 10.2147C0.953551 10.8394 0 12.2619 0 13.9129V17.7272C0 18.4286 0.54608 18.9993 1.21732 18.9993H5.63969H6.03742H14.1667C14.9447 18.9993 15.5775 18.3379 15.5775 17.525V12.5221C15.5775 10.3168 14.2313 8.43413 12.3526 7.72387ZM9.90325 1.64336C11.4222 1.64336 12.6579 2.92685 12.6579 4.50448C12.6579 6.08211 11.4222 7.3656 9.90325 7.3656C8.38433 7.3656 7.14859 6.08211 7.14859 4.50448C7.14859 2.92685 8.38433 1.64336 9.90325 1.64336ZM4.46783 5.78889C5.5477 5.78889 6.42624 6.70097 6.42624 7.82208C6.42624 7.98467 6.40747 8.14602 6.37111 8.30217C5.82245 8.69228 5.35345 9.19597 4.99541 9.78076C4.82467 9.83021 4.64788 9.85532 4.46783 9.85532C3.388 9.85532 2.50946 8.94323 2.50946 7.82208C2.50946 6.70102 3.388 5.78889 4.46783 5.78889ZM1.19318 17.7272V13.9129C1.19318 12.3745 2.39086 11.1229 3.86301 11.1229H4.41565C4.29406 11.5677 4.22891 12.0371 4.22891 12.5221V17.525C4.22891 17.6023 4.23472 17.6783 4.24574 17.7524H1.21732C1.20539 17.7524 1.19318 17.7396 1.19318 17.7272ZM14.3844 17.525C14.3844 17.6482 14.2847 17.7524 14.1667 17.7524H6.03746H5.63973C5.52177 17.7524 5.4221 17.6482 5.4221 17.525V12.5221C5.4221 10.3971 7.07644 8.66829 9.1099 8.66829H10.6966C12.73 8.66829 14.3844 10.3971 14.3844 12.5221V17.525Z" fill="#FD7676"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_482_27001">
                                                <rect width="16" height="19" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    <p id="profile-refferal-users">...</p>
                                    <span>{{ __('site.referral_invited') }}</span>
                                </div>
                                <div class="profile__referral__stat" id="earned">
                                    <svg width="19" height="20" viewBox="0 0 19 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M16.625 9.40625H12.4688C11.1661 9.40625 10.0938 10.4786 10.0938 11.7812C10.0938 13.0839 11.1661 14.1562 12.4688 14.1562H14.8438V15.9375C14.8438 16.2635 14.576 16.5312 14.25 16.5312H4.75C3.77269 16.5312 2.96875 15.7273 2.96875 14.75V5.84375C2.96875 4.86644 3.77269 4.0625 4.75 4.0625H5.9375C6.26347 4.0625 6.53125 3.79472 6.53125 3.46875C6.53125 3.14278 6.26347 2.875 5.9375 2.875H4.75C3.12134 2.875 1.78125 4.21509 1.78125 5.84375V14.75C1.78125 16.3787 3.12134 17.7188 4.75 17.7188H14.25C15.2273 17.7188 16.0312 16.9148 16.0312 15.9375V14.1562H16.625C16.951 14.1562 17.2188 13.8885 17.2188 13.5625V10C17.2188 9.67403 16.951 9.40625 16.625 9.40625ZM16.0312 12.9688H12.4688C11.8174 12.9688 11.2812 12.4326 11.2812 11.7812C11.2812 11.1299 11.8174 10.5938 12.4688 10.5938H16.0312V12.9688Z" fill="white"/>
                                        <path d="M4.53593 5.29134C3.8733 5.58049 4.0924 6.43728 4.74968 6.43728H14.2497C14.5756 6.43728 14.8434 6.70506 14.8434 7.03103V7.62478C14.8434 7.95075 15.1112 8.21853 15.4372 8.21853C15.7631 8.21853 16.0309 7.95075 16.0309 7.62478V7.03103C16.0309 6.55861 15.8433 6.10554 15.5092 5.77149C15.1752 5.43744 14.7221 5.24978 14.2497 5.24978H14.0597L13.0206 2.65509C12.9618 2.5105 12.8487 2.39471 12.7055 2.33255C12.5623 2.2704 12.4005 2.26682 12.2547 2.32259L4.53593 5.29134ZM12.7772 5.24978H7.94999L12.1359 3.64071L12.7772 5.24978Z" fill="white"/>
                                    </svg>
                                    <p id="profile-refferal-balance">...</p>
                                    <span>{{ __('site.referral_earned') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="profile__empty-block" id="topup_empty">
                            <div class="profile__empty-block__icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g opacity="0.5">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.125 5.42534C13.125 3.59754 11.5546 2.13355 9.69368 2.31408L9.69141 2.3143C8.17354 2.45597 6.875 3.99404 6.875 5.58367V6.392C6.875 6.73718 6.59518 7.017 6.25 7.017C5.90482 7.017 5.625 6.73718 5.625 6.392V5.58367C5.625 3.42367 7.34255 1.27877 9.57412 1.06981C12.1794 0.817724 14.375 2.87017 14.375 5.42534V6.57534C14.375 6.92051 14.0952 7.20034 13.75 7.20034C13.4048 7.20034 13.125 6.92051 13.125 6.57534V5.42534Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.81165 7.34899C3.54949 6.47178 4.8145 6.04297 6.66703 6.04297H13.3337C15.1862 6.04297 16.4512 6.47178 17.1891 7.34899C17.921 8.21913 17.9895 9.36273 17.8716 10.4284L17.8706 10.4372L17.2462 15.4319C17.1541 16.2871 16.9383 17.2238 16.175 17.9211C15.4158 18.6147 14.2394 18.9596 12.5004 18.9596H7.50036C5.76132 18.9596 4.58497 18.6147 3.82569 17.9211C3.0624 17.2237 2.84665 16.287 2.75452 15.4318L2.12909 10.4284C2.01117 9.36274 2.07975 8.21913 2.81165 7.34899ZM3.3711 10.2866L3.9969 15.293C4.08011 16.0696 4.25249 16.6179 4.66879 16.9982C5.09076 17.3837 5.88941 17.7096 7.50036 17.7096H12.5004C14.1113 17.7096 14.91 17.3837 15.3319 16.9982C15.7482 16.6179 15.9207 16.0697 16.0039 15.2931L16.0051 15.2821L16.6296 10.2865C16.7359 9.32108 16.625 8.62026 16.2325 8.15361C15.8453 7.69332 15.0395 7.29297 13.3337 7.29297H6.66703C4.96123 7.29297 4.15541 7.69332 3.76825 8.15361C3.37573 8.62028 3.26482 9.32112 3.3711 10.2866Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0801 10.0013C12.0801 9.54106 12.4532 9.16797 12.9134 9.16797H12.9209C13.3811 9.16797 13.7542 9.54106 13.7542 10.0013C13.7542 10.4615 13.3811 10.8346 12.9209 10.8346H12.9134C12.4532 10.8346 12.0801 10.4615 12.0801 10.0013Z" fill="white"></path>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.24512 10.0013C6.24512 9.54106 6.61821 9.16797 7.07845 9.16797H7.08594C7.54617 9.16797 7.91927 9.54106 7.91927 10.0013C7.91927 10.4615 7.54617 10.8346 7.08594 10.8346H7.07845C6.61821 10.8346 6.24512 10.4615 6.24512 10.0013Z" fill="white"></path>
                                    </g>
                                </svg>
                            </div>
                            <p class="profile__empty-block__caption">{{ __('site.referral_no_topups') }}</p>
                            <p class="profile__empty-block__text">{{ __('site.referral_no_topups_text') }}</p>
                        </div>
                        <div class="profile__table profile__referral-table" id="topup_table" style="display: none">
                            <div class="profile__table__header">
                                <div class="profile__table__col">#</div>
                                <div class="profile__table__col">{{ __('site.referral_table_invited') }}</div>
                                <div class="profile__table__col">{{ __('site.referral_table_date') }}</div>
                                <div class="profile__table__col profile__table__col_justify-end">{{ __('site.referral_table_deductions') }}</div>
                                </div>
                            <div class="profile__table__body" id="user_topup" data-simplebar></div>
                        </div>
                    </div>
                    <div class="profile__block" data-profile-tab="withdraw-requests">
                        <div class="profile__main__header">
                            <button class="profile__main__btn-back" data-profile-switch-tab="main"></button>
                            <p class="profile__caption">{{ __('site.referral_withdraw_requests') }}</p>
                        </div>
                        <div class="profile__empty-block" id="withdraw_empty">
                            <div class="profile__empty-block__icon">
                                <svg width="19" height="20" viewBox="0 0 19 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16.625 9.40625H12.4688C11.1661 9.40625 10.0938 10.4786 10.0938 11.7812C10.0938 13.0839 11.1661 14.1562 12.4688 14.1562H14.8438V15.9375C14.8438 16.2635 14.576 16.5312 14.25 16.5312H4.75C3.77269 16.5312 2.96875 15.7273 2.96875 14.75V5.84375C2.96875 4.86644 3.77269 4.0625 4.75 4.0625H5.9375C6.26347 4.0625 6.53125 3.79472 6.53125 3.46875C6.53125 3.14278 6.26347 2.875 5.9375 2.875H4.75C3.12134 2.875 1.78125 4.21509 1.78125 5.84375V14.75C1.78125 16.3787 3.12134 17.7188 4.75 17.7188H14.25C15.2273 17.7188 16.0312 16.9148 16.0312 15.9375V14.1562H16.625C16.951 14.1562 17.2188 13.8885 17.2188 13.5625V10C17.2188 9.67403 16.951 9.40625 16.625 9.40625ZM16.0312 12.9688H12.4688C11.8174 12.9688 11.2812 12.4326 11.2812 11.7812C11.2812 11.1299 11.8174 10.5938 12.4688 10.5938H16.0312V12.9688Z" fill="white"></path>
                                    <path d="M4.53593 5.29134C3.8733 5.58049 4.0924 6.43728 4.74968 6.43728H14.2497C14.5756 6.43728 14.8434 6.70506 14.8434 7.03103V7.62478C14.8434 7.95075 15.1112 8.21853 15.4372 8.21853C15.7631 8.21853 16.0309 7.95075 16.0309 7.62478V7.03103C16.0309 6.55861 15.8433 6.10554 15.5092 5.77149C15.1752 5.43744 14.7221 5.24978 14.2497 5.24978H14.0597L13.0206 2.65509C12.9618 2.5105 12.8487 2.39471 12.7055 2.33255C12.5623 2.2704 12.4005 2.26682 12.2547 2.32259L4.53593 5.29134ZM12.7772 5.24978H7.94999L12.1359 3.64071L12.7772 5.24978Z" fill="white"></path>
                                </svg>
                            </div>
                            <p class="profile__empty-block__caption">{{ __('site.referral_no_requests') }}</p>
                            <p class="profile__empty-block__text">{{ __('site.referral_no_requests_text') }}</p>
                        </div>
                        <div class="profile__table profile__withdraw-table" id="withdraw_table" style="display: none">
                            <div class="profile__table__header">
                                <div class="profile__table__col">#</div>
                                <div class="profile__table__col">{{ __('site.referral_table_method') }}</div>
                                <div class="profile__table__col">{{ __('site.referral_table_amount') }}</div>
                                <div class="profile__table__col">{{ __('site.referral_table_date') }}</div>
                                <div class="profile__table__col"></div>
                            </div>
                            <div class="profile__table__body" id="user_withdraw" data-simplebar></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <div class="popup" id="withdraw">
        <p class="popup__caption">{{ __('site.referral_popup_title') }}</p>
        <div class="input-block input-block_showHide">
            <div class="input-block__label-container">
                <label
                    for=""
                    class="input-block__label"
                >
                    {{ __('site.referral_choose_method') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <div class="select _unfold input-block__select">
                    <select name="withdraw-method">
                        @foreach($methods as $s)
                            <option value="{{ $s->id }}">{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="input-block" id="requisites">
            <div class="input-block__label-container">
                <label
                    for="withdraw-details"
                    class="input-block__label"
                >
                    {{ __('site.referral_requisites') }}
                </label>
            </div>
            <div class="input-block__input-container">
                <input
                    type="text"
                    class="input-block__input"
                    placeholder="{{ __('site.referral_requisites_placeholder') }}"
                    id="withdraw-details"
                >
            </div>
        </div>
        <div class="checkbox popup__checkbox" id="requisites_check">
            <input
                type="checkbox"
                class="checkbox__input"
                id="withdraw-confirm-data"
            >
            <label
                for="withdraw-confirm-data"
                class="checkbox__label"
            >
                <span class="checkbox__icon"></span>
                    <span class="checkbox__text">
						{{ __('site.referral_confirm_data') }}
					</span>
            </label>
        </div>
        <button class="btn btn-accent popup__submit-btn" type="submit" onclick="createWithdraw();">{{ __('site.referral_create_request') }}</button>
    </div>
@endsection
