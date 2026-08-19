<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ __('site.pwreset_title') }}</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        * { box-sizing:border-box; }
        html, body { min-width:0; }
        body { background:#0e141d; color:#eaeaea; font-family: sans-serif; min-height:100dvh; display:flex; align-items:center; justify-content:center; margin:0; padding:max(20px, env(safe-area-inset-top)) max(20px, env(safe-area-inset-right)) max(20px, env(safe-area-inset-bottom)) max(20px, env(safe-area-inset-left)); overflow-x:hidden; overflow-y:auto; }
        .card { background:#1a2230; padding:32px; border-radius:12px; max-width:min(420px, calc(100vw - 40px - env(safe-area-inset-left) - env(safe-area-inset-right))); width:100%; box-shadow:0 8px 32px rgba(0,0,0,.4); min-width:0; }
        h1 { margin:0 0 16px; font-size:22px; }
        .field { margin:16px 0; }
        label { display:block; font-size:13px; margin-bottom:6px; color:#9aafd1; }
        input { width:100%; box-sizing:border-box; padding:10px 12px; border-radius:6px; border:1px solid #2c3a52; background:#0f1825; color:#eaeaea; font-size:16px; }
        input:focus { outline:none; border-color:#dcac01; }
        button { width:100%; min-height:48px; background:#dcac01; color:#000; border:0; padding:12px; border-radius:6px; font-size:15px; font-weight:bold; cursor:pointer; margin-top:16px; white-space:normal; overflow-wrap:anywhere; }
        button:disabled { opacity:.5; cursor:not-allowed; }
        .msg { margin-top:16px; padding:10px 14px; border-radius:6px; font-size:14px; display:none; white-space:normal; overflow-wrap:anywhere; word-break:break-word; }
        .msg.ok { background:#1d3a2a; color:#a8e6c1; display:block; }
        .msg.err { background:#3a1d22; color:#ff9aa6; display:block; }
        .hint { font-size:12px; color:#6a7894; margin-top:8px; }
        @media (max-width: 479px) {
            .card { padding:24px 20px; }
        }
        @media (max-height: 560px) {
            body { align-items:flex-start; }
        }
    </style>
</head>
<body>
<div class="card">
    <h1>{{ __('site.pwreset_title') }}</h1>
    <p class="hint">{{ __('site.pwreset_hint') }}</p>
    <form id="resetForm" autocomplete="off">
        <div class="field">
            <label for="pw1">{{ __('site.pwreset_new_pass') }}</label>
            <input type="password" id="pw1" minlength="8" maxlength="128" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="pw2">{{ __('site.pwreset_repeat_pass') }}</label>
            <input type="password" id="pw2" minlength="8" maxlength="128" required autocomplete="new-password">
        </div>
        <button type="submit" id="submitBtn">{{ __('site.pwreset_save') }}</button>
        <div id="msg" class="msg"></div>
    </form>
</div>

<script>
    (function () {
        var token = @json($token);
        var L = {
            save: @json(__('site.pwreset_save')),
            saving: @json(__('site.pwreset_saving')),
            mismatch: @json(__('site.pwreset_mismatch')),
            minLen: @json(__('site.pwreset_min_len')),
            done: @json(__('site.pwreset_done')),
            error: @json(__('site.pwreset_error')),
            networkError: @json(__('site.pwreset_network_error'))
        };
        var $form = $('#resetForm');
        var $btn = $('#submitBtn');
        var $msg = $('#msg');

        function showMsg(type, text) {
            $msg.removeClass('ok err').addClass(type).text(text).show();
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            var p1 = $('#pw1').val();
            var p2 = $('#pw2').val();

            if (p1 !== p2) { showMsg('err', L.mismatch); return; }
            if (p1.length < 8) { showMsg('err', L.minLen); return; }

            $btn.prop('disabled', true).text(L.saving);

            $.ajax({
                type: 'POST',
                url: '/api/password-reset/' + encodeURIComponent(token),
                contentType: 'application/json',
                data: JSON.stringify({ new_password: p1 }),
                success: function (data) {
                    if (data.ok) {
                        showMsg('ok', data.description || L.done);
                        setTimeout(function () { location.href = '/'; }, 1500);
                    } else {
                        showMsg('err', data.description || L.error);
                        $btn.prop('disabled', false).text(L.save);
                    }
                },
                error: function () {
                    showMsg('err', L.networkError);
                    $btn.prop('disabled', false).text(L.save);
                }
            });
        });
    })();
</script>
</body>
</html>
