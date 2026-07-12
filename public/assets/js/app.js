let api_url = window.location.origin + '/api';
let path = window.location.pathname;
let path_a = path.split('/')[1];
let path_b = path.split('/')[2];
let path_c = path.split('/')[3];
var timerInterval;

$(document).ready(function() {

    if(path_a !== undefined && path_b !== undefined && path_c !== undefined){
        getProductByAlias(path_c);
    }

    // Watchdog: if userInfo never resolves (slow API), force-resume animation after 2s
    setTimeout(function(){
        $('#block-user').css('visibility','');
        if (window.loginTimeline && window.loginTimeline.paused()) { window.loginTimeline.resume(); }
    }, 2000);
    userInfo(function(data) {
        if (data.ok === true) {
            if(data.result.is_ban === 1){
                location.href = '/blocked';
                return
            }
            if (path_a === '') {
                let btn_register = $('button[id="register"]');
                let btn_register_name = $('button[id="register"] span.name');
                btn_register_name.text(window.lang.my_profile);
                btn_register.attr('onclick', 'location.href="/my/profile";');
                btn_register.attr('data-popup', '');
            }
            if (path_a === 'my'){
                if(path_b === 'orders') {
                    memberOrders();
                }
                // if(path_b === 'tickets') { // disabled - ticket system removed
                //     memberTickets();
                // }
                if(path_b === 'referral') {
                    memberTopups();
                    memberWithdraw();
                }
            }
        }
    });

    $('#withdraw select[name="withdraw-method"]').change(function(){
        let selectedItem = $(this).val();
        if(selectedItem == 3){
            $('#requisites').hide();
            $('#requisites_check').hide();
        } else {
            $('#requisites').show();
            $('#requisites_check').show();
        }
    });

    // Ticket file upload handlers disabled - ticket system removed
    // $('#create #ticket-file').change(function() {
    //     $(this).simpleUpload(api_url + '/attachments/image/upload', {
    //         start: function (file) {
    //             //upload started
    //         },
    //         progress: function (progress) {
    //             //received progress
    //         },
    //         success: function (data) {
    //             //upload successful
    //         },
    //         error: function (error) {
    //             //upload failed
    //         }
    //     });
    // });

    // $('#msg-create #ticket-file').change(function() {
    //     $(this).simpleUpload(api_url + '/attachments/image/upload', {
    //         start: function (file) {
    //             //upload started
    //         },
    //         progress: function (progress) {
    //             //received progress
    //         },
    //         success: function (data) {
    //             //upload successful
    //         },
    //         error: function (error) {
    //             //upload failed
    //         }
    //     });
    // });
});

$('button[id="check-promo"]').click(function() {
    let tariff_id = $('#buy [name="tariff_id"]:checked').val();
    let product_id = $(this).attr('data-id');
    let promocode = $('#buy #buy-promo').val();

    promoCheckByCode(product_id, tariff_id, promocode, function (data) {
        if (data.ok == true) {
            $('#buy #buy-promo').attr('data-is-applied', 1)
            $('#buy #buy-promo').attr('disabled', 'disabled');
            $("#buy #check-promo").hide();
            $('#block_promo').append('<small id="promo-notify" style="margin-left:5px;margin-top:10px;display:block">'+window.lang.discount_applied+data.result.sale+'</small>');
        }
    });
});

function createOrderWeb(product_id) {
    let tariff_id = $('#block_tariffs input[name="tariff_id"]:checked').val();
    let promocode = $('#buy #buy-promo').val();
    let email = $('#buy #buy-email').val();
    let is_applied = $('#buy #buy-promo').attr('data-is-applied');
    var method_pay_id = 0
    if($('#buy-method-2').is(':checked')){
        $('#buy-payments-methods').hide();
        method_pay_id = 99
    } else if($('#buy-method-1').is(':checked')){
        $('#buy-payments-methods').show();
    }

    if(method_pay_id === 99) {
        createOrder(product_id, tariff_id, email, promocode, is_applied, method_pay_id, function(data) {
            if (data.ok == true) {
                getOrdersPaymentLink(data.result.id, 99, "product");
            }
        });
        return;
    }

    let price = parseInt($('#block_tariffs input[name="tariff_id"]:checked').attr('data-price')) || 0;
    let periodDays = $.trim($('#block_tariffs input[name="tariff_id"]:checked').next('label').find('b').text());
    paymentMethodsProduct(price);

    createOrder(product_id, tariff_id, email, promocode, is_applied, 0, function(data) {
        if (data.ok == true) {
            window._createdOrderId = data.result.id;
            window._createdOrderHash = data.result.hash;
            window._createdOrderPrice = parseFloat(data.result.amount_full) || price;

            // Fill the "Информация о заказе" sub-screen.
            $('#buy #buy-product-name').text($.trim($('#buy #modal_title').text()) || '—');
            $('#buy #buy-order-id').text(data.result.hash);
            $('#buy #buy-period').text(periodDays || '—');
            $('#buy #buy-sum').text(data.result.amount);
            $('#buy #buy-expired').text(data.result.time_expired);

            checkOrder(data.result.hash, 0);

            // Open step 2 on the payment-method sub-screen (screen A).
            let popup = $('[data-popup-step="1"]').closest(".popup");
            popup.find("[data-popup-step]._active").removeClass("_active");
            $('[data-popup-step="2"]').addClass("_active");
            if (window._buyShowSubstep) { window._buyShowSubstep('method'); }

            // "Оплатить" — method already chosen & validated on the picker screen.
            $('#buy #btn-pay').off('click').on('click', function() {
                if(!$('input[name="payment_method"]:checked').length) {
                    if (window._buyShowSubstep) { window._buyShowSubstep('method'); }
                    showNotification(window.lang.choose_payment_method, 'fail');
                    return;
                }
                getOrdersPaymentLink(window._createdOrderId, 0, "product");
            });
        }
    });
}


function getProductByAlias(alias) {

    $.ajax({
        type: "GET",
        url: api_url + '/products/'+alias+'/tariffs',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $("#btn_price").text(window.lang.buy_from+data.result.btn_price);

                var items_html = ''

                $(data.result.tariffs).each(function (index, e) {
                    var checked = '';
                    if(index == 0){
                        checked = ' checked';
                    }
                    items_html += ' <input type="radio" name="tariff_id" id="tariff-'+e.id+'" value="'+e.id+'" data-price="'+e.price+'" '+checked+'>\n' +
                        '                            <label for="tariff-'+e.id+'" class="input-block__radio-label">\n' +
                        '                                <div class="input-block__radio-label__icon"></div>\n' +
                        '                                <b>' + e.days + '</b>' +
                        '<span style="margin:0 5px">/</span>' +
                        '<span>' + e.price+'</span>\n' +
                        '                            </label>';
                });

                $('#block_tariffs').html(items_html);
            }
        }
    });
}



// Payment icons per Figma "payson-keys / оплата 4" — decided IN CODE by the
// method name (no DB dependency) so it works everywhere after a plain git pull.
// Assets are repo files in /assets/img (payson-*).
function pmIconHtml(name, src){
    var n = (name || '').toString().toLowerCase();
    var L = '/assets/img/';
    // full circular brand logo (object-cover)
    function full(p){ return '<span class="pm-fig"><img src="' + p + '" alt="" onerror="this.style.display=\'none\'"></span>'; }
    // logo glyph centred on a white circle (e.g. СБП)
    function white(p){ return '<span class="pm-fig pm-fig--white"><img src="' + p + '" alt="" onerror="this.style.display=\'none\'"></span>'; }
    // payson-card.svg is already a complete icon (purple circle + white card),
    // so render it as a full circular logo — NO tint/filter/bg overlay.
    function card(){ return '<span class="pm-fig pm-fig--card"><img src="' + L + 'payson-card.svg" alt=""></span>'; }

    // Per-gateway icons — one distinct mark per payment system. The checkout
    // now passes the system title (Pally, CryptoBot, …) as `name`, so match it
    // first. Real brand logos where publicly available; clean monogram badges
    // for Pally/BTKassa (no public logo online). Keep these BEFORE the generic
    // asset-name rules below.
    if (/free ?kassa|фрикасса/.test(n))              return full(L + 'payson-freekassa.svg');
    if (/crystal ?pay|crystal|кристал/.test(n))      return full(L + 'payson-crystalpay.png');
    if (/stream ?pay|стримпей|стрим/.test(n))        return full(L + 'payson-streampay.svg');
    if (/pally|paypalych|palych/.test(n))            return card(); // фиолетовая карта — переводы/карты
    if (/btkassa|бткасса/.test(n))                   return full(L + 'payson-btkassa.svg');

    if (/сбп|sbp/.test(n))                                   return white(L + 'payment_sbp.svg');
    if (/сбер|sber/.test(n))                                 return full(L + 'payson-sber.png');
    if (/lzt|лзт|crypto ?bot|cryptobot|крипто.?бот/.test(n)) return full(L + 'payson-cryptobot.png');
    if (/usdt|tether|\bcrypto\b|крипт|\bton\b/.test(n))      return full(L + 'payson-cryptobot.png');
    if (/xtr|stars?|звезд|telegram|телеграм/.test(n))        return full(L + 'payson-tgstars.png');
    if (/банковск|перевод|карт|visa|master|\bмир\b|\bmir\b|остальн/.test(n)) return card();
    // anything else → bank card style (never a broken/ugly remote logo).
    return card();
}

// Curated allow-list + display order (Figma). Methods not listed are hidden.
// Returns 0 to hide, or a positive rank for ordering.
function pmOrder(name){
    var n = (name || '').toString().toLowerCase().trim();
    if (/сбп|sbp/.test(n))                  return 1;
    if (/банковск/.test(n))                 return 2;
    if (/crypto ?bot|cryptobot/.test(n))    return 3;
    if (/telegram stars|\bstars\b|\bxtr\b/.test(n)) return 4;
    if (/перевод|streampay|stream/.test(n)) return 5;
    return 0; // Сбер Pay, LZT, BTKassa, прочее — скрыты
}

// Right-side region / currency label per Figma (gray, by method name).
function pmRegion(name, currency){
    var n = (name || '').toString().toLowerCase();
    var cur = (currency || '').toString().trim();
    if (/сбп|sbp|сбер|sber|банковск/.test(n)) return 'Для России';
    // Переводы/карты — show the actual currency list (как в дизайне/invoice):
    // a comma-separated list renders as "Валюты: ...", a single one as-is.
    if (/перевод|streampay|stream/.test(n)) {
        if (cur.indexOf(',') !== -1) return 'Валюты: ' + cur;
        return cur || 'Все страны';
    }
    if (/lzt|crypto ?bot|cryptobot|telegram|stars?|xtr|звезд|usdt|tether|crypto|крипт|crystal|\bton\b/.test(n)) return 'Все страны';
    return cur || '';
}

// Gateway list for checkout: ONE row per payment SYSTEM (not per currency),
// named by the system title. Returns display order (rank) + right-side region
// label for the 7 enabled gateways; null → gateway hidden.
//   pp Pally · cb CryptoBot · bt BTKassa · cp Crystal Pay ·
//   fk Freekassa · sm StreamPay · ts Telegram Stars
function pmGatewayMeta(type){
    switch((type || '').toString().toLowerCase()){
        case 'pp': return { rank: 1, region: 'Все страны' };
        case 'cb': return { rank: 2, region: 'Все страны' };
        case 'bt': return { rank: 3, region: 'Все страны' };
        case 'cp': return { rank: 4, region: 'Все страны' };
        case 'fk': return { rank: 5, region: 'Для России' };
        case 'sm': return { rank: 6, region: 'Все страны' };
        case 'ts': return { rank: 7, region: 'Все страны' };
        default:   return null;
    }
}

// Pick the system's representative payable asset: prefer the storefront
// currency, else the first active asset returned by the API.
function pmPickAsset(assets, mainCur){
    if (!assets || !assets.length) return null;
    for (var k = 0; k < assets.length; k++){
        if (assets[k] && assets[k].currency === mainCur) return assets[k];
    }
    return assets[0];
}

// Right-side label per gateway: region for crypto/SBP gateways, or the list
// of supported currencies for card/transfer aggregators (matches the design).
function pmRegionText(e){
    var t = (e && e.type ? String(e.type) : '').toLowerCase();
    if (t === 'cb' || t === 'cp' || t === 'ts') return 'Все страны';
    if (t === 'fk') return 'Для России';
    var curs = [];
    (e && e.assets ? e.assets : []).forEach(function(a){
        if (a && a.currency && curs.indexOf(a.currency) === -1) curs.push(a.currency);
    });
    if (curs.length > 1) return 'Валюты: ' + curs.join(', ');
    if (curs.length === 1) return curs[0] === 'RUB' ? 'Для России' : curs[0];
    return 'Все страны';
}

function paymentMethodsTopup(price) {

    $.ajax({
        type: "POST",
        url: api_url + '/methods_payments/ps/all',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            price: price
        }),
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                window._mainCurrency = data.main_currency || 'RUB';
                var items_html = '';
                var bt_html = '';
                var bt_entry = null;

                // One row per PAYMENT SYSTEM (gateway), named by the system title.
                // The attached asset is only used to carry the payable currency /
                // min amount / asset id for the invoice.
                var rowHtml = function(e, a) {
                    var name = (e && e.title) || (a && a.title) || (a && a.currency) || '';
                    var meta = pmGatewayMeta(e && e.type);
                    var iconImg = pmIconHtml(name, (e && e.icon) || '');
                    return '<input type="radio" data-id="' + a.id + '" data-min="' + a.min_main + '" data-currency="' + a.currency + '" name="topup_method" id="topup_method_' + a.id + '">' +
                        '<label for="topup_method_' + a.id + '" class="popup__payment-method">' +
                            '<span class="popup__payment-method__icon">' + iconImg + '</span>' +
                            '<span class="popup__payment-method__text">' +
                                '<span class="popup__payment-method__name">' + name + '</span>' +
                                '<span class="popup__payment-method__region">' + pmRegionText(e) + '</span>' +
                            '</span>' +
                        '</label>';
                };

                var rows = [];
                $(data.result).each(function (index, e) {
                    var meta = pmGatewayMeta(e.type);
                    if (!meta) return;                 // не один из 7 шлюзов — скрыт
                    var a = pmPickAsset(e.assets, window._mainCurrency);
                    if (!a) return;                    // нет активной валюты — платить нечем
                    rows.push({ rank: meta.rank, html: rowHtml(e, a) });
                });
                rows.sort(function (x, y) { return x.rank - y.rank; });
                items_html = rows.map(function (r) { return r.html; }).join('');

                $('#topup-payments-methods').html('<div id="topup-bt-main" style="display: flex; flex-direction: column; gap: 10px;">' + items_html + '</div>');
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
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                window._mainCurrency = data.main_currency || 'RUB';
                var items_html = '';
                var bt_html = '';
                var bt_entry = null;

                // One row per PAYMENT SYSTEM (gateway), named by the system title
                // (Pally, CryptoBot, BTKassa, Crystal Pay, Freekassa, StreamPay,
                // Telegram Stars). The attached asset only carries the payable
                // currency / min amount / asset id for the invoice.
                var rowHtml = function(e, a) {
                    var name = (e && e.title) || (a && a.title) || (a && a.currency) || '';
                    var meta = pmGatewayMeta(e && e.type);
                    var iconImg = pmIconHtml(name, (e && e.icon) || '');
                    return '<input type="radio" data-id="' + a.id + '" data-min="' + a.min_main + '" data-currency="' + a.currency + '" name="payment_method" id="payment_method_' + a.id + '">' +
                        '<label for="payment_method_' + a.id + '" class="popup__payment-method">' +
                            '<span class="popup__payment-method__icon">' + iconImg + '</span>' +
                            '<span class="popup__payment-method__text">' +
                                '<span class="popup__payment-method__name">' + name + '</span>' +
                                '<span class="popup__payment-method__region">' + pmRegionText(e) + '</span>' +
                            '</span>' +
                        '</label>';
                };

                var rows = [];
                $(data.result).each(function (index, e) {
                    if (e.is_active !== 1) return;
                    var meta = pmGatewayMeta(e.type);
                    if (!meta) return;                 // не один из 7 шлюзов — скрыт
                    var a = pmPickAsset(e.assets, window._mainCurrency);
                    if (!a) return;                    // нет активной валюты — платить нечем
                    rows.push({ rank: meta.rank, html: rowHtml(e, a) });
                });
                rows.sort(function (x, y) { return x.rank - y.rank; });
                items_html = rows.map(function (r) { return r.html; }).join('');

                $('#buy-payments-methods').html('<div id="product-bt-main">' + items_html + '</div>');
            }
        }
    });
}

function memberTopups() {

    $.ajax({
        type: "GET",
        url: api_url + '/members/refills',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {


                var items_html = ''

                $(data.result).each(function (index, e) {
                    items_html += '<div class="profile__table__row">\n' +
                        '  <div class="profile__table__col">' + e.id + '</div>\n' +
                        '  <div class="profile__table__col">\n' +
                        '  <div class="profile__referral-table__user-card">\n' +
                        '  <span>' + e.member + '</span>\n' +
                        '  </div>\n' +
                        '  </div>\n' +
                        '  <div class="profile__table__col">' + e.created_at + '</div>\n' +
                        '  <div class="profile__table__col profile__table__col_justify-end">\n' +
                        '  <span class="profile__referral-table__income">+' + e.sum + '</span>\n' +
                        '  </div>\n' +
                        '  </div>';
                });

                $('#topup_empty').hide();
                $('#topup_table').show();
                $('#user_topup .simplebar-content').html(items_html);
            }
        }
    });
}


function memberWithdraw() {

    $.ajax({
        type: "GET",
        url: api_url + '/members/withdraw',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {

                var items_html = ''

                $(data.result).each(function (index, e) {

                    var status = 'process';
                    if(e.status === 1){status = 'success';}

                    var method_payment = '';
                    if(e.method === 1){method_payment = 'Qiwi';}
                    if(e.method === 2){method_payment = window.lang.bank_card;}

                    items_html += '<div class="profile__table__row">\n' +
                        '  <div class="profile__table__col">' + e.id + '</div>\n' +
                        '  <div class="profile__table__col">\n' +
                        '  <span class="profile__table__col__col-name">'+window.lang.withdrawal_method+'</span>\n' +
                        '  <span>' + method_payment + '</span>\n' +
                        '  </div>\n' +
                        '  <div class="profile__table__col">' + e.sum + '</div>\n' +
                        '  <div class="profile__table__col">' + e.created_at + '</div>\n' +
                        '  <div class="profile__table__col profile__table__col_justify-end">\n' +
                        '  <div class="profile__withdraw-table__status profile__withdraw-table__status_' + status + '"></div>\n' +
                        '  </div>\n' +
                        '  </div>';
                });

                $('#withdraw_empty').hide();
                $('#withdraw_table').show();
                $('#user_withdraw .simplebar-content').html(items_html);
            }
        }
    });
}

function userInfo(callback) {

    $.ajax({
        type: "GET",
        url: api_url + '/users/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#block-user').attr('href', '/my/profile');
                $('#block-user').removeAttr('data-popup');
                $('#block-user').html('<span class="btn__icon"><img src="/assets/img/icon_user.svg" alt=""></span><span>'+data.result.username+'</span>');
                $('#block-user').css('visibility','');
                if (window.loginTimeline && window.loginTimeline.paused()) { window.loginTimeline.resume(); }
                $('#tg-unlink').hide();
                var telegram_status = '';
                if(data.result.tid === 0){
                    telegram_status = window.lang.account_not_connected;
                }
                if(data.result.tid > 0){
                    telegram_status = 'ID: '+data.result.tid;
                    $('#profile-telegram-btn').addClass(' _linked');
                    $('#tg-unlink').show();
                }
                $('#profile-telegram-status').text(telegram_status);
                $('#profile-username').text(data.result.username);
                $('#edit-login').val(data.result.username);
                $('#edit-email').val(data.result.email);
                $('#profile-balance').text(data.result.balance_main);
                $('#profile-refferal-balance').text(data.result.balance_affiliate);
                $('#profile-refferal-users').text(data.result.ref_users);
                $('#profile-ref-code').text(data.result.ref_code);
                $('#profile-ref-link').text(window.location.origin+'/r/'+data.result.ref_code);
                $('#profile-telegram-btn').attr('href', 'https://t.me/Fanru_bot?start=connect_'+data.result.remember_token);

                if(data.result.email_notify_orders === 1){
                    $('#notify-orders-toggle').attr('checked', 'checked');
                }

                // if(data.result.email_notify_tickets === 1){ // disabled - ticket system removed
                //     $('#notify-tickets-toggle').attr('checked', 'checked');
                // }

                if(data.result.email_notify_status_changed === 1){
                    $('#notify-status-toggle').attr('checked', 'checked');
                }
            } else {
                $('#block-user').css('visibility','');
                if (window.loginTimeline && window.loginTimeline.paused()) { window.loginTimeline.resume(); }
                if (path_a === 'my') {
                    location.href = '/';
                }
            }
            callback(data);
        },
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        $('#block-user').css('visibility','');
        if (window.loginTimeline && window.loginTimeline.paused()) { window.loginTimeline.resume(); }
        if (jqXHR.status === 401) {
            if (path_a === 'my') {
                location.href = '/';
            }
        } else {
            messageSystem(false, window.lang.error_occurred + jqXHR.status + " " + jqXHR.statusText, 3000);
        }
    });

}


function memberOrders() {

    $.ajax({
        type: "GET",
        url: api_url + '/members/orders',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                var items_html = '';

                $(data.result).each(function(index, e) {
                    items_html += '<div class="profile__table__row">\n' +
                        '                                <div class="profile__table__col">'+e.id+'</div>\n' +
                        '                                <div class="profile__table__col">\n' +
                        '                                    <div class="profile__orders-table__cheat-card">\n' +
                        '                                        <span>' + e.title + '</span>\n' +
                        '                                    </div>\n' +
                        '                                </div>\n' +
                        '                                <div class="profile__table__col">\n' +
                        '                                    <span class="profile__table__col__col-name">'+window.lang.purchase_date+'</span>' + e.payment_at + '</div>\n' +
                        '                                <div class="profile__table__col">\n' +
                        '                                    <span class="profile__table__col__col-name">'+window.lang.period+'</span>\n' + e.tariff +
                        '                                </div>\n' +
                        '                                <div class="profile__table__col">\n' +
                        '                                    <span class="profile__table__col__col-name">'+window.lang.price+'</span>\n' +
                        '                                    <span>' + e.amount + '</span>\n' +
                        '                                    <a href="/delivery/' + e.delivery_hash + '" class="profile__table__link"></a>\n' +
                        '                                </div>\n' +
                        '                            </div>';
                });
                $('#orders_empty').hide();
                $('#orders_table').show();
                $('#orders_table .simplebar-content').html(items_html);
            }
        }

    });

}


// function memberTickets() { // disabled - ticket system removed
//
//     $.ajax({
//         type: "GET",
//         url: api_url + '/tickets/list',
//         dataType: 'json',
//         contentType: 'application/json',
//         beforeSend: function(xhr) {
//             xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//         },
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//                 var items_html = '';
//
//                 $(data.result).each(function(index, e) {
//
//                     var status_style;
//                     var status;
//
//                     if(e.status === 0){status_style = 'process';status = 'Ожидает'}
//                     if(e.status === 1){status_style = 'success';status = 'Решено'}
//                     if(e.status === 2){status_style = 'answered';status = 'Отвечен'}
//
//                     items_html += '<div class="profile__ticket" data-profile-switch-tab="ticket" onclick="ticketInfo(' + e.id + ');">\n' +
//                         '                                <div class="profile__ticket__icon"></div>\n' +
//                         '                                <div class="profile__ticket__info">\n' +
//                         '                                    <p>' + e.subject + '</p>\n' +
//                         '                                    <div>\n' +
//                         '                                        <span>' + e.last_answer_at + '</span>\n' +
//                         '                                    </div>\n' +
//                         '                                </div>\n' +
//                         '                                <div class="profile__ticket__status profile__ticket__status_'+status_style+'">'+status+'</div>\n' +
//                         '                            </div>';
//                 });
//
//                 $('#tickets_empty').hide();
//                 $('#tickets').show();
//                 $('#tickets .simplebar-content').html(items_html);
//             }
//         }
//
//     });
//
// }

function logout() {
    $.ajax({
        type: "POST",
        url: api_url + '/auth/logout',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                location.href = '/';
            } else if (data.ok === false) {
                messageSystem(false, data.description, 3000);
            }
        }
    });
}


function btnSave(id) {

    var json = '';
    var prefix = '';

    if (id == 'edit-login'){
        json = JSON.stringify({
            username: $('#'+id).val(),
        });
        prefix = 'login';
    }
    if (id == 'edit-email'){
        json = JSON.stringify({
            email: $('#'+id).val(),
        });
        prefix = 'email';
    }

    $.ajax({
        type: "POST",
        url: api_url + '/members/change/'+prefix,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: json,
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description, 'success');
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });
}


function changeNotify(type) {

    $.ajax({
        type: "POST",
        url: api_url + '/members/change/notify/' + type,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description, 'success');
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });
}


function telegramUnlink() {

    $.ajax({
        type: "POST",
        url: api_url + '/members/change/telegram/unlink',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description, 'success');
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });
}



// function ticketCreate() { // disabled - ticket system removed
//
//     var subject_id = $('select[name="ticket-theme"]').val();
//     var message = $('.profile__ticket-form #ticket-body').val();
//
//     $.ajax({
//         type: "POST",
//         url: api_url + '/tickets/create',
//         dataType: 'json',
//         contentType: 'application/json',
//         beforeSend: function(xhr) {
//             xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//         },
//         data: JSON.stringify({
//             subject_id: subject_id,
//             message: message,
//         }),
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//                 showNotification(data.description,'success')
//                 location.href = '/my/tickets';
//             } else if (data.ok === false) {
//                 showNotification(data.description,'fail')
//             }
//         }
//
//     });
//
// }

function createWithdraw() {

    var method_id = $('#withdraw .select__option._active').attr('data-value');
    var requisites = $('#withdraw #withdraw-details').val();
    var is_confirm = 0;

    if($('#withdraw-confirm-data').is(":checked")){
        is_confirm = 1;
    }

    $.ajax({
        type: "POST",
        url: api_url + '/withdrawals/create',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            method_id: method_id,
            requisites: requisites,
            is_confirm: is_confirm
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description,'success')
                userInfo();
                memberWithdraw();
                closePopup();
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });
}

// function createOrderTopup() {
//
//     var method_id = $('input[name="topup_method"]:checked').attr('data-id')
//     var sum = $('#topup_sum').val();
//     var _token = $('meta[name="csrf-token"]').attr('content');
//
//     $.ajax({
//         type: "POST",
//         url: api_url + '/orders/balance/create',
//         dataType: 'json',
//         contentType: 'application/json',
//         beforeSend: function(xhr) {
//             xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//         },
//         data: JSON.stringify({
//             method_id: method_id,
//             sum: sum,
//             _token: _token
//         }),
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//                 if(data.result.payment_link) {
//                     checkOrder(data.result.hash, 99);
//                     redirect(data.result.payment_link,false);
//                 } else {
//                     clearInterval(timerInterval);
//                     let popup = $('[data-popup-step="2"]').closest(".popup");
//                     popup.find("[data-popup-step]._active").removeClass("_active");
//                     $('[data-popup-step="2"]').addClass("_active");
//                 }
//
//             } else if (data.ok === false) {
//                 showNotification(data.description,'fail')
//             }
//         }
//
//     });
//
// }


function createOrderTopup() {
    let modal_id = '#replenishment';
    var sum = $(modal_id + ' #topup_sum').val();
    var _token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        type: "POST",
        url: api_url + '/orders/balance/create',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            sum: sum,
            _token: _token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                if(data.result.hash) {
                    paymentMethodsTopup(sum);
                    window._createdOrderPrice = parseFloat(sum) || 0;
                    checkOrder(data.result.hash, 0);
                    let popup = $('[data-popup-step="1"]').closest(".popup");
                    popup.find("[data-popup-step]._active").removeClass("_active");
                    $('[data-popup-step="2"]').addClass("_active");
                    $(modal_id + ' #buy-sum').text(data.result.amount);
                    $(modal_id + ' #btn-pay').attr('onclick', 'getOrdersPaymentLink('+data.result.id+', 0, "balance")');
                }
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });

}


const inputField = document.getElementById('search_query');
// Anti-autofill: clear any browser-injected value on page load
if (inputField) {
    setTimeout(function() { inputField.value = ''; }, 50);
    // Also clear on DOM ready in case setTimeout fires too early
    document.addEventListener('DOMContentLoaded', function() {
        inputField.value = '';
    });
}
inputField.addEventListener('keyup', function(event) {
    const query = event.target.value;

    $.ajax({
        type: "POST",
        url: api_url + '/search',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            q: query,
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#results_search .simplebar-content').html('');
                let categories = data.result.categories;
                let products = data.result.products;


                $(data.result.categories).each(function(index, e) {
                    $('#results_search .simplebar-content').append('<div class="search-item">\n' +
                        '                            <a href="'+e.alias+'" class="search-item__link"></a>\n' +
                        '                            <div class="search-item__image">\n' +
                        '                                <img src="'+e.image_site+'" alt="">\n' +
                        '                            </div>\n' +
                        '                            <div class="search-item__info">\n' +
                        '                                <span class="search-item__name">'+e.title+'</span>\n' +
                        '                            </div>\n' +
                        '                        </div>');
                });

                $(data.result.products).each(function(index, e) {

                    var status_hack = '';

                    if(e.status_hack != '') {
                        status_hack = '<div class="types" style="margin-top: 5px">' + e.status_hack + '</div>';
                    }

                    $('#results_search .simplebar-content').append('<div class="search-item">\n' +
                        '                            <a href="'+e.alias+'" class="search-item__link"></a>\n' +
                        '                            <div class="search-item__image">\n' +
                        '                                <img src="'+e.image_site+'" alt="">\n' +
                        '                            </div>\n' +
                        '                            <div class="search-item__info">\n' +
                        '                                <span class="search-item__name">'+e.title+'</span>\n' + status_hack +
                        '                            </div>\n' +
                        '                        </div>');
                });

                if (categories.length === 0 && products.length === 0) {
                    $('#results_search .simplebar-content').html('<div style="text-align:center;font-size:14px;margin:15px 0">'+window.lang.nothing_found+'</div>');
                }

            }
        }

    });

});


function searchStatus(query, status) {

    $.ajax({
        type: "POST",
        url: api_url + '/cheats/search',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            query: query,
            status: status
        }),
        async: true,
        success: function(data) {
            if (data.ok === true && data.count !== 0) {

                var results = '';

                $(data.result).each(function(index, e) {
                    var cheats = [];

                    $(e.cheats).each(function(index, s) {
                        cheats.push('<span class="status _' + s.status + '">' + s.title + '</span>');
                    });

                    results +=
                        '<div class="game-status-block">' +
                        '    <div class="game-status-block__info">' +
                        '        <p class="game-status-block__name">' + e.title + '</p>' +
                        '        <div class="game-status-block__cheats">' + cheats.join('') +
                        '        </div>' +
                        '    </div>' +
                        '</div>';

                });
                $('.cheat-statuses #results_empty').html('');
                $('.cheat-statuses #results_search').html(results);
            } else {

                results = '<div class="profile__empty-block" style="padding: 50px 0;width:100%">\n' +
                    '                            <div class="profile__empty-block__icon">\n' +
                    '                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
                    '                                    <g opacity="0.5">\n' +
                    '                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.125 5.42534C13.125 3.59754 11.5546 2.13355 9.69368 2.31408L9.69141 2.3143C8.17354 2.45597 6.875 3.99404 6.875 5.58367V6.392C6.875 6.73718 6.59518 7.017 6.25 7.017C5.90482 7.017 5.625 6.73718 5.625 6.392V5.58367C5.625 3.42367 7.34255 1.27877 9.57412 1.06981C12.1794 0.817724 14.375 2.87017 14.375 5.42534V6.57534C14.375 6.92051 14.0952 7.20034 13.75 7.20034C13.4048 7.20034 13.125 6.92051 13.125 6.57534V5.42534Z" fill="white"></path>\n' +
                    '                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.81165 7.34899C3.54949 6.47178 4.8145 6.04297 6.66703 6.04297H13.3337C15.1862 6.04297 16.4512 6.47178 17.1891 7.34899C17.921 8.21913 17.9895 9.36273 17.8716 10.4284L17.8706 10.4372L17.2462 15.4319C17.1541 16.2871 16.9383 17.2238 16.175 17.9211C15.4158 18.6147 14.2394 18.9596 12.5004 18.9596H7.50036C5.76132 18.9596 4.58497 18.6147 3.82569 17.9211C3.0624 17.2237 2.84665 16.287 2.75452 15.4318L2.12909 10.4284C2.01117 9.36274 2.07975 8.21913 2.81165 7.34899ZM3.3711 10.2866L3.9969 15.293C4.08011 16.0696 4.25249 16.6179 4.66879 16.9982C5.09076 17.3837 5.88941 17.7096 7.50036 17.7096H12.5004C14.1113 17.7096 14.91 17.3837 15.3319 16.9982C15.7482 16.6179 15.9207 16.0697 16.0039 15.2931L16.0051 15.2821L16.6296 10.2865C16.7359 9.32108 16.625 8.62026 16.2325 8.15361C15.8453 7.69332 15.0395 7.29297 13.3337 7.29297H6.66703C4.96123 7.29297 4.15541 7.69332 3.76825 8.15361C3.37573 8.62028 3.26482 9.32112 3.3711 10.2866Z" fill="white"></path>\n' +
                    '                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.0801 10.0013C12.0801 9.54106 12.4532 9.16797 12.9134 9.16797H12.9209C13.3811 9.16797 13.7542 9.54106 13.7542 10.0013C13.7542 10.4615 13.3811 10.8346 12.9209 10.8346H12.9134C12.4532 10.8346 12.0801 10.4615 12.0801 10.0013Z" fill="white"></path>\n' +
                    '                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.24512 10.0013C6.24512 9.54106 6.61821 9.16797 7.07845 9.16797H7.08594C7.54617 9.16797 7.91927 9.54106 7.91927 10.0013C7.91927 10.4615 7.54617 10.8346 7.08594 10.8346H7.07845C6.61821 10.8346 6.24512 10.4615 6.24512 10.0013Z" fill="white"></path>\n' +
                    '                                    </g>\n' +
                    '                                </svg>\n' +
                    '                            </div>\n' +
                    '                            <p class="profile__empty-block__caption">'+window.lang.nothing_found+'</p>\n' +
                    '                        </div>';
                $('.cheat-statuses #results_empty').html(results);
                $('.cheat-statuses #results_search').html('');
            }



        }

    });
}

function changeStatusQuery (status){
    var query = $('.status-heading-section__filter #search_query').val();
    searchStatus(query, status)
}


if(path_a === 'status') {
    const query_input = document.querySelector('.status-heading-section__filter #search_query');
    query_input.addEventListener('keyup', function (event) {
        const query = event.target.value;
        var status = $('#status-select').attr('data-value')
            || $('.status-heading-section__filter .st-select__opt.is-active').attr('data-value')
            || $('.status-heading-section__filter .select__option._active').attr('data-value')
            || 0;
        searchStatus(query, status)
    });
}

function getOrdersPaymentLink(order_id, method_id, type) {

    var selectedInput;
    if (type === 'balance') {
        selectedInput = $('input[name="topup_method"]:checked');
        method_id = selectedInput.attr('data-id');
    } else {
        if (method_id !== 99) {
            selectedInput = $('input[name="payment_method"]:checked');
            method_id = selectedInput.attr('data-id');
        }
    }

    if (selectedInput && selectedInput.length) {
        var minAmount = parseFloat(selectedInput.attr('data-min')) || 0;
        var methodCurrency = window._mainCurrency || selectedInput.attr('data-currency') || '';
        var orderPrice = window._createdOrderPrice || 0;
        if (minAmount > 0 && orderPrice > 0 && orderPrice < minAmount) {
            var msg = window.lang.min_payment_amount.replace(':min', minAmount).replace(':currency', methodCurrency);
            showNotification(msg, 'fail');
            return;
        }
    }

    $.ajax({
        type: "POST",
        url: api_url + '/orders/payment_link/get',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            method_pay_id: method_id,
            order_id: order_id,
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                if (data.result.payment_link) {
                    var paymentUrl = data.result.payment_link;
                    $.ajax({
                        type: 'GET',
                        url: paymentUrl,
                        dataType: 'json',
                        headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                        timeout: 30000,
                        success: function(d) {
                            if (d && d.ok === true && d.redirect) {
                                redirect(d.redirect, false);
                            } else if (d && d.ok === false) {
                                showNotification(d.message || 'Ошибка оплаты', 'fail');
                            } else {
                                redirect(paymentUrl, false);
                            }
                        },
                        error: function() {
                            redirect(paymentUrl, false);
                        }
                    });
                } else {
                    clearInterval(timerInterval);

                    let popup = $('[data-popup-step="1"]').closest(".popup");
                    popup.find("[data-popup-step]._active").removeClass("_active");
                    $('[data-popup-step="3"]').addClass("_active");

                    if (type === 'product') {
                        $('#buy #material_link').attr("href", data.result.material_link);
                    }
                    // $('#result #body').hide();
                    // $('#result').append('<div id="system-msg" style="margin: 87px auto; display: block; text-align: center;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 104px; fill: #4ABD5C;"><path d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"></path></svg><h2 style="margin-top: 12px;">Заказ оплачен</h2><a target="_blank" href="'+data.result.material_link+'" class="btn btn-accent" style="padding: 15px 31px; margin: 0 auto; margin-top: 30px;max-width: 200px;">Получить товар</a></div>');
                }
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });

}

function hideNotify(){
    $('#result #body').show();
    $('#result #system-msg').hide();
}

// function ticketCreateByID(id) { // disabled - ticket system removed
//
//     var message = $('.profile__form__textarea-container #ticket-message').val();
//
//     $.ajax({
//         type: "POST",
//         url: api_url + '/tickets/' + id + '/create',
//         dataType: 'json',
//         contentType: 'application/json',
//         beforeSend: function(xhr) {
//             xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//         },
//         data: JSON.stringify({
//             ticket_id: id,
//             message: message,
//         }),
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//                 $('.profile__form__textarea-container #ticket-message').val('');
//                 ticketInfo(id);
//             } else if (data.ok === false) {
//                 showNotification(data.description,'fail')
//             }
//         }
//
//     });
//
// }


function lastMessageScroll() {
    var container = document.querySelector('.profile__ticket-chat .simplebar-content-wrapper');
    container.scrollTop = container.scrollHeight;
}

// function ticketInfo(id) { // disabled - ticket system removed
//
//     $.ajax({
//         type: "GET",
//         url: api_url + '/tickets/' + id + '/info',
//         dataType: 'json',
//         contentType: 'application/json',
//         beforeSend: function(xhr) {
//             xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//         },
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//
//                 if(data.result.status === 1){
//                     $('.profile__form.profile__ticket-form').hide();
//                 } else {
//                     $('.profile__form.profile__ticket-form').show();
//                 }
//
//                 $('#ticket_subject').text(data.result.subject);
//                 $('#ticket_last_answer_at').text(data.result.last_answer_at);
//                 $('#ticket_btn_submit').attr('onclick', 'ticketCreateByID('+id+');')
//
//                 if(data.result.status === 0){var status_style = 'profile__ticket__status_process';var status = 'Ожидает'}
//                 if(data.result.status === 1){var status_style = 'profile__ticket__status_success';var status = 'Решено'}
//                 if(data.result.status === 2){var status_style = 'profile__ticket__status_answered';var status = 'Отвечен'}
//
//                 $('.profile__ticket_without-bg .profile__ticket__status').removeClass('profile__ticket__status_process');
//                 $('.profile__ticket_without-bg .profile__ticket__status').removeClass('profile__ticket__status_success');
//                 $('.profile__ticket_without-bg .profile__ticket__status').removeClass('profile__ticket__status_answered');
//
//                 $('.profile__ticket_without-bg .profile__ticket__status').addClass(status_style).text(status)
//
//                 var items_html = '';
//
//                 $(data.result.messages).each(function(index, e) {
//                     var msg_style = '';
//                     var avatar = '';
//
//                     var block_image = '';
//                     if(e.image != ''){
//                         block_image = '<a style="margin-bottom: 10px; display: block; overflow: hidden;" target="_blank" href="/i' + e.image + '"><img src="/i' + e.image + '" width="300" /></a>';
//                     }
//
//                     if(e.user_id != 0){
//                         msg_style = ' profile__ticket-chat__message_own';
//                         avatar = '<div class="profile__ticket__icon__user" style="border-radius: 50%;margin-right: 10px;"></div>';
//                     } else {
//                         avatar = '<div class="profile__ticket__icon" style="border-radius: 50%;background-color: #262839;margin-right: 10px;"></div>';
//                     }
//
//                     items_html += '<div class="profile__ticket-chat__message'+msg_style+'">\n' + avatar +
//                     '                                <div class="profile__ticket-chat__message__body">\n' + block_image +
//                     '                                    <p>' + e.message + '</p>\n' +
//                     '                                    <span class="time">' + e.created_at + '</span>\n' +
//                     '                                </div>\n' +
//                     '                            </div>';
//                 });
//
//                 $('#ticket_messages .simplebar-content').html(items_html);
//                 lastMessageScroll();
//             } else if (data.ok === false) {
//                 showNotification(data.description,'fail')
//             }
//         }
//     });
// }


//
// document.querySelector('.edit-email .submit').addEventListener('click', function(event) {
//
//     event.preventDefault();
//     var email = $('#edit-email').val();
//
//     $.ajax({
//         type: "POST",
//         url: api_url + '/members/change/email',
//         dataType: 'json',
//         contentType: 'application/json',
//             beforeSend: function(xhr) {
//                 xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
//             },
//         data: JSON.stringify({
//             email: email,
//         }),
//         async: true,
//         success: function(data) {
//             if (data.ok === true) {
//                 showNotification(data.description,'success')
//             } else if (data.ok === false) {
//                 showNotification(data.description,'fail')
//             }
//         }
//
//     });
// });


function checkOrder(id, method_pay_id) {
    $.ajax({
        type: "GET",
        url: api_url + '/orders/'+id+'/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function (data) {
            if (data.ok === true && data.action === 'done') {
                let popup = $('[data-popup-step="1"]').closest(".popup");
                popup.find("[data-popup-step]._active").removeClass("_active");
                $('[data-popup-step="3"]').addClass("_active");

                if (data.result && data.result.material_link) {
                    $('#buy #material_link').attr("href", data.result.material_link);
                }
            } else if (data.ok === false) {
                showNotification(data.description, 'fail');
                let popup = $('[data-popup-step="3"]').closest(".popup");
                popup.find("[data-popup-step]._active").removeClass("_active");
                $('[data-popup-step="4"]').addClass("_active");
            } else {
                $('#timer').text(data.expired_sec);
                timerInterval = setTimeout(function () {
                    checkOrder(id, method_pay_id);
                }, 2000);
            }
        }
    });
}


function createOrder(product_id, tariff_id, email, promocode, is_applied, method_pay_id, callback) {
    $.ajax({
        type: "POST",
        url: api_url + '/orders/create',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            product_id: product_id,
            tariff_id: tariff_id,
            email: email,
            promocode: promocode,
            is_applied: is_applied
        }),
        async: true,
        success: function(data) {
            if (data.ok === false) {
                if (data.description.indexOf('войти') !== -1) {
                    openPopup('register');
                }
                showNotification(data.description,'fail')
            }
            callback(data);
        }
    });
}

function promoCheckByCode(product_id, tariff_id, promocode, callback) {
    $.ajax({
        type: "POST",
        url: api_url + '/coupons/check',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            product_id: product_id,
            tariff_id: tariff_id,
            promocode: promocode
        }),
        async: true,
        success: function(data) {
            if (data.ok === false) {
                showNotification(data.description,'fail')
            }
            callback(data);
        }
    });
}

function tariffByProductID(id, pid, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/tariffs/'+id+'/product/'+pid,
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === false) {
                showNotification(data.description,'fail')
            }
            callback(data);
        }
    });
}

function productInfoByID(id, callback) {
    $.ajax({
        type: "GET",
        url: api_url + '/products/'+id+'/buy/info',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        async: true,
        success: function(data) {
            if (data.ok === false) {
                showNotification(data.description,'fail')
            }
            callback(data);
        }
    });
}

function openModalBuy(id) {
    userInfo(function(data) {
        if (data.ok === true) {
            $('#block_payment #buy-email').text(data.result.email);
        }
    });

    productInfoByID(id, function(data) {
        if (data.ok === true) {
            $('#modal_title').text(data.result.title);
            $('#modal_price').text(data.result.price);
        }
    });
}
function changePassword() {

    var old_password = $('#change-old-password').val();
    var new_password = $('#change-new-password').val();
    var repeat_password = $('#change-repeat-password').val();

    $.ajax({
        type: "POST",
        url: api_url + '/members/change/password',
        dataType: 'json',
        contentType: 'application/json',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('Authorization', 'Bearer ' + getCookie('session_token'));
        },
        data: JSON.stringify({
            old_password: old_password,
            new_password: new_password,
            repeat_password: repeat_password
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                $('#change-old-password').val('');
                $('#change-new-password').val('');
                $('#change-repeat-password').val('');
                closePopup()
                showNotification(data.description,'success')
            } else if (data.ok === false) {
                showNotification(data.description,'fail')
            }
        }

    });

}


function signIn() {

    let modal_id = '#auth';

    var username = $(modal_id + ' #username').val();
    var password = $(modal_id + ' #password').val();
    var hcaptcha_token = $(modal_id + ' textarea[name="h-captcha-response"]').val();

    $.ajax({
        type: "POST",
        url: api_url + '/auth/login',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            username: username,
            password: password,
            "h-captcha-response": hcaptcha_token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                createCookie('session_token', data.result.token, window.SESSION_TTL_DAYS || 15);
                if (getCookie('session_token') !== undefined) {
                    showNotification(data.description,'success');
                    setTimeout(function() {
                        location.href = window.location.pathname;
                    }, 2000);
                }
            } else if (data.ok === false) {
                showNotification(data.description,'fail');
                refreshCaptcha('auth');
            }
        },
        error: function(xhr) {
            showNotification(window.lang.server_connection_error,'fail');
        }

    });

}



function resetPassword() {

    let modal_id = '#resetPass';

    var email = $(modal_id + ' #reset-email').val();
    var code = $(modal_id + ' #reset-code').val();
    var new_password = $(modal_id + ' #reset-new-password').val();
    var hcaptcha_token = $(modal_id + ' textarea[name="h-captcha-response"]').val();

    $.ajax({
        type: "POST",
        url: api_url + '/auth/reset-password',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            email: email,
            code: code,
            new_password: new_password,
            "h-captcha-response": hcaptcha_token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description,'success')
                if(code === '' && new_password === ''){
                    $("#reset-block-code").show();
                    $("#reset-block-new-password").show();
                } else {
                    $('#reset-email').val('');
                    $('#reset-code').val('');
                    $('#reset-new-password').val('');
                    closePopup();
                }
            } else if (data.ok === false) {
                showNotification(data.description,'fail');
                refreshCaptcha('resetPass');
            }
        }

    });

}

function signUp() {

    let modal_id = '#register';

    var username = $(modal_id + ' #reg-username').val();
    var password = $(modal_id + ' #reg-password').val();
    var repassword = $(modal_id + ' #reg-re-password').val();
    var referral_code = $(modal_id + ' #reg-referral-code').val();
    var hcaptcha_token = $(modal_id + ' textarea[name="h-captcha-response"]').val();

    $.ajax({
        type: "POST",
        url: api_url + '/auth/register',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({
            username: username,
            password: password,
            repassword: repassword,
            referral_code: referral_code,
            "h-captcha-response": hcaptcha_token
        }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                createCookie('session_token', data.result.token, window.SESSION_TTL_DAYS || 15);
                if (getCookie('session_token') !== undefined) {
                    showNotification(data.description,'success');
                    setTimeout(function() {
                        location.href = window.location.pathname;
                    }, 2000);
                }
            } else if (data.ok === false) {
                showNotification(data.description,'fail');
                refreshCaptcha('register');
            }
        }

    });

}


function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

function createCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = ";expires=" + date.toGMTString();
    }
    document.cookie = name + "=" + value + expires + ";path=/";
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}


// function startTimer(duration, display) {
//     var timer = duration, minutes, seconds;
//     timerInterval = setInterval(function () {
//         minutes = parseInt(timer / 60, 10);
//         seconds = parseInt(timer % 60, 10);
//
//         minutes = minutes < 10 ? "0" + minutes : minutes;
//         seconds = seconds < 10 ? "0" + seconds : seconds;
//
//         display.text(minutes + ":" + seconds);
//
//         if (--timer < 0) {
//             clearInterval(timerInterval);
//             display.text("00:00");
//         }
//     }, 1000);
// }

function redirect(link, noref) {

    var deviceAgent = navigator.userAgent;
    var ios = deviceAgent.toLowerCase().match(/(iphone|ipod|ipad)/);
    if (noref) {
        var meta = document.createElement('meta');
        meta.name = "referrer";
        meta.content = "no-referrer";
        document.getElementsByTagName('head')[0].appendChild(meta);
        meta.remove();
    }
    if(!noref){
        var meta = document.createElement('meta');
        meta.name = "referrer";
        meta.content = "no-referrer-when-downgrade";
        document.getElementsByTagName('head')[0].appendChild(meta);
        meta.remove();
    }
    if (ios) {
        window.location.href = link;
    } else {
        var a = document.createElement("a");
        a.href = link;
        a.target = "_blank";
        if (noref) {
            a.rel = "noreferrer noopener";
        }else{
            a.rel = "no-referrer-when-downgrade";
        }
        a.click();
        a.remove();
    }
}
function changeLanguage(lang) {
    window.location.href = '/lang/' + lang;
}

function submitReview() {
    var author = ($('#review-author').val() || '').trim();
    var text = ($('#review-text').val() || '').trim();
    var link = ($('#review-link').val() || '').trim();

    $.ajax({
        type: "POST",
        url: api_url + '/reviews/submit',
        dataType: 'json',
        contentType: 'application/json',
        data: JSON.stringify({ author: author, text: text, link: link }),
        async: true,
        success: function(data) {
            if (data.ok === true) {
                showNotification(data.description, 'success');
                $('#review-author').val('');
                $('#review-text').val('');
                $('#review-link').val('');
                if (typeof closePopup === 'function') closePopup();
            } else {
                showNotification(data.description, 'fail');
            }
        },
        error: function() {
            showNotification(window.lang.server_connection_error, 'fail');
        }
    });
}


/* ============================================================== */
/*  Mobile carousel pagination dots (hackexe-style) for EVERY        */
/*  horizontal scroll carousel. CSS shows the dots only on mobile.   */
/*  Active dot follows whichever card is nearest the viewport centre */
/*  of the scroller — works for both CSS-scroll and Swiper cssMode.  */
/* ============================================================== */
(function () {
    // [scroller selector, card selector inside it]
    var CAROUSELS = [
        ['#catalog .catalog__cards-container', '.catalog-card'],
        ['.section2__grid', '.s2-card'],
        ['.game-cheats-slider', '.swiper-slide'],
        ['.reviews-slider', '.swiper-slide'],
        ['.game-cards-slider', '.swiper-slide']
    ];

    function buildDots(scroller, cardSel) {
        if (!scroller || scroller.dataset.dotsInit) return;
        var cards = Array.prototype.slice.call(scroller.querySelectorAll(cardSel))
            .filter(function (c) { return !c.classList.contains('swiper-slide-duplicate'); });
        if (cards.length < 2) return;
        scroller.dataset.dotsInit = '1';

        var dots = document.createElement('div');
        dots.className = 'catalog__dots';
        cards.forEach(function (card, i) {
            var d = document.createElement('span');
            d.className = 'catalog__dots__dot' + (i === 0 ? ' is-active' : '');
            d.addEventListener('click', function () {
                card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            });
            dots.appendChild(d);
        });
        scroller.parentNode.insertBefore(dots, scroller);

        var dotEls = Array.prototype.slice.call(dots.children);
        var ticking = false;
        function update() {
            ticking = false;
            var sRect = scroller.getBoundingClientRect();
            var center = sRect.left + sRect.width / 2;
            var best = 0, bestDist = Infinity;
            cards.forEach(function (c, i) {
                var r = c.getBoundingClientRect();
                var dist = Math.abs((r.left + r.width / 2) - center);
                if (dist < bestDist) { bestDist = dist; best = i; }
            });
            dotEls.forEach(function (d, i) { d.classList.toggle('is-active', i === best); });
        }
        // the actual scrolling element may be the node itself or a child wrapper
        scroller.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
        }, { passive: true });
        var wrap = scroller.querySelector('.swiper-wrapper');
        if (wrap) wrap.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(update); }
        }, { passive: true });
    }

    function initAllDots() {
        CAROUSELS.forEach(function (pair) {
            document.querySelectorAll(pair[0]).forEach(function (sc) { buildDots(sc, pair[1]); });
        });
    }

    // run after Swiper has had a chance to initialise (it may add/clone slides)
    if (document.readyState === 'complete') {
        initAllDots();
    } else {
        window.addEventListener('load', function () { setTimeout(initAllDots, 60); });
    }
})();


/* ============================================================
 * Buy modal — two-screen payment (design in-modal, not a page):
 *   screen A: choose method (with currencies)  →  «Продолжить»
 *   screen B: order info  →  «Оплатить / Проверить оплату / Отменить»
 * ============================================================ */
jQuery(function ($) {
    window._buyShowSubstep = function (name) {
        $('#buy [data-substep]').removeClass('_active');
        $('#buy [data-substep="' + name + '"]').addClass('_active');
        var b = document.getElementById('body'); if (b) { b.scrollTop = 0; }
        var m = document.getElementById('buy-payments-methods'); if (m) { m.scrollTop = 0; }
    };

    // A → B: validate a method is chosen (and the amount fits) before showing order info.
    $(document).on('click', '#buy-method-continue', function () {
        var sel = $('#buy input[name="payment_method"]:checked');
        if (!sel.length) { showNotification(window.lang.choose_payment_method, 'fail'); return; }
        var minAmount = parseFloat(sel.attr('data-min')) || 0;
        var cur = window._mainCurrency || sel.attr('data-currency') || '';
        if (minAmount > 0 && (window._createdOrderPrice || 0) < minAmount) {
            showNotification(window.lang.min_payment_amount.replace(':min', minAmount).replace(':currency', cur), 'fail');
            return;
        }
        window._buyShowSubstep('order');
    });

    // B → A
    $(document).on('click', '[data-substep-back]', function () {
        window._buyShowSubstep($(this).attr('data-substep-back'));
    });

    // Manual "Проверить оплату" — force an immediate order-status check.
    $(document).on('click', '#buy-check-pay', function () {
        if (!window._createdOrderHash) { return; }
        var msg = $(this).attr('data-checking');
        if (msg) { showNotification(msg, 'success'); }
        try { clearTimeout(timerInterval); } catch (e) {}
        checkOrder(window._createdOrderHash, 0);
    });
});
