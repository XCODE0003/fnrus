<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Сброс двухфакторной защиты</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f6f9; padding:24px; color:#222">
    <div style="max-width:540px;margin:0 auto;background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 6px rgba(0,0,0,.06)">
        <h2 style="margin-top:0;color:#1d2533">Сброс двухфакторной защиты</h2>

        <p>Здравствуйте, <strong>{{ $username }}</strong>!</p>

        <p>Мы получили запрос на отключение 2FA в админ-панели <strong>{{ config('app.name', 'Fnrus') }}</strong>.</p>

        <p>Если это были вы — введите следующий одноразовый код в форме сброса 2FA:</p>

        <p style="text-align:center;margin:24px 0">
            <span style="display:inline-block;font-family:monospace;font-size:22px;letter-spacing:.15em;background:#1d2533;color:#fff;padding:14px 22px;border-radius:6px">
                {{ $code }}
            </span>
        </p>

        <p>Код действует <strong>{{ $valid_minutes }} минут</strong>.</p>

        <p style="color:#7a8194;font-size:14px">
            Если запрос отправили не вы — просто проигнорируйте письмо. 2FA останется включённой.
            На всякий случай рекомендуем сменить пароль и проверить активность по адресу
            запроса: <code>{{ $request_ip }}</code>.
        </p>

        <hr style="border:none;border-top:1px solid #e6e8ee;margin:24px 0">
        <p style="color:#9aa0b3;font-size:12px;margin:0">
            Это автоматическое сообщение, не отвечайте на него.<br>
            {{ config('app.name', 'Fnrus') }} · {{ now()->format('Y-m-d H:i') }} UTC
        </p>
    </div>
</body>
</html>
