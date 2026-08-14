<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="MobileOptimized" content="176" />
    <meta name="HandheldFriendly" content="True" />
    <meta name="robots" content="noindex,nofollow" />
    <title>{{ __('site.invoice_title') }} #{{ $hash }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png?v=3">
    <link rel="shortcut icon" href="/assets/img/favicon.ico?v=3">
@php
    // Prefer the minified build (php artisan assets:build) when it is present;
    // fall back to the hand-edited source so local editing keeps working.
    $__cssVer = '4.15.10';
    $__cssFile = file_exists(public_path('assets/css/style.build.css'))
        ? 'assets/css/style.build.css'
        : 'assets/css/style.min.css';
    $__cssHref = '/' . $__cssFile . '?v=' . $__cssVer;
@endphp
    <link rel="stylesheet" href="{{ $__cssHref }}">
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
</head>
<body class="pay-page-body">

<div class="pay-page">

    {{-- ============ Toast ============ --}}
    <div id="payment-warning" class="pay-toast" role="status" aria-live="polite"></div>

    {{-- ============ Screen 1: Order info ============ --}}
    <section class="pay-card pay-info" data-screen="info">
        <h2 class="pay-card__title">{{ __('site.invoice_order_info') }}</h2>

        <div class="pay-info__grid">
            <div class="pay-info__field pay-info__field--wide">
                <span class="pay-info__label">{{ __('site.invoice_product_name') }}</span>
                <div class="pay-chip">
                    <span class="pay-chip__value" id="info-product">—</span>
                    <button type="button" class="pay-chip__copy" data-copy-target="info-product" aria-label="Copy">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 3H6a2 2 0 0 0-2 2v12h2V5h10V3zm3 4H10a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zm0 14h-9V9h9v12z" fill="currentColor"/></svg>
                    </button>
                </div>
            </div>

            <div class="pay-info__field">
                <span class="pay-info__label">{{ __('site.invoice_order_id') }}</span>
                <div class="pay-chip">
                    <span class="pay-chip__value" id="info-order-id">#{{ $hash }}</span>
                    <button type="button" class="pay-chip__copy" data-copy-target="info-order-id" aria-label="Copy">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M16 3H6a2 2 0 0 0-2 2v12h2V5h10V3zm3 4H10a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h9a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zm0 14h-9V9h9v12z" fill="currentColor"/></svg>
                    </button>
                </div>
            </div>

            <div class="pay-info__field">
                <span class="pay-info__label">{{ __('site.invoice_product_period') }}</span>
                <div class="pay-chip">
                    <span class="pay-chip__value" id="info-period">—</span>
                </div>
            </div>
        </div>

        <p class="pay-info__timer">
            <span class="pay-info__timer-label">{{ __('site.invoice_payment_period') }}</span>
            <span class="pay-info__timer-value" id="info-timer">—</span>
        </p>

        <div class="pay-hint" role="note">
            <span class="pay-hint__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" fill="currentColor"/></svg>
            </span>
            <span class="pay-hint__text">
                {!! __('site.invoice_hint_must_check') !!}
            </span>
        </div>
    </section>

    {{-- ============ Screen 2: Methods picker ============ --}}
    <section class="pay-card pay-picker" data-screen="picker">
        <h2 class="pay-card__title">{{ __('site.invoice_choose_method') }}</h2>

        <div class="pay-methods" id="pay-methods">
            <div class="pay-methods__skeleton">
                <span></span><span></span><span></span><span></span>
            </div>
        </div>

        {{-- BT (sub-aggregator) screen --}}
        <div class="pay-methods pay-methods--bt" id="pay-methods-bt" hidden>
            <button type="button" class="pay-back" id="pay-bt-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('site.invoice_back') }}
            </button>
            <div id="pay-methods-bt-list"></div>
        </div>

        {{-- ТЗ §4 — this page is a standalone document with no site footer, so
             the legal pages have to be linked here too. --}}
        <p class="legal-consent">{!! __('site.legal_consent_pay') !!}</p>

        <div class="pay-actions">
            <button type="button" class="pay-btn pay-btn--primary" id="btn-pay" disabled>
                {{ __('site.invoice_pay') }}
            </button>
            <div class="pay-actions__row">
                <button type="button" class="pay-btn pay-btn--secondary" id="btn-check-payment" onclick="checkPayment()">
                    {{ __('site.invoice_check_payment') }}
                </button>
                <button type="button" class="pay-btn pay-btn--secondary pay-btn--danger" id="btn-cancel-order" onclick="openCancelModal()">
                    {{ __('site.invoice_cancel_order') }}
                </button>
            </div>
        </div>
    </section>
</div>

{{-- ============ Screen 3: Cancel confirmation modal ============ --}}
<div class="pay-modal" id="pay-cancel-modal" hidden role="dialog" aria-modal="true" aria-labelledby="pay-cancel-title">
    <div class="pay-modal__backdrop" data-close-modal></div>
    <div class="pay-modal__panel">
        <h3 class="pay-modal__title" id="pay-cancel-title">{{ __('site.invoice_cancel_title') }}</h3>
        <div class="pay-modal__icon" aria-hidden="true">
            <svg width="111" height="111" viewBox="0 0 111 111" fill="none">
                <circle cx="55.5" cy="55.5" r="50" stroke="#E74C3C" stroke-width="6"/>
                <line x1="22" y1="22" x2="89" y2="89" stroke="#E74C3C" stroke-width="6" stroke-linecap="round"/>
            </svg>
        </div>
        <div class="pay-modal__actions">
            <button type="button" class="pay-btn pay-btn--secondary" id="pay-cancel-confirm">
                {{ __('site.invoice_cancel_order') }}
            </button>
            <button type="button" class="pay-btn pay-btn--primary" data-close-modal>
                {{ __('site.invoice_continue_pay') }}
            </button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.js" integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E=" crossorigin="anonymous"></script>
<script>
(function(){
    'use strict';

    var lang = {
        order_paid: @json(__('site.invoice_order_paid')),
        order_cancelled: @json(__('site.invoice_order_cancelled')),
        payment_expired: @json(__('site.invoice_payment_expired')),
        cancelling: @json(__('site.invoice_cancelling')),
        cancel_error: @json(__('site.invoice_cancel_error')),
        network_error: @json(__('site.invoice_network_error')),
        cancel_order: @json(__('site.invoice_cancel_order')),
        check_payment: @json(__('site.invoice_check_payment')),
        pay: @json(__('site.invoice_pay')),
        checking: @json(__('site.invoice_checking')),
        not_paid_yet: @json(__('site.invoice_not_paid_yet')),
        min_payment_amount: @json(__('site.js_min_payment_amount')),
        max_payment_amount: @json(__('site.js_max_payment_amount')),
        copied: @json(__('site.invoice_copied'))
    };

    var apiUrl = window.location.origin + '/api';
    var hash = @json($hash);
    var selectedMethodHref = null;
    var expiresAt = 0;
    var timerInterval = null;
    var pollInterval = null;

    // ?warning=... — show toast immediately and strip from URL
    try {
        var params = new URLSearchParams(window.location.search);
        var warn = params.get('warning');
        if (warn) {
            $(function(){ showToast(warn); });
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }
    } catch(e) {}

    function showToast(msg) {
        var $w = $('#payment-warning');
        $w.text(msg).stop(true, true).addClass('is-visible');
        clearTimeout(window._pwHide);
        window._pwHide = setTimeout(function(){ $w.removeClass('is-visible'); }, 6000);
    }

    function pad(n){ return n < 10 ? '0' + n : '' + n; }

    function updateTimer(){
        if (!expiresAt) return;
        var now = Math.floor(Date.now() / 1000);
        var diff = expiresAt - now;
        var $el = $('#info-timer');
        if (diff <= 0) {
            $el.text('00:00:00').addClass('is-expired');
            clearInterval(timerInterval);
            return;
        }
        var h = Math.floor(diff / 3600);
        var m = Math.floor((diff % 3600) / 60);
        var s = diff % 60;
        $el.text(pad(h) + ':' + pad(m) + ':' + pad(s));
    }

    function fillOrderInfo(result){
        if (result.product_name) $('#info-product').text(result.product_name);
        if (result.order_id) $('#info-order-id').text('#' + result.order_id);
        if (result.period) $('#info-period').text(result.period);
        if (result.expires_at) {
            expiresAt = result.expires_at;
            updateTimer();
            clearInterval(timerInterval);
            timerInterval = setInterval(updateTimer, 1000);
        }
    }

    // === Order info ===
    $.ajax({
        type: 'GET',
        url: apiUrl + '/orders/' + hash + '/info',
        dataType: 'json',
        success: function(data){
            if (data.ok === true) {
                fillOrderInfo(data.result);
                renderMethods(data.result.sum_usd);
                pollInterval = setInterval(pollStatus, 1500);
            } else if (data.description) {
                showToast(data.description);
            }
        }
    });

    // === Status polling ===
    function pollStatus(){
        $.ajax({
            type: 'GET',
            url: '/invoice/' + hash + '/status',
            dataType: 'json',
            success: function(data){
                if (data.ok === 'paid') {
                    showTerminal('paid');
                    try { Telegram.WebApp.close(); } catch(e) {}
                } else if (data.ok === 'cancelled') {
                    showTerminal('cancelled');
                } else if (data.ok === 'expired') {
                    showTerminal('expired');
                }
            }
        });
    }

    function showTerminal(state){
        clearInterval(pollInterval);
        clearInterval(timerInterval);
        var icons = {
            paid:      '<svg viewBox="0 0 24 24" fill="none" width="64" height="64"><circle cx="12" cy="12" r="10" stroke="#4ABD5C" stroke-width="2"/><path d="M7 12l3.5 3.5L17 9" stroke="#4ABD5C" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            cancelled: '<svg viewBox="0 0 24 24" fill="none" width="64" height="64"><circle cx="12" cy="12" r="10" stroke="#E74C3C" stroke-width="2"/><path d="M8 8l8 8M16 8l-8 8" stroke="#E74C3C" stroke-width="2.4" stroke-linecap="round"/></svg>',
            expired:   '<svg viewBox="0 0 24 24" fill="none" width="64" height="64"><circle cx="12" cy="12" r="10" stroke="#F39C12" stroke-width="2"/><path d="M12 7v5l3 2" stroke="#F39C12" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
        var texts = { paid: lang.order_paid, cancelled: lang.order_cancelled, expired: lang.payment_expired };
        $('.pay-page').html(
            '<div class="pay-terminal">' + icons[state] +
            '<p class="pay-terminal__text">' + texts[state] + '</p></div>'
        );
    }

    // === Methods ===
    function renderMethods(price){
        $.ajax({
            type: 'POST',
            url: apiUrl + '/methods_payments/ps/all',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ price: price }),
            success: function(data){
                if (data.ok !== true) return;

                var itemsHtml = '';
                var btHtml = '';
                var btEntry = null;

                data.result.forEach(function(e){
                    if (e.type === 'bt' && e.assets && e.assets.length > 0) {
                        btEntry = e;
                        e.assets.forEach(function(a){
                            btHtml += renderMethodRow(e, a, true);
                        });
                        return;
                    }
                    (e.assets || []).forEach(function(a){
                        itemsHtml += renderMethodRow(e, a, false);
                    });
                });

                if (btEntry) {
                    itemsHtml += '<button type="button" class="pay-method" id="pay-bt-aggregator">' +
                        '<span class="pay-method__icon"><img src="' + btEntry.icon + '" alt=""></span>' +
                        '<span class="pay-method__name">' + (btEntry.title || 'BTKassa') + '</span>' +
                        '<span class="pay-method__hint">' + (btEntry.region || '') + '</span>' +
                        '<span class="pay-method__chevron">' +
                            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '</span>' +
                    '</button>';
                }

                $('#pay-methods').html(itemsHtml || '<p class="pay-methods__empty">—</p>');
                $('#pay-methods-bt-list').html(btHtml);
                wireMethodHandlers(price);
            }
        });
    }

    function renderMethodRow(entry, asset, isBt){
        var href = '/invoice/' + hash + '/' + entry.type + '/' + asset.id;
        var hint = asset.currency
            ? (asset.currency.indexOf(',') !== -1 ? '{{ __('site.invoice_currencies') }}: ' + asset.currency : asset.currency)
            : (entry.region || '');
        return '<button type="button" class="pay-method" ' +
            'data-href="' + href + '" ' +
            'data-min-main="' + (asset.min_main || 0) + '" ' +
            'data-max-main="' + (asset.max_main || 0) + '" ' +
            'data-min-display="' + (asset.min || 0) + '" ' +
            'data-max-display="' + (asset.max || 0) + '" ' +
            'data-currency="' + (asset.currency || '') + '">' +
                '<span class="pay-method__icon"><img src="' + asset.icon + '" alt="" loading="lazy"></span>' +
                '<span class="pay-method__name">' + (asset.title || entry.title || '') + '</span>' +
                '<span class="pay-method__hint">' + hint + '</span>' +
            '</button>';
    }

    function wireMethodHandlers(price){
        $('#pay-bt-aggregator').on('click', function(e){
            e.preventDefault();
            $('#pay-methods').attr('hidden', true);
            $('#pay-methods-bt').removeAttr('hidden');
        });
        $('#pay-bt-back').on('click', function(){
            $('#pay-methods-bt').attr('hidden', true);
            $('#pay-methods').removeAttr('hidden');
        });
        $('#pay-methods, #pay-methods-bt').on('click', '.pay-method', function(){
            var $btn = $(this);
            if ($btn.attr('id') === 'pay-bt-aggregator') return;
            $btn.closest('.pay-methods').find('.pay-method').removeClass('is-selected');
            $btn.addClass('is-selected');
            selectedMethodHref = $btn.data('href');
            // Validate amount window
            var minMain = parseFloat($btn.data('min-main')) || 0;
            var maxMain = parseFloat($btn.data('max-main')) || 0;
            var cur = $btn.data('currency') || '';
            var minD = $btn.data('min-display'), maxD = $btn.data('max-display');
            $('#btn-pay').prop('disabled', false).data('warning', null);
            if (minMain > 0 && price < minMain) {
                $('#btn-pay').data('warning', lang.min_payment_amount.replace(':min', minD).replace(':amount', minD).replace(':currency', cur));
            } else if (maxMain > 0 && price > maxMain) {
                $('#btn-pay').data('warning', lang.max_payment_amount.replace(':max', maxD).replace(':amount', maxD).replace(':currency', cur));
            }
        });
    }

    // === Pay button ===
    $('#btn-pay').on('click', function(){
        var $btn = $(this);
        if ($btn.prop('disabled') || !selectedMethodHref) return;
        var warning = $btn.data('warning');
        if (warning) { showToast(warning); return; }
        $btn.prop('disabled', true);
        $.ajax({
            type: 'GET',
            url: selectedMethodHref,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            timeout: 30000,
            success: function(data){
                if (data && data.ok === true && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data && data.ok === false && data.message) {
                    showToast(data.message);
                    $btn.prop('disabled', false);
                } else {
                    window.location.href = selectedMethodHref;
                }
            },
            error: function(){
                window.location.href = selectedMethodHref;
            }
        });
    });

    // === Check payment ===
    window.checkPayment = function(){
        var $btn = $('#btn-check-payment');
        var original = $btn.text();
        $btn.prop('disabled', true).text(lang.checking);
        $.ajax({
            type: 'GET',
            url: '/invoice/' + hash + '/status',
            dataType: 'json',
            success: function(data){
                if (data.ok === 'wait') {
                    showToast(lang.not_paid_yet);
                    $btn.prop('disabled', false).text(original);
                } else {
                    pollStatus();
                }
            },
            error: function(){ $btn.prop('disabled', false).text(original); }
        });
    };

    // === Cancel modal ===
    window.openCancelModal = function(){
        $('#pay-cancel-modal').removeAttr('hidden').addClass('is-open');
        document.body.classList.add('pay-modal-open');
    };
    function closeCancelModal(){
        $('#pay-cancel-modal').removeClass('is-open').attr('hidden', true);
        document.body.classList.remove('pay-modal-open');
    }
    $(document).on('click', '[data-close-modal]', closeCancelModal);
    $(document).on('keydown', function(e){ if (e.key === 'Escape') closeCancelModal(); });

    $('#pay-cancel-confirm').on('click', function(){
        var $btn = $(this);
        $btn.prop('disabled', true).text(lang.cancelling);
        $.ajax({
            type: 'POST',
            url: apiUrl + '/orders/' + hash + '/cancel',
            dataType: 'json',
            contentType: 'application/json',
            success: function(data){
                if (data.ok === true) {
                    closeCancelModal();
                    showTerminal('cancelled');
                } else {
                    showToast(data.description || lang.cancel_error);
                    $btn.prop('disabled', false).text(lang.cancel_order);
                }
            },
            error: function(){
                showToast(lang.network_error);
                $btn.prop('disabled', false).text(lang.cancel_order);
            }
        });
    });

    // === Copy chip values ===
    $(document).on('click', '.pay-chip__copy', function(){
        var id = $(this).data('copy-target');
        var text = ($('#' + id).text() || '').replace(/^#/, '');
        if (!text || text === '—') return;
        var copy = function(t){
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(t);
            }
            var ta = document.createElement('textarea');
            ta.value = t; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); } catch(e) {}
            document.body.removeChild(ta);
            return Promise.resolve();
        };
        copy(text).then(function(){ showToast(lang.copied); });
    });

})();
</script>
</body>
</html>
