/* global $, tinymce, api_url, setAuthorization, messageSystem */
/**
 * Admin / Email-broadcasts UI.
 *
 * Modal-based CRUD for draft broadcasts plus a styled confirmation
 * for sending. The actual fan-out runs in the queue (SendBroadcastEmail
 * job per recipient); this UI only schedules and polls progress.
 *
 * waitForJQuery: see same note in maintenance.js.
 */
(function waitForDeps() {
    'use strict';
    if (typeof window.jQuery === 'undefined'
        || typeof api_url === 'undefined'
        || typeof setAuthorization !== 'function'
        || typeof messageSystem !== 'function') {
        return setTimeout(waitForDeps, 30);
    }
    var $ = window.jQuery;

    var ENDPOINT = '/email-broadcasts';
    var TINY_SELECTOR = '#bcast_body';

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/[<>&"']/g, function (s) {
            return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[s];
        });
    }

    function fmtDate(ts) {
        return ts ? new Date(ts * 1000).toLocaleString('ru-RU') : '—';
    }

    function statusBadge(status) {
        var color = ({
            draft: 'secondary',
            queued: 'info',
            sending: 'warning',
            sent: 'success',
            failed: 'danger',
            cancelled: 'dark'
        })[status] || 'secondary';
        return '<span class="badge badge-' + color + '">' + escapeHtml(status) + '</span>';
    }

    function tinyEditor() { return (typeof tinymce !== 'undefined') ? tinymce.get('bcast_body') : null; }

    function tinyContent() {
        var ed = tinyEditor();
        return ed ? ed.getContent() : ($(TINY_SELECTOR).val() || '');
    }

    function setTinyContent(html) {
        var ed = tinyEditor();
        if (ed) ed.setContent(html || ''); else $(TINY_SELECTOR).val(html || '');
    }

    function initTinyMCE() {
        if (typeof tinymce === 'undefined') return;
        if (tinymce.get('bcast_body')) return;
        tinymce.init({
            selector: TINY_SELECTOR,
            license_key: 'gpl',
            height: 360,
            menubar: false,
            branding: false,
            promotion: false,
            plugins: 'lists link image emoticons autolink',
            toolbar: 'undo redo | h2 h3 | bold italic underline strikethrough | bullist numlist | link image emoticons | removeformat',
            skin: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'oxide-dark' : 'oxide',
            content_css: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'default'
        }).catch(function (err) { console.warn('TinyMCE init failed:', err); });
    }

    function destroyTinyMCE() { var ed = tinyEditor(); if (ed) ed.remove(); }

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
            error: function () { messageSystem(false, 'Сетевая ошибка', 3500); }
        };
        if (body !== null && body !== undefined) {
            opts.contentType = 'application/json';
            opts.data = JSON.stringify(body);
        }
        $.ajax(opts);
    }

    function loadAudience() {
        $.ajax({
            type: 'GET',
            url: api_url + ENDPOINT + '/audience',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) return;
                var r = data.result || {};
                $('#audience_pill').html('<small>Аудитория: <strong>' + Number(r.eligible).toLocaleString('ru-RU') +
                    '</strong> из ' + Number(r.total_with_email).toLocaleString('ru-RU') +
                    ' (' + Number(r.opted_out).toLocaleString('ru-RU') + ' отписались)</small>');
            }
        });
    }

    function loadList() {
        $.ajax({
            type: 'GET',
            url: api_url + ENDPOINT,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) {
                    $('#bcast_list').html('<tr><td colspan="8" class="text-danger">' + escapeHtml(data.description) + '</td></tr>');
                    return;
                }
                var rows = data.result || [];
                if (!rows.length) {
                    $('#bcast_list').html('<tr><td colspan="8" class="text-center text-muted py-3">Рассылок ещё нет</td></tr>');
                    return;
                }
                var html = rows.map(function (r) {
                    var actions = '';
                    if (r.status === 'draft') {
                        actions = '<a href="javascript:;" class="bcast-edit mr-2" data-id="' + Number(r.id) + '"><i class="far fa-pencil-alt"></i></a>' +
                            '<a href="javascript:;" class="bcast-send text-success mr-2" data-id="' + Number(r.id) + '" data-subject="' + escapeHtml(r.subject) + '"><i class="far fa-paper-plane"></i></a>' +
                            '<a href="javascript:;" class="bcast-delete text-danger" data-id="' + Number(r.id) + '"><i class="far fa-trash-alt"></i></a>';
                    } else if (r.status === 'sent' || r.status === 'failed' || r.status === 'cancelled') {
                        actions = '<a href="javascript:;" class="bcast-delete text-danger" data-id="' + Number(r.id) + '"><i class="far fa-trash-alt"></i></a>';
                    }
                    return '<tr>' +
                        '<td>' + Number(r.id) + '</td>' +
                        '<td>' + escapeHtml(r.subject) + '</td>' +
                        '<td>' + statusBadge(r.status) + '</td>' +
                        '<td>' + Number(r.recipients_total) + '</td>' +
                        '<td>' + Number(r.recipients_sent) + '</td>' +
                        '<td>' + Number(r.recipients_failed) + '</td>' +
                        '<td>' + escapeHtml(fmtDate(r.created_at)) + '</td>' +
                        '<td>' + actions + '</td>' +
                        '</tr>';
                }).join('');
                $('#bcast_list').html(html);
            }
        });
    }

    function openCreate() {
        $('#bcast_id').val('');
        $('#bcast_subject').val('');
        setTimeout(function () { setTinyContent(''); }, 80);
        $('#bcast_modal_title').text('Новая рассылка');
        $('#createBroadcast').modal('show');
    }

    function openEdit(id) {
        $.ajax({
            type: 'GET',
            url: api_url + ENDPOINT + '/' + id,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) { messageSystem(false, data.description, 3000); return; }
                var b = data.result.broadcast;
                $('#bcast_id').val(b.id);
                $('#bcast_subject').val(b.subject);
                $('#bcast_modal_title').text('Редактирование #' + b.id);
                $('#createBroadcast').modal('show');
                setTimeout(function () { setTinyContent(b.body_html || ''); }, 200);
            }
        });
    }

    window.bcastSaveDraft = function () {
        var id = $('#bcast_id').val();
        var subject = $('#bcast_subject').val().trim();
        var body = tinyContent();

        if (!subject) { messageSystem(false, 'Введите тему', 3000); return; }
        if (!body) { messageSystem(false, 'Тело письма пустое', 3000); return; }

        if (id) {
            ajax('POST', ENDPOINT + '/' + id + '/update', { subject: subject, body_html: body }, function () {
                $('#createBroadcast').modal('hide');
                loadList();
            });
        } else {
            ajax('POST', ENDPOINT, { subject: subject, body_html: body }, function () {
                $('#createBroadcast').modal('hide');
                loadList();
            });
        }
    };

    window.bcastPreview = function () {
        var body = tinyContent();
        if (!body) { messageSystem(false, 'Пустое тело', 3000); return; }
        ajax('POST', ENDPOINT + '/preview', { body_html: body }, function (data) {
            var iframe = document.getElementById('bcast_preview_frame');
            iframe.srcdoc = data.result.html;
            $('#previewBroadcast').modal('show');
        });
    };

    function bcastSend(id, subject) {
        $.ajax({
            type: 'GET',
            url: api_url + ENDPOINT + '/audience',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                var aud = (data.ok && data.result) ? data.result.eligible : '?';
                $('#send_subject_label').text(subject || ('#' + id));
                $('#send_audience_label').text(aud);
                $('#send_confirm').val('').attr('data-id', id);
                $('#sendBroadcast').modal('show');
            }
        });
    }

    window.bcastSendConfirm = function () {
        var id = $('#send_confirm').attr('data-id');
        var confirm = $('#send_confirm').val().trim();
        if (confirm !== 'ОТПРАВИТЬ') {
            messageSystem(false, 'Введите ОТПРАВИТЬ', 3000);
            return;
        }
        ajax('POST', ENDPOINT + '/' + id + '/send', { confirm: confirm }, function () {
            $('#sendBroadcast').modal('hide');
            loadList();
        });
    };

    function bcastDelete(id) {
        if (!window.confirm('Удалить рассылку #' + id + '?')) return;
        ajax('DELETE', ENDPOINT + '/' + id, null, loadList);
    }

    /* ---------- Wire-up ---------- */

    $(document).on('show.bs.modal', '#createBroadcast', function () { setTimeout(initTinyMCE, 80); });
    $(document).on('hidden.bs.modal', '#createBroadcast', function () { destroyTinyMCE(); });

    $(document).on('click', '[data-target="#createBroadcast"]', openCreate);

    $(document).on('click', '.bcast-edit',   function () { openEdit($(this).data('id')); });
    $(document).on('click', '.bcast-send',   function () { bcastSend($(this).data('id'), $(this).data('subject')); });
    $(document).on('click', '.bcast-delete', function () { bcastDelete($(this).data('id')); });

    $(function () {
        loadAudience();
        loadList();
        // Light-touch poll: refresh list every 8s while a broadcast is active.
        setInterval(loadList, 8000);
    });
})();
