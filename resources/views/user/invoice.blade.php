<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="format-detection" content="telephone=no" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="MobileOptimized" content="176" />
    <meta name="HandheldFriendly" content="True" />
    <meta name="robots" content="noindex,nofollow" />
    <title>{{ __('site.invoice_title') }} #{{ $hash }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png?v=3">
    <link rel="shortcut icon" href="/assets/img/favicon.ico?v=3">
    <link rel="stylesheet" href="/assets/css/style.min.css?60">
    <script src="https://telegram.org/js/telegram-web-app.js?1"></script>
</head>

<body>

<div class="wrapper">
<div id="payment-warning" style="display:none;position:fixed;left:0;right:0;bottom:0;background:#11181f;color:#f5727f;font-size:14px;line-height:1.4;padding:14px 18px;text-align:center;font-weight:500;z-index:9999;box-shadow:0 -2px 12px rgba(0,0,0,0.4)"></div>
<div class="p-4 mt-2">
    <div class="p-0 mb-1">{{ __('site.invoice_order') }} <span class="font-weight-600">#{{ $hash }}</span></div>
    <h5 class="p-0"><b>{{ __('site.invoice_to_pay') }} <span id="price">...</span></b></h5>
</div>
<div class="px-4">
    <div class="popup__payment-methods" id="buy-payments-methods"></div>
    <div id="bt-payments-wrapper" style="display:none">
        <div class="mb-2" style="cursor:pointer;font-size:14px;color:#6c757d" id="bt-back">&larr; {{ __('site.invoice_back') ?: 'Назад' }}</div>
        <div class="popup__payment-methods" id="bt-payments-methods"></div>
    </div>
</div>
<div class="px-4 mt-3 text-center">
    <button class="btn btn-outline-danger btn-sm" id="btn-cancel-order" onclick="cancelOrder()">{{ __('site.invoice_cancel_order') }}</button>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.4.js" integrity="sha256-a9jBBRygX1Bh5lt8GZjXDzyOB+bWve9EiO7tROUtj/E=" crossorigin="anonymous"></script>

<script type="application/javascript">

    var lang = {
        order_paid: @json(__('site.invoice_order_paid')),
        order_cancelled: @json(__('site.invoice_order_cancelled')),
        payment_expired: @json(__('site.invoice_payment_expired')),
        confirm_cancel: @json(__('site.invoice_confirm_cancel')),
        cancelling: @json(__('site.invoice_cancelling')),
        cancel_error: @json(__('site.invoice_cancel_error')),
        network_error: @json(__('site.invoice_network_error')),
        cancel_order: @json(__('site.invoice_cancel_order')),
        min_payment_amount: @json(__('site.js_min_payment_amount')),
        max_payment_amount: @json(__('site.js_max_payment_amount'))
    };

    let api_url = window.location.origin + '/api';
    let path = window.location.pathname;
    let path_b = path.split('/')[2];

    // If redirected back with ?warning=..., show inline plate immediately and clean URL
    (function(){
        try {
            var params = new URLSearchParams(window.location.search);
            var warn = params.get('warning');
            if (warn) {
                $(function(){
                    var $w = $('#payment-warning');
                    if ($w.length) {
                        $w.text(warn).show();
                        clearTimeout(window._pwHide);
                        window._pwHide = setTimeout(function(){ $w.fadeOut(200); }, 8000);
                    }
                });
                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
            }
        } catch(e) {}
    })();

    getOrderInfo(function (data) {
        if (data.ok === true) {
            $('#price').text(data.result.sum_main);
            paymentMethodsProduct(data.result.sum_usd);
            setInterval(function () {
                getOrder();
            }, 1000);
        }
    });

    function getOrderInfo(callback) {
        $.ajax({
            type: "GET",
            url: api_url + '/orders/'+path_b+'/info',
            dataType: 'json',
            contentType: 'application/json',
            async: true,
            success: function (data) {
                if (data.ok === false) {
                    alert(data.description);
                }
                callback(data);
            }
        });
    }

    function getOrder() {

        $.ajax({
            type: "GET",
            url: '/invoice/{{ $hash }}/status',
            dataType: 'json',
            contentType: 'application/json',
            async: true,
            success: function(data) {
                if(data.ok == 'paid'){
                    Telegram.WebApp.close();
                } else if(data.ok == 'is_sent' || data.ok == 'paid'){
                    var html = '<div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#4ABD5C" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--! Font Awesome Pro 6.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M243.8 339.8C232.9 350.7 215.1 350.7 204.2 339.8L140.2 275.8C129.3 264.9 129.3 247.1 140.2 236.2C151.1 225.3 168.9 225.3 179.8 236.2L224 280.4L332.2 172.2C343.1 161.3 360.9 161.3 371.8 172.2C382.7 183.1 382.7 200.9 371.8 211.8L243.8 339.8zM512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256zM256 48C141.1 48 48 141.1 48 256C48 370.9 141.1 464 256 464C370.9 464 464 370.9 464 256C464 141.1 370.9 48 256 48z"/></svg></div> <div class="d-block mb-0" style="font-weight: 500;font-size: 18px">'+lang.order_paid+'</div>';
                    $('.wrapper').html('<div class="my-5 text-center" style="width: 200px;margin:0 auto">'+html+'</div>');
                } else if(data.ok == 'cancelled'){
                    var html = '<div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#E74C3C" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg></div> <div class="d-block mb-0" style="font-weight: 500;font-size: 18px">'+lang.order_cancelled+'</div>';
                    $('.wrapper').html('<div class="my-5 text-center" style="width: 200px;margin:0 auto">'+html+'</div>');
                } else if(data.ok == 'expired'){
                    var html = '<div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#F39C12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 120v136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2V120c0-13.3-10.7-24-24-24s-24 10.7-24 24z"/></svg></div> <div class="d-block mb-0" style="font-weight: 500;font-size: 18px">'+lang.payment_expired+'</div>';
                    $('.wrapper').html('<div class="my-5 text-center" style="width: 200px;margin:0 auto">'+html+'</div>');
                }
            }

        });

    }

    function paymentMethodsProduct(price) {

        $.ajax({
            type: "POST",
            url: api_url + '/methods_payments/ps/all',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                price: price
            }),
            async: true,
            success: function(data) {
                if (data.ok === true) {

                    var items_html = '';
                    var bt_html = '';
                    var bt_entry = null;

                    $(data.result).each(function (index, e) {

                        if (e.type === 'bt' && e.assets && e.assets.length > 0) {
                            bt_entry = e;
                            $(e.assets).each(function (index, a) {
                                bt_html += '<a href="/invoice/{{ $hash }}/'+e.type+'/'+a.id+'" class="popup__payment-method" data-min-main="'+(a.min_main || 0)+'" data-max-main="'+(a.max_main || 0)+'" data-min-display="'+(a.min || 0)+'" data-max-display="'+(a.max || 0)+'" data-currency="'+(a.currency || '')+'"><div class="popup__payment-method__custom-info">' + a.currency + '</div><img src="' + a.icon + '" alt=""> </a>';
                            });
                            return; // skip default flatten for bt
                        }

                        $(e.assets).each(function (index, a) {

                            items_html += '<a href="/invoice/{{ $hash }}/'+e.type+'/'+a.id+'" class="popup__payment-method" data-min-main="'+(a.min_main || 0)+'" data-max-main="'+(a.max_main || 0)+'" data-min-display="'+(a.min || 0)+'" data-max-display="'+(a.max || 0)+'" data-currency="'+(a.currency || '')+'"><div class="popup__payment-method__custom-info">' + a.currency + '</div><img src="' + a.icon + '" alt=""> </a>';
                        });

                    });

                    if (bt_entry) {
                        items_html += '<a href="#" class="popup__payment-method" id="bt-aggregator"><div class="popup__payment-method__custom-info">' + (bt_entry.title || 'BTKassa') + '</div><img src="' + bt_entry.icon + '" alt=""> </a>';
                    }

                    $('#buy-payments-methods').html(items_html);
                    $('#bt-payments-methods').html(bt_html);

                    $('#bt-aggregator').on('click', function(e) {
                        e.preventDefault();
                        $('#buy-payments-methods').hide();
                        $('#bt-payments-wrapper').show();
                    });

                    $('#bt-back').on('click', function() {
                        $('#bt-payments-wrapper').hide();
                        $('#buy-payments-methods').show();
                    });

                    function showPaymentWarning(msg) {
                        var $w = $('#payment-warning');
                        $w.text(msg).stop(true, true).fadeIn(120);
                        clearTimeout(window._pwHide);
                        window._pwHide = setTimeout(function(){ $w.fadeOut(200); }, 6000);
                    }

                    $('#buy-payments-methods, #bt-payments-methods').on('click', 'a.popup__payment-method', function(e) {
                        if (this.id === 'bt-aggregator') return;
                        var $a = $(this);
                        var href = $a.attr('href');
                        if (!href || href === '#') return;

                        // Local fast min/max check (instant feedback when conversion rate available)
                        var minMain = parseFloat($a.data('min-main')) || 0;
                        var maxMain = parseFloat($a.data('max-main')) || 0;
                        var cur = $a.data('currency') || '';
                        var minDisplay = $a.data('min-display');
                        var maxDisplay = $a.data('max-display');
                        if (minMain > 0 && price < minMain) {
                            e.preventDefault();
                            showPaymentWarning(lang.min_payment_amount.replace(':min', minDisplay).replace(':amount', minDisplay).replace(':currency', cur));
                            return;
                        }
                        if (maxMain > 0 && price > maxMain) {
                            e.preventDefault();
                            showPaymentWarning(lang.max_payment_amount.replace(':max', maxDisplay).replace(':amount', maxDisplay).replace(':currency', cur));
                            return;
                        }

                        // AJAX request to the same endpoint — server returns JSON when X-Requested-With is set.
                        // If ok:true → navigate to payment URL. If ok:false → show inline warning. No page redirect to ?warning=.
                        e.preventDefault();
                        if ($a.data('busy')) return;
                        $a.data('busy', true);
                        $.ajax({
                            type: 'GET',
                            url: href,
                            dataType: 'json',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            timeout: 30000,
                            success: function(data) {
                                $a.data('busy', false);
                                if (data && data.ok === true && data.redirect) {
                                    window.location.href = data.redirect;
                                } else if (data && data.ok === false && data.message) {
                                    showPaymentWarning(data.message);
                                } else {
                                    // Unexpected response — fall back to direct navigation
                                    window.location.href = href;
                                }
                            },
                            error: function(xhr) {
                                $a.data('busy', false);
                                // Network or non-JSON response — fall back to direct navigation
                                window.location.href = href;
                            }
                        });
                    });
                }
            }
        });
    }

    function cancelOrder() {
        if(!confirm(lang.confirm_cancel)) return;
        $('#btn-cancel-order').prop('disabled', true).text(lang.cancelling);
        $.ajax({
            type: "POST",
            url: api_url + '/orders/'+path_b+'/cancel',
            dataType: 'json',
            contentType: 'application/json',
            async: true,
            success: function(data) {
                if(data.ok === true) {
                    var html = '<div class="d-block" style="margin-bottom: 13px"><svg style="width: 50px;fill:#E74C3C" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/></svg></div> <div class="d-block mb-0" style="font-weight: 500;font-size: 18px">'+lang.order_cancelled+'</div>';
                    $('.wrapper').html('<div class="my-5 text-center" style="width: 200px;margin:0 auto">'+html+'</div>');
                } else {
                    alert(data.description || lang.cancel_error);
                    $('#btn-cancel-order').prop('disabled', false).text(lang.cancel_order);
                }
            },
            error: function() {
                alert(lang.network_error);
                $('#btn-cancel-order').prop('disabled', false).text(lang.cancel_order);
            }
        });
    }
</script>

</body>
</html>
