<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Установить новый пароль</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { background:#0e141d; color:#eaeaea; font-family: sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; margin:0; padding:20px; }
        .card { background:#1a2230; padding:32px; border-radius:12px; max-width:420px; width:100%; box-shadow:0 8px 32px rgba(0,0,0,.4); }
        h1 { margin:0 0 16px; font-size:22px; }
        .field { margin:16px 0; }
        label { display:block; font-size:13px; margin-bottom:6px; color:#9aafd1; }
        input { width:100%; box-sizing:border-box; padding:10px 12px; border-radius:6px; border:1px solid #2c3a52; background:#0f1825; color:#eaeaea; font-size:15px; }
        input:focus { outline:none; border-color:#dcac01; }
        button { width:100%; background:#dcac01; color:#000; border:0; padding:12px; border-radius:6px; font-size:15px; font-weight:bold; cursor:pointer; margin-top:16px; }
        button:disabled { opacity:.5; cursor:not-allowed; }
        .msg { margin-top:16px; padding:10px 14px; border-radius:6px; font-size:14px; display:none; }
        .msg.ok { background:#1d3a2a; color:#a8e6c1; display:block; }
        .msg.err { background:#3a1d22; color:#ff9aa6; display:block; }
        .hint { font-size:12px; color:#6a7894; margin-top:8px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Установить новый пароль</h1>
    <p class="hint">Администратор сайта сбросил ваш текущий пароль. Введите новый пароль ниже — после успешного сохранения вы сможете войти как обычно.</p>
    <form id="resetForm" autocomplete="off">
        <div class="field">
            <label for="pw1">Новый пароль</label>
            <input type="password" id="pw1" minlength="8" maxlength="128" required autocomplete="new-password">
        </div>
        <div class="field">
            <label for="pw2">Повторите пароль</label>
            <input type="password" id="pw2" minlength="8" maxlength="128" required autocomplete="new-password">
        </div>
        <button type="submit" id="submitBtn">Сохранить пароль</button>
        <div id="msg" class="msg"></div>
    </form>
</div>

<script>
    (function () {
        var token = @json($token);
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

            if (p1 !== p2) { showMsg('err', 'Пароли не совпадают'); return; }
            if (p1.length < 8) { showMsg('err', 'Минимум 8 символов'); return; }

            $btn.prop('disabled', true).text('Сохраняем…');

            $.ajax({
                type: 'POST',
                url: '/api/password-reset/' + encodeURIComponent(token),
                contentType: 'application/json',
                data: JSON.stringify({ new_password: p1 }),
                success: function (data) {
                    if (data.ok) {
                        showMsg('ok', data.description || 'Готово');
                        setTimeout(function () { location.href = '/'; }, 1500);
                    } else {
                        showMsg('err', data.description || 'Ошибка');
                        $btn.prop('disabled', false).text('Сохранить пароль');
                    }
                },
                error: function () {
                    showMsg('err', 'Сетевая ошибка');
                    $btn.prop('disabled', false).text('Сохранить пароль');
                }
            });
        });
    })();
</script>
</body>
</html>
