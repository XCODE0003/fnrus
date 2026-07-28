@extends('user.layouts.main')
@section('content')
    <main>
        <section class="profile">
            <div class="content profile__container">
                @include('user.partials.profile-nav', ['active' => 'referral'])
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
