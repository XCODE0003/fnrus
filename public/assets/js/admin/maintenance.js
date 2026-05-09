/* global $, api_url, setAuthorization, messageSystem */
/**
 * Admin / Обслуживание — analytics reset, bulk cleanup, audit log.
 *
 * Each destructive action ships a `confirm` field so the controller
 * can re-validate the phrase server-side. The UI uses a styled modal
 * fallback (window.confirm) for the "are you sure" prompt; the
 * server-side phrase check is the actual safety net.
 */
(function () {
    'use strict';

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[<>&"']/g, function (s) {
            return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[s];
        });
    }

    function ajax(method, path, body, onOk) {
        var opts = {
            type: method,
            url: api_url + path,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) {
                    if (data.description) messageSystem(true, data.description, 2500);
                    if (typeof onOk === 'function') onOk(data);
                } else {
                    messageSystem(false, data.description || 'Ошибка', 3500);
                }
            },
            error: function () {
                messageSystem(false, 'Сетевая ошибка', 3500);
            }
        };
        if (body) {
            opts.contentType = 'application/json';
            opts.data = JSON.stringify(body);
        }
        $.ajax(opts);
    }

    function loadStats() {
        $.ajax({
            type: 'GET',
            url: api_url + '/maintenance/stats',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) {
                    $('#maint_stats').html('<div class="col-12"><small class="text-danger">' + escapeHtml(data.description) + '</small></div>');
                    return;
                }
                var r = data.result || {};
                var a = r.analytics || {};
                var o = r.orders || {};
                var html = '' +
                    card('Заказы (оплаченные)', o.paid) +
                    card('Заказы (просроченные)', o.expired) +
                    card('Заказы (отменённые)', o.cancelled) +
                    card('Заказы (ожидают)', o.pending) +
                    card('Промокоды', r.coupons) +
                    card('Файлы (attachments)', r.attachments) +
                    card('История экспорта', r.exports) +
                    card('Просмотры товаров', (a.products && a.products.count_views) || 0) +
                    card('Продажи товаров', (a.products && a.products.count_sales) || 0);
                $('#maint_stats').html(html);
            }
        });
    }

    function card(label, value) {
        var v = value === undefined || value === null ? 0 : value;
        return '<div class="col-md-3 col-sm-4 col-6 mb-2">' +
               '<div class="border rounded p-2 text-center">' +
               '<div class="small text-muted">' + escapeHtml(label) + '</div>' +
               '<div style="font-size:1.4rem"><strong>' + Number(v).toLocaleString('ru-RU') + '</strong></div>' +
               '</div></div>';
    }

    function loadLog() {
        $.ajax({
            type: 'GET',
            url: api_url + '/maintenance/log?limit=50',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) {
                    $('#maint_log_body').html('<tr><td colspan="6" class="text-danger">' + escapeHtml(data.description) + '</td></tr>');
                    return;
                }
                var rows = data.result || [];
                if (!rows.length) {
                    $('#maint_log_body').html('<tr><td colspan="6" class="text-center text-muted py-3">Записей нет</td></tr>');
                    return;
                }
                var html = rows.map(function (r) {
                    var when = r.created_at ? new Date(r.created_at * 1000).toLocaleString('ru-RU') : '—';
                    return '<tr>' +
                        '<td>' + Number(r.id) + '</td>' +
                        '<td>' + escapeHtml(when) + '</td>' +
                        '<td>' + Number(r.admin_id) + '</td>' +
                        '<td><code>' + escapeHtml(r.action) + '</code></td>' +
                        '<td>' + escapeHtml(r.target || '') + '</td>' +
                        '<td>' + Number(r.affected) + '</td>' +
                        '</tr>';
                }).join('');
                $('#maint_log_body').html(html);
            }
        });
    }

    function ensureConfirm(phrase) {
        if (phrase !== 'СБРОСИТЬ') {
            messageSystem(false, 'Введите подтверждение «СБРОСИТЬ»', 3000);
            return false;
        }
        return window.confirm('Эта операция необратима. Продолжить?');
    }

    /* ---------- Public actions wired in Blade onclick="..." ---------- */

    window.maintLoadLog = loadLog;

    window.maintResetAnalytics = function () {
        var confirm = $('#reset_confirm').val().trim();
        if (!ensureConfirm(confirm)) return;
        ajax('POST', '/maintenance/analytics/reset', {
            scope:   $('#reset_scope').val(),
            confirm: confirm
        }, function () {
            $('#reset_confirm').val('');
            loadStats();
            loadLog();
        });
    };

    window.maintCleanupOrders = function () {
        var confirm = $('#cl_orders_confirm').val().trim();
        if (!ensureConfirm(confirm)) return;
        ajax('POST', '/maintenance/cleanup/orders', {
            type:    $('#cl_orders_type').val(),
            limit:   Number($('#cl_orders_limit').val()),
            confirm: confirm
        }, function () {
            $('#cl_orders_confirm').val('');
            loadStats();
            loadLog();
        });
    };

    window.maintCleanupCoupons = function () {
        var confirm = $('#cl_coupons_confirm').val().trim();
        if (!ensureConfirm(confirm)) return;
        ajax('POST', '/maintenance/cleanup/coupons', {
            limit:   Number($('#cl_coupons_limit').val()),
            confirm: confirm
        }, function () {
            $('#cl_coupons_confirm').val('');
            loadStats();
            loadLog();
        });
    };

    window.maintCleanupFiles = function () {
        var confirm = $('#cl_files_confirm').val().trim();
        if (!ensureConfirm(confirm)) return;
        ajax('POST', '/maintenance/cleanup/files', {
            type:    $('#cl_files_type').val(),
            limit:   Number($('#cl_files_limit').val()),
            confirm: confirm
        }, function () {
            $('#cl_files_confirm').val('');
            loadStats();
            loadLog();
        });
    };

    window.maintCleanupExports = function () {
        var confirm = $('#cl_exports_confirm').val().trim();
        if (!ensureConfirm(confirm)) return;
        ajax('POST', '/maintenance/cleanup/exports', {
            limit:   Number($('#cl_exports_limit').val()),
            confirm: confirm
        }, function () {
            $('#cl_exports_confirm').val('');
            loadStats();
            loadLog();
        });
    };

    /* ---------- Bootstrapping ---------- */

    $(function () {
        loadStats();
        loadLog();
    });
})();
