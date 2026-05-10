/* global tinymce, api_url, setAuthorization, messageSystem */
/**
 * Admin / Settings → Notifications → Status broadcast template editor.
 *
 * Mirrors the per-status modal editor, but writes/reads the global
 * default stored in shops_settings.status_broadcast_*. Used as a
 * fallback when an individual status row leaves its template blank.
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

    var TINY_SELECTOR = '#sb_template';

    function tinyEditor() {
        return (typeof tinymce !== 'undefined') ? tinymce.get('sb_template') : null;
    }

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
        if (tinymce.get('sb_template')) return;
        tinymce.init({
            selector: TINY_SELECTOR,
            license_key: 'gpl',
            height: 280,
            menubar: false,
            branding: false,
            promotion: false,
            plugins: 'lists link emoticons autolink',
            toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | link emoticons | removeformat',
            skin: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'oxide-dark' : 'oxide',
            content_css: (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'default'
        }).catch(function (err) { console.warn('TinyMCE init failed:', err); });
    }

    function setImagePath(path, url) {
        $('#sb_image_path').val(path || '');
        if (path) {
            $('#sb_image_preview').removeClass('d-none').find('img').attr('src', url);
            $('#sb_image_remove').removeClass('d-none');
        } else {
            $('#sb_image_preview').addClass('d-none').find('img').attr('src', '');
            $('#sb_image_remove').addClass('d-none');
        }
    }

    function load() {
        $.ajax({
            type: 'GET',
            url: api_url + '/shops/settings/status-broadcast',
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (!data.ok) {
                    messageSystem(false, data.description || 'Ошибка загрузки', 3000);
                    return;
                }
                var r = data.result || {};
                setTinyContent(r.template || '');
                if (r.image_path && r.image_url) {
                    setImagePath(r.image_path, r.image_url);
                } else {
                    setImagePath('', '');
                }
            }
        });
    }

    function uploadImage(file) {
        var fd = new FormData();
        fd.append('image', file);
        $('#sb_image_status').text('Загрузка…').removeClass('text-danger');
        $.ajax({
            type: 'POST',
            url: api_url + '/shops/settings/status-broadcast/upload-image',
            data: fd,
            contentType: false,
            processData: false,
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok && data.result) {
                    setImagePath(data.result.path, data.result.url);
                    $('#sb_image_status').text('Загружено').removeClass('text-danger');
                } else {
                    $('#sb_image_status').text(data.description || 'Ошибка').addClass('text-danger');
                    messageSystem(false, data.description || 'Не удалось загрузить', 3000);
                }
            },
            error: function () {
                $('#sb_image_status').text('Ошибка').addClass('text-danger');
            }
        });
    }

    window.sbSave = function () {
        $.ajax({
            type: 'POST',
            url: api_url + '/shops/settings/status-broadcast/save',
            contentType: 'application/json',
            data: JSON.stringify({
                template:   tinyContent(),
                image_path: $('#sb_image_path').val() || null
            }),
            beforeSend: function (xhr) { setAuthorization(xhr); },
            success: function (data) {
                if (data.ok) messageSystem(true, data.description || 'Сохранено', 2500);
                else messageSystem(false, data.description || 'Ошибка', 3500);
            }
        });
    };

    /* Wire-up */
    $(document).on('click', '.sb-placeholder', function () {
        var token = $(this).data('token');
        var ed = tinyEditor();
        if (ed) ed.insertContent(token);
        else {
            var $ta = $(TINY_SELECTOR);
            $ta.val(($ta.val() || '') + token);
        }
    });

    $(document).on('click', '#sb_image_pick', function () {
        $('#sb_image_file').trigger('click');
    });

    $(document).on('change', '#sb_image_file', function (e) {
        var file = e.target.files && e.target.files[0];
        if (file) uploadImage(file);
    });

    $(document).on('click', '#sb_image_remove', function () {
        setImagePath('', '');
        $('#sb_image_status').text('').removeClass('text-danger');
        $('#sb_image_file').val('');
    });

    $(function () {
        setTimeout(initTinyMCE, 80);
        load();
    });
})();
