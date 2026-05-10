/* global $, tinymce, api_url, setAuthorization, messageSystem, statusesCheats */
/**
 * Admin / "Статусы софта" — extra logic on top of main.js.
 *
 * Adds: TinyMCE rich-text template editor, image upload, placeholder
 * insertion, Telegram-channel quick toggles, and CRUD for the channel
 * list — all wired to /api/admin/statuses/* and /api/admin/telegram-channels/*.
 *
 * Overrides the legacy `changeCheat()` in main.js so the PUT payload
 * carries the new fields (message_template, image_path).
 *
 * Bootstrapping: this file is included inside @section('content') which
 * renders BEFORE the layout's footer scripts (jQuery, etc). The
 * waitForJQuery loop polls until jQuery is loaded so the IIFE never
 * runs against an undefined `$`.
 */
(function waitForJQuery() {
    'use strict';
    if (typeof window.jQuery === 'undefined') {
        return setTimeout(waitForJQuery, 30);
    }
    var $ = window.jQuery;

    var TINY_SELECTOR = '#changeCheat #message_template';
    var IMAGE_PATH_INPUT = '#changeCheat #cheat_image_path';
    var IMAGE_PREVIEW = '#changeCheat #cheat_image_preview';
    var IMAGE_REMOVE = '#changeCheat #cheat_image_remove';

    function tinyEditor() {
        return (typeof tinymce !== 'undefined') ? tinymce.get('message_template') : null;
    }

    function tinyContent() {
        var ed = tinyEditor();
        return ed ? ed.getContent() : ($(TINY_SELECTOR).val() || '');
    }

    function setTinyContent(html) {
        var ed = tinyEditor();
        if (ed) {
            ed.setContent(html || '');
        } else {
            $(TINY_SELECTOR).val(html || '');
        }
    }

    function initTinyMCE() {
        if (typeof tinymce === 'undefined') return;
        if (tinymce.get('message_template')) return;
        tinymce.init({
            selector: TINY_SELECTOR,
            license_key: 'gpl',
            height: 260,
            menubar: false,
            branding: false,
            promotion: false,
            plugins: 'lists link emoticons autolink',
            toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | link emoticons | removeformat',
            skin: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'oxide-dark' : 'oxide',
            content_css: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'default'
        }).catch(function (err) {
            console.warn('TinyMCE init failed:', err);
        });
    }

    function destroyTinyMCE() {
        var ed = tinyEditor();
        if (ed) ed.remove();
    }

    function insertPlaceholder(token) {
        var ed = tinyEditor();
        if (ed) {
            ed.insertContent(token);
        } else {
            var $ta = $(TINY_SELECTOR);
            var current = $ta.val() || '';
            $ta.val(current + token);
        }
    }

    function setImagePath(path, url) {
        $(IMAGE_PATH_INPUT).val(path || '');
        if (path) {
            $(IMAGE_PREVIEW).removeClass('d-none').find('img').attr('src', url);
            $(IMAGE_REMOVE).removeClass('d-none');
        } else {
            $(IMAGE_PREVIEW).addClass('d-none').find('img').attr('src', '');
            $(IMAGE_REMOVE).addClass('d-none');
        }
    }

    function uploadImage(file) {
        var fd = new FormData();
        fd.append('image', file);
        $('#cheat_image_status').text('Загрузка…').removeClass('text-danger');
        $.ajax({
            type: 'POST',
            url: api_url + '/statuses/upload-image',
            data: fd,
            contentType: false,
            processData: false,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok && data.result) {
                    setImagePath(data.result.path, data.result.url);
                    $('#cheat_image_status').text('Загружено').removeClass('text-danger');
                } else {
                    $('#cheat_image_status').text(data.description || 'Ошибка').addClass('text-danger');
                    messageSystem(false, data.description || 'Не удалось загрузить', 3000);
                }
            },
            error: function () {
                $('#cheat_image_status').text('Ошибка').addClass('text-danger');
            }
        });
    }

    function renderChannelToggles(channels) {
        var $box = $('#cheat_channels_list');
        if (!channels || !channels.length) {
            $box.html('<small class="text-muted">Каналы не подключены. Откройте «Telegram-каналы» в шапке.</small>');
            return;
        }
        var html = channels.map(function (c) {
            var checked = c.is_active ? 'checked' : '';
            var safeTitle = String(c.title).replace(/[<>&"']/g, function (s) {
                return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[s];
            });
            var safeChat = String(c.chat_id).replace(/[<>&"']/g, function (s) {
                return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#39;' })[s];
            });
            return '' +
                '<div class="d-flex align-items-center justify-content-between py-1">' +
                '  <div>' +
                '    <strong>' + safeTitle + '</strong>' +
                '    <small class="text-muted ml-2">' + safeChat + '</small>' +
                '  </div>' +
                '  <div class="custom-control custom-switch">' +
                '    <input type="checkbox" class="custom-control-input cheat-channel-toggle" ' +
                '           data-id="' + Number(c.id) + '" id="cheatch_' + Number(c.id) + '" ' + checked + '>' +
                '    <label class="custom-control-label" for="cheatch_' + Number(c.id) + '"></label>' +
                '  </div>' +
                '</div>';
        }).join('');
        $box.html(html);
    }

    function loadChannelsForEdit() {
        $.ajax({
            type: 'GET',
            url: api_url + '/telegram-channels',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) renderChannelToggles(data.result);
                else $('#cheat_channels_list').html('<small class="text-danger">' + (data.description || 'Ошибка') + '</small>');
            }
        });
    }

    function toggleChannel(id, $checkbox) {
        $.ajax({
            type: 'POST',
            url: api_url + '/telegram-channels/' + id + '/toggle',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) {
                    $checkbox.prop('checked', !$checkbox.prop('checked'));
                    messageSystem(false, data.description || 'Ошибка', 3000);
                }
            },
            error: function () {
                $checkbox.prop('checked', !$checkbox.prop('checked'));
            }
        });
    }

    /* ---------- Channel manager modal ---------- */

    function renderChannelManager(channels) {
        var $box = $('#tgch_list');
        if (!channels || !channels.length) {
            $box.html('<small class="text-muted">Каналов пока нет.</small>');
            return;
        }
        var html = '<table class="table table-sm align-middle">' +
            '<thead><tr><th>Название</th><th>chat_id</th><th>Тип</th><th>Активен</th><th></th></tr></thead><tbody>';
        channels.forEach(function (c) {
            var safeTitle = $('<div/>').text(c.title).html();
            var safeChat = $('<div/>').text(c.chat_id).html();
            var checked = c.is_active ? 'checked' : '';
            html += '<tr data-id="' + Number(c.id) + '">' +
                '  <td>' + safeTitle + '</td>' +
                '  <td><code>' + safeChat + '</code></td>' +
                '  <td>' + (c.type === 'group' ? 'Группа' : 'Канал') + '</td>' +
                '  <td>' +
                '    <div class="custom-control custom-switch">' +
                '      <input type="checkbox" class="custom-control-input tgch-toggle" data-id="' + Number(c.id) + '" id="tgchm_' + Number(c.id) + '" ' + checked + '>' +
                '      <label class="custom-control-label" for="tgchm_' + Number(c.id) + '"></label>' +
                '    </div>' +
                '  </td>' +
                '  <td><a href="javascript:;" class="text-danger tgch-delete" data-id="' + Number(c.id) + '"><i class="far fa-trash-alt"></i></a></td>' +
                '</tr>';
        });
        html += '</tbody></table>';
        $box.html(html);
    }

    function tgChannelLoad() {
        $.ajax({
            type: 'GET',
            url: api_url + '/telegram-channels',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) renderChannelManager(data.result);
                else $('#tgch_list').html('<small class="text-danger">' + (data.description || 'Ошибка') + '</small>');
            }
        });
    }

    window.tgChannelCreate = function () {
        var title = $('#tgch_title').val().trim();
        var chatId = $('#tgch_chat_id').val().trim();
        var type = $('#tgch_type').val();
        if (!title || !chatId) {
            messageSystem(false, 'Заполните название и chat_id', 3000);
            return;
        }
        $.ajax({
            type: 'POST',
            url: api_url + '/telegram-channels',
            contentType: 'application/json',
            data: JSON.stringify({ title: title, chat_id: chatId, type: type }),
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) {
                    $('#tgch_title').val('');
                    $('#tgch_chat_id').val('');
                    tgChannelLoad();
                    messageSystem(true, data.description || 'Добавлено', 2000);
                } else {
                    messageSystem(false, data.description || 'Ошибка', 3000);
                }
            }
        });
    };

    function tgChannelDelete(id) {
        if (!confirm('Удалить канал?')) return;
        $.ajax({
            type: 'DELETE',
            url: api_url + '/telegram-channels/' + id,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) {
                    tgChannelLoad();
                    messageSystem(true, data.description || 'Удалено', 2000);
                } else {
                    messageSystem(false, data.description || 'Ошибка', 3000);
                }
            }
        });
    }

    /* ---------- Override changeCheat() with extended payload ---------- */

    window.changeCheat = function () {
        var modal = '#changeCheat';
        var id = $(modal).attr('data-id');
        var isNotify = $(modal + ' #is_notify').is(':checked') ? 1 : 0;

        $.ajax({
            type: 'POST',
            url: api_url + '/statuses/' + id + '/update',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                game_id:          $(modal + ' #game_id').val(),
                title:            $(modal + ' #title').val(),
                status:           $(modal + ' #status').val(),
                is_notify:        isNotify,
                message_template: tinyContent(),
                image_path:       $(IMAGE_PATH_INPUT).val() || null
            }),
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok === true) {
                    $(modal).modal('hide');
                    if (typeof statusesCheats === 'function') statusesCheats();
                    messageSystem(true, data.description, 2000);
                } else {
                    messageSystem(false, data.description, 3000);
                }
            }
        });
    };

    /* ---------- Wire-up ---------- */

    $(document).on('show.bs.modal', '#changeCheat', function () {
        // Defer a tick so Bootstrap finishes inserting the DOM.
        setTimeout(initTinyMCE, 50);
    });

    $(document).on('hidden.bs.modal', '#changeCheat', function () {
        destroyTinyMCE();
        setImagePath('', '');
        $('#cheat_image_status').text('').removeClass('text-danger');
        $('#cheat_channels_list').html('<small class="text-muted">Загрузка…</small>');
    });

    // After main.js fills the basic fields via /fullinfo, we still need
    // to populate the new ones. main.js' handler resolves async; piggy-back
    // a second AJAX so the two responses don't fight over the modal state.
    $(document).on('shown.bs.modal', '#changeCheat', function () {
        var id = $(this).attr('data-id');
        if (!id) return;
        $.ajax({
            type: 'GET',
            url: api_url + '/statuses/' + id + '/fullinfo',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) return;
                var r = data.result || {};
                setTinyContent(r.message_template || '');
                if (r.image_path && r.image_url) {
                    setImagePath(r.image_path, r.image_url);
                } else {
                    setImagePath('', '');
                }
                renderChannelToggles(r.channels || []);
            }
        });
    });

    $(document).on('click', '.cheat-placeholder', function () {
        insertPlaceholder($(this).data('token'));
    });

    $(document).on('click', '#cheat_image_pick', function () {
        $('#cheat_image_file').trigger('click');
    });

    $(document).on('change', '#cheat_image_file', function (e) {
        var file = e.target.files && e.target.files[0];
        if (file) uploadImage(file);
    });

    $(document).on('click', '#cheat_image_remove', function () {
        setImagePath('', '');
        $('#cheat_image_status').text('').removeClass('text-danger');
        $('#cheat_image_file').val('');
    });

    $(document).on('change', '.cheat-channel-toggle', function () {
        toggleChannel($(this).data('id'), $(this));
    });

    $(document).on('show.bs.modal', '#manageChannels', tgChannelLoad);

    $(document).on('change', '.tgch-toggle', function () {
        toggleChannel($(this).data('id'), $(this));
    });

    $(document).on('click', '.tgch-delete', function () {
        tgChannelDelete($(this).data('id'));
    });
})();
