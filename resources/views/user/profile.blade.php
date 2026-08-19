@extends('user.layouts.main')
@section('content')
    <main>
        <section class="profile">
            <div class="content profile__container">
                @include('user.partials.profile-nav', ['active' => 'profile'])
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
                                    <button type="button" class="profile__settings-block__cancel-btn" aria-label="{{ __('site.a11y_cancel') }}"></button>
                                    <button class="profile__settings-block__edit-btn"><span class="edit">{{ __('site.profile_edit') }}</span><span class="submit">{{ __('site.profile_apply') }}</span></button>
                                </div>
                                <div class="profile__settings-block profile__settings-block_input edit-email">
                                    <div class="profile__settings-block__text">
                                        <p class="profile__settings-block__name">Email</p>
                                        <p class="profile__settings-block__descr">{{ __('site.profile_email_hint') }}</p>
                                    </div>
                                    <input type="text" class="profile__settings-block__input" id="edit-email" readonly>
                                    <button type="button" class="profile__settings-block__cancel-btn" aria-label="{{ __('site.a11y_cancel') }}"></button>
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
